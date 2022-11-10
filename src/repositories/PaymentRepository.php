<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @author    PayPlug SAS
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use DateTime;
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

    public function __construct(
        $cartAdapter,
        $confAdapter,
        $dependencies,
        $logger,
        $paymentEntity,
        $query,
        $constant
    ) {
        $this->dependencies = $dependencies;
        $this->cartAdapter = $cartAdapter;
        $this->confAdapter = $confAdapter;
        $this->logger = $logger;
        $this->paymentEntity = $paymentEntity;
        $this->query = $query;
        $this->constant = $constant;

        $this->logger->setParams(['process' => 'payment']);
    }

    /**
     * @description Return hashed cart with needed (and formatted) elements, ready to be hashed.
     *
     * @param $paymentDetails
     *
     * @return string | array
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
            $paymentDetails['paymentMethod'] .= $paymentDetails['oneyDetails'];
        }

        // Adding cart informationObjectModel
        $cartToHash[] = 'Cart $id_address_delivery: ' . $paymentDetails['cart']->id_address_delivery;
        $cartToHash[] = 'Cart $id_address_invoice: ' . $paymentDetails['cart']->id_address_invoice;
        $cartToHash[] = 'Cart $id_currency: ' . $paymentDetails['cart']->id_currency;
        $cartToHash[] = 'Cart $id_customer: ' . $paymentDetails['cart']->id_customer;
        $cartToHash[] = 'Cart $delivery_option: ' . $paymentDetails['cart']->delivery_option;

        // Adding cart amount to hash
        $cartToHash[] = 'Cart amount: ' . (float) $paymentDetails['cart']->getOrderTotal(true);

        return hash('sha256', $paymentDetails['paymentMethod'] . json_encode($cartToHash));
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

        $paymentStored = $this->checkPaymentTable($paymentDetails['cartId']);

        $cartHash = $this->getHashedCart($paymentDetails);

        if ($paymentStored['cart_hash'] === $cartHash
            && ($paymentStored['payment_method'] == $paymentDetails['paymentMethod'])) {
            return [
                'result' => true,
                'paymentDetails' => $paymentDetails,
                'response' => 'OK. Comparaison result: Same hash and same payment method.',
            ];
        }
        // Create payment or installment
        $createPayment = $this->createPayment($paymentDetails);

        if ($createPayment['result'] && $createPayment['paymentDetails']) {
            $paymentDetails = $createPayment['paymentDetails'];
        } elseif (!$createPayment['result']) {
            unset($paymentDetails['paymentTab']);

            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                $createPayment['response']
            );
        }

        // Update payment table
        $updatePaymentTable = $this->updatePaymentTable($paymentDetails);
        if ($updatePaymentTable['result'] && $updatePaymentTable['paymentDetails']) {
            $paymentDetails = $updatePaymentTable['paymentDetails'];
        } elseif (!$updatePaymentTable['result']) {
            return $this->returnPaymentError(
                ['name' => 'updatePaymentTable', 'value' => $updatePaymentTable],
                $updatePaymentTable['response']
            );
        }

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

        $this->logger->setParams(['process' => 'paymentRepository']);
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
     * @description Check if existing payment / installment in payment table
     *
     * @param int $idCart
     *
     * @return array|bool
     */
    public function checkPaymentTable($idCart)
    {
        if (!$idCart || !is_int($idCart)) {
            return $this->returnPaymentError(
                ['name' => 'cart id', 'value' => $idCart],
                '[checkPaymentTable] Problem with $idCart parameter'
            );
        }

        $reqCheck = $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('id_cart = ' . (int) $idCart)
        ;

        $resCheck = $reqCheck->build();

        if (!$resCheck) {
            return false;
        }

        return end($resCheck);
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
        if (!$paymentDetails || !is_array($paymentDetails)) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => 'No payment details'],
                '[createPayment] Invalid $paymentDetails given'
            );
        }

        if (!isset($paymentDetails['paymentTab'])
            || !is_array($paymentDetails['paymentTab'])
            || empty($paymentDetails['paymentTab'])) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] Invalid paymentTab given in $paymentDetails'
            );
        }

        if (!isset($paymentDetails['paymentMethod']) || !is_string($paymentDetails['paymentMethod'])) {
            unset($paymentDetails['paymentTab']);

            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] Invalid paymentMethod given in $paymentDetails'
            );
        }

        if (!isset($paymentDetails['cartId']) || !is_int($paymentDetails['cartId'])) {
            unset($paymentDetails['paymentTab']);

            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] Invalid cartId given in $paymentDetails'
            );
        }

        if ($paymentDetails['paymentMethod'] == 'standard' && !$this->confAdapter->get('PAYPLUG_STANDARD')) {
            return $this->returnPaymentError(
                ['name' => 'Configuration::get', 'value' => $this->confAdapter->get('PAYPLUG_STANDARD')],
                '[createPayment] Try to create standard payment with PAYPLUG_STANDARD disabled'
            );
        }

        // Before create a new payment, delete the previous one, if exists and it's not a oneclick payment
        // to avoid double order creation
        if ($apiPayment = $this->checkPaymentTable($paymentDetails['cartId'])) {
            if (!in_array($apiPayment['payment_method'], ['oneclick', 'bancontact', 'apple_pay', 'oney', 'amex'])) {
                $payment = $this->dependencies->apiClass->retrievePayment($apiPayment['id_payment']);
                if ($payment['result'] && !$payment['resource']->failure) {
                    $this->logger->addLog('Payment already exists: ' . $apiPayment['id_payment'] . ', so we delete it before create a new one');
                    $abort = $this->dependencies->apiClass->abortPayment($apiPayment['id_payment']);
                    if (!$abort['result']) {
                        return $this->returnPaymentError(
                            ['name' => 'paymentId', 'value' => $apiPayment['id_payment']],
                            '[createPayment] Exception. Unable to abort payment. Error: ' . $abort['message']
                        );
                    }

                    $this->logger->addLog('Payment aborted.');
                }
            } elseif ($apiPayment['payment_method'] == 'apple_pay') {
                $this->dependencies->paymentClass->deletePayment($paymentDetails['cartId']);
            }
        }

        if ($paymentDetails['paymentMethod'] !== 'installment') {
            $payment = $this->dependencies->apiClass->createPayment($paymentDetails['paymentTab']);
            if (!$payment['result']) {
                unset($paymentDetails['paymentTab']);

                return $this->returnPaymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    '[createPayment] Exception. Unable to create payment. Error: ' . $payment['message']
                );
            }

            $this->paymentEntity->setApiPayment($payment['resource']);
        } else {
            $payment = $this->dependencies->apiClass->createInstallment($paymentDetails['paymentTab']);
            if (!$payment['result']) {
                unset($paymentDetails['paymentTab']);

                return $this->returnPaymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    '[createPayment] Exception. Unable to create installment plan. Error: ' . $payment['message']
                );
            }
            $this->paymentEntity->setApiPayment($payment['resource']);
        }

        $this->apiPayment = $this->paymentEntity->getApiPayment();

        if ($this->apiPayment->failure == true && !empty($this->apiPayment->failure->message)) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $this->apiPayment],
                (string) $this->apiPayment->failure->message
            );
        }

        // We can now hydrate our params
        if (isset($this->apiPayment->id)) {
            $paymentDetails['paymentId'] = $this->apiPayment->id;
        }

        if (!$paymentDetails['paymentId']) {
            unset($paymentDetails['paymentTab']);

            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment ' . (string) $paymentDetails['paymentMethod'] . '] The payment id is null.'
            );
        }

        if (isset($paymentDetails['paymentTab']['integration']) || $paymentDetails['paymentMethod'] == 'apple_pay') {
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

        if (($paymentDetails['paymentMethod'] !== 'installment') && ($this->apiPayment->authorization !== null)) {
            if ($this->apiPayment->authorization->authorized_at !== null) {
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

        $paymentDate = date('Y-m-d H:i:s');

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

        $table = $this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment';

        $this->query
            ->update()
            ->table($table)
            ->set('id_payment =         "' . $this->query->escape($paymentDetails['paymentId']) . '"')
            ->set('payment_method =     "' . $this->query->escape($paymentDetails['paymentMethod']) . '"')
            ->set('payment_url =        "' . $this->query->escape($paymentDetails['paymentUrl']) . '"')
            ->set('payment_return_url = "' . $this->query->escape($paymentDetails['paymentReturnUrl']) . '"')
            ->set('cart_hash =          "' . $this->query->escape($cartHash) . '"')
            ->set('authorized_at =      "' . $this->query->escape($paymentDetails['authorizedAt']) . '"')
            ->set('is_paid =            "' . $this->query->escape($paymentDetails['isPaid']) . '"')
            ->set('date_upd =           "' . $this->query->escape($paymentDate) . '"')
            ->where('id_cart =          ' . (int) $paymentDetails['cartId'])
        ;

        try {
            if (!$this->query->build()) {
                return $this->returnPaymentError(
                    ['name' => 'paymentDetails', 'value' => $this->query],
                    '[updatePaymentTable] Unable to fetch the query on DB but no throw'
                );
            }
        } catch (Exception $e) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[updatePaymentTable] Unable to fetch the query on DB. Error: ' . $e->getMessage()
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
     * @return array|bool
     */
    public function checkTimeoutPayment($idCart)
    {
        if (!$idCart || !is_int($idCart)) {
            return $this->returnPaymentError(
                ['name' => 'id cart', 'value' => $idCart],
                '[checkTimeoutPayment] Problem with $idCart parameter'
            );
        }

        $dateStored = $this->checkPaymentTable($idCart)['date_upd'];

        $date = new DateTime($dateStored);
        $date2 = new DateTime('now');

        if ($date->diff($date2)->y !== 0
            || $date->diff($date2)->d !== 0
            || $date->diff($date2)->h !== 0
            || $date->diff($date2)->i > 3) {
            // Plus de 3 minutes
            return false;
        }
        // Moins de 3 minutes
        return true;
    }

    /**
     * @description Generate and return correct payment return url
     *
     * @param array $paymentDetails
     *
     * @return array
     */
    public function getPaymentReturnUrl($paymentDetails)
    {
        if (!$paymentDetails) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[getPaymentReturnUrl] $paymentDetails is null'
            );
        }

        $paymentStored = $this->checkPaymentTable($paymentDetails['cartId']);

        if (!$paymentStored) {
            return $this->returnPaymentError(
                ['name' => 'paymentStored', 'value' => false],
                '[getPaymentReturnUrl] $paymentStored is null or invalid'
            );
        }

        if (!$paymentDetails['paymentUrl']) {
            $paymentDetails['paymentUrl'] = $paymentStored['payment_url'];
        }

        if (!$paymentDetails['paymentReturnUrl']) {
            $paymentDetails['paymentReturnUrl'] = $paymentStored['payment_return_url'];
        }

        if (!$paymentDetails['authorizedAt']) {
            $paymentDetails['authorizedAt'] = $paymentStored['authorized_at'];
        }

        if (!$paymentDetails['isPaid']) {
            $paymentDetails['isPaid'] = $paymentStored['is_paid'];
        }

        if (!$paymentDetails['paymentUrl'] && !$paymentDetails['paymentReturnUrl']) {
            $err = '[getPaymentReturnUrl] $paymentDetails[\'paymentUrl\'] ';
            $err .= '&& $paymentDetails[\'paymentReturnUrl\'] are null';

            return $this->returnPaymentError(
                ['name' => 'paymentUrl', 'value' => false],
                $err
            );
        }

        switch ($paymentDetails['paymentMethod']) {
            case 'oneclick':
                $redirect = $paymentDetails['isPaid'];
                if (!$redirect && $paymentDetails['isDeferred']) {
                    $redirect = (bool) $paymentDetails['authorizedAt'];
                }

                $paymentReturnUrl = [
                    'result' => true,
                    'embedded' => true,
                    'redirect' => $redirect, // force `true` we are in 3DS 1
                    'return_url' => $redirect ?
                        $paymentDetails['paymentReturnUrl'] : $paymentDetails['paymentUrl'],
                ];

                break;

            case 'oney':
                $paymentReturnUrl = [
                    'result' => 'new_card',
                    'embedded' => false,
                    'redirect' => true,
                    'return_url' => $paymentDetails['paymentUrl'],
                ];

                break;

            case 'amex':
            case 'apple_pay':
            case 'bancontact':
            case 'installment':
            case 'integrated':
            case 'standard':
                $returnUrl = $paymentDetails['paymentUrl']
                    ? $paymentDetails['paymentUrl']
                    : $paymentDetails['paymentReturnUrl'];
                $paymentReturnUrl = [
                    'result' => 'new_card',
                    'embedded' => $paymentDetails['isEmbedded']
                        && !$paymentDetails['isMobileDevice']
                        && $paymentDetails['paymentMethod'] !== 'bancontact',
                    'redirect' => $paymentDetails['isMobileDevice'],
                    'return_url' => $returnUrl,
                ];
                if ($paymentDetails['isIntegrated']) {
                    $paymentReturnUrl['payment_id'] = $paymentDetails['paymentId'];
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
            $paymentReturnUrl['payment_id'] = $paymentDetails['paymentId'];
        }

        return [
            'result' => true,
            'url' => $paymentReturnUrl,
            'response' => 'Return URL successfully generated',
        ];
    }

    /**
     * @description  get payment id
     *
     * @param $idCart
     *
     * @return array
     */
    public function getPayment($idCart)
    {
        if (!$idCart || !is_int($idCart)) {
            return $this->returnPaymentError(
                ['name' => 'idCart', 'value' => $idCart],
                '[getPayment] $cart id  is not valid'
            );
        }
        $this->query
            ->select()
            ->fields('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('id_cart = ' . (int) $idCart)
        ;

        return $this->query->build('unique_value');
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
            || !isset($paymentDetails['paymentId'])
            || !$paymentDetails['paymentId']
        ) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[insertPaymentTable] $paymentDetail or paymentId is null, or $paymentDetail is not an array'
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

        $paymentExist = $this->getPayment((int) $paymentDetails['cartId']);
        if (isset($paymentExist['result']) && !$paymentExist['result']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[insertPaymentTable] Problem with the getpayment method.'
            );
        }
        if ($paymentExist) {
            $this->dependencies->paymentClass->deletePayment($paymentDetails['cartId']);
        }
        $this->query
            ->insert()
            ->into($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->fields('id_payment')->values($this->query->escape($paymentDetails['paymentId']))
            ->fields('payment_method')->values($this->query->escape($paymentDetails['paymentMethod']))
            ->fields('payment_return_url')->values($this->query->escape($paymentDetails['paymentReturnUrl']))
            ->fields('id_cart')->values((int) $paymentDetails['cartId'])
            ->fields('cart_hash')->values($this->query->escape($cartHash))
            ->fields('authorized_at')->values((int) $paymentDetails['authorizedAt'])
            ->fields('is_paid')->values((int) $paymentDetails['isPaid'])
            ->fields('date_upd')->values($this->query->escape($paymentDate));

        if ($paymentDetails['paymentUrl'] != '') {
            $this->query->fields('payment_url')->values($this->query->escape($paymentDetails['paymentUrl']));
        }

        try {
            if (!$this->query->build()) {
                return $this->returnPaymentError(
                    ['name' => 'DB Query', 'value' => $this->query],
                    '[insertPaymentCart] Unable to flush DB (build method)'
                );
            }
        } catch (Exception $e) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[insertPaymentTable] Error: ' . $e->getMessage()
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
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['cartId'])
            || !$paymentDetails['cartId']
            || !is_int($paymentDetails['cartId'])
        ) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] $paymentDetail or cartId is null, or $paymentDetail is not an array'
            );
        }

        try {
            $storedPayment = $this->checkPaymentTable($paymentDetails['cartId']);
        } catch (Exception $e) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] Error: ' . $e->getMessage()
            );
        }

        if (!$storedPayment || !$storedPayment['payment_method'] || !$storedPayment['id_payment']) {
            return $this->returnPaymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[isValidApiPayment] $storedPayment or payment_method or id_payment is null'
            );
        }

        if ($storedPayment['payment_method'] == 'installment') {
            $installment = $this->dependencies->apiClass->retrieveInstallment($storedPayment['id_payment']);
            if (!$installment['result']) {
                return $this->returnPaymentError(
                    ['name' => 'storedPayment', 'value' => $storedPayment],
                    '[isValidApiPayment] Cannot retrieve payment with id:' . $storedPayment['id_payment']
                );
            }
            $installment = $installment['resource'];
            $firstSchedule = $installment->schedule[0]->payment_ids;
            // Try to see if the first schedule was cancelled
            $storedPayment['id_payment'] = end($firstSchedule);
        }

        if (!$storedPayment['id_payment']) {
            return $this->returnPaymentError(
                ['name' => 'storedPayment', 'value' => $storedPayment],
                '[isValidApiPayment] $storedPayment[\'id_payment\'] is null'
            );
        }

        $retrievedPayment = $this->dependencies->apiClass->retrievePayment($storedPayment['id_payment']);
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
