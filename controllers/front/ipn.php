<?php
/**
 * 2013 - 2018 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2017 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugIPNModuleFrontController extends ModuleFrontController
{
    public function addLog($debug, $log, $str, $level)
    {
        $debugBacktrace = debug_backtrace();
        $line_n = $debugBacktrace[0]['line'];
        if ($debug) {
            $log->$level($str, '--', $line_n);
        }
        return($str);
    }

    public function postProcess()
    {
        //Inclusions
        require_once(dirname(__FILE__).'/../../../../config/config.inc.php');
        require_once(_PS_MODULE_DIR_.'../init.php');
        require_once(_PS_MODULE_DIR_.'payplug/payplug.php');
        require_once(_PS_MODULE_DIR_.'payplug/classes/PayplugLock.php');
        require_once(_PS_MODULE_DIR_.'payplug/lib/init.php');

        //Settings
        $debug = Configuration::get('PAYPLUG_DEBUG_MODE');

        if ($debug) {
            require_once(dirname(__FILE__).'/../../classes/MyLogPHP.class.php');
            $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/ipn-'.date("Y-m-d").'.csv');
            $log->info('---------------- NEW IPN RECEIVED ----------------');
        }

        $payplug = new Payplug();
        $body = Tools::file_get_contents('php://input');

        //Notification identification
        if (Tools::isSubmit('debug')) {
            if (Tools::getValue('debug') == 1) {
                $this->addLog($debug, $log, 'DEBUG MODE', 'info');
                $cid = (int)Configuration::get('PAYPLUG_COMPANY_ID');
                if (Tools::getValue('cid') == $cid) {
                    $modules = Module::getModulesOnDisk();
                    $mod_tab = array();
                    foreach ($modules as $mod) {
                        if ($mod->active == 1) {
                            $mod_tab[] = $mod->name;
                        }
                    }
                    $response = array(
                        'is_module_active' => (int)$payplug->active,
                        'sandbox_mode' => (int)Configuration::get('PAYPLUG_SANDBOX_MODE'),
                        'embedded_mode' => (int)Configuration::get('PAYPLUG_EMBEDDED_MODE'),
                        'one_click' => (int)Configuration::get('PAYPLUG_ONE_CLICK'),
                        'cid' => Configuration::get('PAYPLUG_COMPANY_ID'),
                        'module_list' => $mod_tab,
                    );
                    die(json_encode($response));
                } else {
                    header(
                        $_SERVER['SERVER_PROTOCOL'].' 503 Access not granted.',
                        true,
                        503
                    );
                    die(json_encode($this->addLog($debug, $log, 'KO: Access not granted.', 'error')));
                }
            } else {
                header(
                    $_SERVER['SERVER_PROTOCOL'].' 503 Access not granted.',
                    true,
                    503
                );
                die(json_encode($this->addLog($debug, $log, 'KO: Access not granted.', 'error')));
            }
        } else {
            $this->addLog($debug, $log, 'NOTIFICATION MODE', 'info');
            $this->addLog($debug, $log, 'Notification treatment and authenticity verification:', 'info');
            try {
                $resource = \Payplug\Notification::treat($body);
            } catch (\Payplug\Exception\UnknownAPIResourceException $exception) {
                $this->addLog($debug, $log, 'KO: '.$exception->getMessage(), 'error');
                $response = array(
                    'exception' => $exception->getMessage(),
                );
                header(
                    $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                    true,
                    $exception->getCode()
                );
                die(json_encode($response));
            }
            if ($resource instanceof \Payplug\Resource\Payment) {
                $this->addLog($debug, $log, 'PAYMENT MODE', 'info');
                $this->addLog($debug, $log, 'Payment ID: '.$resource->id, 'info');
                $this->addLog($debug, $log, 'Paid: '.(int)$resource->is_paid, 'info');
                if (!$resource->is_paid) {
                    $this->addLog($debug, $log, 'The transaction is not paid yet.', 'info');
                    $this->addLog($debug, $log, 'No action will be done.', 'info');
                    header($_SERVER['SERVER_PROTOCOL'].' 200 The transaction is not paid.', true, 200);
                    die;
                } else {
                    $this->addLog($debug, $log, 'The transaction is paid.', 'info');
                    $payment = $resource;
                    $this->addLog($debug, $log, 'Payment details:', 'info');
                    $this->addLog($debug, $log, 'Cart ID: '.(int)$payment->metadata['Cart'], 'debug');
                    $this->addLog($debug, $log, 'Is Live: '.(int)$payment->is_live, 'debug');
                    $this->addLog($debug, $log, 'Amount: '.(int)$payment->amount, 'debug');

                    //Payment treatment
                    try {
                        $cart = new Cart((int)$payment->metadata['ID Cart']);
                    } catch (Exception $exception) {
                        $this->addLog($debug, $log, 'The cart cannot be loaded: '.$exception->getMessage(), 'error');
                        $response = array(
                            'exception' => $exception->getMessage(),
                        );
                        header(
                            $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                            true,
                            $exception->getCode()
                        );
                        die(json_encode($response));
                    }
                    if (!Validate::isLoadedObject($cart)) {
                        $this->addLog($debug, $log, 'The cart cannot be loaded.', 'error');
                        header($_SERVER['SERVER_PROTOCOL'].' 500 The cart cannot be loaded.', true, 500);
                        die;
                    } else {
                        try {
                            $address = new Address((int)$cart->id_address_invoice);
                        } catch (Exception $exception) {
                            $this->addLog($debug, $log, 'The address cannot be loaded: '.$exception->getMessage(), 'error');
                            $response = array(
                                'exception' => $exception->getMessage(),
                            );
                            header(
                                $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                true,
                                $exception->getCode()
                            );
                            die(json_encode($response));
                        }
                        if (!Validate::isLoadedObject($address)) {
                            $this->addLog($debug, $log, 'The address cannot be loaded.', 'error');
                            header($_SERVER['SERVER_PROTOCOL'].' 500 The address cannot be loaded.', true, 500);
                            die;
                        } else {
                            $this->addLog($debug, $log, 'Lock checking start.', 'debug');
                            PayplugLock::check($cart->id);
                            $this->addLog($debug, $log, 'Lock checking end.', 'debug');

                            $cart_lock = PayplugLock::createLockG2($cart->id, 'ipn');
                            if (!$cart_lock) {
                                $this->addLog($debug, $log, 'Lock cannot be created.', 'error');
                            } else {
                                $this->addLog($debug, $log, 'Lock created.', 'debug');
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
                            $pending_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING'.$state_addons);
                            $paid_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID'.$state_addons);
                            $error_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR'.$state_addons);

                            if ($order_id) {
                                $this->addLog($debug, $log, 'UPDATE MODE', 'info');
                                try {
                                    $order = new Order((int)$order_id);
                                } catch (Exception $exception) {
                                    $this->addLog($debug, $log, 'The order cannot be loaded: '.$exception->getMessage(), 'error');
                                    $response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(json_encode($response));
                                }
                                if (!Validate::isLoadedObject($order)) {
                                    echo $this->addLog($debug, $log, 'Order cannot be loaded.', 'error');
                                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                    if (!$cart_unlock) {
                                        $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                    }
                                    header($_SERVER['SERVER_PROTOCOL'].' 500 Order cannot be loaded.', true, 500);
                                    die;
                                } else {
                                    try {
                                        $current_state = (int)$order->getCurrentState();
                                    } catch (Exception $exception) {
                                        $this->addLog($debug, $log, 'The current state cannot be loaded: '.$exception->getMessage(), 'error');
                                        $response = array(
                                            'exception' => $exception->getMessage(),
                                        );
                                        header(
                                            $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                            true,
                                            $exception->getCode()
                                        );
                                        die(json_encode($response));
                                    }
                                    if ($current_state == $pending_state) {
                                        $this->addLog($debug, $log, 'Order is currently pending.', 'info');
                                        $this->addLog($debug, $log, 'Payment amount: '.$payment->amount, 'debug');
                                        if (PayPlug::checkAmountPaidIsCorrect($payment->amount / 100, $order)) {
                                            $this->addLog($debug, $log, 'Order new status will be \'paid\'.', 'info');
                                            $new_order_state = $paid_state;
                                        } else {
                                            $this->addLog($debug, $log, 'Payment amount is not correct.', 'error');
                                            $new_order_state = $error_state;
                                            $this->addLog($debug, $log, 'Order new status will be \'error\'.', 'error');
                                            $message = new Message();
                                            $message->message = $payplug->l('The amount collected by PayPlug is not the same')
                                                .$payplug->l(' as the total value of the order');
                                            $message->id_order = $order->id;
                                            $message->id_cart = $order->id_cart;
                                            $message->private = true;
                                            try {
                                                $message->save();
                                            } catch (Exception $exception) {
                                                $this->addLog($debug, $log, 'The message cannot be saved: '.$exception->getMessage(), 'error');
                                                $response = array(
                                                    'exception' => $exception->getMessage(),
                                                );
                                                header(
                                                    $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                                    true,
                                                    $exception->getCode()
                                                );
                                                die(json_encode($response));
                                            }
                                        }

                                        $order_history = new OrderHistory();
                                        $order_history->id_order = (int)$order_id;
                                        try {
                                            $order_history->changeIdOrderState((int)$new_order_state, $order_id);
                                            $order_history->save();
                                        } catch (Exception $exception) {
                                            $this->addLog($debug, $log, 'Order history cannot be saved: '.$exception->getMessage(), 'error');
                                            $this->addLog($debug, $log, 'Please check if order state '.(int)$new_order_state.' exists.', 'error');
                                            $response = array(
                                                'exception' => $exception->getMessage(),
                                            );
                                            header(
                                                $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
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
                                            $this->addLog($debug, $log, 'Order cannot be updated: '.$exception->getMessage(), 'error');
                                            $response = array(
                                                'exception' => $exception->getMessage(),
                                            );
                                            header(
                                                $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                                true,
                                                $exception->getCode()
                                            );
                                            die(json_encode($response));
                                        }
                                        echo $this->addLog($debug, $log, 'Order updated.', 'info');
                                        $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                        if (!$cart_unlock) {
                                            $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                        } else {
                                            $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                        }
                                        header($_SERVER['SERVER_PROTOCOL'].' 200 Order updated.', true, 200);
                                        die;
                                    } elseif ($current_state == $paid_state) {
                                        echo $this->addLog($debug, $log, 'Order is already paid.', 'info');
                                        $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                        if (!$cart_unlock) {
                                            $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                        } else {
                                            $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                        }
                                        header($_SERVER['SERVER_PROTOCOL'].' 200 Order is already paid.', true, 200);
                                        die;
                                    } else {
                                        echo $this->addLog(
                                            $debug,
                                            $log,
                                            'Current state: '.(int)$current_state,
                                            'debug'
                                        );
                                        echo $this->addLog(
                                            $debug,
                                            $log,
                                            'Pending state: '.(int)$pending_state,
                                            'debug'
                                        );
                                        echo $this->addLog(
                                            $debug,
                                            $log,
                                            'Paid state: '.(int)$paid_state,
                                            'debug'
                                        );
                                        echo $this->addLog($debug, $log, 'Current order state is in conflict with IPN.', 'error');
                                        $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                        if (!$cart_unlock) {
                                            $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                        } else {
                                            $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                        }
                                        header($_SERVER['SERVER_PROTOCOL'].' 500 Current order state is in conflict with IPN.', true, 500);
                                        die('500 Current order state is in conflict with IPN.');
                                    }
                                }
                            } else {
                                $this->addLog($debug, $log, 'CREATE MODE', 'info');

                                $order_state = $paid_state;
                                $amount = (float)$payment->amount / 100;
                                $extra_vars = array(
                                    'transaction_id' => $payment->id
                                );
                                $currency = (int)$cart->id_currency;
                                try {
                                    $customer = new Customer((int)$cart->id_customer);
                                } catch (Exception $exception) {
                                    $this->addLog($debug, $log, 'Customer cannot be loaded: '.$exception->getMessage(), 'error');
                                    $response = array(
                                        'exception' => $exception->getMessage(),
                                    );
                                    header(
                                        $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                        true,
                                        $exception->getCode()
                                    );
                                    die(json_encode($response));
                                }
                                if (!Validate::isLoadedObject($customer)) {
                                    echo $this->addLog($debug, $log, 'Customer cannot be loaded.', 'error');
                                    header($_SERVER['SERVER_PROTOCOL'].' 500 Customer cannot be loaded.', true, 500);
                                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                    if (!$cart_unlock) {
                                        $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                    } else {
                                        $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                    }
                                    die;
                                } else {
                                    try {
                                        $is_order_validated = $payplug->validateOrder(
                                            $cart->id,
                                            $order_state,
                                            $amount,
                                            $payplug->displayName,
                                            null,
                                            $extra_vars,
                                            $currency,
                                            false,
                                            $customer->secure_key
                                        );
                                    } catch (Exception $exception) {
                                        $this->addLog($debug, $log, 'Order cannot be validated: '.$exception->getMessage(), 'error');
                                        $response = array(
                                            'exception' => $exception->getMessage(),
                                        );
                                        header(
                                            $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                            true,
                                            $exception->getCode()
                                        );
                                        die(json_encode($response));
                                    }
                                    if (!$is_order_validated) {
                                        echo $this->addLog($debug, $log, 'Order cannot be validated.', 'error');
                                        header($_SERVER['SERVER_PROTOCOL'].' 500 Order cannot be validated.', true, 500);
                                        $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                        if (!$cart_unlock) {
                                            $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                        } else {
                                            $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                        }
                                        die;
                                    } else {
                                        echo $this->addLog($debug, $log, 'Order validated.', 'info');
                                        $order_id = Order::getOrderByCartId($cart->id);
                                        $order = new Order($order_id);
                                        $api_key = Payplug::setAPIKey();
                                        $data = array();
                                        $data['metadata'] = $payment->metadata;
                                        $data['metadata']['Order'] = $order_id;
                                        try {
                                            $payplug->patchPayment($api_key, $payment->id, $data);
                                        } catch (Exception $exception) {
                                            $this->addLog($debug, $log, 'Payment cannot be patched: '.$exception->getMessage(), 'error');
                                            $response = array(
                                                'exception' => $exception->getMessage(),
                                            );
                                            header(
                                                $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                                true,
                                                $exception->getCode()
                                            );
                                            die(json_encode($response));
                                        }
                                        if (!Validate::isLoadedObject($order)) {
                                            echo $this->addLog($debug, $log, 'Order cannot be loaded.', 'error');
                                            header($_SERVER['SERVER_PROTOCOL'].' 500 Order cannot be loaded.', true, 500);
                                            $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                            if (!$cart_unlock) {
                                                $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                            } else {
                                                $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                            }
                                            die;
                                        } else {
                                            $order_payment = end($order->getOrderPayments());
                                            $order_payment->transaction_id = $extra_vars['transaction_id'];
                                            try {
                                                $order_payment->update();
                                            } catch (Exception $exception) {
                                                $this->addLog($debug, $log, 'Payment cannot be updated: '.$exception->getMessage(), 'error');
                                                $response = array(
                                                    'exception' => $exception->getMessage(),
                                                );
                                                header(
                                                    $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                                    true,
                                                    $exception->getCode()
                                                );
                                                die(json_encode($response));
                                            }
                                            $this->addLog($debug, $log, 'Transaction ID added.', 'info');
                                        }
                                    }
                                }

                                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                                if (!$cart_unlock) {
                                    $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                                } else {
                                    $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                                }

                                $this->addLog($debug, $log, 'Checking number of order passed with this id_cart', 'info');
                                $req_nb_orders = '
                                    SELECT o.* 
                                    FROM '._DB_PREFIX_.'orders o 
                                    WHERE o.id_cart = '.$cart->id;
                                $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                                if (!$res_nb_orders) {
                                    $this->addLog($debug, $log, 'No order can be found using id_cart '.(int)$cart->id, 'error');
                                    header($_SERVER['SERVER_PROTOCOL'].' 500 No order can be found using id_cart '.(int)$cart->id, true, 500);
                                    die;
                                } elseif (count($res_nb_orders) > 1) {
                                    $this->addLog($debug, $log, 'There is more than one order using id_cart '.(int)$cart->id, 'error');
                                    foreach ($res_nb_orders as $o) {
                                        $this->addLog($debug, $log, 'Order ID : '.$o['id_order'], 'debug');
                                    }
                                    header($_SERVER['SERVER_PROTOCOL'].' 500 There is more than one order using id_cart '.(int)$cart->id, true, 500);
                                    die;
                                } else {
                                    $this->addLog($debug, $log, 'OK', 'info');
                                    $id_order = (int)$res_nb_orders[0]['id_order'];
                                }

                                $this->addLog($debug, $log, 'Checking number of transaction validated for this order', 'info');
                                $payments = $order->getOrderPaymentCollection();
                                if (!$payments) {
                                    $this->addLog($debug, $log, 'No transaction can be found using id_order '.(int)$id_order, 'error');
                                    header($_SERVER['SERVER_PROTOCOL'].' 500 No transaction can be found using id_order '.(int)$id_order, true, 500);
                                    die;
                                } elseif (count($payments) > 1) {
                                    $this->addLog($debug, $log, 'There is more than one transaction using id_order '.(int)$id_order, 'error');
                                    header($_SERVER['SERVER_PROTOCOL'].' 500 There is more than one transaction using id_order '.(int)$id_order, true, 500);
                                    die;
                                } else {
                                    $this->addLog($debug, $log, 'OK', 'info');
                                }

                                echo $this->addLog($debug, $log, 'Order created.', 'info');
                                header($_SERVER['SERVER_PROTOCOL'].' 200 Order created.', true, 200);
                                die;
                            }
                        }
                    }
                }
            } elseif ($resource instanceof \Payplug\Resource\Refund) {
                $this->addLog($debug, $log, 'REFUND MODE', 'info');
                $this->addLog($debug, $log, 'Refund ID : '.$resource->id, 'info');
                $refund = $resource;

                //Refund treatment
                try {
                    $payment = $payplug->retrievePayment($refund->payment_id);
                } catch (ConfigurationNotSetException $exception) {
                    $this->addLog($debug, $log, 'Payment cannot be retrieved: '.$exception->getMessage(), 'error');
                    $response = array(
                        'exception' => $exception->getMessage(),
                    );
                    header(
                        $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                        true,
                        $exception->getCode()
                    );
                    die(json_encode($response));
                }
                $is_totaly_refunded = $payment->is_refunded;
                if ($is_totaly_refunded) {
                    $this->addLog($debug, $log, 'TOTAL REFUND MODE', 'info');

                    $cart_id = (int)$payment->metadata['ID Cart'];
                    $order_id = (int)Order::getOrderByCartId($cart_id);
                    $order = new Order($order_id);
                    $this->addLog($debug, $log, 'Order ID : '.$order_id, 'info');
                    if (!Validate::isLoadedObject($order)) {
                        echo $this->addLog($debug, $log, 'Order cannot be loaded.', 'error');
                        header($_SERVER['SERVER_PROTOCOL'].' 500 Order cannot be loaded.', true, 500);
                        die;
                    } else {
                        $state_addons = ($payment->is_live ? '' : '_TEST');
                        $new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND'.$state_addons);
                        $current_state = $order->getCurrentState();

                        if ($current_state != $new_order_state) {
                            $this->addLog($debug, $log, 'Changing status to \'refunded\'', 'info');
                            $order_history = new OrderHistory();
                            $order_history->id_order = $order_id;
                            try {
                                $order_history->changeIdOrderState((int)$new_order_state, $order_id);
                                $order_history->save();
                            } catch (Exception $exception) {
                                $this->addLog($debug, $log, 'Order history cannot be saved: '.$exception->getMessage(), 'error');
                                $this->addLog($debug, $log, 'Please check if order state '.(int)$new_order_state.' exists.', 'error');
                                $response = array(
                                    'exception' => $exception->getMessage(),
                                );
                                header(
                                    $_SERVER['SERVER_PROTOCOL'].' '.$exception->getCode().' '.$exception->getMessage(),
                                    true,
                                    $exception->getCode()
                                );
                                die(json_encode($response));
                            }
                        } else {
                            $this->addLog($debug, $log, 'Order status is already \'refunded\'', 'info');
                        }
                    }
                } else {
                    $this->addLog($debug, $log, 'PARTIAL REFUND', 'info');
                }
            }
        }
    }
}
