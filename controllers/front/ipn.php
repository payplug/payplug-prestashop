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
     * @var bool $debug
     */
    public $debug;
    /**
     * @var object $logger
     */
    public $logger;

    /**
     * @var object $resource
     */
    public $resource = null;
    /**
     * @var bool $flag
     */
    public $flag = false;
    /**
     * @var object $except
     */
    public $except = null;
    /**
     * @var array $resp
     */
    public $resp = array();
    /**
     * @var $debug
     */
    public $payplug = null;

    public $api_key;

    /**
     * @description
     * Set Config to process the notification
     * @throws Exception
     */
    private function setConfig()
    {
        $this->payplug = new Payplug();
        $this->setLogger();
        $this->getResource();
        $this->logger->addLog('set configuration');
    }

    /**
     * @description
     * Set the resource from the notification body
     */
    private function getResource()
    {
        $body = Tools::file_get_contents('php://input');

        try {
            $resource = json_decode($body);
            $api_key = (bool)$resource->is_live ? Configuration::get('PAYPLUG_LIVE_API_KEY') : Configuration::get('PAYPLUG_TEST_API_KEY');
            $authentication = $this->payplug->setSecretKey($api_key);
            $this->logger->addLog('set api key: ' . $api_key);
            $this->resource = \Payplug\Notification::treat($body, $authentication);
            $this->logger->addLog('resource id: ' . $this->resource->id);
        } catch (Exception $exception) {
            $this->logger->addLog('An error occured while getting resource: ' . $exception->getMessage(), 'error');
            header(
                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                true,
                $exception->getCode()
            );
            die(json_encode(['exception' => $exception->getMessage()]));
        }
    }

    /**
     * @description
     * Set the log method
     */
    private function setLogger()
    {
        $this->logger = new PayPlugLogger('notification');
        $this->logger->addLog('New notification');
    }

    /**
     * @description
     * Process the notification
     * @throws Exception
     */
    public function postProcess()
    {
        //Settings
        $this->setConfig();

        $this->logger->addLog('OK');

        if ($this->resource instanceof \Payplug\Resource\Payment) {
            $this->processPayment();
        } elseif ($this->resource instanceof \Payplug\Resource\Refund) {
            $this->processRefund();
        } elseif ($this->resource instanceof \Payplug\Resource\InstallmentPlan) {
            $this->processInstallment();
        }
    }

    /**
     * @description
     * Process the notification as a payment
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processPayment()
    {
        $this->logger->addLog('process payment');

        if ($this->resource->installment_plan_id != null) {
            $this->logger->addLog('Installment ID: ' . $this->resource->installment_plan_id);
        }

        $this->logger->addLog('PAYMENT MODE');
        $this->logger->addLog('Payment ID: ' . $this->resource->id);
        $this->logger->addLog('Paid (Resource): ' . (int)$this->resource->is_paid);

        try {
            $payment = $this->payplug->retrievePayment($this->resource->id);
        } catch (ConfigurationNotSetException $exception) {
            $this->logger->addLog('Payment cannot be retrieved: ' . $exception->getMessage(), 'error');
            $response = array(
                'exception' => $exception->getMessage(),
            );
            header(
                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                true,
                $exception->getCode()
            );
            die(json_encode($response));
        }

        $this->logger->addLog('Paid (Payment): ' . (int)$payment->is_paid);

        if ($payment->authorization && isset($payment->authorization->expires_at)) {
            $deferred = true;
            $is_expired = $payment->authorization->expires_at - time() <= 0;
        } else {
            $deferred = false;
            $is_expired = false;
        }

        if (!$payment->is_paid && !$deferred) {
            $this->logger->addLog('The transaction is not paid yet.');
            $this->logger->addLog('No action will be done.');
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 The transaction is not paid.', true, 200);
            die;
        } else {
            if ($deferred) {
                $this->logger->addLog('The transaction is authorized but not captured yet.');
            } else {
                $this->logger->addLog('The transaction is paid.');
            }
            $this->logger->addLog('Payment details:');


            $is_oney = false;
            if (isset($payment->payment_method) && isset($payment->payment_method['type'])) {
                switch ($payment->payment_method['type']) {
                    case 'oney_x3_with_fees':
                    case 'oney_x4_with_fees':
                        $is_oney = true;
                        break;
                    default:
                        $is_oney = false;
                }
            }

            if ($this->resource->installment_plan_id != null) {
                $installment = $this->payplug->retrieveInstallment($this->resource->installment_plan_id);
                $meta = $installment->metadata;
                $sql = 'SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'payplug_installment_cart` WHERE `id_installment` = "' . $this->resource->installment_plan_id . '"';
                $id_cart = Db::getInstance()->getValue($sql);

                if (!$id_cart) {
                    $error_msg = 'The cart cannot be found with payment ID: ' . $this->resource->installment_plan_id;
                    $this->logger->addLog($error_msg, 'error');
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 ' . $error_msg,
                        true,
                        500
                    );
                    die($error_msg);
                }
            } else {
                $meta = $payment->metadata;
                $sql = 'SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'payplug_payment_cart` WHERE `id_payment` = "' . $this->resource->id . '"';
                $id_cart = Db::getInstance()->getValue($sql);

                if (!$id_cart) {
                    $error_msg = 'The cart cannot be found with payment ID: ' . $this->resource->id;
                    $this->logger->addLog($error_msg, 'error');
                    //HOTFIX: MR331 We use custom http code to precisely log this case of desync between real payment notification and wrong ones.
                    $response_code = ($is_oney ? 242 : 500);
                    header($_SERVER['SERVER_PROTOCOL'] . ' ' . $response_code . ' ' . $error_msg, true, $response_code);
                    die($error_msg);
                }
            }

            $this->logger->addLog('Cart ID: ' . (int)$meta['ID Cart'], 'debug');
            $this->logger->addLog('Is Live: ' . (int)$payment->is_live, 'debug');
            $this->logger->addLog('Amount: ' . (int)$payment->amount, 'debug');

            //Payment treatment
            try {
                $cart = new Cart((int)$meta['ID Cart']);
            } catch (Exception $exception) {
                $this->logger->addLog('The cart cannot be loaded: ' . $exception->getMessage(), 'error');
                $response = array(
                    'exception' => $exception->getMessage(),
                );
                header(
                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                    true,
                    $exception->getCode()
                );
                die(json_encode($response));
            }
            if (!Validate::isLoadedObject($cart)) {
                $this->logger->addLog('The cart cannot be loaded.', 'error');
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 The cart cannot be loaded.', true, 500);
                die;
            } else {
                $this->setContextFromCartID($cart->id);

                $lock_counter = 0;
                $cart_lock = false;
                do {
                    $lock_counter++;
                    $cart_lock = PayplugLock::createLockG2($cart->id, 'ipn');
                    if (!$cart_lock) {
                        PayplugLock::check($cart->id);
                    } else {
                        $this->logger->addLog('Lock created');
                    }
                } while (!$cart_lock);
                $this->logger->addLog('Lock_count: ' . (int)$lock_counter, 'debug');

                $id_order = Order::getOrderByCartId($cart->id);

                $state_addons = ($payment->is_live ? '' : '_TEST');
                $pending_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);
                $paid_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                $error_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR' . $state_addons);
                $cancelled_state = (int)Configuration::get('PS_OS_CANCELED');
                /*
                * initialy, there was an order state for installment but no it has been removed and we use 'paid' state.
                * We keep this $inst_state to give more readability.
                */
                $inst_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                $auth_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_AUTH' . $state_addons);
                $exp_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_EXP' . $state_addons);
                $refund_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
                $oney_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons);

                if ($id_order) {
                    $this->logger->addLog('UPDATE MODE');
                    try {
                        $order = new Order((int)$id_order);
                    } catch (Exception $exception) {
                        $this->logger->addLog(
                            'The order cannot be loaded: ' . $exception->getMessage(), 'error');
                        $response = array(
                            'exception' => $exception->getMessage(),
                        );
                        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                            true, $exception->getCode());
                        die(json_encode($response));
                    }
                    if (!Validate::isLoadedObject($order)) {
                        $this->logger->addLog('Order cannot be loaded.', 'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.', true, 500);
                        die;
                    } else {
                        try {
                            $current_state = (int)$order->getCurrentState();
                            $this->logger->addLog('Get the current state: ' . $current_state, 'info');
                        } catch (Exception $exception) {
                            $this->logger->addLog(
                                'The current state cannot be loaded: ' . $exception->getMessage(), 'error');
                            $response = array(
                                'exception' => $exception->getMessage(),
                            );
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                true,
                                $exception->getCode()
                            );
                            die(json_encode($response));
                        }

                        // if it's a refused oney payment, we switch to payment_error status
                        if ($is_oney
                            && isset($payment->failure)
                            && $payment->failure !== null
                            && !in_array($current_state, array($cancelled_state, $paid_state))
                        ) {
                            $this->logger->addLog('The payment is refused by Oney.');
                            $new_order_state = $error_state;

                            $order_history = new OrderHistory();
                            $order_history->id_order = (int)$id_order;
                            try {
                                $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                                $order_history->save();
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'Order history cannot be saved: ' . $exception->getMessage(), 'error');
                                $this->logger->addLog(
                                    'Please check if order state ' . (int)$new_order_state . ' exists.',
                                    'error');
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }

                            $order->current_state = $order_history->id_order_state;
                            try {
                                $order->update();
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'Order cannot be updated: ' . $exception->getMessage(), 'error');
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }
                            $this->logger->addLog('Order updated.');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                            die;
                        }

                        // if payment is deferred and expired
                        if ($deferred && $current_state == $auth_state && $is_expired) {
                            $this->logger->addLog('The payment authorization has expired.');
                            $this->logger->addLog('Payment amount: ' . $payment->amount, 'debug');
                            $this->logger->addLog('Order new status will be \'Authorization expired\'.', 'info');
                            $new_order_state = $exp_state;

                            $order_history = new OrderHistory();
                            $order_history->id_order = (int)$id_order;
                            try {
                                $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                                $order_history->save();
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'Order history cannot be saved: ' . $exception->getMessage(), 'error');
                                $this->logger->addLog(
                                    'Please check if order state ' . (int)$new_order_state . ' exists.',
                                    'error');
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }

                            $order->current_state = $order_history->id_order_state;
                            try {
                                $order->update();
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'Order cannot be updated: ' . $exception->getMessage(), 'error');
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }
                            $this->logger->addLog('Order updated.');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                            die;
                        } elseif (in_array($current_state, array($pending_state, $auth_state, $oney_state))) {
                            $this->logger->addLog('Order is currently pending.');
                            $this->logger->addLog('Payment amount: ' . $payment->amount, 'debug');
                            $is_amount_correct = false;
                            if ($payment->installment_plan_id !== null) {
                                $is_amount_correct = (bool)$payment->is_paid;
                            } else {
                                $is_amount_correct = (bool)PayPlug::checkAmountPaidIsCorrect($payment->amount / 100,
                                    $order);
                            }
                            if ($is_amount_correct) {
                                $this->logger->addLog('Order new status will be \'paid\'.');
                                $new_order_state = $paid_state;
                            } else {
                                $this->logger->addLog('Payment amount is not correct.', 'error');
                                $new_order_state = $error_state;
                                $this->logger->addLog('Order new status will be \'error\'.', 'error');
                                $message = new Message();
                                $message->message = $this->payplug->l('The amount collected by PayPlug is not the same')
                                    . $this->payplug->l(' as the total value of the order');
                                $message->id_order = $order->id;
                                $message->id_cart = $order->id_cart;
                                $message->private = true;
                                try {
                                    $message->save();
                                } catch (Exception $exception) {
                                    $this->logger->addLog(
                                        'The message cannot be saved: ' . $exception->getMessage(),
                                        'error');
                                    $response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(json_encode($response));
                                }
                            }

                            if (!$payment->is_paid) {
                                $this->logger->addLog('The payment is not paid yet.');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' 200 The payment is not paid yet.',
                                    true,
                                    200
                                );
                                die;
                            }

                            $order_history = new OrderHistory();
                            $order_history->id_order = (int)$id_order;
                            try {
                                $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                                $order_history->save();
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'Order history cannot be saved: ' . $exception->getMessage(), 'error');
                                $this->logger->addLog(
                                    'Please check if order state ' . (int)$new_order_state . ' exists.',
                                    'error');
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }
                            if (count($order->getOrderPayments()) == 0) {
                                $order->addOrderPayment($payment->amount / 100);
                            }
                            $order->current_state = $order_history->id_order_state;
                            try {
                                $order->update();
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'Order cannot be updated: ' . $exception->getMessage(), 'error');
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }
                            $this->logger->addLog('Order updated.');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                            die;
                        } elseif ($current_state == $paid_state) {
                            $this->logger->addLog('Order is already paid.');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order is already paid.', true, 200);
                            die;
                        } elseif ($installment = $this->payplug->retrieveInstallment($payment->installment_plan_id)) {
                            $this->logger->addLog('Order is currently pending for installment.',
                                'info');
                            $this->logger->addLog('Payment amount: ' . $payment->amount, 'debug');
                            if ((int)$installment->is_fully_paid == 1) {
                                $this->logger->addLog('Installment is fully paid.');
                                $this->logger->addLog('Order updated.');
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                die;
                            } else {
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                die;
                            }
                            $this->payplug->updatePayplugInstallment($installment);
                        } elseif ($current_state == $refund_state) {
                            $this->logger->addLog('Order has been refunded.');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order has been refunded.', true, 200);
                            die;
                        } else {
                            $this->logger->addLog(
                                'Current state: ' . (int)$current_state,
                                'debug'
                            );
                            $this->logger->addLog(
                                'Pending state: ' . (int)$pending_state,
                                'debug'
                            );
                            $this->logger->addLog(
                                'Paid state: ' . (int)$paid_state, 'debug'
                            );
                            $this->logger->addLog('Current order state is in conflict with IPN.', 'error');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Current order state is in conflict with IPN.',
                                true, 500);
                            die('500 Current order state is in conflict with IPN.');
                        }
                    }
                } else {
                    $this->logger->addLog('CREATE MODE');

                    if (isset($payment->failure) && $payment->failure !== null) {
                        $this->logger->addLog('The payment has failed.');
                        header($_SERVER['SERVER_PROTOCOL'] . ' 200 No treatment because payment has failed.', true,
                            200);
                        die;
                    }

                    $amount = 0;

                    if ($payment->installment_plan_id != null) {
                        $installment = new PPPaymentInstallment($this->resource->installment_plan_id);
                        $first_payment = $installment->getFirstPayment();
                        if ($first_payment->isDeferred()) {
                            $order_state = $auth_state;
                        } else {
                            $order_state = $inst_state;
                        }
                        $extra_vars = array(
                            'transaction_id' => $this->resource->installment_plan_id
                        );

                        $installment = $this->payplug->retrieveInstallment($this->resource->installment_plan_id);
                        foreach ($installment->schedule as $schedule) {
                            $amount += (int)$schedule->amount;
                        }
                    } else {
                        //We can't treat Oney pending IPN anymore because it's sent with no reason
                        if ($is_oney && !$payment->is_paid) {
                            $this->logger->addLog('This is a pending IPN, no order will be created.', 'info');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header(
                                $_SERVER['SERVER_PROTOCOL']
                                . ' 200 This is a pending IPN, no order will be created.',
                                true,
                                200
                            );
                            die;
                        } elseif ($deferred && !$payment->is_paid) {
                            $order_state = $auth_state;
                        } else {
                            $order_state = $paid_state;
                        }

                        $amount = (int)$payment->amount;

                        $extra_vars = array(
                            'transaction_id' => $payment->id
                        );
                    }

                    $amount = $amount / 100;

                    $currency = (int)$cart->id_currency;
                    try {
                        $customer = new Customer((int)$cart->id_customer);
                    } catch (Exception $exception) {
                        $this->logger->addLog(
                            'Customer cannot be loaded: ' . $exception->getMessage(), 'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        $response = array(
                            'exception' => $exception->getMessage(),
                        );
                        header(
                            $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                            true,
                            $exception->getCode()
                        );
                        die(json_encode($response));
                    }
                    if (!Validate::isLoadedObject($customer)) {
                        $this->logger->addLog('Customer cannot be loaded.', 'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        header($_SERVER['SERVER_PROTOCOL'] . ' 500 Customer cannot be loaded.', true, 500);
                        die;
                    } else {
                        /*
                         * For some reasons, secure key form cart can differ from secure key from customer
                         * Maybe due to migration or Prestashop's Update
                         */
                        $secure_key = false;
                        if (isset($customer->secure_key) && !empty($customer->secure_key)) {
                            if (isset($cart->secure_key) && !empty($cart->secure_key) && $cart->secure_key !== $customer->secure_key) {
                                $secure_key = $cart->secure_key;
                                $this->logger->addLog('Secure keys do not match.', 'error');
                                $this->logger->addLog('Customer Secure Key: ' . $customer->secure_key,
                                    'error');
                                $this->logger->addLog('Cart Secure Key: ' . $cart->secure_key,
                                    'error');
                            } else {
                                $secure_key = $customer->secure_key;
                            }
                        }

                        try {
                            $cart_amount = (float)($cart->getOrderTotal(true, Cart::BOTH));

                            if ($is_oney) {
                                switch ($this->resource->payment_method['type']) {
                                    case 'oney_x3_with_fees' :
                                    case 'oney_x3_without_fees' :
                                        $module_name = $this->payplug->l('Oney 3x');
                                        break;
                                    case 'oney_x4_with_fees' :
                                    case 'oney_x4_without_fees' :
                                        $module_name = $this->payplug->l('Oney 4x');
                                        break;
                                    default:
                                        $module_name = $this->payplug->displayName;
                                        break;
                                }
                            } else {
                                $module_name = $this->payplug->displayName;
                            }

                            if ($amount != $cart_amount) {
                                $this->logger->addLog('Cart amount is different and may occured an error', 'info');
                                $this->logger->addLog('Order create with amount:' . $cart_amount, 'info');

                                $is_order_validated = $this->payplug->validateOrder(
                                    $cart->id,
                                    $order_state,
                                    $cart_amount,
                                    $module_name,
                                    null,
                                    $extra_vars,
                                    $currency,
                                    false,
                                    $secure_key
                                );

                                if (Tools::version_compare(_PS_VERSION_, '1.7.1.0', '>')) {
                                    $order = Order::getByCartId($cart->id);
                                } else {
                                    $id_order = Order::getOrderByCartId($cart->id);
                                    $order = new Order($id_order);
                                }

                                $this->logger->addLog('Order payment patch with amount:' . $amount, 'info');
                                $order->total_paid = $amount;
                                $order->total_paid_real = $amount;
                                $order->total_paid_tax_incl = $amount;
                                $order->update();

                                $sql = 'UPDATE `' . _DB_PREFIX_ . 'order_payment` SET `amount` = ' . (float)$amount . ' WHERE  `transaction_id` = "' . pSQL($this->resource->id) . '"';
                                Db::getInstance()->execute($sql);

                                $this->logger->addLog('Order amount is patched' . $cart_amount, 'info');
                            } else {
                                $is_order_validated = $this->payplug->validateOrder(
                                    $cart->id,
                                    $order_state,
                                    $amount,
                                    $module_name,
                                    null,
                                    $extra_vars,
                                    $currency,
                                    false,
                                    $secure_key
                                );
                            }

                        } catch (Exception $exception) {
                            $this->logger->addLog(
                                'Order cannot be validated: ' . $exception->getMessage(), 'error');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            $response = array(
                                'exception' => $exception->getMessage(),
                            );
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                true,
                                $exception->getCode()
                            );
                            die(json_encode($response));
                        }
                        if (!$is_order_validated) {
                            $this->logger->addLog('Order cannot be validated.', 'error');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be validated.', true,
                                500);
                            die;
                        } else {
                            $id_order = Order::getOrderByCartId($cart->id);
                            $order = new Order($id_order);
                            $this->logger->addLog('Order validated: ' . $order->id);

                            if ($this->resource->installment_plan_id != null) {
                                $this->payplug->addPayplugInstallment($this->resource->installment_plan_id, $order);
                            }

                            $api_key = Payplug::setAPIKey();
                            $data = array();
                            $data['metadata'] = $meta;
                            $data['metadata']['Order'] = $id_order;
                            try {
                                $this->payplug->patchPayment($api_key, $payment->id, $data);
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'Payment cannot be patched: ' . $exception->getMessage(), 'error');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }
                            $this->logger->addLog('Payment patched');
                            if (!Validate::isLoadedObject($order)) {
                                $this->logger->addLog('Order cannot be loaded.', 'error');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.', true,
                                    500);
                                die;
                            } else {
                                if ($this->resource->installment_plan_id != null) {
                                    $this->logger->addLog('Installment correctly registered.',
                                        'info');
                                    if (!PayplugLock::deleteLockG2($cart->id)) {
                                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->logger->addLog('Lock deleted.', 'debug');
                                    }
                                    header($_SERVER['SERVER_PROTOCOL'] . ' 200 Installment correctly registered.',
                                        true, 200);
                                    die;
                                } else {
                                    $order_payments = $order->getOrderPayments();
                                    if (is_array($order_payments) && !empty($order_payments)) {
                                        $order_payment = end($order_payments);
                                        $order_payment->transaction_id = $extra_vars['transaction_id'];
                                        try {
                                            $order_payment->update();
                                        } catch (Exception $exception) {
                                            $this->logger->addLog(
                                                'Payment cannot be updated: ' . $exception->getMessage(),
                                                'error');
                                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                                            } else {
                                                $this->logger->addLog('Lock deleted.', 'debug');
                                            }
                                            $response = array(
                                                'exception' => $exception->getMessage(),
                                            );
                                            header(
                                                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                                true,
                                                $exception->getCode()
                                            );
                                            die(json_encode($response));
                                        }
                                        $this->logger->addLog('Transaction ID added.');
                                    } else {
                                        $this->logger->addLog('Can\'t find any order payment to update with transaction id: ' . $extra_vars['transaction_id']);
                                    }
                                }
                            }
                        }
                    }

                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }

                    $this->logger->addLog('Checking number of order passed with this id_cart',
                        'info');
                    $req_nb_orders = '
                                SELECT o.* 
                                FROM ' . _DB_PREFIX_ . 'orders o 
                                WHERE o.id_cart = ' . $cart->id;
                    $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                    if (!$res_nb_orders) {
                        $this->logger->addLog('No order can be found using id_cart ' . (int)$cart->id,
                            'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        header($_SERVER['SERVER_PROTOCOL'] . ' 500 No order can be found using id_cart ' . (int)$cart->id,
                            true, 500);
                        die;
                    } elseif (count($res_nb_orders) > 1) {
                        $this->logger->addLog(
                            'There is more than one order using id_cart ' . (int)$cart->id, 'error');
                        foreach ($res_nb_orders as $o) {
                            $this->logger->addLog('Order ID : ' . $o['id_order'], 'debug');
                        }
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        header($_SERVER['SERVER_PROTOCOL'] . ' 500 There is more than one order using id_cart ' . (int)$cart->id,
                            true, 500);
                        die;
                    } else {
                        $this->logger->addLog('OK');
                        $id_order = (int)$res_nb_orders[0]['id_order'];
                    }

                    $this->logger->addLog('Checking number of transaction validated for this order',
                        'info');
                    $payments = $order->getOrderPaymentCollection();
                    if (!$payments) {
                        $this->logger->addLog(
                            'No transaction can be found using id_order ' . (int)$id_order, 'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        header($_SERVER['SERVER_PROTOCOL'] . ' 500 No transaction can be found using id_order ' . (int)$id_order,
                            true, 500);
                        die;
                    } elseif (count($payments) > 1) {
                        $this->logger->addLog(
                            'There is more than one transaction using id_order ' . (int)$id_order, 'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        header($_SERVER['SERVER_PROTOCOL'] . ' 500 There is more than one transaction using id_order ' . (int)$id_order,
                            true, 500);
                        die;
                    } else {
                        $this->logger->addLog('OK');
                    }

                    $this->logger->addLog('Order created.');
                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order created.', true, 200);
                    die;
                }
            }
        }
    }

    /**
     * @description
     * Process the notification as a refund
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processRefund()
    {
        $this->logger->addLog('process refund');
        $this->logger->addLog('Refund ID : ' . $this->resource->id);
        $refund = $this->resource;

        //Refund treatment
        try {
            $payment = $this->payplug->retrievePayment($refund->payment_id);
        } catch (ConfigurationNotSetException $exception) {
            $this->logger->addLog('Payment cannot be retrieved: ' . $exception->getMessage(), 'error');
            $response = array(
                'exception' => $exception->getMessage(),
            );
            header(
                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                true,
                $exception->getCode()
            );
            die(json_encode($response));
        }

        if ($payment->installment_plan_id != null) {
            $installment = $this->payplug->retrieveInstallment($payment->installment_plan_id);
            $meta = $installment->metadata;
        } else {
            $meta = $payment->metadata;
        }

        $is_totaly_refunded = $payment->is_refunded;
        if ($is_totaly_refunded) {
            $this->logger->addLog('TOTAL REFUND MODE');

            $cart_id = (int)$meta['ID Cart'];
            $id_order = (int)Order::getOrderByCartId($cart_id);
            $order = new Order($id_order);
            $this->logger->addLog('Order ID : ' . $id_order);
            if (!Validate::isLoadedObject($order)) {
                $this->logger->addLog('Order cannot be loaded.', 'error');
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.', true, 500);
                die;
            } else {
                $state_addons = ($payment->is_live ? '' : '_TEST');
                $new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
                $current_state = $order->getCurrentState();

                if ($current_state != $new_order_state) {
                    $this->logger->addLog('Changing status to \'refunded\'');
                    $order_history = new OrderHistory();
                    $order_history->id_order = $id_order;
                    try {
                        $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                        $order_history->save();
                        header($_SERVER['SERVER_PROTOCOL'] . ' 200 Changing status to \'refunded\'', true, 200);
                        die();
                    } catch (Exception $exception) {
                        $this->logger->addLog(
                            'Order history cannot be saved: ' . $exception->getMessage(), 'error');
                        $this->logger->addLog(
                            'Please check if order state ' . (int)$new_order_state . ' exists.', 'error');
                        $response = array(
                            'exception' => $exception->getMessage(),
                        );
                        header(
                            $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                            true,
                            $exception->getCode()
                        );
                        die(json_encode($response));
                    }
                } else {
                    $this->logger->addLog('Order status is already \'refunded\'');
                    header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order status is already \'refunded\'', true, 200);
                    die();
                }
            }
        } else {
            $this->logger->addLog('PARTIAL REFUND');
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 The payment is partially refunded.', true, 200);
            die();
        }
    }

    /**
     * @description
     * Process the notification as an installment plan
     */
    private function processInstallment()
    {
        $this->logger->addLog('process installment');
        $this->logger->addLog('Installment ID: ' . $this->resource->id);
        $this->logger->addLog('Active : ' . (int)$this->resource->is_active);
        header($_SERVER['SERVER_PROTOCOL'] . ' 200 installment resource receive.', true, 200);
        die();
    }

    /**
     * @description
     * @param $id_cart
     */
    protected function setContextFromCartID($id_cart)
    {
        Context::getContext()->cart = new Cart((int)$id_cart);
        $address = new Address((int)Context::getContext()->cart->id_address_invoice);
        Context::getContext()->country = new Country((int)$address->id_country);
        Context::getContext()->customer = new Customer((int)Context::getContext()->cart->id_customer);
        Context::getContext()->language = new Language((int)Context::getContext()->cart->id_lang);
        Context::getContext()->currency = new Currency((int)Context::getContext()->cart->id_currency);
        if (isset(Context::getContext()->cart->id_shop)) {
            Context::getContext()->shop = new Shop(Context::getContext()->cart->id_shop);
        }
    }
}
