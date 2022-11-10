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

namespace PayPlug\classes;

use Exception;
use Payplug\Exception\UnknownAPIResourceException;
use Payplug\Notification;
use Payplug\Resource\InstallmentPlan;
use Payplug\Resource\Payment;
use Payplug\Resource\Refund;

/**
 * Class PayPlugNotifications
 * Use for treat notification from Payplug API
 */
class PayPlugNotifications
{
    public $api_key;
    public $cart;
    public $except;
    public $flag;
    public $is_amex = false;
    public $is_applepay = false;
    public $is_oney = false;
    public $is_installment = false;
    public $is_deferred = false;
    public $is_bancontact = false;
    public $key;
    public $lock_key;
    public $logger;
    public $order;
    public $order_states = [];
    public $payment;
    public $resource;
    public $resp;
    public $sandbox;
    public $type;
    public $query;

    private $dependencies;

    // Plugin adapter
    private $addressAdapter;
    private $cartAdapter;
    private $configAdapter;
    private $constantAdapter;
    private $contextAdapter;
    private $countryAdapter;
    private $currencyAdapter;
    private $customerAdapter;
    private $languageAdapter;
    private $messageAdapter;
    private $orderAdapter;
    private $orderHistoryAdapter;
    private $shopAdapter;
    private $toolsAdapter;
    private $validateAdapter;

    private $amountCurrencyClass;
    private $apiClass;
    private $orderClass;
    private $paymentClass;
    private $installmentClass;
    private $payplugLock;
    private $module;
    private $plugin;

    public function __construct()
    {
        $this->setConfig();
    }

    /**
     * @description Set the logger
     */
    public function setLogger()
    {
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->logger->addLog('Notification: setLogger');
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->logger->setParams(['process' => 'notification']);
    }

    /**
     * @description Entry point to treat the notification
     */
    public function treat()
    {
        //Notification identification
        $this->logger->addLog('Notification treatment and authenticity verification:');

        $this->logger->addLog('OK');

        if ($this->resource instanceof Payment) {
            $this->processPayment();
        } elseif ($this->resource instanceof Refund) {
            $this->processRefund();
        } elseif ($this->resource instanceof InstallmentPlan) {
            $this->processInstallment();
        }
    }

    /**
     * @descrition Check if the resource allow to save the payment card
     *
     * @return bool
     */
    private function canSaveCard()
    {
        $this->logger->addLog('Notification: canSaveCard');
        $can_save_card = $this->is_installment ? false : true;

        return $can_save_card && (
            $this->payment->save_card
                || (
                    $this->payment->card->id
                    && $this->payment->hosted_payment
                )
        );
    }

    /**
     * @descrition Check if the payment resource can be treated
     */
    private function checkIsValidPaymentResource()
    {
        if (!$this->payment->is_paid && !$this->is_deferred && !$this->is_oney) {
            $this->logger->addLog('The transaction is not paid yet.');
            $this->logger->addLog('No action will be done.');
            $this->exitProcess('The transaction is not paid.');
        }
    }

    /**
     * @descrition Dispatch the payment to create or update the relative order
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function dispatchPayment()
    {
        $this->logger->addLog('Notification: dispatchPayment');
        $id_order = $this->orderAdapter->getOrderByCartId($this->cart->id);
        if ($id_order) {
            $this->order = $this->orderAdapter->get((int) $id_order);
            if (!$this->validateAdapter->validate('isLoadedObject', $this->order)) {
                $this->exitProcess('Order cannot be loaded.', 500);
            }
            $this->processUpdateOrder();
        } else {
            $this->processCreateOrder();
        }
    }

    /**
     * @description Entry point to treat the notification
     *
     * @param string $str
     * @param int    $http_code
     */
    private function exitProcess($str = '', $http_code = 200)
    {
        $this->logger->addLog('Notification: exitProcess');
        if ($str) {
            $this->logger->addLog($str);
        }
        if ($this->lock_key) {
            if (!$this->payplugLock->deleteLockG2($this->lock_key)) {
                $this->logger->addLog('Lock cannot be deleted.', 'error');
            } else {
                $this->logger->addLog('Lock deleted.', 'debug');
            }
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $http_code . ' ' . $str, true, $http_code);

        exit;
    }

    /**
     * @descrition Get the new order state we should attribute to
     *
     * @return array
     */
    private function getNewOrderState()
    {
        $this->logger->addLog('Notification: getNewOrderState');
        // Check if order is refused by oney
        if ($this->is_oney && $this->payment->failure) {
            $this->logger->addLog('NewOrderState: cancelled');

            return [
                'valid' => false,
                'status' => 'cancelled',
            ];
        }

        // CHeck if payment capture is expired
        if ($this->is_deferred && ($this->payment->authorization->expires_at - time()) <= 0) {
            $this->logger->addLog('NewOrderState: expired');

            return [
                'valid' => false,
                'status' => 'expired',
            ];
        }

        // Check if payment has failure
        if ($this->payment->failure) {
            $this->logger->addLog('NewOrderState: error');

            return [
                'valid' => false,
                'status' => 'error',
            ];
        }

        $this->logger->addLog('NewOrderState: paid');

        return [
            'valid' => true,
            'status' => 'paid',
        ];
    }

    /**
     * @description Get the resource from the notification
     */
    private function getResource()
    {
        $this->logger->addLog('Notification: getResource');
        $body = $this->toolsAdapter->tool('file_get_contents', 'php://input');

        try {
            $resource = json_decode($body);
            $this->api_key = (bool) $resource->is_live ?
                $this->configAdapter->get(
                    $this->dependencies->getConfigurationKey('liveApiKey')
                ) :
                $this->configAdapter->get(
                    $this->dependencies->getConfigurationKey('testApiKey')
                );
            $this->apiClass->setSecretKey($this->api_key);
            $this->resource = Notification::treat($body);
            $this->logger->addLog('Resource ID: ' . $this->resource->id);
        } catch (UnknownAPIResourceException $exception) {
            $this->exitProcess($exception->getMessage(), 500);
        }
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function processCreateOrder()
    {
        $this->logger->addLog('Notification: processCreateOrder');

        if (isset($this->resource->failure) && $this->resource->failure !== null) {
            $this->logger->addLog('The payment has failed.');
            $this->exitProcess('No treatment because payment has failed.');
        }

        $is_paid = $this->resource->is_paid;

        if ($this->is_installment) {
            $installment = new PPPaymentInstallment($this->resource->installment_plan_id, $this->dependencies);
            $first_payment = $installment->getFirstPayment();
            if ($first_payment->isDeferred()) {
                $order_state = $this->order_states['auth'];
            } else {
                $order_state = $this->order_states['paid'];
            }

            $amount = 0;
            $installment = $this->apiClass->retrieveInstallment($this->resource->installment_plan_id);
            if (!$installment['result']) {
                $this->logger->addLog('Can\'t retrieve installment: ' . $installment['message']);
                $this->exitProcess('Can\'t retrieve installment: ' . $installment['message']);
            }

            $installment = $installment['resource'];
            foreach ($installment->schedule as $schedule) {
                $amount += (int) $schedule->amount;
            }

            $transaction_id = $this->resource->installment_plan_id;
        } else {
            //We can't treat Oney pending IPN anymore because it's sent with no reason
            if ($this->is_oney && !$is_paid) {
                $order_state = $this->order_states['oney'];
            } elseif ($this->is_deferred && !$is_paid) {
                $order_state = $this->order_states['auth'];
            } else {
                $order_state = $this->order_states['paid'];
            }

            $amount = $this->payment->amount;
            $transaction_id = $this->payment->id;
        }

        $extra_vars = [
            'transaction_id' => $transaction_id,
        ];

        $amount = $this->amountCurrencyClass->convertAmount($amount, true);

        $currency = (int) $this->cart->id_currency;

        try {
            $customer = $this->customerAdapter->get((int) $this->cart->id_customer);
        } catch (Exception $exception) {
            $this->logger->addLog(
                'Customer cannot be loaded: ' . $exception->getMessage(),
                'error'
            );
            $this->exitProcess($exception->getMessage(), 500);
        }
        if (!$this->validateAdapter->validate('isLoadedObject', $customer)) {
            $this->logger->addLog('Customer cannot be loaded.', 'error');
            $this->exitProcess('Customer cannot be loaded.', 500);
        }

        /*
             * For some reasons, secure key form cart can differ from secure key from customer
             * Maybe due to migration or Prestashop's Update
             */
        $secure_key = false;
        if (isset($customer->secure_key) && !empty($customer->secure_key)) {
            if (isset($this->cart->secure_key)
                && !empty($this->cart->secure_key)
                && $this->cart->secure_key !== $customer->secure_key
            ) {
                $secure_key = $this->cart->secure_key;
                $this->logger->addLog('Secure keys do not match.', 'error');
                $this->logger->addLog(
                    'Customer Secure Key: ' . $customer->secure_key,
                    'error'
                );
                $this->logger->addLog('Cart Secure Key: ' . $this->cart->secure_key, 'error');
            } else {
                $secure_key = $customer->secure_key;
            }
        }

        $module_name = $this->module->displayName;

        if ($this->is_oney) {
            switch ($this->payment->payment_method['type']) {
                case 'oney_x3_with_fees':
                    $name = $this->dependencies->l('Oney 3x', 'payplugnotifications');

                    break;

                case 'oney_x4_with_fees':
                    $name = $this->dependencies->l('Oney 4x', 'payplugnotifications');

                    break;

                case 'oney_x3_without_fees':
                    $name = $this->dependencies->l('notification.createOrder.oneyX3WithoutFees', 'payplugnotifications');

                    break;

                case 'oney_x4_without_fees':
                    $name = $this->dependencies->l('notification.createOrder.oneyX4WithoutFees', 'payplugnotifications');

                    break;

                default:
                    $name = $module_name;

                    break;
            }
            $module_name = $name;
        } elseif ($this->is_bancontact) {
            $module_name = $this->dependencies->l('notification.createOrder.bancontact', 'payplugnotifications');
        } elseif ($this->is_applepay) {
            $module_name = $this->dependencies->l('notification.createOrder.applepay', 'payplugnotifications');
        } elseif ($this->is_amex) {
            $module_name = $this->dependencies->l('notification.createOrder.amex', 'payplugnotifications');
        }

        // Create Order
        try {
            $cart_amount = (float) $this->cart->getOrderTotal(true);

            if ($amount != $cart_amount) {
                $this->logger->addLog('Cart amount is different and may occurred an error');
                $this->logger->addLog('Cart amount:' . $cart_amount);
            }

            $this->logger->addLog('Order create with amount:' . $amount);
            $is_order_validated = $this->module->validateOrder(
                $this->cart->id,
                $order_state,
                $amount,
                $module_name,
                null,
                $extra_vars,
                $currency,
                false,
                $secure_key
            );
        } catch (Exception $exception) {
            $this->logger->addLog('Order cannot be validated: ' . $exception->getMessage(), 'error');
            $this->exitProcess($exception->getMessage(), 500);
        }
        if (!$is_order_validated) {
            $this->logger->addLog('Order cannot be validated.', 'error');
            $this->exitProcess('Order cannot be validated.', 500);
        }
        $this->logger->addLog('Order validated.');

        // Then load it
        $this->order = $this->orderAdapter->get((int) $this->module->currentOrder);
        if (!$this->validateAdapter->validate('isLoadedObject', $this->order)) {
            $this->logger->addLog('Order cannot be loaded.', 'error');
            $this->exitProcess('Order cannot be loaded.', 500);
        }
        $this->logger->addLog('Order loaded.', 'debug');

        // Add payplug OrderPayment && Installment
        if ($this->is_installment) {
            $this->installmentClass->addPayplugInstallment($this->resource->installment_plan_id, $this->order);
        } else {
            $data = [];
            $data['metadata'] = $this->payment->metadata;
            $data['metadata']['Order'] = $this->order->id;

            $patchPayment = $this->apiClass->patchPayment($this->payment->id, $data);
            if (!$patchPayment['result']) {
                $this->logger->addLog('Payment cannot be patched: ' . $patchPayment['message'], 'error');
                $this->exitProcess($patchPayment['message'], 500);
            }

            $this->logger->addLog('Payment patched.', 'debug');

            if (!$this->orderClass->addPayplugOrderPayment((int) $this->order->id, $this->payment->id)) {
                $this->logger->addLog(
                    'IPN Failed: unable to create order payment.',
                    'error'
                );
                $this->exitProcess('IPN Failed: unable to create order payment.', 500);
            } else {
                $this->logger->addLog('Order payment created.');
            }
        }

        // Add prestashop OrderPayment
        $order_payments = $this->order->getOrderPayments();
        if (!$order_payments) {
            $this->logger->addLog('Add new orderPayment for deferred - ' . count($order_payments), 'debug');
            $this->order->addOrderPayment($amount, null, $transaction_id);
        }

        // Check number of order using this cart
        $this->logger->addLog('Checking number of order passed with this id_cart');

        $res_nb_orders = $this->query
            ->select()
            ->fields('*')
            ->from($this->constantAdapter->get('_DB_PREFIX_') . 'orders')
            ->where('id_cart = ' . (int) $this->cart->id)
            ->build()
        ;
        if (!$res_nb_orders) {
            $this->logger->addLog(
                'No order can be found using id_cart ' . (int) $this->cart->id,
                'error'
            );
            $this->exitProcess('No order can be found using id_cart: ' . (int) $this->cart->id, 500);
        } elseif (count($res_nb_orders) > 1) {
            $this->logger->addLog(
                'There is more than one order using id_cart ' . (int) $this->cart->id,
                'error'
            );
            foreach ($res_nb_orders as $o) {
                $this->logger->addLog('Order ID : ' . $o['id_order'], 'debug');
            }
            $this->exitProcess('There is more than one order using id_cart: ' . (int) $this->cart->id, 500);
        } else {
            $this->logger->addLog('OK');
            $id_order = (int) $res_nb_orders[0]['id_order'];
        }

        // Check number of orderPayment using this cart
        $this->logger->addLog('Checking number of transaction validated for this order');
        $payments = $this->order->getOrderPayments();
        if (!$payments) {
            $this->logger->addLog(
                'No transaction can be found using id_order ' . (int) $id_order,
                'error'
            );
            $this->exitProcess('No transaction can be found using id_order: ' . (int) $id_order, 500);
        } elseif (count($payments) > 1) {
            $this->logger->addLog(
                'There is more than one transaction using id_order ' . (int) $id_order,
                'error'
            );
            $this->exitProcess('There is more than one transaction using id_order: ' . (int) $id_order, 500);
        } else {
            $this->logger->addLog('OK');
        }

        $this->logger->addLog('Order created.');
        $this->exitProcess('Order created.');
    }

    /**
     * @description Treat the notification has an installment
     */
    private function processInstallment()
    {
        $this->logger->addLog('Notification: processInstallment');
        $this->logger->addLog('Installment ID: ' . $this->resource->id);
        $this->logger->addLog('Active : ' . (int) $this->resource->is_active);
        $this->exitProcess('Installment notification');
    }

    /**
     * @description Treat the notification has a payment
     */
    private function processPayment()
    {
        $this->logger->addLog('Notification: processPayment');

        // Set the payment
        $this->setPayment();
        if (!$this->payment) {
            $this->logger->addLog('Can\'t retrieve payment with the TEST and LIVE API Key');
            $this->exitProcess('Can\'t retrieve payment with the TEST and LIVE API Key', 500);
        }

        // Set the order state
        $this->setOrderStates();

        // Set the order state
        $this->setResourceProps(); // hydrate $resource_props

        // Check the payment ressource
        $this->checkIsValidPaymentResource();

        // Save card
        $this->processSaveCard();

        // Set cart from resource
        $this->setCartFromResource();

        // Set Context
        $this->setContext();

        // Set Lock
        $this->setLock();

        // Dipatch to the create|update process
        $this->dispatchPayment();
    }

    /**
     * @description Treat the notification has a refund
     */
    private function processRefund()
    {
        $this->logger->addLog('Notification: processRefund');
        $this->logger->addLog('Refund ID : ' . $this->resource->id);

        $payment = $this->apiClass->retrievePayment($this->resource->payment_id);
        if (!$payment['result']) {
            $this->logger->addLog('Payment cannot be retrieved: ' . $payment['message'], 'error');
            $this->exitProcess($payment['message'], 500);
        }
        $this->payment = $payment['resource'];
        $this->setOrderStates();

        if ($this->payment->installment_plan_id) {
            $installment = $this->apiClass->retrieveInstallment($this->payment->installment_plan_id);
            if (!$installment['result']) {
                $this->logger->addLog('Installment cannot be retrieved: ' . $installment['message'], 'error');
                $this->exitProcess($installment['message'], 500);
            }

            $installment = $installment['resource'];
            $meta = $installment->metadata;
        } else {
            $meta = $this->payment->metadata;
        }

        $is_totaly_refunded = $this->payment->is_refunded;
        if ($is_totaly_refunded) {
            $this->logger->addLog('TOTAL REFUND MODE');
            $cart_id = '';

            if (isset($meta['Cart'])) {
                $cart_id = (int) $meta['Cart'];
                $this->logger->addLog('Cart ID : ' . $cart_id);
            } elseif (isset($meta['ID Cart'])) {
                $cart_id = (int) $meta['ID Cart'];
                $this->logger->addLog('Cart ID : ' . $cart_id);
            } else {
                $this->logger->addLog(
                    'Can\'t be refunded, because there is an error during retrieving Cart ID.',
                    'error'
                );
                $this->exitProcess('Can\'t be refunded, because there is an error during retrieving Cart ID.', 500);
            }

            $this->cart = $this->cartAdapter->get((int) $cart_id);

            if (!$this->validateAdapter->validate('isLoadedObject', $this->cart)) {
                $this->logger->addLog('Cart cannot be loaded.', 'error');
                $this->logger->addLog('$cart_id : ' . $cart_id, 'debug');
                $this->exitProcess('Cart cannot be loaded.', 500);
            }

            $id_order = (int) $this->orderAdapter->getOrderByCartId((int) $cart_id);
            $this->order = $this->orderAdapter->get((int) $id_order);
            $this->logger->addLog('Order ID : ' . $this->order->id);
            if (!$this->validateAdapter->validate('isLoadedObject', $this->order)) {
                $this->logger->addLog('Order cannot be loaded.', 'error');
                $this->exitProcess('Order cannot be loaded.', 500);
            }

            // Set lock Lock the process with id_cart from order object
            do {
                $cart_lock = $this->payplugLock->createLockG2((int) $this->cart->id, 'ipn');
                if (!$cart_lock) {
                    $checkReturn = $this->payplugLock->check((int) $this->cart->id);
                    if ($checkReturn == 'stop ipn') {
                        $this->exitProcess('Lock cannot be created.', 500);
                    }
                } else {
                    $this->logger->addLog('Lock created');
                    $this->lock_key = $this->cart->id;
                }
            } while (!$cart_lock);

            $new_order_state = $this->order_states['refund'];
            $current_state = $this->orderClass->getCurrentOrderState($this->order->id);
            $this->logger->addLog('Current state: ' . $current_state);

            if ($current_state != $new_order_state) {
                $this->updateOrderState($new_order_state);
            } else {
                $this->logger->addLog('Order status is already \'refunded\'');
                $this->exitProcess('Order status is already \'refunded\'');
            }
        } else {
            $this->logger->addLog('PARTIAL REFUND');
            $this->exitProcess('PARTIAL REFUND');
        }
    }

    /**
     * @description Process the card saving for one click use
     */
    private function processSaveCard()
    {
        $this->logger->addLog('Notification: processSaveCard');
        if ($this->canSaveCard()) {
            $this->logger->addLog('[Save Card] Saving card...');
            $res_payplug_card = $this->dependencies->getPlugin()->getCard()->saveCard($this->payment);

            if (!$res_payplug_card) {
                $this->logger->addLog('[Save Card] Card cannot be saved.', 'error');

                if (!isset($this->payment->save_card)) {
                    $this->logger->addLog('[Save Card] $payment->save_card is not set', 'debug');
                }

                if (isset($this->payment->save_card) && $this->payment->save_card !== 1) {
                    $this->logger->addLog('[Save Card] $this->payment->save_card is set but not equal to 1', 'debug');
                }

                if (!isset($this->payment->card->id)) {
                    $this->logger->addLog('[Save Card] $this->payment->card->id is not set', 'debug');
                }

                if (isset($this->payment->card->id) && $this->payment->card->id == '') {
                    $this->logger->addLog('[Save Card] $this->payment->card->id is set but empty', 'debug');
                }

                if (!isset($this->payment->hosted_payment)) {
                    $this->logger->addLog('[Save Card] $this->payment->hosted_payment is not set', 'debug');
                }

                if ((isset($this->payment->hosted_payment)) && $this->payment->hosted_payment == '') {
                    $this->logger->addLog('[Save Card] $this->payment->hosted_payment is set but empty', 'debug');
                }
            } else {
                $this->logger->addLog('[Save Card] Card saved', 'debug');
            }
        }
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Cancelled
     */
    private function processTypeCancelled()
    {
        $this->logger->addLog('Notification: processTypeCancelled');
        $this->exitProcess('Order is already set as cancelled.');
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Error
     */
    private function processTypeError()
    {
        $this->logger->addLog('Notification: processTypeError');
        $this->exitProcess('Order is set as error.');
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Expired
     */
    private function processTypeExpired()
    {
        $this->logger->addLog('Notification: processTypeExpired');
        $this->exitProcess('Order is already set as expired.');
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Nothing
     */
    private function processTypeNothing()
    {
        $this->logger->addLog('Notification: processTypeNothing');
        $this->exitProcess('Order is configure to not be treat');
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Paid
     */
    private function processTypePaid()
    {
        $this->logger->addLog('Notification: processTypePaid');
        $this->exitProcess('Order is already paid.');
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Pending
     */
    private function processTypePending()
    {
        $this->logger->addLog('Notification: processTypePending');

        // Get the new order state
        $new_order_state = $this->getNewOrderState();
        $new_order_state_id = $this->order_states[$new_order_state['status']];
        if (!$new_order_state['valid']) {
            $this->updateOrderState($new_order_state_id);
        }

        // Check if payment is paid
        if (!$this->payment->is_paid) {
            $this->exitProcess('The payment is not paid yet.');
        }

        // Check if order amount is valid
        $is_valid_amount = (bool) $this->payment->is_paid;
        if ($this->is_installment) {
            $is_valid_amount = $this->amountCurrencyClass->checkAmountPaidIsCorrect(
                $this->payment->amount / 100,
                $this->order
            );
        }
        $this->logger->addLog('Is valid amount: ' . ($is_valid_amount ? 'ok' : 'ko'));

        if (!$is_valid_amount) {
            $message = $this->messageAdapter->get();
            $msg = $this->dependencies->l('The amount collected by PayPlug is not the same', 'payplugnotifications');
            $msg .= $this->dependencies->l(' as the total value of the order', 'payplugnotifications');
            $message->message = $msg;
            $message->id_order = $this->order->id;
            $message->id_cart = $this->order->id_cart;
            $message->private = true;

            try {
                $message_saved = $message->save();
                $this->logger->addLog('Message saved: ' . ($message_saved ? 'ok' : 'ko'));
            } catch (Exception $exception) {
                $this->logger->addLog('The message cannot be saved: ' . $exception->getMessage(), 'error');
                $this->exitProcess($exception->getMessage(), 500);
            }

            $new_order_state_id = $this->order_states['error'];
            $this->updateOrderState($new_order_state_id);
        }

        // Get associated transaction
        $payplug_order_payments = $this->orderClass->getPayplugOrderPayments((int) $this->order->id);
        $order_payments = $this->order->getOrderPayments();

        // Check if the payment is related to the order with payplug order payment
        $related = false;
        if ($payplug_order_payments) {
            foreach ($payplug_order_payments as $payment) {
                if ($payment['id_payment'] == $this->payment->id) {
                    $related = true;
                }
            }
        } elseif ($order_payments) {
            foreach ($order_payments as $payment) {
                if ($payment->transaction_id == $this->payment->id) {
                    $related = true;
                }
            }
        }
        $this->logger->addLog('Is related: ' . ($related ? 'ok' : 'ko'));
        if (!$related) {
            $this->exitProcess('The payment is not related to this order.');
        }

        // Add prestashop OrderPayment if need
        $this->logger->addLog('Has order payments ' . ($order_payments ? 'ok' : 'ko'));
        if (!$order_payments) {
            $this->logger->addLog('Create new order payment');
            $this->order->addOrderPayment($this->payment->amount / 100, null, $this->payment->id);
        }

        // Then update the order state
        $this->updateOrderState($new_order_state_id);
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Refund
     */
    private function processTypeRefund()
    {
        $this->logger->addLog('Notification: processTypeRefund');
        $this->exitProcess('Order is already set as refunded.');
    }

    /**
     * @description Process Payment Ressource when related Order is with a status Nothing
     */
    private function processTypeUndefined()
    {
        $this->logger->addLog('Notification: processTypeUndefined');
        $id_order_state = $this->order->current_state;

        // check current order state type to create if it's empty
        $type = $this->dependencies->getPlugin()->getOrderState()->getType((int) $id_order_state);
        if ($type != $this->type) {
            $this->plugin->getOrderState()->setType((int) $id_order_state, $this->type);
        }

        $this->exitProcess('The order state is not defined');
    }

    /**
     * @description Update order from the notification
     *
     * @param $id_order Identificer of the order to update
     */
    private function processUpdateOrder()
    {
        $this->logger->addLog('Notification: processUpdateOrder');

        $type = $this->dependencies->getPlugin()->getOrderState()->getType((int) $this->order->current_state);

        $this->logger->addLog('Current order state: ' . $this->order->current_state);
        $this->logger->addLog('Type: ' . $type);

        $this->type = $type ?: 'undefined';
        $method = 'processType' . $this->toolsAdapter->tool('ucfirst', $this->type);
        $this->{$method}();
    }

    /**
     * @description Set $this->>cart in the DB from the resource id
     */
    private function setCartFromResource()
    {
        $this->logger->addLog('Notification: setCartFromResource');
        if ($this->is_installment) {
            $id_cart = $this->query
                ->select()
                ->fields('id_cart')
                ->from($this->constantAdapter->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
                ->where('`id_payment` = "' . pSQL($this->payment->installment_plan_id) . '"')
                ->build('unique_value')
            ;
            if (!$id_cart) {
                if (isset($this->resource->failure->code) && $this->resource->failure->code == 'timeout') {
                    $this->logger->addLog('Payment timeout for paymentID: ' . $this->payment->installment_plan_id);
                    $this->exitProcess('Payment timeout for paymentID: ' . $this->payment->installment_plan_id, 200);
                }

                $error_msg = 'The cart cannot be found with payment ID: ' . $this->payment->installment_plan_id;
                $this->exitProcess($error_msg, 500);
            }
        } else {
            $id_cart = $this->query
                ->select()
                ->fields('id_cart')
                ->from($this->constantAdapter->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
                ->where('`id_payment` = "' . pSQL($this->resource->id) . '"')
                ->build('unique_value')
            ;

            if (!$id_cart) {
                if (isset($this->resource->failure->code) && $this->resource->failure->code == 'timeout') {
                    $this->logger->addLog('Payment timeout for payment ID: ' . $this->resource->id);
                    $this->exitProcess('Payment timeout for payment ID: ' . $this->resource->id, 200);
                }

                $error_msg = 'The cart cannot be found with payment ID: ' . $this->resource->id;
                $this->exitProcess($error_msg, ($this->is_oney ? 242 : 500));
            }
        }

        $this->cart = $this->cartAdapter->get((int) $id_cart);
        if (!$this->validateAdapter->validate('isLoadedObject', $this->cart)) {
            $this->logger->addLog('The cart cannot be loaded with id ' . $id_cart, 'error');
            $this->exitProcess('The cart cannot be loaded.', 500);
        }
    }

    /**
     * @description Set adapter use for the notification
     */
    private function setAdapters()
    {
        $this->addressAdapter = $this->dependencies->getPlugin()->getAddress();
        $this->cartAdapter = $this->dependencies->getPlugin()->getCart();
        $this->configAdapter = $this->dependencies->getPlugin()->getConfiguration();
        $this->constantAdapter = $this->dependencies->getPlugin()->getConstant();
        $this->contextAdapter = $this->dependencies->getPlugin()->getContext();
        $this->countryAdapter = $this->dependencies->getPlugin()->getCountry();
        $this->currencyAdapter = $this->dependencies->getPlugin()->getCurrency();
        $this->customerAdapter = $this->dependencies->getPlugin()->getCustomer();
        $this->languageAdapter = $this->dependencies->getPlugin()->getLanguage();
        $this->messageAdapter = $this->dependencies->getPlugin()->getMessage();
        $this->orderAdapter = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistoryAdapter = $this->dependencies->getPlugin()->getOrderHistory();
        $this->shopAdapter = $this->dependencies->getPlugin()->getShop();
        $this->toolsAdapter = $this->dependencies->getPlugin()->getTools();
        $this->validateAdapter = $this->dependencies->getPlugin()->getValidate();
    }

    /**
     * @description Set the notification's global configuration
     *
     * @throws Exception
     */
    private function setConfig()
    {
        $this->key = microtime(true) * 10000;
        $this->flag = false;
        $this->except = null;
        $this->resp = [];
        $this->dependencies = new DependenciesClass();
        $this->setAdapters();

        $this->apiClass = $this->dependencies->apiClass;
        $this->orderClass = $this->dependencies->orderClass;
        $this->paymentClass = $this->dependencies->paymentClass;
        $this->installmentClass = $this->dependencies->installmentClass;
        $this->amountCurrencyClass = $this->dependencies->amountCurrencyClass;
        $this->payplugLock = $this->dependencies->payplugLock;

        $this->module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $this->sandbox = $this->configAdapter->get($this->dependencies->getConfigurationKey('sandboxMode'));
        $this->query = $this->dependencies->getPlugin()->getQuery();

        $this->setLogger();
        $this->getResource();
    }

    /**
     * @description Set the context of the order
     *
     * @param $id_cart
     */
    private function setContext()
    {
        $this->logger->addLog('Notification: setContext');
        if (!isset($this->context)) {
            $this->context = $this->contextAdapter->get();
        }

        $this->context->cart = $this->cart;
        $address = $this->addressAdapter->get((int) $this->cart->id_address_invoice);
        $this->context->country = $this->countryAdapter->get((int) $address->id_country);
        $this->context->customer = $this->customerAdapter->get((int) $this->cart->id_customer);
        $this->context->language = $this->languageAdapter->get((int) $this->cart->id_lang);
        $this->context->currency = $this->currencyAdapter->get((int) $this->cart->id_currency);
        if (isset($this->cart->id_shop)) {
            $this->context->shop = $this->shopAdapter->get((int) $this->cart->id_shop);
        }

        $this->logger->addLog('Context setted');
    }

    private function setLock()
    {
        $this->logger->addLog('Notification: setLock');
        do {
            $cart_lock = $this->payplugLock->createLockG2($this->cart->id, 'ipn');
            if (!$cart_lock) {
                $checkReturn = $this->payplugLock->check($this->cart->id);
                if ($checkReturn == 'stop ipn') {
                    $this->exitProcess('Lock cannot be created.', 500);
                }
            } else {
                $this->lock_key = $this->cart->id;
            }
        } while (!$cart_lock);
        $this->logger->addLog('Lock created');
    }

    /**
     * @description Set the order state from configuration
     */
    private function setOrderStates()
    {
        $this->logger->addLog('Notification: setOrderStates');
        $state_addons = ($this->payment->is_live ? '' : '_TEST');
        $this->order_states = [
            'pending' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PENDING') . $state_addons
            ),
            'paid' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID') . $state_addons
            ),
            'error' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ERROR') . $state_addons
            ),
            'auth' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_AUTH') . $state_addons
            ),
            'expired' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_EXP') . $state_addons
            ),
            'oney' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ONEY_PG') . $state_addons
            ),
            'cancelled' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_CANCELED')
            ),
            'refund' => $this->configAdapter->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_REFUND') . $state_addons
            ),
        ];
    }

    private function setPayment()
    {
        $this->logger->addLog('Notification: setPayment');
        $payment = $this->apiClass->retrievePayment($this->resource->id);
        if (!$payment['result']) {
            if ($this->sandbox) {
                $this->apiClass->initializeApi(false);
                $payment = $this->apiClass->retrievePayment($this->resource->id);
            } else {
                $this->apiClass->initializeApi(true);
                $payment = $this->apiClass->retrievePayment($this->resource->id);
            }
        }

        if (!$payment['result']) {
            $this->logger->addLog('Can\'t retrieve payment with pay id: ' . $this->resource->id, 'debug');
            $this->apiClass->initializeApi((bool) $this->sandbox);
            $this->payment = null;
        } else {
            $this->payment = $payment['resource'];
        }
    }

    private function setResourceProps()
    {
        $this->logger->addLog('Notification: setResourceProps');
        // Define if payment is oney resource
        $oney_payment_methods = [
            'oney_x3_with_fees',
            'oney_x4_with_fees',
            'oney_x3_without_fees',
            'oney_x4_without_fees',
        ];
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_oney = in_array($this->payment->payment_method['type'], $oney_payment_methods);
        }
        $this->logger->addLog('Notification: is_oney: ' . ($this->is_oney ? 'ok' : 'nok'));

        // Define if payment is bancontact resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_bancontact = $this->payment->payment_method['type'] == 'bancontact';
        }
        $this->logger->addLog('Notification: is_bancontact: ' . ($this->is_bancontact ? 'ok' : 'nok'));

        // Define if payment is bancontact resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_bancontact = $this->payment->payment_method['type'] == 'bancontact';
        }
        $this->logger->addLog('Notification: is_bancontact: ' . ($this->is_bancontact ? 'ok' : 'nok'));

        // Define if payment is bancontact resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_bancontact = $this->payment->payment_method['type'] == 'bancontact';
        }
        $this->logger->addLog('Notification: is_bancontact: ' . ($this->is_bancontact ? 'ok' : 'nok'));

        // Define if payment is deferred resource
        if (isset($this->payment->authorization) && !$this->is_oney) {
            $this->is_deferred = isset($this->payment->authorization->authorized_at)
                && $this->payment->authorization->authorized_at;
        }
        $this->logger->addLog('Notification: is_deferred: ' . ($this->is_deferred ? 'ok' : 'nok'));

        // Define if payment is from installment
        $this->is_installment = $this->payment->installment_plan_id ?: false;
        $this->logger->addLog('Notification: is_installment: ' . ($this->is_installment ? 'ok' : 'nok'));

        // Define if payment is applepay resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_applepay = $this->payment->payment_method['type'] == 'apple_pay';
        }
        $this->logger->addLog('Notification: is_applepay: ' . ($this->is_applepay ? 'ok' : 'nok'));

        // Define if payment is amex resource
        if (isset($this->payment->payment_method, $this->payment->payment_method['type'])) {
            $this->is_amex = $this->payment->payment_method['type'] == 'american_express';
        }
        $this->logger->addLog('Notification: is_amex ' . ($this->is_amex ? 'ok' : 'nok'));
    }

    /**
     * @param int $new_order_state
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    private function updateOrderState($new_order_state = false)
    {
        if (!is_int($new_order_state) && !$new_order_state) {
            $this->exitProcess('Try to update order without valid order state id', 500);
        } elseif ($new_order_state == $this->order->current_state) {
            $this->exitProcess('The order is already with the status id: ' . $new_order_state, 200);
        }

        try {
            $order_history = $this->orderHistoryAdapter->get();
            $order_history->id_order = (int) $this->order->id;
            $order_history->changeIdOrderState((int) $new_order_state, $this->order->id, true);
            $order_history->save();
        } catch (Exception $exception) {
            $this->logger->addLog(
                'Order history cannot be saved: ' . $exception->getMessage(),
                'error'
            );
            $this->logger->addLog(
                'Please check if order state ' . (int) $new_order_state . ' exists.',
                'error'
            );
            $this->exitProcess($exception->getMessage(), 500);
        }

        $this->order = $this->orderAdapter->get((int) $this->order->id);
        $this->order->current_state = $order_history->id_order_state;

        try {
            $this->order->update();
        } catch (Exception $exception) {
            $this->logger->addLog('Order cannot be updated: ' . $exception->getMessage(), 'error');
            $this->exitProcess($exception->getMessage(), 500);
        }
        $this->exitProcess('Order updated with state id: ' . $new_order_state);
    }
}
