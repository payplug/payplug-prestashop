<?php
/**
 * 2013 - 2019 PayPlug SAS
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
 * @copyright 2013 - 2019 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

//Inclusions
require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
require_once(_PS_MODULE_DIR_ . '../init.php');
require_once(_PS_MODULE_DIR_ . 'payplug/payplug.php');
require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayplugLock.php');
require_once(_PS_MODULE_DIR_ . 'payplug/lib/init.php');

class PayplugIPNModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool $debug
     */
    public $debug;
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

    public $notification;

    public $api_key;

    /**
     * @param $str
     * @param string $level
     * @return mixed
     */
    public function addLog($str, $level = 'info')
    {
        $this->debugBacktrace = debug_backtrace();
        $line_n = $this->debugBacktrace[0]['line'];
        if ($this->debug) {
            $this->log->$level($str, '--', $line_n);
        }
        return ($str);
    }

    /**
     * Set Config to process the notification
     * @throws Exception
     */
    private function setConfig()
    {
        $this->payplug = new Payplug();
        $this->notification->info('set configuration', '--', __LINE__);
        $this->debug = (int)Configuration::get('PAYPLUG_DEBUG_MODE');
        $this->getResource();
        $this->setLogger();

        //Notification identification
        $this->addLog('Notification treatment and authenticity verification:');

        $this->notification = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/notification-' . date('Y-m-d') . '.csv');

    }

    /**
     * Set the resource from the notification body
     */
    private function getResource()
    {
        $body = Tools::file_get_contents('php://input');

        $this->notification->info('get resource: ' . $body, '--', __LINE__);

        try {
            $resource = json_decode($body);
            $api_key = (bool)$resource->is_live ? Configuration::get('PAYPLUG_LIVE_API_KEY') : Configuration::get('PAYPLUG_TEST_API_KEY');
            $authentication = $this->payplug->setSecretKey($api_key);
            $this->notification->info('set api key: ' . $api_key, '--', __LINE__);
            $this->resource = \Payplug\Notification::treat($body,$authentication);
            $this->notification->info('resource id: ' . $this->resource->id, '--', __LINE__);
        } catch (Exception $exception) {
            $this->notification->error('An error occured while getting resource: ' .$exception->getMessage(), '--', __LINE__);
            header(
                $_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                true,
                $exception->getCode()
            );
            die(json_encode(['exception' => $exception->getMessage()]));
        }
    }

    /**
     * Set the log method
     */
    private function setLogger()
    {
    }

    /**
     * Process the notification
     * @throws Exception
     */
    public function postProcess()
    {
        //Settings
        $this->setConfig();

        $this->addLog('OK');

        if ($this->resource instanceof \Payplug\Resource\Payment) {
            $this->processPayment();
        } elseif ($this->resource instanceof \Payplug\Resource\Refund) {
            $this->processRefund();
        } elseif ($this->resource instanceof \Payplug\Resource\InstallmentPlan) {
            $this->processInstallment();
        }
    }

    /**
     * Process the notification as a payment
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processPayment()
    {

        if ($this->resource->installment_plan_id != null) {
        }




        try {
            $payment = $this->payplug->retrievePayment($this->resource->id);
        } catch (ConfigurationNotSetException $exception) {
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


        if ($payment->authorization && isset($payment->authorization->expires_at)) {
            $deferred = true;
            $is_expired = $payment->authorization->expires_at - time() <= 0;
        } else {
            $deferred = false;
            $is_expired = false;
        }

        if (!$payment->is_paid && !$deferred) {
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 The transaction is not paid.', true, 200);
            die;
        } else {
            if ($deferred) {
            } else {
            }

            if ($this->resource->installment_plan_id != null) {
                $installment = $this->payplug->retrieveInstallment($this->resource->installment_plan_id);
                $meta = $installment->metadata;
            } else {
                $meta = $payment->metadata;
            }

            //Payment treatment
            try {
                $cart = new Cart((int)$meta['ID Cart']);
            } catch (Exception $exception) {
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
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 The cart cannot be loaded.', true, 500);
                die;
            } else {
                try {
                    $address = new Address((int)$cart->id_address_invoice);
                } catch (Exception $exception) {
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
                if (!Validate::isLoadedObject($address)) {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 The address cannot be loaded.', true, 500);
                    die;
                } else {
                    PayplugLock::check($cart->id);

                    $cart_lock = PayplugLock::createLockG2($cart->id, 'ipn');
                    if (!$cart_lock) {
                    } else {
                        switch ($cart_lock) {
                            case 'ipn':
                            case 'validation':
                                $order_id = false;
                                break;
                            default:
                                $order_id = (int)$cart_lock;
                        }
                    }

                    $state_addons = ($payment->is_live ? '' : '_TEST');
                    $pending_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);
                    $paid_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                    $error_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR' . $state_addons);
                    /*
                    * initialy, there was an order state for installment but no it has been removed and we use 'paid' state.
                    * We keep this $inst_state to give more readability.
                    */
                    $inst_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                    $auth_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_AUTH' . $state_addons);
                    $exp_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_EXP' . $state_addons);
                    $refund_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);

                    if ($order_id) {
                        try {
                            $order = new Order((int)$order_id);
                        } catch (Exception $exception) {
                            $response = array(
                                'exception' => $exception->getMessage(),
                            );
                            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $exception->getCode() . ' ' . $exception->getMessage(),
                                true, $exception->getCode());
                            die(json_encode($response));
                        }
                        if (!Validate::isLoadedObject($order)) {
                            $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                            if (!$cart_unlock) {
                            } else {
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.', true, 500);
                            die;
                        } else {
                            try {
                                $current_state = (int)$order->getCurrentState();
                                echo $this->addLog('Get the current state: ' . $current_state, 'info');
                            } catch (Exception $exception) {
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

                            // if payment is deferred and expired
                            if ($deferred && $current_state == $auth_state && $is_expired) {
                                $new_order_state = $exp_state;

                                $order_history = new OrderHistory();
                                $order_history->id_order = (int)$order_id;
                                try {
                                    $order_history->changeIdOrderState((int)$new_order_state, $order_id);
                                    $order_history->save();
                                } catch (Exception $exception) {
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
                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                } else {
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                die;
                            } elseif ($current_state == $pending_state || $current_state == $auth_state) {
                                $is_amount_correct = false;
                                if ($payment->installment_plan_id !== null) {
                                    $is_amount_correct = (bool)$payment->is_paid;
                                } else {
                                    $is_amount_correct = (bool)PayPlug::checkAmountPaidIsCorrect($payment->amount / 100,
                                        $order);
                                }
                                if ($is_amount_correct) {
                                    $new_order_state = $paid_state;
                                } else {
                                    $new_order_state = $error_state;
                                    $message = new Message();
                                    $message->message = $this->payplug->l('The amount collected by PayPlug is not the same')
                                        . $this->payplug->l(' as the total value of the order');
                                    $message->id_order = $order->id;
                                    $message->id_cart = $order->id_cart;
                                    $message->private = true;
                                    try {
                                        $message->save();
                                    } catch (Exception $exception) {
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
                                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                    if (!$cart_unlock) {
                                    } else {
                                    }
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'] . ' 200 The payment is not paid yet.',
                                        true,
                                        200
                                    );
                                    die;
                                }

                                $order_history = new OrderHistory();
                                $order_history->id_order = (int)$order_id;
                                try {
                                    $order_history->changeIdOrderState((int)$new_order_state, $order_id);
                                    $order_history->save();
                                } catch (Exception $exception) {
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
                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                } else {
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                die;
                            } elseif ($current_state == $paid_state) {
                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                } else {
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order is already paid.', true, 200);
                                die;
                            } elseif ($installment = $this->payplug->retrieveInstallment($payment->installment_plan_id)) {
                                if ((int)$installment->is_fully_paid == 1) {
                                    header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                    die;
                                } else {
                                    header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order updated.', true, 200);
                                    die;
                                }
                                $this->payplug->updatePayplugInstallment($installment);
                            } elseif ($current_state == $refund_state) {
                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                } else {
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order has been refunded.', true, 200);
                                die;
                            } else {
                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                } else {
                                }
                                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Current order state is in conflict with IPN.',
                                    true, 500);
                                die('500 Current order state is in conflict with IPN.');
                            }
                        }
                    } else {
                        if ($this->resource->installment_plan_id != null) {
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
                        } else {
                            if ($deferred) {
                                $order_state = $auth_state;
                            } else {
                                $order_state = $paid_state;
                            }
                            $extra_vars = array(
                                'transaction_id' => $payment->id
                            );
                        }

                        $amount = (float)($cart->getOrderTotal(true, Cart::BOTH));
                        $currency = (int)$cart->id_currency;
                        try {
                            $customer = new Customer((int)$cart->id_customer);
                        } catch (Exception $exception) {
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
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 Customer cannot be loaded.', true, 500);
                            $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                            if (!$cart_unlock) {
                            } else {
                            }
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
                                } else {
                                    $secure_key = $customer->secure_key;
                                }
                            }

                            try {
                                $is_order_validated = $this->payplug->validateOrder(
                                    $cart->id,
                                    $order_state,
                                    $amount,
                                    $this->payplug->displayName,
                                    null,
                                    $extra_vars,
                                    $currency,
                                    false,
                                    $secure_key
                                );
                            } catch (Exception $exception) {
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
                                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be validated.', true,
                                    500);
                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                } else {
                                }
                                die;
                            } else {
                                $order_id = Order::getOrderByCartId($cart->id);
                                $order = new Order($order_id);

                                if ($this->resource->installment_plan_id != null) {
                                    $this->payplug->addPayplugInstallment($this->resource->installment_plan_id, $order);
                                }

                                $api_key = Payplug::setAPIKey();
                                $data = array();
                                $data['metadata'] = $meta;
                                $data['metadata']['Order'] = $order_id;
                                try {
                                    $this->payplug->patchPayment($api_key, $payment->id, $data);
                                } catch (Exception $exception) {
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
                                if (!Validate::isLoadedObject($order)) {
                                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.', true,
                                        500);
                                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                    if (!$cart_unlock) {
                                    } else {
                                    }
                                    die;
                                } else {
                                    if ($this->resource->installment_plan_id != null) {
                                        header($_SERVER['SERVER_PROTOCOL'] . ' 200 Installment correctly registered.',
                                            true, 200);
                                        die;
                                    } else {
                                        $order_payment = end($order->getOrderPayments());
                                        $order_payment->transaction_id = $extra_vars['transaction_id'];
                                        try {
                                            $order_payment->update();
                                        } catch (Exception $exception) {
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
                                }
                            }
                        }

                        $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                        if (!$cart_unlock) {
                        } else {
                        }

                        $req_nb_orders = '
                                SELECT o.* 
                                FROM ' . _DB_PREFIX_ . 'orders o 
                                WHERE o.id_cart = ' . $cart->id;
                        $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                        if (!$res_nb_orders) {
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 No order can be found using id_cart ' . (int)$cart->id,
                                true, 500);
                            die;
                        } elseif (count($res_nb_orders) > 1) {
                            foreach ($res_nb_orders as $o) {
                            }
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 There is more than one order using id_cart ' . (int)$cart->id,
                                true, 500);
                            die;
                        } else {
                            $id_order = (int)$res_nb_orders[0]['id_order'];
                        }

                        $payments = $order->getOrderPaymentCollection();
                        if (!$payments) {
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 No transaction can be found using id_order ' . (int)$id_order,
                                true, 500);
                            die;
                        } elseif (count($payments) > 1) {
                            header($_SERVER['SERVER_PROTOCOL'] . ' 500 There is more than one transaction using id_order ' . (int)$id_order,
                                true, 500);
                            die;
                        } else {
                        }

                        header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order created.', true, 200);
                        die;
                    }
                }
            }
        }
    }

    /**
     * Process the notification as a refund
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function processRefund()
    {
        $refund = $this->resource;

        //Refund treatment
        try {
            $payment = $this->payplug->retrievePayment($refund->payment_id);
        } catch (ConfigurationNotSetException $exception) {
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
            $cart_id = (int)$meta['ID Cart'];
            $order_id = (int)Order::getOrderByCartId($cart_id);
            $order = new Order($order_id);
            if (!Validate::isLoadedObject($order)) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Order cannot be loaded.', true, 500);
                die;
            } else {
                $state_addons = ($payment->is_live ? '' : '_TEST');
                $new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
                $current_state = $order->getCurrentState();

                if ($current_state != $new_order_state) {
                    $order_history = new OrderHistory();
                    $order_history->id_order = $order_id;
                    try {
                        $order_history->changeIdOrderState((int)$new_order_state, $order_id);
                        $order_history->save();
                        header($_SERVER['SERVER_PROTOCOL'] . ' 200 Changing status to \'refunded\'', true, 200);
                        die();
                    } catch (Exception $exception) {
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
                    header($_SERVER['SERVER_PROTOCOL'] . ' 200 Order status is already \'refunded\'', true, 200);
                    die();
                }
            }
        } else {
            header($_SERVER['SERVER_PROTOCOL'] . ' 200 The payment is partially refunded.', true, 200);
            die();
        }
    }

    /**
     * Process the notification as an installment plan
     */
    private function processInstallment()
    {
        header($_SERVER['SERVER_PROTOCOL'] . ' 200 installment resource receive.', true, 200);
        die();
    }
}
