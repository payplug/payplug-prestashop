<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\repositories;

use Exception;
use PayPlug\src\application\dependencies\BaseClass;

class PaymentRepository extends BaseClass
{
    protected $dependencies;

    private $apiPayment;
    private $cartAdapter;
    private $confAdapter;
    private $logger;
    private $paymentEntity;
    private $query;
    private $constant;
    private $validators;
    private $configuration;

    public function __construct(
        $cartAdapter,
        $confAdapter,
        $configuration,
        $constant,
        $dependencies,
        $logger,
        $paymentEntity,
        $query
    ) {
        $this->dependencies = $dependencies;
        $this->cartAdapter = $cartAdapter;
        $this->confAdapter = $confAdapter;
        $this->logger = $logger;
        $this->paymentEntity = $paymentEntity;
        $this->query = $query;
        $this->constant = $constant;
        $this->configuration = $configuration;

        $this->logger->setProcess('payment');
        $this->validators = $this->dependencies->getValidators();
    }

    /**
     * @description Return hashed cart with needed (and formatted) elements, ready to be hashed.
     *
     * @param $paymentDetails
     *
     * @return array|string
     */
    public function getHashedCart($paymentDetails)
    {
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['cart'])
            || !$paymentDetails['cart']
            || !is_object($paymentDetails['cart'])
        ) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => 'No payment details nor cart'],
                '[getHashedCart] $paymentDetails or cart is null, or $paymentDetails is not an array'
            );
        }
        $cartToHash = [];
        $products = $paymentDetails['cart']->getProducts();

        if (!$products) {
            return $this->returnPaymentError(
                ['name' => '$paymentDetails[\'cart\']', 'value' => $paymentDetails['cart']],
                '[getHashedCart] no product found'
            );
        }

        foreach ($products as $product) {
            $product = array_map('json_encode', $product);
            $cartToHash[] = array_map('strval', $product);
        }

        // For optimised / non optimised Oney + 3x / 4x to have a good hash ;-)
        if (isset($paymentDetails['oneyDetails'])) {
            $paymentDetails['method'] .= $paymentDetails['oneyDetails'];
        }

        // Adding cart informationObjectModel
        $cartToHash[] = 'Cart $id_address_delivery: ' . $paymentDetails['cart']->id_address_delivery;
        $cartToHash[] = 'Cart $id_address_invoice: ' . $paymentDetails['cart']->id_address_invoice;
        $cartToHash[] = 'Cart $id_currency: ' . $paymentDetails['cart']->id_currency;
        $cartToHash[] = 'Cart $id_customer: ' . $paymentDetails['cart']->id_customer;
        $cartToHash[] = 'Cart $delivery_option: ' . $paymentDetails['cart']->delivery_option;

        // Adding cart amount to hash
        $cartToHash[] = 'Cart amount: ' . (float) $paymentDetails['cart']->getOrderTotal(true);

        return hash('sha256', $paymentDetails['method'] . json_encode($cartToHash));
    }

    /**
     * @description Compare hash (sha256 on payment method + cart)
     * Create an other payment request if cart or payment method changed
     * and update payment table in consequence
     *
     * @param array $paymentDetails
     *
     * @return array
     */
    public function checkHash($paymentDetails)
    {
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['cartId'])
            || !$paymentDetails['cartId']
        ) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => 'No payment details nor cart ID'],
                '[checkHash] $paymentDetails or cartId is null, or $paymentDetails is not an array'
            );
        }

        $paymentStored = $this->dependencies->getPlugin()->getPaymentRepository()
            ->getByCart((int) $paymentDetails['cartId']);

        if (empty($paymentStored)) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[checkHash] No payment found for given cart id'
            );
        }

        $cartHash = $this->getHashedCart($paymentDetails);
        $is_cached_payment = $this->validators['payment']->isCachedPayment($paymentStored['cart_hash'], $cartHash);
        if ($is_cached_payment['result']) {
            return [
                'result' => true,
                'paymentDetails' => $paymentDetails,
                'response' => 'OK. Comparaison result: Same hash and same payment method.',
            ];
        }

        // Create payment or installment
        $createPayment = $this->createPayment($paymentDetails);
        if (!$createPayment['result']) {
            unset($paymentDetails['paymentTab']);

            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                $createPayment['response']
            );
        }

        // Then update payment detail
        $paymentDetails = $createPayment['paymentDetails'];

        // Update payment table
        $updatePaymentTable = $this->updatePaymentTable($paymentDetails);
        if (!$updatePaymentTable['result']) {
            return $this->returnPaymentError(
                ['name' => 'updatePaymentTable', 'value' => $updatePaymentTable],
                $updatePaymentTable['response']
            );
        }

        // Then update payment detail
        $paymentDetails = $updatePaymentTable['paymentDetails'];

        return [
            'result' => true,
            'paymentDetails' => $paymentDetails,
            'response' => 'Payment created and updated successfully',
        ];
    }

    /**
     * @description Return an error with some details in logger
     *
     * @param array  $element
     * @param null   $errorMessage
     * @param string $level
     *
     * @return array
     */
    public function returnPaymentError($element = [], $errorMessage = null, $level = 'error')
    {
        if (!$errorMessage || !is_string($errorMessage)) {
            $errorMessage = '[PaymentRepository] Error during payment creation process.';
        }

        $this->dependencies->paymentClass->setPaymentErrorsCookie([
            $this->dependencies->l('The transaction was not completed and your card was not charged.', 'paymentrepository'),
        ]);

        $this->logger->setProcess('payment');
        $this->logger->addLog($errorMessage, $level);

        if (!is_array($element) || empty($element)) {
            return [
                'result' => false,
                'response' => $errorMessage,
            ];
        }

        $element['value'] = json_encode($element['value']);
        $this->logger->addLog($element['name'] . ': ' . $element['value'], 'debug');

        return [
            'result' => false,
            $element['name'] => $element['value'],
            'response' => $errorMessage,
        ];
    }

    /**
     * @description Create payment / installment
     *
     * @param array $paymentDetails
     *
     * @return array
     */
    public function createPayment($paymentDetails = [])
    {
        if (!is_array($paymentDetails) || empty($paymentDetails)) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] Invalid parameters given, $paymentDetails must be an non empty array'
            );
        }

        if (!isset($paymentDetails['paymentTab'])
            || !is_array($paymentDetails['paymentTab'])
            || empty($paymentDetails['paymentTab'])) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] Invalid parameters given, $paymentDetails[paymentTab] must be an non empty array'
            );
        }

        if (!isset($paymentDetails['method'])
            || !is_string($paymentDetails['method'])
            || !$paymentDetails['method']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] Invalid parameters given, $paymentDetails[method] must be a non empty string'
            );
        }

        if (!isset($paymentDetails['cartId'])
            || !is_int($paymentDetails['cartId'])
            || !$paymentDetails['cartId']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] Invalid parameters given, $paymentDetails[cartId] must be a non null integer'
            );
        }

        // Check if configuration allow this payment method
        $payment_methods = [
            'amex' => 'amex',
            'applepay' => 'apple_pay',
            'bancontact' => 'bancontact',
            'giropay' => 'giropay',
            'ideal' => 'ideal',
            'installment' => 'installment',
            'mybank' => 'mybank',
            'one_click' => 'oneclick',
            'oney' => 'oney',
            'satispay' => 'satispay',
            'sofort' => 'sofort',
            'standard' => 'standard',
        ];
        $database_payment_methods = json_decode($this->configuration->getValue('payment_methods'), true);
        foreach ($payment_methods as $config_key => $payment_method) {
            if ($payment_method == $paymentDetails['method'] && !$database_payment_methods[$config_key]) {
                return $this->returnPaymentError(
                    [
                        'name' => 'Configuration::get',
                        'value' => $this->configuration->getValue('payment_methods'),
                    ],
                    '[createPayment] Try to create payment with disabled feature ' . $payment_method
                );
            }
        }

        // Before create a new payment, delete the previous one, if exists and it's not a oneclick payment
        // to avoid double order creation
        $apiPayment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $paymentDetails['cartId']);
        if (!empty($apiPayment)) {
            $is_cancellable = $this->validators['payment']->isCancellable($apiPayment['method']);
            if ($is_cancellable['result']) {
                $payment = $this->dependencies->apiClass->retrievePayment($apiPayment['resource_id']);
                if ($payment['result'] && !$payment['resource']->failure) {
                    $this->logger->addLog('Payment already exists: ' . $apiPayment['resource_id'] . ', so we delete it before create a new one');
                    $abort = $this->dependencies->apiClass->abortPayment($apiPayment['resource_id']);
                    if (!$abort['result']) {
                        return $this->returnPaymentError(
                            ['name' => 'resource_id', 'value' => $apiPayment['resource_id']],
                            '[createPayment] Exception. Unable to abort payment. Error: ' . $abort['message']
                        );
                    }

                    $this->logger->addLog('Payment aborted.');
                }
            }
        }

        if ('installment' !== $paymentDetails['method']) {
            $payment = $this->dependencies->apiClass->createPayment($paymentDetails['paymentTab']);

            if (!$payment['result']) {
                // If the payment resource can not be created due to wrong permission, then disabled associated feature
                if (403 == (int) $payment['code']) {
                    $cart = $this->dependencies->getPlugin()->getCart()->get((int) $paymentDetails['cartId']);
                    $permissions = $this->dependencies->configClass->getAvailableOptions($cart);
                    $this->dependencies->getPlugin()->getPaymentMethodClass()->resetPaymentMethodFromPermission($permissions);
                    $paymentDetails['error_code'] = (int) $payment['code'];
                }

                // If the payment resource can not be created due to bad credential, we log out the merchand
                if (401 == (int) $payment['code']) {
                    $this->dependencies
                        ->getPlugin()
                        ->getConfigurationAction()
                        ->logoutAction();
                    $paymentDetails['error_code'] = (int) $payment['code'];
                }

                return $this->returnPaymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    '[createPayment] Exception. Unable to create payment. Error: ' . $payment['message']
                );
            }

            $this->paymentEntity->setApiPayment($payment['resource']);
        } else {
            $payment = $this->dependencies->apiClass->createInstallment($paymentDetails['paymentTab']);

            if (!$payment['result']) {
                // If the payment resource can not be created due to wrong permission, then disabled associated feature
                if (403 == (int) $payment['code']) {
                    $cart = $this->dependencies->getPlugin()->getCart()->get((int) $paymentDetails['cartId']);
                    $permissions = $this->dependencies->configClass->getAvailableOptions($cart);
                    $this->dependencies->getPlugin()->getPaymentMethodClass()->resetPaymentMethodFromPermission($permissions);
                }

                // If the payment resource can not be created due to bad credential, we log out the merchand
                if (401 == (int) $payment['code']) {
                    $this->dependencies
                        ->getPlugin()
                        ->getConfigurationAction()
                        ->logoutAction();
                }

                unset($paymentDetails['paymentTab']);

                return $this->returnPaymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    '[createPayment] Exception. Unable to create installment plan. Error: ' . $payment['message']
                );
            }
            $this->paymentEntity->setApiPayment($payment['resource']);
        }

        $this->apiPayment = $this->paymentEntity->getApiPayment();

        if ($this->validators['payment']->isFailed($this->apiPayment)['result']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                (string) $this->apiPayment->failure->message
            );
        }

        // We can now hydrate our params
        $paymentDetails['resource_id'] = $this->apiPayment->id;

        if (isset($paymentDetails['paymentTab']['integration']) || 'apple_pay' == $paymentDetails['method']) {
            $paymentDetails['paymentReturnUrl'] = $paymentDetails['paymentTab']['hosted_payment']['return_url'];
        } elseif (isset($this->apiPayment->hosted_payment->return_url)) {
            $paymentDetails['paymentReturnUrl'] = $this->apiPayment->hosted_payment->return_url;
        }

        if (!$paymentDetails['paymentReturnUrl']) {
            unset($paymentDetails['paymentTab']);

            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] payment return URL is null.'
            );
        }

        if (('installment' !== $paymentDetails['method']) && (null !== $this->apiPayment->authorization)) {
            if (null !== $this->apiPayment->authorization->authorized_at) {
                $paymentDetails['authorizedAt'] = $this->apiPayment->authorization->authorized_at;
            }
        }

        if (isset($this->apiPayment->is_paid)) {
            $paymentDetails['isPaid'] = $this->apiPayment->is_paid;
        }

        if (isset($this->apiPayment->hosted_payment->payment_url)) {
            $paymentDetails['paymentUrl'] = $this->apiPayment->hosted_payment->payment_url;
        }

        return [
            'result' => true,
            'paymentDetails' => $paymentDetails,
            'resource' => $payment['resource'],
            'response' => '[createPayment] Payment successfully created',
        ];
    }

    /**
     * @description Update hash and payment id in Payplug Payment Cart table
     *
     * @param array $paymentDetails
     *
     * @return array
     */
    public function updatePaymentTable($paymentDetails)
    {
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['cart'])
            || !$paymentDetails['cart']
        ) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[updatePaymentTable] $paymentDetails or cart is null, or $paymentDetails is not an array'
            );
        }

        $cartHash = $this->getHashedCart($paymentDetails);

        if (isset($cartHash['result']) && !$cartHash['result']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[updatePaymentTable] Problem with the getHashedCart method.'
            );
        }

        if (!$cartHash || !is_string($cartHash)) {
            return $this->returnPaymentError(
                ['name' => 'cartHash', 'value' => $cartHash],
                '[updatePaymentTable] $cartHash is null or not a string'
            );
        }

        $parameters = [
            'resource_id' => $paymentDetails['resource_id'],
            'method' => $paymentDetails['method'],
            'cart_hash' => $cartHash,
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        $update = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->updateByCart((int) $paymentDetails['cartId'], $parameters);

        if (!$update) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[updatePaymentTable] Unable to fetch the query on DB but no throw'
            );
        }

        return [
            'result' => true,
            'paymentDetails' => $paymentDetails,
            'response' => 'Update DB with new payment creation successfully',
        ];
    }

    /**
     * @description Check if payment created < 3 min in DB
     *
     * @param $idCart
     *
     * @throws Exception
     *
     * @return bool
     */
    public function checkTimeoutPayment($idCart)
    {
        if (!$idCart || !is_int($idCart)) {
            // todo: add log
            return false;
        }

        $payment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $idCart);
        if (empty($payment)) {
            return true;
        }

        $dateStored = $payment['date_upd'];
        $is_timeout_cache = $this->validators['payment']->isTimeoutCachedPayment($dateStored);

        return $is_timeout_cache['result'];
    }

    /**
     * @description Generate and return correct payment return url
     *
     * @param array $paymentDetails
     *
     * @return array
     */
    public function getPaymentReturnUrl($paymentDetails = [])
    {
        if (!is_array($paymentDetails) || empty($paymentDetails)) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[getPaymentReturnUrl] Invalid parameters given, $paymentDetails must be an non empty array'
            );
        }

        $paymentStored = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $paymentDetails['cartId']);
        if (!$paymentStored) {
            return $this->returnPaymentError(
                ['name' => 'paymentStored', 'value' => false],
                '[getPaymentReturnUrl] No payment found for given cart id'
            );
        }

        if ('installment' == $paymentStored['method']) {
            $retrievedPayment = $this->dependencies->apiClass->retrieveInstallment($paymentStored['resource_id']);
        } else {
            $retrievedPayment = $this->dependencies->apiClass->retrievePayment($paymentStored['resource_id']);
        }
        if (!$retrievedPayment['result']) {
            return $this->returnPaymentError(
                ['name' => 'paymentStored', 'value' => false],
                '[getPaymentReturnUrl] Can not retrieve payment related to given cart id'
            );
        }

        switch ($paymentDetails['method']) {
            case 'oneclick':
                $redirect = $retrievedPayment['resource']->is_paid;
                if (!$redirect && $paymentDetails['isDeferred']) {
                    $redirect = (bool) isset($retrievedPayment['resource']->authorization)
                        && $retrievedPayment['resource']->authorization->authorized_at;
                }

                $paymentReturnUrl = [
                    'result' => true,
                    'embedded' => true,
                    'redirect' => $redirect, // force `true` we are in 3DS 1
                    'return_url' => $redirect ? $retrievedPayment['resource']->hosted_payment->return_url
                        : $retrievedPayment['resource']->hosted_payment->payment_url,
                ];

                break;

            case 'oney':
                $paymentReturnUrl = [
                    'result' => 'new_card',
                    'embedded' => false,
                    'redirect' => true,
                    'return_url' => $retrievedPayment['resource']->hosted_payment->payment_url,
                ];

                break;

            case 'amex':
            case 'apple_pay':
            case 'bancontact':
            case 'giropay':
            case 'ideal':
            case 'installment':
            case 'integrated':
            case 'mybank':
            case 'satispay':
            case 'sofort':
            case 'standard':
                $returnUrl = $retrievedPayment['resource']->hosted_payment
                    ? $retrievedPayment['resource']->hosted_payment->payment_url
                        ?: $retrievedPayment['resource']->hosted_payment->return_url
                    : '';
                $paymentReturnUrl = [
                    'result' => 'new_card',
                    'embedded' => $paymentDetails['isEmbedded']
                        && !$paymentDetails['isMobileDevice']
                        && !in_array($paymentDetails['method'], [
                            'bancontact',
                            'giropay',
                            'ideal',
                            'mybank',
                            'satispay',
                            'sofort',
                        ]),
                    'redirect' => $paymentDetails['isMobileDevice'],
                    'return_url' => $returnUrl,
                ];
                if ($paymentDetails['isIntegrated']) {
                    $paymentReturnUrl['resource_id'] = $paymentDetails['resource_id'];
                    $paymentReturnUrl['cart_id'] = $paymentDetails['cartId'];
                }

                break;

            default:
                return $this->returnPaymentError(
                    ['name' => 'paymentStored', 'value' => false],
                    '[getPaymentReturnUrl] Invalid payment method given'
                );
        }
        if ($paymentDetails['isIntegrated']) {
            $paymentReturnUrl['payment_id'] = $paymentDetails['resource_id'];
        }

        return [
            'result' => true,
            'url' => $paymentReturnUrl,
            'response' => 'Return URL successfully generated',
        ];
    }

    /**
     * @description Insert payment with all details in table
     *
     * @param array $paymentDetails
     *
     * @return array|bool
     */
    public function insertPaymentTable($paymentDetails)
    {
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['resource_id'])
            || !$paymentDetails['resource_id']
        ) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[insertPaymentTable] $paymentDetail or resource_id is null, or $paymentDetail is not an array'
            );
        }

        $paymentDate = date('Y-m-d H:i:s');
        $cartHash = $this->getHashedCart($paymentDetails);

        if (isset($cartHash['result']) && !$cartHash['result']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[insertPaymentTable] Problem with the getHashedCart method.'
            );
        }

        $paymentExist = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $paymentDetails['cartId']);

        if (!empty($paymentExist)) {
            $this->dependencies
                ->getPlugin()
                ->getPaymentRepository()
                ->remove((int) $paymentDetails['cartId']);
        }

        $parameters = [
            'resource_id' => $paymentDetails['resource_id'],
            'method' => $paymentDetails['method'],
            'id_cart' => (int) $paymentDetails['cartId'],
            'cart_hash' => $cartHash,
            'date_upd' => $paymentDate,
        ];

        $create_payment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->createPayment($parameters);

        if (!$create_payment) {
            return $this->returnPaymentError(
                ['name' => 'DB Query', 'value' => $this->query],
                '[insertPaymentCart] Unable to flush DB (build method)'
            );
        }

        return [
            'result' => true,
            'paymentDetails' => $paymentDetails,
            'response' => 'Insert data in DB successfully',
        ];
    }

    /**
     * @description Check if the payment is valid in API,
     * bc if cancelled, to recreate another one
     *
     * @param array $paymentDetails
     *
     * @return array
     */
    public function isValidApiPayment($paymentDetails)
    {
        if (!is_array($paymentDetails) || empty($paymentDetails)) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] Invalid parameters given, $paymentDetails must be an non empty array'
            );
        }

        if (!isset($paymentDetails['cartId']) || !is_int($paymentDetails['cartId']) || !$paymentDetails['cartId']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] Invalid parameters given, $paymentDetail[cartId] must be a non-null integer'
            );
        }

        $storedPayment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getByCart((int) $paymentDetails['cartId']);
        if (empty($storedPayment)) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] No payment found for given cart id'
            );
        }

        if (!isset($storedPayment['method']) || !$storedPayment['method']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] Invalid stored payment getted, payment_method is not given'
            );
        }

        if (!isset($storedPayment['resource_id']) || !$storedPayment['resource_id']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] Invalid stored payment getted, resource_id is not given'
            );
        }

        if ('installment' == $storedPayment['method']) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($storedPayment['resource_id']);
            if (!$installment['result']) {
                return $this->returnPaymentError(
                    ['name' => 'storedPayment', 'value' => $storedPayment],
                    '[isValidApiPayment] Cannot retrieve payment with id: ' . $storedPayment['resource_id']
                );
            }
            $installment = $installment['resource'];
            $firstSchedule = $installment->schedule[0]->payment_ids;
            // Try to see if the first schedule was cancelled
            $storedPayment['resource_id'] = end($firstSchedule);
        }

        $retrievedPayment = $this->dependencies->apiClass->retrievePayment($storedPayment['resource_id']);
        if (!$retrievedPayment['result']
            || (
                isset($retrievedPayment['resource']->failure->code)
                && $retrievedPayment['resource']->failure->code
            )) {
            $payment = $this->createPayment($paymentDetails);

            return $this->updatePaymentTable($payment['paymentDetails']);
        }

        return [
            'result' => true,
            'paymentDetails' => $paymentDetails,
            'response' => 'Valid API payment/installment',
        ];
    }
}
