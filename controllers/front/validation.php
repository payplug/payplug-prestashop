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

class PayplugValidationModuleFrontController extends ModuleFrontController
{
    public function addLog($debug, $log, $str, $level)
    {
        $debugBacktrace = debug_backtrace();
        $line_n = $debugBacktrace[0]['line'];
        if ($debug) {
            $log->$level($str, '--', $line_n);
        }
        return ($str);
    }

    public function postProcess()
    {
        //Inclusions
        require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
        require_once(_PS_MODULE_DIR_ . '../init.php');
        require_once(_PS_MODULE_DIR_ . 'payplug/payplug.php');
        require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayplugLock.php');
        require_once(_PS_MODULE_DIR_ . 'payplug/lib/init.php');


        //Settings
        $debug = Configuration::get('PAYPLUG_DEBUG_MODE');
        $type = 'payment';

        if ($debug) {
            require_once(dirname(__FILE__) . '/../../classes/MyLogPHP.class.php');
            $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/validation-' . date("Y-m-d") . '.csv');
            $log->info('Validation Starting.');
        } else {
            $log = false;
        }

        $payplug = new Payplug();

        $redirect_url_error = 'index.php?controller=order&step=1';
        $order_confirmation_url = 'index.php?controller=order-confirmation&';


        // Cancelling
        if (!($cart_id = Tools::getValue('cartid'))) {
            $this->addLog($debug, $log, 'No Cart ID.', 'error');
            Tools::redirect($redirect_url_error);
        } elseif (!($ps = Tools::getValue('ps')) || $ps != 1) {
            if ($ps == 2) {
                $this->addLog($debug, $log, 'Order has been cancelled on PayPlug page', 'info');
            } else {
                $this->addLog($debug, $log, 'Wrong GET parameter ps = ' . $ps, 'error');
            }
            Tools::redirect($redirect_url_error);
        }


        // Treatment
        $this->addLog($debug, $log, 'Cart ID : ' . (int)$cart_id, 'info');
        $cart = new Cart((int)$cart_id);
        if (!Validate::isLoadedObject($cart)) {
            $this->addLog($debug, $log, 'Cart cannot be loaded.', 'error');
            Tools::redirect($redirect_url_error);
        } else {
            if (!$pay_id = $payplug->getPaymentByCart((int)$cart_id)) {
                if (!$inst_id = $payplug->getInstallmentByCart((int)$cart_id)) {
                    $this->addLog($debug, $log, 'Payment is not stored or is already consumed.', 'error');
                    $id_order = Order::getOrderByCartId($cart->id);
                    $customer = new Customer((int)$cart->id_customer);
                    $link_redirect = __PS_BASE_URI__ . $order_confirmation_url . 'id_cart=' . $cart->id
                        . '&id_module=' . $payplug->id . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
                    Tools::redirect($link_redirect);
                } elseif ($inst_id = $payplug->getInstallmentByCart((int)$cart_id)) {
                    $this->addLog($debug, $log, 'Installment is not consumed yet.', 'info');
                    $amount = 0;
                    $pay_id = '';
                    $type = 'installment';
                    try {
                        $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
                        if (isset($installment->schedule)) {
                            foreach ($installment->schedule as $schedule) {
                                if (!empty($schedule->payment_ids)) {
                                    $amount = (int)$schedule->amount;
                                    $pay_id = $schedule->payment_ids[0];
                                    break;
                                }
                            }
                        }
                        $this->addLog($debug, $log, 'Retrieving installment...', 'info');
                        if ($installment->failure) {
                            $this->addLog($debug, $log, 'Installment failure : ' . $installment->failure->message,
                                'error');
                            Tools::redirect($redirect_url_error);
                        }
                    } catch (Exception $e) {
                        $this->addLog($debug, $log, 'Installment cannot be retrieved.', 'error');
                        Tools::redirect($redirect_url_error);
                    }
                }
            } else {
                $this->addLog($debug, $log, 'Payment is not consumed yet.', 'info');
                try {
                    $payment = \Payplug\Payment::retrieve($pay_id);
                    $this->addLog($debug, $log, 'Retrieving payment...', 'info');
                    if ($payment->failure) {
                        $this->addLog($debug, $log, 'Payment failure : ' . $payment->failure->message, 'error');
                        Tools::redirect($redirect_url_error);
                    }
                    $is_paid = $payment->is_paid;
                    $is_authorized = isset($payment->authorization->authorized_at) && $payment->authorization->authorized_at > 0;
                } catch (Exception $e) {
                    $this->addLog($debug, $log, 'Payment cannot be retrieved payment: ' . $pay_id, 'error');
                    Tools::redirect($redirect_url_error);
                }

                if ($payment->save_card == 1 || ($payment->card->id != '' && $payment->hosted_payment != '')) {
                    $this->addLog($debug, $log, 'Saving card...', 'info');
                    $res_payplug_card = $payplug->saveCard($payment);

                    if (!$res_payplug_card) {
                        $this->addLog($debug, $log, 'Card cannot be saved.', 'error');
                    }
                }
            }

            $customer = new Customer((int)$cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                $this->addLog($debug, $log, 'Customer cannot be loaded.', 'error');
                Tools::redirect($redirect_url_error);
            }

            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $this->addLog($debug, $log, 'Total : ' . $total, 'info');

            $this->addLog($debug, $log, 'Lock checking start.', 'debug');
            PayplugLock::check($cart->id);
            $this->addLog($debug, $log, 'Lock checking end.', 'debug');

            $cart_lock = PayplugLock::createLockG2($cart->id, 'validation');
            if (!$cart_lock) {
                $this->addLog($debug, $log, 'Lock cannot be created.', 'error');
            } else {
                $this->addLog($debug, $log, 'Lock created.', 'debug');
                switch ($cart_lock) {
                    case 'ipn':
                    case 'validation':
                        $id_order = false;
                        break;
                    default:
                        $id_order = (int)$cart_lock;
                }
            }

            $id_order = Order::getOrderByCartId($cart->id);

            if ($id_order) {
                $this->addLog($debug, $log, 'Order already exists.', 'info');

                if ($type == 'payment') {
                    $this->addLog($debug, $log, 'Deleting stored payment.', 'info');
                    if ($payplug->isTransactionPending((int)$cart_id)) {
                        $this->addLog($debug, $log, 'Transaction is pending so stored payment will not be deleted.',
                            'info');
                    }
                }
            } else {
                $this->addLog($debug, $log, 'Order does\'nt exists yet.', 'info');

                if ($type == 'payment') {
                    $state_addons = ($payment->is_live ? '' : '_TEST');
                } else {
                    $state_addons = ($installment->is_live ? '' : '_TEST');
                }

                $pending_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);
                $paid_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                /*
                * initialy, there was an order state for installment but no it has been removed and we use 'paid' state.
                * We keep this $inst_state to give more readability.
                */
                $inst_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                $auth_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_AUTH'.$state_addons);
                if ($type == 'installment') {
                    $installment = new PPPaymentInstallment($inst_id);
                    $first_payment = $installment->getFirstPayment();
                    if ($first_payment->isDeferred()) {
                        $order_state = $auth_state;
                    } else {
                        $order_state = $inst_state;
                    }
                } elseif ($is_paid) {
                    $order_state = $paid_state;
                } elseif ($is_authorized) {
                    $order_state = $auth_state;
                } else {
                    $order_state = $pending_state;
                    $this->addLog($debug, $log, 'Stored payment become pending.', 'info');
                    if (!$payplug->registerPendingTransaction((int)$cart_id)) {
                        $this->addLog($debug, $log, 'Stored payment cannot be pending.', 'error');
                    } else {
                        $this->addLog($debug, $log, 'Stored payment successfully set up to pending.', 'info');
                    }
                }
                $this->addLog($debug, $log, 'Order state will be :' . $order_state, 'info');

                if ($type == 'payment') {
                    $extra_vars = array(
                        'transaction_id' => $payment->id
                    );
                } elseif ($type == 'installment') {
                    $extra_vars = array(
                        'transaction_id' => $inst_id
                    );
                }
                /*
                 * For some reasons, secure key form cart can differ from secure key from customer
                 * Maybe due to migration or Prestashop's Update
                 */
                $secure_key = false;
                if (isset($customer->secure_key) && !empty($customer->secure_key)) {
                    if (isset($cart->secure_key) && !empty($cart->secure_key) && $cart->secure_key !== $customer->secure_key) {
                        $secure_key = $cart->secure_key;
                        $this->addLog($debug, $log, 'Secure keys do not match.', 'error');
                        $this->addLog($debug, $log, 'Customer Secure Key: ' . $customer->secure_key, 'error');
                        $this->addLog($debug, $log, 'Cart Secure Key: ' . $cart->secure_key, 'error');
                    } else {
                        $secure_key = $customer->secure_key;
                    }
                }

                $validateOrder_result = $payplug->validateOrder(
                    $cart->id,
                    $order_state,
                    $total,
                    $payplug->displayName,
                    false,
                    $extra_vars,
                    (int)$cart->id_currency,
                    false,
                    $secure_key
                );
                $id_order = $payplug->currentOrder;
                $order = new Order($id_order);

                if (!$validateOrder_result) {
                    $this->addLog($debug, $log, 'Order not validated', 'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                    } else {
                        $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                    }
                    Tools::redirect($redirect_url_error);
                } else {
                    $this->addLog($debug, $log, 'Order validated', 'info');
                    if ($type == 'payment') {
                        $api_key = Payplug::setAPIKey();
                        $data = array();
                        $data['metadata'] = $payment->metadata;
                        $data['metadata']['Order'] = $id_order;
                        $payplug->patchPayment($api_key, $payment->id, $data);
                    } elseif ($type == 'installment') {
                        $payplug->addPayplugInstallment($installment->resource, $order);
                    }
                }

                $this->addLog($debug, $log, 'Checking number of order passed with this id_cart...', 'info');
                $req_nb_orders = '
            SELECT o.* 
            FROM ' . _DB_PREFIX_ . 'orders o 
            WHERE o.id_cart = ' . $cart->id;
                $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                if (!$res_nb_orders) {
                    $this->addLog($debug, $log, 'No order can be found using id_cart ' . (int)$cart->id, 'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                    } else {
                        $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                    }
                    Tools::redirect($redirect_url_error);
                } elseif (count($res_nb_orders) > 1) {
                    $this->addLog($debug, $log, 'There is more than one order using id_cart ' . (int)$cart->id,
                        'error');
                    foreach ($res_nb_orders as $o) {
                        $this->addLog($debug, $log, 'Order ID : ' . $o['id_order'], 'debug');
                    }
                } else {
                    $this->addLog($debug, $log, 'Everything looks good.', 'info');
                }

                $this->addLog($debug, $log, 'Checking number of transaction validated for this order...', 'info');
                $order = new Order((int)$id_order);
                $payments = $order->getOrderPaymentCollection();

                if (!$payments) {
                    $this->addLog($debug, $log, 'No transaction can be found using id_order ' . (int)$id_order,
                        'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                    } else {
                        $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                    }
                    Tools::redirect($redirect_url_error);
                } elseif (count($payments) > 1) {
                    $this->addLog($debug, $log, 'There is more than one transaction using id_order ' . (int)$id_order,
                        'error');
                } else {
                    $this->addLog($debug, $log, 'Everything looks good.', 'info');
                }
            }

            $cart_unlock = PayplugLock::deleteLockG2($cart->id);
            if (!$cart_unlock) {
                $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
            } else {
                $this->addLog($debug, $log, 'Lock deleted.', 'debug');
            }

            $link_redirect = __PS_BASE_URI__ . $order_confirmation_url . 'id_cart=' . $cart->id . '&id_module=' . $payplug->id
                . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
            $this->addLog($debug, $log, 'Redirecting to :' . $link_redirect, 'info');
            Tools::redirect($link_redirect);
        }
    }
}
