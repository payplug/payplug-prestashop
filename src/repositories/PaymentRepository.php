<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use DateTime;
use Exception;
use Payplug;
use Payplug\InstallmentPlan;
use Payplug\Payment;

class PaymentRepository extends Repository
{
    protected $payplug;
    private $apiPayment;
    private $cartSpecific;
    private $logger;
    private $paymentEntity;
    private $query;
    private $constant;

    public function __construct(
        $payplug,
        $cartSpecific,
        $logger,
        $paymentEntity,
        $query,
        $constant
    )
    {
        $this->payplug = $payplug;
        $this->cartSpecific = $cartSpecific;
        $this->logger = $logger;
        $this->paymentEntity = $paymentEntity;
        $this->query = $query;
        $this->constant = $constant;

        $this->logger->setParams(['process' => 'payment']);
    }

    /**
     * @description Compare hash (sha256 on payment method + cart)
     * Create an other payment request if cart or payment method changed
     * and update payment table in consequence
     * @param array $paymentDetails
     * @return array
     */
    public function checkHash($paymentDetails)
    {
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['cartId'])
            || !$paymentDetails['cartId']
        ) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[checkHash] $paymentDetails or cartId is null, or $paymentDetails is not an array'
            );
        }

        $paymentStored = $this->checkPaymentTable($paymentDetails['cartId']);

        $cartToHash = $paymentDetails['cart'];
        $cartToHash->date_add = $cartToHash->date_upd = null;
        $cartToHash->id_address_delivery = (string)$cartToHash->id_address_delivery;
        $cartToHash->id_address_invoice = (string)$cartToHash->id_address_invoice;

        $cartHash = hash('sha256', $paymentDetails['paymentMethod'] . json_encode($cartToHash));

        if ($paymentStored['cart_hash'] === $cartHash
            &&
            ($paymentStored['payment_method'] == $paymentDetails['paymentMethod'])) {
            return [
                'result' => true,
                'paymentDetails' => $paymentDetails,
                'response' => 'OK. Comparaison result: Same hash and same payment method.'
            ];
        } else {
            // Create payment or installment
            try {
                $createPayment = $this->createPayment($paymentDetails);
            } catch (Payplug\Exception\ConfigurationNotSetException $e) {
                return $this->paymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    '[checkHash -> createPayment] Error: ' . $e->getMessage()
                );
            }

            if ($createPayment['result'] && $createPayment['paymentDetails']) {
                $paymentDetails = $createPayment['paymentDetails'];
            } elseif (!$createPayment['result']) {
                return $this->paymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    $createPayment['response']
                );
            }

            // Update payment table
            $updatePaymentTable = $this->updatePaymentTable($paymentDetails);
            if ($updatePaymentTable['result'] && $updatePaymentTable['paymentDetails']) {
                $paymentDetails = $updatePaymentTable['paymentDetails'];
            } elseif (!$updatePaymentTable['result']) {
                return $this->paymentError(
                    ['name' => 'paymentDetails', 'value' => $updatePaymentTable['paymentDetails']],
                    $updatePaymentTable['response']
                );
            }

            return [
                'result' => true,
                'paymentDetails' => $paymentDetails,
                'response' => 'Payment created and updated successfully'
            ];
        }
    }

    /**
     * @description Return an error with some details in logger
     * @param array $element
     * @param null $errorMessage
     * @param string $level
     * @return array
     */
    public function paymentError($element = [], $errorMessage = null, $level = 'error')
    {
        $element['value'] = json_encode($element['value']);

        if (!$errorMessage) {
            $errorMessage = $this->l('[PaymentRepository] Error during payment creation process.');
        }

        if (!is_string($errorMessage)) {
            $this->paymentError($element, '[paymentError] The error message in parameter is not a string.');
        }

        $this->logger->setParams(['process' => 'paymentRepository']);
        $this->logger->addLog($errorMessage, $level);
        $this->logger->addLog($element['name'] . ': ' . $element['value'], 'debug');

        $this->payplug->setPaymentErrorsCookie([
            $this->l('The transaction was not completed and your card was not charged.')
        ]);

        return [
            'result' => false,
            $element['name'] => $element['value'],
            'response' => $errorMessage,
        ];
    }

    /**
     * @description Check if existing payment / installment in payment table
     * @param integer $idCart
     * @return bool|array
     */
    public function checkPaymentTable($idCart)
    {
        if (!$idCart || !is_int($idCart)) {
            return $this->paymentError(
                ['name' => 'cart id', 'value' => $idCart],
                '[checkPaymentTable] Problem with $idCart parameter'
            );
        }

        $reqCheck = $this->query
            ->select()
            ->fields('*')
            ->from(_DB_PREFIX_ . 'payplug_payment')
            ->where('id_cart = ' . (int)$idCart);

        $resCheck = $reqCheck->build();

        if (!$resCheck) {
            return false;
        } else {
            return end($resCheck);
        }
    }

    /**
     * @description Create payment / installment
     * @param array $paymentDetails
     * @return array
     */
    public function createPayment($paymentDetails)
    {
        if (!$paymentDetails || !$paymentDetails['paymentTab'] || !$paymentDetails['paymentMethod']) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment] $paymentDetails or paymentTab or paymentMethod is null'
            );
        }

        if ($paymentDetails['paymentMethod'] !== 'installment') {
            try {
                if ($apiPayment = Payment::create($paymentDetails['paymentTab'])) {
                    $this->paymentEntity->setApiPayment($apiPayment);
                }
            } catch (Exception $e) {
                return $this->paymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    '[createPayment] Exception. Unable to create payment. Error: ' . $e->getMessage()
                );
            }
        } else {
            try {
                $apiPayment = InstallmentPlan::create($paymentDetails['paymentTab']);
                $this->paymentEntity->setApiPayment($apiPayment);
            } catch (Exception $e) {
                return $this->paymentError(
                    ['name' => 'paymentDetails', 'value' => $paymentDetails],
                    '[createPayment] Exception. Unable to installment plan. Error: ' . $e->getMessage()
                );
            }
        }

        $this->apiPayment = $this->paymentEntity->getApiPayment();

        if ($this->apiPayment->failure == true && !empty($this->apiPayment->failure->message)) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $this->apiPayment],
                (string)$this->apiPayment->failure->message
            );
        }

        // We can now hydrate our params
        if (isset($this->apiPayment->id)) {
            $paymentDetails['paymentId'] = $this->apiPayment->id;
        }

        if (!$paymentDetails['paymentId']) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[createPayment ' . (string)$paymentDetails['paymentMethod'] . '] The payment id is null.'
            );
        }

        if (isset($this->apiPayment->hosted_payment->return_url)) {
            $paymentDetails['paymentReturnUrl'] = $this->apiPayment->hosted_payment->return_url;
        }

        if (!$paymentDetails['paymentReturnUrl']) {
            return $this->paymentError(
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
            'response' => '[createPayment] Payment successfully created'
        ];
    }

    /**
     * @description Update hash and payment id in Payplug Payment Cart table
     * @param array $paymentDetails
     * @return array
     */
    public function updatePaymentTable($paymentDetails)
    {
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['cart'])
            || !$paymentDetails['cart']
        ) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[updatePaymentTable] $paymentDetails or cart is null, or $paymentDetails is not an array'
            );
        }

        $paymentDate = date('Y-m-d H:i:s');

        $cartToHash = $paymentDetails['cart'];
        $cartToHash->date_add = $cartToHash->date_upd = null;
        $cartToHash->id_address_delivery = (string)$cartToHash->id_address_delivery;
        $cartToHash->id_address_invoice = (string)$cartToHash->id_address_invoice;

        $cartHash = hash('sha256', $paymentDetails['paymentMethod'] . json_encode($cartToHash));

        $table = $this->constant->get('_DB_PREFIX_') . 'payplug_payment';

        $this->query
            ->update()
            ->table($table)
            ->set('id_payment =         \'' . $paymentDetails['paymentId'] . '\'')
            ->set('payment_method =     \'' . $paymentDetails['paymentMethod'] . '\'')
            ->set('payment_url =        \'' . $paymentDetails['paymentUrl'] . '\'')
            ->set('payment_return_url = \'' . $paymentDetails['paymentReturnUrl'] . '\'')
            ->set('cart_hash =          \'' . $cartHash . '\'')
            ->set('authorized_at =      \'' . $paymentDetails['authorizedAt'] . '\'')
            ->set('is_paid =            \'' . $paymentDetails['isPaid'] . '\'')
            ->set('date_upd =           \'' . $paymentDate . '\'')
            ->where('id_cart =          ' . (int)$paymentDetails['cartId']);

        try {
            if (!$this->query->build()) {
                return $this->paymentError(
                    ['name' => 'paymentDetails', 'value' => $this->query],
                    '[updatePaymentTable] Unable to fetch the query on DB but no throw'
                );
            }
        } catch (Exception $e) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[updatePaymentTable] Unable to fetch the query on DB. Error: ' . $e->getMessage()
            );
        }

        return [
            'result' => true,
            'paymentDetails' => $paymentDetails,
            'response' => 'Update DB with new payment creation successfully'
        ];
    }

    /**
     * @description Check if payment created < 3 min in DB
     * @param $idCart
     * @return array|bool
     * @throws Exception
     */
    public function checkTimeoutPayment($idCart)
    {
        if (!$idCart || !is_int($idCart)) {
            return $this->paymentError(
                ['name' => 'id cart', 'value' => $idCart],
                '[checkTimeoutPayment] Problem with $idCart parameter'
            );
        }

        $dateStored = $this->checkPaymentTable($idCart)['date_upd'];

        $date = new DateTime($dateStored);
        $date2 = new DateTime('now');

        if ($date->diff($date2)->y !== 0 ||
            $date->diff($date2)->d !== 0 ||
            $date->diff($date2)->h !== 0 ||
            $date->diff($date2)->i > 3) {
            // Plus de 3 minutes
            return false;
        } else {
            // Moins de 3 minutes
            return true;
        }
    }

    /**
     * @description Generate and return correct payment return url
     * @param array $paymentDetails
     * @return array
     */
    public function getPaymentReturnUrl($paymentDetails)
    {
        if (!$paymentDetails) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[getPaymentReturnUrl] $paymentDetails is null'
            );
        }

        $paymentStored = $this->checkPaymentTable($paymentDetails['cartId']);

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

        switch ($paymentDetails['paymentMethod']) {
            case 'oneclick':
                $redirect = $paymentDetails['isPaid'];
                if (!$redirect && $paymentDetails['isDeferred']) {
                    $redirect = (bool)$paymentDetails['authorizedAt'];
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
            case 'standard':
            case 'installment':
            default:
                $returnUrl = $paymentDetails['paymentUrl'] ? $paymentDetails['paymentUrl'] :
                    $paymentDetails['paymentReturnUrl'];
                $paymentReturnUrl = [
                    'result' => 'new_card',
                    'embedded' => $paymentDetails['isEmbedded'] && !$paymentDetails['isMobileDevice'],
                    'redirect' => $paymentDetails['isMobileDevice'],
                    'return_url' => $returnUrl,
                ];
                break;
        }

        return [
            'result' => true,
            'url' => $paymentReturnUrl,
            'response' => 'Return URL successfully generated'
        ];
    }

    /**
     * @description Insert payment with all details in table
     * @param array $paymentDetails
     * @return array|bool
     */
    public function insertPaymentTable($paymentDetails)
    {
        if (!$paymentDetails
            || !is_array($paymentDetails)
            || !isset($paymentDetails['paymentId'])
            || !$paymentDetails['paymentId']
        ) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[insertPaymentTable] $paymentDetail or paymentId is null, or $paymentDetail is not an array'
            );
        }

        $paymentDate = date('Y-m-d H:i:s');

        $cartToHash = $paymentDetails['cart'];
        $cartToHash->date_add = $cartToHash->date_upd = null;
        $cartToHash->id_address_delivery = (string)$cartToHash->id_address_delivery;
        $cartToHash->id_address_invoice = (string)$cartToHash->id_address_invoice;

        $cartHash = hash('sha256', $paymentDetails['paymentMethod'] . json_encode($cartToHash));

        $this->query
            ->insert()
            ->into(_DB_PREFIX_ . 'payplug_payment')
            ->fields('id_payment')->values(pSQL($paymentDetails['paymentId']))
            ->fields('payment_method')->values(pSQL($paymentDetails['paymentMethod']))
            ->fields('payment_url')->values(pSQL($paymentDetails['paymentUrl']))
            ->fields('payment_return_url')->values(pSQL($paymentDetails['paymentReturnUrl']))
            ->fields('id_cart')->values(pSQL($paymentDetails['cartId']))
            ->fields('cart_hash')->values(pSQL($cartHash))
            ->fields('authorized_at')->values(pSQL($paymentDetails['authorizedAt']))
            ->fields('is_paid')->values(pSQL($paymentDetails['isPaid']))
            ->fields('date_upd')->values(pSQL($paymentDate));

        try {
            if (!$this->query->build()) {
                return $this->paymentError(
                    ['name' => 'DB Query', 'value' => $this->query],
                    '[insertPaymentCart] Unable to flush DB (build method)'
                );
            }
        } catch (Exception $e) {
            return $this->paymentError(
                ['name' => 'paymentDetails', 'value' => $paymentDetails],
                '[insertPaymentTable] Error: ' . $e->getMessage()
            );
        }

        return [
            'result' => true,
            'paymentDetails' => $paymentDetails,
            'response' => 'Insert data in DB successfully'
        ];
    }
}
