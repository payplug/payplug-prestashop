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

use PayPlug\classes\PayPlugLogger;

require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayplugLock.php');

class PayPlugNotifications
{
    public $resource;
    public $flag;
    public $except;
    public $resp;
    public $payplug;
    public $debug;
    public $logger;
    public $sandbox;
    public $key;
    public $api_key;

    public function __construct()
    {
        $this->key = microtime(true) * 10000;
        $this->flag = false;
        $this->except = null;
        $this->resp = array();
        $this->payplug = new Payplug();
        $this->debug = $this->payplug->getConfiguration('PAYPLUG_DEBUG_MODE');
        $this->sandbox = $this->payplug->getConfiguration('PAYPLUG_SANDBOX_MODE');

        $this->setLogger();
        $this->getResource();
    }

    private function getResource()
    {
         $body = Tools::file_get_contents('php://input');

        try {
            $resource = json_decode($body);
            $this->api_key = (bool)$resource->is_live ? Configuration::get('PAYPLUG_LIVE_API_KEY') : Configuration::get('PAYPLUG_TEST_API_KEY');
            $this->payplug->setSecretKey($this->api_key);
            $this->resource = \Payplug\Notification::treat($body);
        } catch (\Payplug\Exception\UnknownAPIResourceException $exception) {
            $this->flag = true;
            $this->except = $exception;
            $this->resp = array(
                'exception' => $exception->getMessage(),
            );
        }
    }

    public function setLogger() {
        $this->logger = new PayPlugLogger('notification');
        $this->logger->addLog('New notification');
    }

    public function treat()
    {
        //Notification identification
        $this->logger->addLog('Notification treatment and authenticity verification:');

        if ($this->flag) {
            header(
                $_SERVER['SERVER_PROTOCOL'] . ' ' . $this->except->getCode() . ' ' . $this->except->getMessage(),
                true,
                $this->except->getCode()
            );
            die(Tools::jsonEncode($this->resp));
        }

        $this->logger->addLog('OK');

        if ($this->resource instanceof \Payplug\Resource\Payment) {
            $this->treatPayment();
        } elseif ($this->resource instanceof \Payplug\Resource\Refund) {
            $this->treatRefund();
        } elseif ($this->resource instanceof \Payplug\Resource\InstallmentPlan) {
            $this->treatInstallment();
        }
    }

    private function treatPayment()
    {
        if ($this->resource->installment_plan_id != null) {
            $this->logger->addLog('Installment ID: ' . $this->resource->installment_plan_id);
        }

        $this->logger->addLog('PAYMENT MODE');
        $this->logger->addLog('Payment ID: ' . $this->resource->id);
        $this->logger->addLog('Paid (Resource): ' . (int)$this->resource->is_paid);

        if (!$payment = $this->payplug->retrievePayment($this->resource->id)) {
            $this->logger->addLog('Can\'t retrieve payment with this API Key.', 'debug');
            if ($this->sandbox) {
                $this->logger->addLog('This was test mode.', 'debug');
                $this->logger->addLog('Trying live mode.', 'debug');
                $this->payplug->initializeApi(false);
                if (!$payment = $this->payplug->retrievePayment($this->resource->id)) {
                    $this->logger->addLog('Can\'t retrieve payment with LIVE API Key.', 'debug');
                    $this->payplug->initializeApi(true);
                    $payment = null;
                }
            } else {
                $this->logger->addLog('This was live mode.', 'debug');
                $this->logger->addLog('Trying test mode.', 'debug');
                $this->payplug->initializeApi(true);
                if (!$payment = $this->payplug->retrievePayment($this->resource->id)) {
                    $this->logger->addLog('Can\'t retrieve payment with the TEST API Key.', 'debug');
                    $this->payplug->initializeApi(false);
                    $payment = null;
                }
            }
        }

        if (
            ((isset($payment->save_card) && (int)$payment->save_card == 1))
            ||
                ((isset($payment->card->id) && $payment->card->id != '')
                && ((isset($payment->hosted_payment)) && $payment->hosted_payment != ''))
        ) {
            $this->logger->addLog('[Save Card] Saving card...', 'info');
            $res_payplug_card = $this->payplug->saveCard($payment);

            if (!$res_payplug_card) {
                $this->logger->addLog('[Save Card] Card cannot be saved.', 'error');

                if (!isset($payment->save_card)) {
                    $this->logger->addLog('[Save Card] $payment->save_card is not set', 'debug');
                }

                if (isset($payment->save_card) && $payment->save_card !== 1) {
                    $this->logger->addLog('[Save Card] $payment->save_card is set but not equal to 1', 'debug');
                }

                if (!isset($payment->card->id)) {
                    $this->logger->addLog('[Save Card] $payment->card->id is not set', 'debug');
                }

                if (isset($payment->card->id) && $payment->card->id == '') {
                    $this->logger->addLog('[Save Card] $payment->card->id is set but empty', 'debug');
                }

                if (!isset($payment->hosted_payment)) {
                    $this->logger->addLog('[Save Card] $payment->hosted_payment is not set', 'debug');
                }

                if ((isset($payment->hosted_payment)) && $payment->hosted_payment == '') {
                    $this->logger->addLog('[Save Card] $payment->hosted_payment is set but empty', 'debug');
                }
            }
        }

        $this->logger->addLog('Paid (Payment): ' . (int)$payment->is_paid);

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

        if ($payment->authorization !== null && !$is_oney) {
            $deferred = true;
        } else {
            $deferred = false;
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

            if ($this->resource->installment_plan_id != null) {
                $installment = $this->payplug->retrieveInstallment($this->resource->installment_plan_id);
                $meta = $installment->metadata;
                $sql = 'SELECT `id_cart` FROM `' . _DB_PREFIX_ . 'payplug_installment_cart` WHERE `id_installment` = "' . $this->resource->installment_plan_id . '"';
                $id_cart = Db::getInstance()->getValue($sql);

                if (!$id_cart) {
                    $error_msg = 'The cart cannot be found with payment ID: ' . $this->resource->installment_plan_id;
                    $this->logger->addLog($error_msg,'error');
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
                    $this->logger->addLog('The cart cannot be found with payment ID: ' . $this->resource->id, 'error');
                    $this->logger->addLog($error_msg,'error');
                    //HOTFIX: MR331 We use custom http code to precisely log this case of desync between real payment notification and wrong ones.
                    $response_code = ($is_oney ? 242 : 500);
                    header($_SERVER['SERVER_PROTOCOL'] . ' ' . $response_code . ' '. $error_msg, true, $response_code);
                    die($error_msg);
                }
            }

            $this->logger->addLog('Cart ID: ' . (int)$id_cart, 'debug');
            $this->logger->addLog('Is Live: ' . (int)$payment->is_live, 'debug');
            $this->logger->addLog('Amount: ' . (int)$payment->amount, 'debug');


            //Payment treatment
            try {
                $cart = new Cart($id_cart);
            } catch (Exception $exception) {
                $this->logger->addLog('The cart cannot be loaded: ' . $exception->getMessage(), 'error');
                $this->response = array(
                    'exception' => $exception->getMessage(),
                );
                header(
                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                    true,
                    $exception->getCode()
                );
                die(Tools::jsonEncode($this->response));
            }

            if (!Validate::isLoadedObject($cart)) {
                $this->logger->addLog('The cart cannot be loaded.', 'error');
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 The cart cannot be loaded.', true, 500);
                die;
            } else {
                $this->setContextFromCartID($cart->id);

                $cart_lock = false;
                do {
                    $cart_lock = PayplugLock::createLockG2($cart->id, 'ipn');
                    if (!$cart_lock) {
                        PayplugLock::check($cart->id);
                    } else {
                        $this->logger->addLog('Lock created');
                    }
                } while (!$cart_lock);

                try {
                    $address = new Address((int)$cart->id_address_invoice);
                } catch (Exception $exception) {
                    $this->logger->addLog('The address cannot be loaded: '
                        . $exception->getMessage(), 'error');
                    $this->response = array(
                        'exception' => $exception->getMessage(),
                    );
                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    header(
                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                        true,
                        $exception->getCode()
                    );
                    die(Tools::jsonEncode($this->response));
                }
                if (!Validate::isLoadedObject($address)) {
                    $this->logger->addLog('The address cannot be loaded.', 'error');
                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 The address cannot be loaded.', true, 500);
                    die;
                } else {
                    $id_order = Order::getOrderByCartId($cart->id);

                    $state_addons = ($payment->is_live ? '' : '_TEST');
                    $pending_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);
                    $paid_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                    $error_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_ERROR' . $state_addons);
                    $inst_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                    $auth_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_AUTH' . $state_addons);
                    $exp_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_EXP' . $state_addons);
                    $oney_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons);
                    $cancelled_state = $this->payplug->getConfiguration('PS_OS_CANCELED');

                    if ($id_order) {
                        $this->logger->addLog('UPDATE MODE');
                        try {
                            $order = new Order((int)$id_order);
                        } catch (Exception $exception) {
                            $this->logger->addLog('The order cannot be loaded: ' . $exception->getMessage(),
                                'error');
                            $this->response = array(
                                'exception' => $exception->getMessage(),
                            );
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                . $exception->getMessage(),
                                true,
                                $exception->getCode()
                            );
                            die(Tools::jsonEncode($this->response));
                        }
                        if (!Validate::isLoadedObject($order)) {
                            $this->logger->addLog('Order cannot be loaded.', 'error');
                            $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                            if (!$cart_unlock) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.',
                                true,
                                500
                            );
                            die;
                        } else {
                            try {
                                $current_state = (int)$order->getCurrentState();
                            } catch (Exception $exception) {
                                $this->logger->addLog(
                                    'The current state cannot be loaded: ' . $exception->getMessage(), 'error');
                                $this->response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                    . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(Tools::jsonEncode($this->response));
                            }

                            // if it's a refused oney payment, we switch to cancelled status
                            if ($is_oney
                                && isset($payment->failure)
                                && $payment->failure !== null
                                && !in_array($current_state, array($cancelled_state, $paid_state))
                            ) {
                                $this->logger->addLog('The payment is refused by Oney.');
                                $new_order_state = $cancelled_state;

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
                                    $this->response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    if (!PayplugLock::deleteLockG2($cart->id)) {
                                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->logger->addLog('Lock deleted.', 'debug');
                                    }
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                        . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(Tools::jsonEncode($this->response));
                                }

                                $order->current_state = $order_history->id_order_state;
                                try {
                                    $order->update();
                                } catch (Exception $exception) {
                                    $this->logger->addLog(
                                        'Order cannot be updated: ' . $exception->getMessage(), 'error');
                                    $this->response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                        . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(Tools::jsonEncode($this->response));
                                }
                                $this->logger->addLog('Order updated.');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                die;
                            } // if payment is deferred and expired
                            elseif (
                                $deferred
                                && $current_state == $auth_state
                                && ($payment->authorization->expires_at - time()) <= 0
                            ) {
                                $this->logger->addLog('The payment authorization has expired.');
                                $this->logger->addLog('Payment amount: ' . $payment->amount, 'debug');
                                $this->logger->addLog('Order new status will be \'Authorization expired\'.');
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
                                    $this->response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    if (!PayplugLock::deleteLockG2($cart->id)) {
                                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->logger->addLog('Lock deleted.', 'debug');
                                    }
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                        . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(Tools::jsonEncode($this->response));
                                }

                                $order->current_state = $order_history->id_order_state;
                                try {
                                    $order->update();
                                } catch (Exception $exception) {
                                    $this->logger->addLog(
                                        'Order cannot be updated: ' . $exception->getMessage(), 'error');
                                    $this->response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                        . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(Tools::jsonEncode($this->response));
                                }
                                $this->logger->addLog('Order updated.');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                die;
                            } // if payment is pending or awaiting a capture
                            elseif (in_array($current_state, array($pending_state, $auth_state, $oney_state)) || !$order->valid) {
                                $this->logger->addLog('Order is currently pending.');
                                $this->logger->addLog('Payment amount: ' . $payment->amount, 'debug');

                                if ($payment->installment_plan_id !== null) {
                                    $is_amount_correct = (bool)$payment->is_paid;
                                } else {
                                    $is_amount_correct = (bool)PayPlug::checkAmountPaidIsCorrect($payment->amount / 100,
                                        $order);
                                }

                                $this->logger->addLog('Order ID: ' . (int)$order->id, 'debug');
                                // We have to check if the payment to update is the one linked to the order
                                // because it's possible to attempt to pay with a method and cancel before payment
                                // then make another attempt with another attempt but still receive previous IPN
                                $req_order_payment = '
                                    SELECT pop.* 
                                    FROM ' . _DB_PREFIX_ . 'payplug_order_payment pop 
                                    WHERE pop.id_order = ' . (int)$order->id;
                                $orderPayments = Db::getInstance()->executeS($req_order_payment);

                                $f = false;
                                if (count($orderPayments) > 0) {
                                    foreach ($orderPayments as $p) {
                                        if ($p['id_payment'] == $payment->id) {
                                            $f = true;
                                        }
                                    }
                                }
                                if (!$f) {
                                    if (!PayplugLock::deleteLockG2($cart->id)) {
                                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->logger->addLog('Lock deleted.', 'debug');
                                    }
                                    $this->logger->addLog('The payment is not related to this order.', 'debug');
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' 200 The payment is not related to this order.',
                                        true,
                                        200
                                    );
                                    die;
                                }

                                if (!$payment->is_paid) {
                                    if (isset($payment->failure) && $payment->failure !== null) {
                                        //todo : Gerer le cas oney refusé
                                        $this->logger->addLog('The payment has failed.');
                                        $this->logger->addLog('Order new status will be \'cancel\'.');
                                        $new_order_state = $cancelled_state;
                                        $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                        if (!$cart_unlock) {
                                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                                        } else {
                                            $this->logger->addLog('Lock deleted.', 'debug');
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
                                            $this->response = array(
                                                'exception' => $exception->getMessage(),
                                            );
                                            header(
                                                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                                . $exception->getMessage(),
                                                true,
                                                $exception->getCode()
                                            );
                                            die(Tools::jsonEncode($this->response));
                                        }
                                        header(
                                            $_SERVER['SERVER_PROTOCOL']
                                            . ' 200 The payment has failed and order has been cancelled.',
                                            true,
                                            200
                                        );
                                        die;
                                    }
                                    $this->logger->addLog('The payment is not paid yet.');
                                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                    if (!$cart_unlock) {
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

                                if ($is_amount_correct) {
                                    $this->logger->addLog('Order new status will be \'paid\'.');
                                    $new_order_state = $paid_state;
                                } else {
                                    $this->logger->addLog('Payment amount is not correct.', 'error');
                                    $new_order_state = $error_state;
                                    $this->logger->addLog(
                                        'Order new status will be \'error\'.',
                                        'error'
                                    );
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
                                        $this->logger->addLog(
                                            'The message cannot be saved: ' . $exception->getMessage(), 'error');
                                        $this->response = array(
                                            'exception' => $exception->getMessage(),
                                        );
                                        if (!PayplugLock::deleteLockG2($cart->id)) {
                                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                                        } else {
                                            $this->logger->addLog('Lock deleted.', 'debug');
                                        }
                                        header(
                                            $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                            . $exception->getMessage(),
                                            true,
                                            $exception->getCode()
                                        );
                                        die(Tools::jsonEncode($this->response));
                                    }
                                }

                                $order_history = new OrderHistory();
                                if (count($order->getOrderPayments()) == 0) {
                                    $this->logger->addLog('Add new orderPayment - ' . count($order->getOrderPayments()),
                                        'debug');
                                    $order->addOrderPayment($payment->amount / 100, null, $payment->id);
                                    $order->setInvoice(true);
                                }
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
                                    $this->response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    if (!PayplugLock::deleteLockG2($cart->id)) {
                                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->logger->addLog('Lock deleted.', 'debug');
                                    }
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                        . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(Tools::jsonEncode($this->response));
                                }

                                try {
                                    $order->current_state = $order_history->id_order_state;
                                    $order->update();
                                } catch (Exception $exception) {
                                    $this->logger->addLog(
                                        'Order cannot be updated: ' . $exception->getMessage(), 'error');
                                    $this->response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    if (!PayplugLock::deleteLockG2($cart->id)) {
                                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->logger->addLog('Lock deleted.', 'debug');
                                    }
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                        . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(Tools::jsonEncode($this->response));
                                }
                                $this->logger->addLog('Order updated.');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                die;
                            } // if payment is already paid
                            elseif ($current_state == $paid_state) {
                                $this->logger->addLog('Order is already paid.');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' 200 Order is already paid.',
                                    true,
                                    200
                                );
                                die;
                            } // if payment is already expired
                            elseif ($current_state == $exp_state) {
                                $this->logger->addLog('Order is already set as expired.');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' 200 Order is already set as expired.',
                                    true,
                                    200
                                );
                                die;
                            } // if payment is already cancelled
                            elseif ($current_state == $cancelled_state) {
                                $this->logger->addLog('Order is already set as cancelled.');
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' 200 Order is already set as cancelled.',
                                    true,
                                    200
                                );
                                die;
                            } // else set error
                            else {
                                $this->logger->addLog(
                                        'Current state: ' . (int)$current_state,
                                        'debug'
                                    );
                                $this->logger->addLog(
                                        'Current Cart ID: ' . (int)$cart->id,
                                        'debug'
                                    );
                                $this->logger->addLog(
                                        'Current Payment ID: ' . (int)$payment->id,
                                        'debug'
                                    );
                                $this->logger->addLog(
                                        'Pending state: ' . (int)$pending_state,
                                        'debug'
                                    );
                                $this->logger->addLog(
                                        'Paid state: ' . (int)$paid_state,
                                        'debug'
                                    );
                                $this->logger->addLog(
                                        'Current order state is in conflict with IPN.',
                                        'error'
                                    );
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header(
                                    $_SERVER['SERVER_PROTOCOL']
                                    . ' 500 Current order state is in conflict with IPN.',
                                    true,
                                    500
                                );
                                die('500 Current order state is in conflict with IPN.');
                            }
                        }
                    } else {

                        $this->logger->addLog('CREATE MODE');

                        if (isset($this->resource->failure) && $this->resource->failure !== null) {
                            $this->logger->addLog('The payment has failed.');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 200 No treatment because payment has failed.', true,
                                200);
                            die;
                        }

                        $is_paid = $this->resource->is_paid;

                        if ($this->resource->installment_plan_id != null) {
                            $installment = new PPPaymentInstallment($this->resource->installment_plan_id);
                            $first_payment = $installment->getFirstPayment();
                            if ($first_payment->isDeferred()) {
                                $order_state = $auth_state;
                            } else {
                                $order_state = $inst_state;
                            }

                            $amount = 0;
                            $installment = $this->payplug->retrieveInstallment($this->resource->installment_plan_id);
                            foreach ($installment->schedule as $schedule) {
                                $amount += (int)$schedule->amount;
                            }

                            $transaction_id = $this->resource->installment_plan_id;
                        } else {
                            //We can't treat Oney pending IPN anymore because it's sent with no reason
                            if ($is_oney && !$is_paid) {
                                $order_state = $oney_state;
                            } elseif ($deferred && !$is_paid) {
                                $order_state = $auth_state;
                            } else {
                                $order_state = $paid_state;
                            }

                            $amount = $payment->amount;
                            $transaction_id = $payment->id;
                        }

                        $extra_vars = array(
                            'transaction_id' => $transaction_id
                        );

                        $amount = $this->payplug->convertAmount($amount, true);

                        $currency = (int)$cart->id_currency;
                        try {
                            $customer = new Customer((int)$cart->id_customer);
                        } catch (Exception $exception) {
                            $this->logger->addLog('Customer cannot be loaded: ' . $exception->getMessage(),
                                'error');
                            $this->response = array(
                                'exception' => $exception->getMessage(),
                            );
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                . $exception->getMessage(),
                                true,
                                $exception->getCode()
                            );
                            die(Tools::jsonEncode($this->response));
                        }
                        if (!Validate::isLoadedObject($customer)) {
                            $this->logger->addLog('Customer cannot be loaded.', 'error');
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            die;
                        } else {
                            /*
                             * For some reasons, secure key form cart can differ from secure key from customer
                             * Maybe due to migration or Prestashop's Update
                             */
                            $secure_key = false;
                            if (isset($customer->secure_key) && !empty($customer->secure_key)) {
                                if (
                                    isset($cart->secure_key)
                                    && !empty($cart->secure_key)
                                    && $cart->secure_key !== $customer->secure_key
                                ) {
                                    $secure_key = $cart->secure_key;
                                    $this->logger->addLog('Secure keys do not match.', 'error');
                                    $this->logger->addLog('Customer Secure Key: ' . $customer->secure_key,
                                        'error');
                                    $this->logger->addLog('Cart Secure Key: ' . $cart->secure_key, 'error');
                                } else {
                                    $secure_key = $customer->secure_key;
                                }
                            }

                            $module_name = $this->payplug->displayName;
                            if ($is_oney) {
                                switch ($payment->payment_method['type']) {
                                    case 'oney_x3_with_fees' :
                                    case 'oney_x3_without_fees' :
                                        $module_name = $this->payplug->l('Oney 3x');
                                        break;
                                    case 'oney_x4_with_fees' :
                                    case 'oney_x4_without_fees' :
                                        $module_name = $this->payplug->l('Oney 4x');
                                        break;
                                    default:
                                        break;
                                }
                            }

                            try {
                                $cart_amount = (float)$cart->getOrderTotal(true, Cart::BOTH);

                                if ($amount != $cart_amount) {
                                    $this->logger->addLog('Cart amount is different and may occured an error');
                                    $this->logger->addLog('Cart amount:' . $cart_amount, 'info');
                                }

                                $this->logger->addLog('Order create with amount:' . $amount);
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
                            } catch (Exception $exception) {
                                $this->logger->addLog('Order cannot be validated: ' . $exception->getMessage(), 'error');
                                $this->response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                if (!PayplugLock::deleteLockG2($cart->id)) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                header(
                                    $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                    . $exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(Tools::jsonEncode($this->response));
                            }
                            if (!$is_order_validated) {
                                $this->logger->addLog('Order cannot be validated.', 'error');
                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                                } else {
                                    $this->logger->addLog('Lock deleted.', 'debug');
                                }
                                die;
                            } else {
                                $this->logger->addLog('Order validated.');
                                $id_order = Order::getOrderByCartId($cart->id);
                                $order = new Order($id_order);
                                if (!Validate::isLoadedObject($order)) {
                                    $this->logger->addLog('Order cannot be loaded.', 'error');
                                    if (!PayplugLock::deleteLockG2($cart->id)) {
                                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->logger->addLog('Lock deleted.', 'debug');
                                    }
                                    die;
                                } else {
                                    if ($deferred && count($order->getOrderPayments()) == 0) {
                                        $this->logger->addLog('Add new orderPayment for deferred - ' . count($order->getOrderPayments()), 'debug');
                                        $order->addOrderPayment($payment->amount / 100, null, $payment->id);
                                    }
                                    $this->logger->addLog('Order loaded.', 'debug');
                                    if (!$this->payplug->addPayplugOrderPayment($id_order, $payment->id)) {
                                        $this->logger->addLog(
                                            'IPN Failed: unable to create order payment.',
                                            'error'
                                        );
                                        $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                        if (!$cart_unlock) {
                                            $this->logger->addLog(
                                                'Lock cannot be deleted.',
                                                'error'
                                            );
                                        } else {
                                            $this->logger->addLog('Lock deleted.', 'debug');
                                        }
                                        die;
                                    } else {
                                        $this->logger->addLog('Order payment created.');
                                    }
                                }

                                if ($this->resource->installment_plan_id != null) {
                                    $this->payplug->addPayplugInstallment($this->resource->installment_plan_id, $order);
                                }

                                $data = array();
                                $data['metadata'] = $meta;
                                $data['metadata']['Order'] = $id_order;
                                try {
                                    $this->logger->addLog('Payment patched.', 'debug');
                                    $this->payplug->patchPayment($this->api_key, $payment->id, $data);
                                } catch (Exception $exception) {
                                    $this->logger->addLog(
                                        'Payment cannot be patched: ' . $exception->getMessage(), 'error');
                                    $this->response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' '
                                        . $exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(Tools::jsonEncode($this->response));
                                }
                            }
                        }

                        $this->logger->addLog('Checking number of order passed with this id_cart');
                        $req_nb_orders = '
                                SELECT o.* 
                                FROM ' . _DB_PREFIX_ . 'orders o 
                                WHERE o.id_cart = ' . $cart->id;
                        $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                        if (!$res_nb_orders) {
                            $this->logger->addLog(
                                'No order can be found using id_cart ' . (int)$cart->id,
                                'error'
                            );
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' 500 No order can be found using id_cart '
                                . (int)$cart->id,
                                true,
                                500
                            );
                            die;
                        } elseif (count($res_nb_orders) > 1) {
                            $this->logger->addLog(
                                'There is more than one order using id_cart ' . (int)$cart->id,
                                'error'
                            );
                            foreach ($res_nb_orders as $o) {
                                $this->logger->addLog('Order ID : ' . $o['id_order'], 'debug');
                            }
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' 500 There is more than one order using id_cart '
                                . (int)$cart->id,
                                true,
                                500
                            );
                            die;
                        } else {
                            $this->logger->addLog('OK');
                            $id_order = (int)$res_nb_orders[0]['id_order'];
                        }

                        $this->logger->addLog('Checking number of transaction validated for this order');
                        $has_payment = true;
                        $req_order_payment = '
                                SELECT pop.* 
                                FROM ' . _DB_PREFIX_ . 'payplug_order_payment pop  
                                WHERE pop.id_order = ' . (int)$id_order;
                        $payments = Db::getInstance()->executeS($req_order_payment);
                        if (!$payments) {
                            $has_payment = false;
                        }
                        if (!$has_payment) {
                            $this->logger->addLog(
                                'No transaction can be found using id_order ' . (int)$id_order,
                                'error'
                            );
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' 500 No transaction can be found using id_order '
                                . (int)$id_order,
                                true,
                                500
                            );
                            die;
                        } elseif (count($payments) > 1) {
                            $this->logger->addLog(
                                'There is more than one transaction using id_order ' . (int)$id_order,
                                'error'
                            );
                            if (!PayplugLock::deleteLockG2($cart->id)) {
                                $this->logger->addLog('Lock cannot be deleted.', 'error');
                            } else {
                                $this->logger->addLog('Lock deleted.', 'debug');
                            }
                            header(
                                $_SERVER['SERVER_PROTOCOL'] . ' 500 There is more than one transaction using id_order '
                                . (int)$id_order,
                                true,
                                500
                            );
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
    }

    private function treatRefund()
    {
        $this->logger->addLog('REFUND MODE');
        $this->logger->addLog('Refund ID : ' . $this->resource->id);
        $refund = $this->resource;

        //Refund treatment
        try {
            $payment = $this->payplug->retrievePayment($refund->payment_id);
        } catch (ConfigurationNotSetException $exception) {
            $this->logger->addLog('Payment cannot be retrieved: ' . $exception->getMessage(), 'error');
            $this->response = array(
                'exception' => $exception->getMessage(),
            );
            header(
                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                true,
                $exception->getCode()
            );
            die(Tools::jsonEncode($this->response));
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

            $cart_id = (int)$meta['Cart'];
            $id_order = (int)Order::getOrderByCartId($cart_id);
            $order = new Order($id_order);
            $this->logger->addLog('Order ID : ' . $id_order);
            if (!Validate::isLoadedObject($order)) {
                $this->logger->addLog('Order cannot be loaded.', 'error');
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.', true, 500);
                die;
            } else {
                $state_addons = ($payment->is_live ? '' : '_TEST');
                $new_order_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
                $current_state = $order->getCurrentState();

                if ($current_state != $new_order_state) {
                    $this->logger->addLog('Changing status to \'refunded\'');
                    $order_history = new OrderHistory();
                    $order_history->id_order = $id_order;
                    try {
                        $order_history->changeIdOrderState((int)$new_order_state, $id_order);
                        $order_history->save();
                    } catch (Exception $exception) {
                        $this->logger->addLog('Order history cannot be saved: ' . $exception->getMessage(),
                            'error');
                        $this->logger->addLog(
                            'Please check if order state ' . (int)$new_order_state . ' exists.', 'error');
                        $this->response = array(
                            'exception' => $exception->getMessage(),
                        );
                        header(
                            $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                            true,
                            $exception->getCode()
                        );
                        die(Tools::jsonEncode($this->response));
                    }
                } else {
                    $this->logger->addLog('Order status is already \'refunded\'');
                }
            }
        } else {
            $this->logger->addLog('PARTIAL REFUND');
        }
    }

    private function treatInstallment()
    {
        $this->logger->addLog('INSTALLMENT MODE');
        $this->logger->addLog('Installment ID: ' . $this->resource->id);
        $this->logger->addLog('Active : ' . (int)$this->resource->is_active);
    }

    /**
     * @param $id_cart
     */
    protected function setContextFromCartID($id_cart) {
        Context::getContext()->cart = new Cart((int) $id_cart);
        $address = new Address((int) Context::getContext()->cart->id_address_invoice);
        Context::getContext()->country = new Country((int) $address->id_country);
        Context::getContext()->customer = new Customer((int) Context::getContext()->cart->id_customer);
        Context::getContext()->language = new Language((int) Context::getContext()->cart->id_lang);
        Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
        if (isset(Context::getContext()->cart->id_shop)) {
            Context::getContext()->shop = new Shop(Context::getContext()->cart->id_shop);
        }
    }
}
