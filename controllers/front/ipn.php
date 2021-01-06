<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

//Inclusions
require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_ . '../init.php');
require_once(_PS_MODULE_DIR_ . 'payplug/payplug.php');
require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayplugLock.php');
require_once(_PS_MODULE_DIR_ . 'payplug/lib/init.php');

/**
 * @description
 * Treat notification received
 */
class PayplugIPNModuleFrontController extends ModuleFrontController
{
    /**
     * @var object $resource
     */
    private $resource;

    /**
     * @var object $payplug
     */
    private $payplug;

    /**
     * @var object $debug
     */
    private $debug;

    /**
     * @var object $logger
     */
    private $logger;

    /**
     * @var bool $sandbox
     */
    private $sandbox;

    /**
     * @var string $api_key
     */
    private $api_key;

    /**
     * @var object $cartObject
     */
    private $cartObject;

    /**
     * @var int $lock_key
     */
    private $lock_key;

    /**
     * @var bool $is_oney
     */
    private $is_oney;

    /**
     * @var bool $is_installment
     */
    private $is_installment;

    /**
     * @var bool $is_deferred
     */
    private $is_deferred;

    /**
     * @var object $payment
     */
    private $payment;

    /**
     * @var array $order_states
     */
    private $order_states;

    /**
     * @description
     * Process the notification
     * @throws Exception
     */
    public function postProcess()
    {
        //Settings
        try {
            $this->setConfig();
            $this->treat();
        } catch (\Payplug\Exception\UnknownAPIResourceException $exception) {
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }
    }

    /**
     * @description Set the notification's global configuration
     *
     * @throws Exception
     */
    private function setConfig()
    {
        $this->payplug = new Payplug();
        $this->debug = Configuration::get('PAYPLUG_DEBUG_MODE');
        $this->sandbox = Configuration::get('PAYPLUG_SANDBOX_MODE');

        $this->setLogger();
        $this->getResource();
    }

    /**
     * @description Set the order state from configuration
     */
    private function setOrderStates()
    {
        $state_addons = ($this->payment->is_live ? '' : '_TEST');
        $this->order_states = [
            'pending' => Configuration::get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons),
            'paid' => Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons),
            'error' => Configuration::get('PAYPLUG_ORDER_STATE_ERROR' . $state_addons),
            'inst' => Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons),
            'auth' => Configuration::get('PAYPLUG_ORDER_STATE_AUTH' . $state_addons),
            'exp' => Configuration::get('PAYPLUG_ORDER_STATE_EXP' . $state_addons),
            'oney' => Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons),
            'cancelled' => Configuration::get('PS_OS_CANCELED'),
            'refund' => Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons)
        ];
    }

    /**
     * @description Get the resource from the notification
     */
    private function getResource()
    {
        $body = Tools::file_get_contents('php://input');

        try {
            $this->resource = \Payplug\Notification::treat($body);
        } catch (\Payplug\Exception\UnknownAPIResourceException $exception) {
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }
    }

    /**
     * @description Set the logger
     */
    public function setLogger()
    {
        $this->logger = new PayPlugLogger('notification');
        $this->logger->addLog('New notification');
    }

    /**
     * @description Entry point to treat the notification
     */
    public function treat()
    {
        //Notification identification
        $this->logger->addLog('Notification treatment and authenticity verification:');

        if ($this->resource instanceof \Payplug\Resource\Payment) {
            $this->treatPayment();
        } elseif ($this->resource instanceof \Payplug\Resource\Refund) {
            $this->treatRefund();
        } elseif ($this->resource instanceof \Payplug\Resource\InstallmentPlan) {
            $this->treatInstallment();
        }
    }

    /**
     * @description Treat the notification has a payment
     */
    private function treatPayment()
    {
        if ($this->resource->installment_plan_id) {
            $this->logger->addLog('Installment ID: ' . $this->resource->installment_plan_id);
            $this->is_installment = true;
        }

        $this->logger->addLog('PAYMENT MODE');
        $this->logger->addLog('Payment ID: ' . $this->resource->id);
        $this->logger->addLog('Paid (Resource): ' . (int)$this->resource->is_paid);

        if (!$this->payment = $this->payplug->retrievePayment($this->resource->id)) {
            $this->logger->addLog('Can\'t retrieve payment with this API Key.', 'debug');
            if ($this->sandbox) {
                $this->logger->addLog('This was test mode.', 'debug');
                $this->logger->addLog('Trying live mode.', 'debug');
                $this->payplug->initializeApi(false);
                if (!$this->payment = $this->payplug->retrievePayment($this->resource->id)) {
                    $this->logger->addLog('Can\'t retrieve payment with LIVE API Key.', 'debug');
                    $this->payplug->initializeApi(true);
                    $this->payment = null;
                }
            } else {
                $this->logger->addLog('This was live mode.', 'debug');
                $this->logger->addLog('Trying test mode.', 'debug');
                $this->payplug->initializeApi(true);
                if (!$this->payment = $this->payplug->retrievePayment($this->resource->id)) {
                    $this->logger->addLog('Can\'t retrieve payment with the TEST API Key.', 'debug');
                    $this->payplug->initializeApi(false);
                    $this->payment = null;
                }
            }
        }

        if (!$this->payment) {
            $this->exitProcess('Can\'t retrieve payment with the TEST and LIVE API Key', 500);
        }

        $this->setOrderStates();

        $this->logger->addLog('Paid (Payment): ' . (int)$this->payment->is_paid);

        $this->is_oney = false;
        if (isset($this->payment->payment_method) && isset($this->payment->payment_method['type'])) {
            switch ($this->payment->payment_method['type']) {
                case 'oney_x3_with_fees':
                case 'oney_x4_with_fees':
                    $this->is_oney = true;
                    break;
                default:
                    $this->is_oney = false;
            }
        }

        if ($this->payment->authorization !== null && !$this->is_oney) {
            $this->is_deferred = true;
        }

        if (!$this->payment->is_paid && !$this->is_deferred) {
            $this->logger->addLog('The transaction is not paid yet.');
            $this->logger->addLog('No action will be done.');
            $this->exitProcess('The transaction is not paid.');
        }

        if ($this->is_deferred) {
            $this->logger->addLog('The transaction is authorized but not captured yet.');
        } else {
            $this->logger->addLog('The transaction is paid.');
        }
        $this->logger->addLog('Payment details:');

        if ($this->is_installment) {
            $sql = 'SELECT `id_cart` 
                    FROM `' . _DB_PREFIX_ . 'payplug_installment_cart` 
                    WHERE `id_installment` = "' . $this->resource->installment_plan_id . '"';
            $id_cart = Db::getInstance()->getValue($sql);

            if (!$id_cart) {
                $error_msg = 'The cart cannot be found with payment ID: ' . $this->resource->installment_plan_id;
                $this->logger->addLog($error_msg, 'error');
                $this->exitProcess($error_msg, 500);
            }
        } else {
            $sql = 'SELECT `id_cart` 
                    FROM `' . _DB_PREFIX_ . 'payplug_payment_cart` 
                    WHERE `id_payment` = "' . $this->resource->id . '"';
            $id_cart = Db::getInstance()->getValue($sql);

            if (!$id_cart) {
                $error_msg = 'The cart cannot be found with payment ID: ' . $this->resource->id;
                $this->logger->addLog('The cart cannot be found with payment ID: ' . $this->resource->id, 'error');
                $this->logger->addLog($error_msg, 'error');
                // HOTFIX: MR331 We use custom http code to precisely log this case of desync
                // between real payment notification and wrong ones.
                $response_code = ($this->is_oney ? 242 : 500);
                $this->exitProcess($error_msg, $response_code);
            }
        }

        $this->logger->addLog('Cart ID: ' . (int)$id_cart, 'debug');
        $this->logger->addLog('Is Live: ' . (int)$this->payment->is_live, 'debug');
        $this->logger->addLog('Amount: ' . (int)$this->payment->amount, 'debug');

        //Payment treatment

        // Get the cart then check if valid
        try {
            $this->cartObject = new Cart($id_cart);
        } catch (Exception $exception) {
            $this->logger->addLog('The cart cannot be loaded: ' . $exception->getMessage(), 'error');
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }
        if (!Validate::isLoadedObject($this->cartObject)) {
            $this->logger->addLog('The cart cannot be loaded.', 'error');
            $this->exitProcess('The cart cannot be loaded.', 500);
        }

        $this->setContextFromCartID($this->cartObject->id);

        // Set lock in db then set $this->lock_key
        $cart_lock = false;
        do {
            $cart_lock = PayplugLock::createLockG2($this->cartObject->id, 'ipn');
            if (!$cart_lock) {
                PayplugLock::check($this->cartObject->id);
            } else {
                $this->logger->addLog('Lock created');
                $this->lock_key = $this->cartObject->id;
            }
        } while (!$cart_lock);

        try {
            $address = new Address((int)$this->cartObject->id_address_invoice);
        } catch (Exception $exception) {
            $this->logger->addLog(
                'The address cannot be loaded: ' . $exception->getMessage(),
                'error'
            );
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }
        if (!Validate::isLoadedObject($address)) {
            $this->logger->addLog('The address cannot be loaded.', 'error');
            $this->exitProcess('The address cannot be loaded.', 500);
        }

        $id_order = Order::getOrderByCartId($this->cartObject->id);

        if ($id_order) {
            $this->updateOrder($id_order);
        } else {
            $this->createOrder();
        }
    }

    /**
     * @description Update order from the notification
     *
     * @param $id_order Identificer of the order to update
     */
    private function updateOrder($id_order)
    {
        $this->logger->addLog('UPDATE MODE');

        // Get the order
        try {
            $order = new Order((int)$id_order);
        } catch (Exception $exception) {
            $this->logger->addLog('The order cannot be loaded: ' . $exception->getMessage(), 'error');
            $this->exitProcess('The order cannot be loaded: ' . $exception->getMessage(), 500);
        }
        if (!Validate::isLoadedObject($order)) {
            $this->logger->addLog('Order cannot be loaded.', 'error');
            $this->exitProcess('Order cannot be loaded.', 500);
        }

        try {
            $current_state = (int)$order->getCurrentState();
        } catch (Exception $exception) {
            $this->logger->addLog(
                'The current state cannot be loaded: ' . $exception->getMessage(),
                'error'
            );
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }

        // if it's a refused oney payment, we switch to cancelled status
        if ($this->is_oney
            && isset($this->payment->failure)
            && $this->payment->failure !== null
            && !in_array($current_state, array($this->order_states['cancelled'], $this->order_states['paid']))
        ) {
            $this->logger->addLog('The payment is refused by Oney.');
            $new_order_state = $this->order_states['cancelled'];

            $order_history = new OrderHistory();
            $order_history->id_order = (int)$id_order;

            try {
                $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                $order_history->save();
            } catch (Exception $exception) {
                $this->logger->addLog(
                    'Order history cannot be saved: ' . $exception->getMessage(),
                    'error'
                );
                $this->logger->addLog(
                    'Please check if order state ' . (int)$new_order_state . ' exists.',
                    'error'
                );
                $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
            }

            $order->current_state = $order_history->id_order_state;
            try {
                $order->update();
            } catch (Exception $exception) {
                $this->logger->addLog(
                    'Order cannot be updated: ' . $exception->getMessage(),
                    'error'
                );
                $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
            }
            $this->logger->addLog('Order updated.');
            $this->exitProcess('Order updated.');
        } elseif ($this->is_deferred
            && $current_state == $this->order_states['auth']
            && ($this->payment->authorization->expires_at - time()) <= 0
        ) { // if payment is deferred and expired
            $this->logger->addLog('The payment authorization has expired.');
            $this->logger->addLog('Payment amount: ' . $this->payment->amount, 'debug');
            $this->logger->addLog('Order new status will be \'Authorization expired\'.');
            $new_order_state = $this->order_states['exp'];

            $order_history = new OrderHistory();
            $order_history->id_order = (int)$id_order;

            try {
                $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                $order_history->save();
            } catch (Exception $exception) {
                $this->logger->addLog(
                    'Order history cannot be saved: ' . $exception->getMessage(),
                    'error'
                );
                $this->logger->addLog(
                    'Please check if order state ' . (int)$new_order_state . ' exists.',
                    'error'
                );
                $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
            }

            $order->current_state = $order_history->id_order_state;
            try {
                $order->update();
            } catch (Exception $exception) {
                $this->logger->addLog(
                    'Order cannot be updated: ' . $exception->getMessage(),
                    'error'
                );
                $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
            }
            $this->logger->addLog('Order updated.');
            $this->exitProcess('Order updated.');
        } elseif (in_array($current_state, array(
                $this->order_states['pending'],
                $this->order_states['auth'],
                $this->order_states['oney']
            )) || !$order->valid) { // if payment is pending or awaiting a capture
            $this->logger->addLog('Order is currently pending.');
            $this->logger->addLog('Payment amount: ' . $this->payment->amount, 'debug');

            if ($this->payment->installment_plan_id !== null) {
                $is_amount_correct = (bool)$this->payment->is_paid;
            } else {
                $is_amount_correct = (bool)PayPlug::checkAmountPaidIsCorrect(
                    $this->payment->amount / 100,
                    $order
                );
            }

            $this->logger->addLog('Order ID: ' . (int)$order->id, 'debug');
            // We have to check if the payment to update is the one linked to the order
            // because it's possible to attempt to pay with a method and cancel before payment
            // then make another attempt with another attempt but still receive previous IPN

            // Check if the payment is related to the order with payplug order payment
            // Use prestashop order payment for pre-updated module order
            $payplug_order_payments = $this->payplug->getPayplugOrderPayments((int)$order->id);
            $order_payments = $order->getOrderPayments();
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
            if (!$related) {
                $this->exitProcess('The payment is not related to this order.');
            }

            // Check if payment is paid
            if (!$this->payment->is_paid) {
                if (isset($this->payment->failure) && $this->payment->failure !== null) {
                    //todo : Gerer le cas oney refusé
                    $this->logger->addLog('The payment has failed.');
                    $this->logger->addLog('Order new status will be \'cancel\'.');
                    $new_order_state = $this->order_states['cancelled'];
                    $order_history = new OrderHistory();
                    $order_history->id_order = (int)$id_order;
                    try {
                        $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                        $order_history->save();
                    } catch (Exception $exception) {
                        $this->logger->addLog(
                            'Order history cannot be saved: ' . $exception->getMessage(),
                            'error'
                        );
                        $this->logger->addLog(
                            'Please check if order state ' . (int)$new_order_state . ' exists.',
                            'error'
                        );
                        $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
                    }
                    $this->exitProcess('The payment has failed and order has been cancelled.');
                }
                $this->logger->addLog('The payment is not paid yet.', 'error');
                $this->exitProcess('The payment is not paid yet.');
            }

            // if amount not correct, add order message
            if ($is_amount_correct) {
                $this->logger->addLog('Order new status will be \'paid\'.');
                $new_order_state = $this->order_states['paid'];
            } else {
                $this->logger->addLog('Payment amount is not correct.', 'error');
                $new_order_state = $this->order_states['error'];
                $this->logger->addLog('Order new status will be \'error\'.', 'error');
                $message = new Message();
                $message->message =
                    $this->payplug->l('The amount collected by PayPlug is not the same')
                    . $this->payplug->l(' as the total value of the order');
                $message->id_order = $order->id;
                $message->id_cart = $order->id_cart;
                $message->private = true;
                try {
                    $message->save();
                } catch (Exception $exception) {
                    $this->logger->addLog('The message cannot be saved: ' . $exception->getMessage(), 'error');
                    $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
                }
            }

            // Add prestashop OrderPayment if need
            if (!$order_payments) {
                $this->logger->addLog('Add new orderPayment for deferred - ' . count($order_payments), 'debug');
                $order->addOrderPayment(
                    $this->payment->amount / 100,
                    null,
                    $this->payment->id
                );
            }

            // If payment is paid, set the invoice
            if ($new_order_state == $this->order_states['paid']) {
                $this->logger->addLog('Set order invoice', 'debug');
                $order->setInvoice(true);
            }

            // Update the order state
            $order_history = new OrderHistory();
            $order_history->id_order = (int)$id_order;
            try {
                $use_existings_payment = false;
                if (!$order->hasInvoice()) {
                    $use_existings_payment = true;
                }
                $order_history->changeIdOrderState((int)$new_order_state, $order, $use_existings_payment);
                $order_history->save();
            } catch (Exception $exception) {
                $this->logger->addLog(
                    'Order history cannot be saved: ' . $exception->getMessage(),
                    'error'
                );
                $this->logger->addLog(
                    'Please check if order state ' . (int)$new_order_state . ' exists.',
                    'error'
                );
                $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
            }

            try {
                $order->current_state = $order_history->id_order_state;
                $order->update();
            } catch (Exception $exception) {
                $this->logger->addLog(
                    'Order cannot be updated: ' . $exception->getMessage(),
                    'error'
                );
                $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
            }
            $this->logger->addLog('Order updated.');
            $this->exitProcess('Order updated.');
        } elseif ($current_state == $this->order_states['paid']) { // if payment is already paid
            $this->logger->addLog('Order is already paid.');
            $this->exitProcess('Order is already paid.');
        } elseif ($current_state == $this->order_states['exp']) { // if payment is already expired
            $this->logger->addLog('Order is already set as expired.');
            $this->exitProcess('Order is already set as expired.');
        } elseif ($current_state == $this->order_states['cancelled']) { // if payment is already cancelled
            $this->logger->addLog('Order is already set as cancelled.');
            $this->exitProcess('Order is already set as cancelled.');
        } else { // else set error
            $this->logger->addLog(
                'Current state: ' . (int)$current_state,
                'debug'
            );
            $this->logger->addLog(
                'Current Cart ID: ' . (int)$this->cartObject->id,
                'debug'
            );
            $this->logger->addLog(
                'Current Payment ID: ' . (int)$this->payment->id,
                'debug'
            );
            $this->logger->addLog(
                'Pending state: ' . (int)$this->order_states['pending'],
                'debug'
            );
            $this->logger->addLog(
                'Paid state: ' . (int)$this->order_states['paid'],
                'debug'
            );
            $this->logger->addLog(
                'Current order state is in conflict with IPN.',
                'error'
            );
            $this->exitProcess('Current order state is in conflict with IPN.', 500);
        }
    }

    /**
     * @description Create order from the notification
     */
    private function createOrder()
    {
        $this->logger->addLog('CREATE MODE');

        if (isset($this->resource->failure) && $this->resource->failure !== null) {
            $this->logger->addLog('The payment has failed.');
            $this->exitProcess('No treatment because payment has failed.');
        }

        $is_paid = $this->resource->is_paid;

        if ($this->is_installment) {
            $installment = new PPPaymentInstallment($this->resource->installment_plan_id);
            $first_payment = $installment->getFirstPayment();
            if ($first_payment->isDeferred()) {
                $order_state = $this->order_states['auth'];
            } else {
                $order_state = $this->order_states['inst'];
            }

            $amount = 0;
            $installment = $this->payplug->retrieveInstallment($this->resource->installment_plan_id);
            foreach ($installment->schedule as $schedule) {
                $amount += (int)$schedule->amount;
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

        $extra_vars = array(
            'transaction_id' => $transaction_id
        );

        $amount = $this->payplug->convertAmount($amount, true);

        $currency = (int)$this->cartObject->id_currency;
        try {
            $customer = new Customer((int)$this->cartObject->id_customer);
        } catch (Exception $exception) {
            $this->logger->addLog(
                'Customer cannot be loaded: ' . $exception->getMessage(),
                'error'
            );
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }
        if (!Validate::isLoadedObject($customer)) {
            $this->logger->addLog('Customer cannot be loaded.', 'error');
            $this->exitProcess('Customer cannot be loaded.', 500);
        }

        /*
             * For some reasons, secure key form cart can differ from secure key from customer
             * Maybe due to migration or Prestashop's Update
             */
        $secure_key = false;
        if (isset($customer->secure_key) && !empty($customer->secure_key)) {
            if (isset($this->cartObject->secure_key)
                && !empty($this->cartObject->secure_key)
                && $this->cartObject->secure_key !== $customer->secure_key
            ) {
                $secure_key = $this->cartObject->secure_key;
                $this->logger->addLog('Secure keys do not match.', 'error');
                $this->logger->addLog(
                    'Customer Secure Key: ' . $customer->secure_key,
                    'error'
                );
                $this->logger->addLog('Cart Secure Key: ' . $this->cartObject->secure_key, 'error');
            } else {
                $secure_key = $customer->secure_key;
            }
        }

        $module_name = $this->payplug->displayName;
        if ($this->is_oney) {
            switch ($this->payment->payment_method['type']) {
                case 'oney_x3_with_fees':
                case 'oney_x3_without_fees':
                    $module_name = $this->payplug->l('Oney 3x');
                    break;
                case 'oney_x4_with_fees':
                case 'oney_x4_without_fees':
                    $module_name = $this->payplug->l('Oney 4x');
                    break;
                default:
                    break;
            }
        }

        // Create Order
        try {
            $cart_amount = (float)$this->cartObject->getOrderTotal(true, Cart::BOTH);

            if ($amount != $cart_amount) {
                $this->logger->addLog('Cart amount is different and may occured an error');
                $this->logger->addLog('Cart amount:' . $cart_amount);
            }

            $this->logger->addLog('Order create with amount:' . $amount);
            $is_order_validated = $this->payplug->validateOrder(
                $this->cartObject->id,
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
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }
        if (!$is_order_validated) {
            $this->logger->addLog('Order cannot be validated.', 'error');
            $this->exitProcess('Order cannot be validated.', 500);
        }
        $this->logger->addLog('Order validated.');

        // Then load it
        $order = new Order($this->payplug->currentOrder);
        if (!Validate::isLoadedObject($order)) {
            $this->logger->addLog('Order cannot be loaded.', 'error');
            $this->exitProcess('Order cannot be loaded.', 500);
        }
        $this->logger->addLog('Order loaded.', 'debug');

        // Add payplug OrderPayment && Installment
        if ($this->is_installment) {
            $this->payplug->addPayplugInstallment($this->resource->installment_plan_id, $order);
        } else {
            $data = array();
            $data['metadata'] = $this->payment->metadata;
            $data['metadata']['Order'] = $order->id;
            try {
                $this->logger->addLog('Payment patched.', 'debug');
                $this->payplug->patchPayment($this->api_key, $this->payment->id, $data);
            } catch (Exception $exception) {
                $this->logger->addLog(
                    'Payment cannot be patched: ' . $exception->getMessage(),
                    'error'
                );
                $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
            }

            if (!$this->payplug->addPayplugOrderPayment($order->id, $this->payment->id)) {
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
        $order_payments = $order->getOrderPayments();
        if (!$order_payments) {
            $this->logger->addLog('Add new orderPayment for deferred - ' . count($order_payments), 'debug');
            $order->addOrderPayment($amount, null, $transaction_id);
        }

        // Check number of order using this cart
        $this->logger->addLog('Checking number of order passed with this id_cart');
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'orders` WHERE `id_cart` = ' . $this->cartObject->id;
        $res_nb_orders = Db::getInstance()->executeS($sql);
        if (!$res_nb_orders) {
            $this->logger->addLog(
                'No order can be found using id_cart ' . (int)$this->cartObject->id,
                'error'
            );
            $this->exitProcess(
                'No order can be found using id_cart: ' . (int)$this->cartObject->id,
                500
            );
        } elseif (count($res_nb_orders) > 1) {
            $this->logger->addLog(
                'There is more than one order using id_cart ' . (int)$this->cartObject->id,
                'error'
            );
            foreach ($res_nb_orders as $o) {
                $this->logger->addLog('Order ID : ' . $o['id_order'], 'debug');
            }
            $this->exitProcess(
                'There is more than one order using id_cart: ' . (int)$this->cartObject->id,
                500
            );
        } else {
            $this->logger->addLog('OK');
            $id_order = (int)$res_nb_orders[0]['id_order'];
        }

        // Check number of orderPayment using this cart
        $this->logger->addLog('Checking number of transaction validated for this order');
        $payments = $order->getOrderPayments();
        if (!$payments) {
            $this->logger->addLog(
                'No transaction can be found using id_order ' . (int)$id_order,
                'error'
            );
            $this->exitProcess('No transaction can be found using id_order: ' . (int)$id_order, 500);
        } elseif (count($payments) > 1) {
            $this->logger->addLog(
                'There is more than one transaction using id_order ' . (int)$id_order,
                'error'
            );
            $this->exitProcess(
                'There is more than one transaction using id_order: ' . (int)$id_order,
                500
            );
        } else {
            $this->logger->addLog('OK');
        }

        //
        $this->logger->addLog('Order created.');
        $this->exitProcess('Order created.');
    }

    /**
     * @description Treat the notification has a refund
     */
    private function treatRefund()
    {
        $this->logger->addLog('REFUND MODE');
        $this->logger->addLog('Refund ID : ' . $this->resource->id);
        $refund = $this->resource;

        //Refund treatment
        try {
            $this->payment = $this->payplug->retrievePayment($refund->payment_id);
            $this->setOrderStates();
        } catch (ConfigurationNotSetException $exception) {
            $this->logger->addLog('Payment cannot be retrieved: ' . $exception->getMessage(), 'error');
            $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
        }

        if ($this->payment->installment_plan_id) {
            $installment = $this->payplug->retrieveInstallment($this->payment->installment_plan_id);
            $meta = $installment->metadata;
        } else {
            $meta = $this->payment->metadata;
        }

        $is_totaly_refunded = $this->payment->is_refunded;
        if ($is_totaly_refunded) {
            $this->logger->addLog('TOTAL REFUND MODE');

            $cart_id = (int)$meta['ID Cart'];
            $id_order = (int)Order::getOrderByCartId($cart_id);
            $order = new Order($id_order);
            $this->logger->addLog('Order ID : ' . $id_order);
            if (!Validate::isLoadedObject($order)) {
                $this->logger->addLog('Order cannot be loaded.', 'error');
                $this->exitProcess('Order cannot be loaded.', 500);
            }

            $new_order_state = $this->order_states['refund'];
            $current_state = $order->getCurrentState();

            if ($current_state != $new_order_state) {
                $this->logger->addLog('Changing status to \'refunded\'');
                $order_history = new OrderHistory();
                $order_history->id_order = $id_order;
                try {
                    $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                    $order_history->save();
                    $this->exitProcess('The order is fully refunded and is status updated to \'refunded\'');
                } catch (Exception $exception) {
                    $this->logger->addLog(
                        'Order history cannot be saved: ' . $exception->getMessage(),
                        'error'
                    );
                    $this->logger->addLog(
                        'Please check if order state ' . (int)$new_order_state . ' exists.',
                        'error'
                    );
                    $this->exitProcess($exception->getMessage(), $exception->getCode(), 500);
                }
            } else {
                $this->logger->addLog('Order status is already \'refunded\'');
                $this->exitProcess('Order status is already \'refunded\'');
            }
        } else {
            $this->logger->addLog('PARTIAL REFUND');
            $this->exitProcess('Partial refund');
        }
    }

    /**
     * @description Treat the notification has an installment
     */
    private function treatInstallment()
    {
        $this->logger->addLog('INSTALLMENT MODE');
        $this->logger->addLog('Installment ID: ' . $this->resource->id);
        $this->logger->addLog('Active : ' . (int)$this->resource->is_active);
        $this->exitProcess('Installment notification');
    }

    /**
     * @description Set the context of the order
     * @param $id_cart
     */
    protected function setContextFromCartID($id_cart)
    {
        if (!isset($this->context)) {
            $this->context = Context::getContext();
        }

        $this->context->cart = new Cart((int)$id_cart);
        $address = new Address((int)$this->context->cart->id_address_invoice);
        $this->context->country = new Country((int)$address->id_country);
        $this->context->customer = new Customer((int)$this->context->cart->id_customer);
        $this->context->language = new Language((int)$this->context->cart->id_lang);
        $this->context->currency = new Currency((int)$this->context->cart->id_currency);
        if (isset($this->context->cart->id_shop)) {
            $this->context->shop = new Shop($this->context->cart->id_shop);
        }
    }

    /**
     * @description Entry point to treat the notification
     */
    private function exitProcess($str = '', $http_code = 200)
    {
        if ($this->lock_key) {
            if (!PayplugLock::deleteLockG2($this->lock_key)) {
                $this->logger->addLog('Lock cannot be deleted.', 'error');
            } else {
                $this->logger->addLog('Lock deleted.', 'debug');
            }
        }

        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $http_code . ' ' . $str, true, $http_code);
        echo $_SERVER['SERVER_PROTOCOL'] . ' ' . $http_code . ' ' . $str;
        die;
    }
}
