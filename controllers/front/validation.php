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
        return;
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
        } else {
            $log = false;
        }

        $payplug = new Payplug();

        $redirect_url_error = 'index.php?controller=order&step=1';
        $order_confirmation_url = 'index.php?controller=order-confirmation&';


        // Cancelling
        if (!($cart_id = Tools::getValue('cartid'))) {
            Tools::redirect($redirect_url_error);
        } elseif (!($ps = Tools::getValue('ps')) || $ps != 1) {
            if ($ps == 2) {
            } else {
            }
            Tools::redirect($redirect_url_error);
        }


        // Treatment
        $cart = new Cart((int)$cart_id);
        if (!Validate::isLoadedObject($cart)) {
            Tools::redirect($redirect_url_error);
        } else {
            if (!$pay_id = $payplug->getPaymentByCart((int)$cart_id)) {
                if (!$inst_id = $payplug->getInstallmentByCart((int)$cart_id)) {
                    $id_order = Order::getOrderByCartId($cart->id);
                    $customer = new Customer((int)$cart->id_customer);
                    $link_redirect = __PS_BASE_URI__ . $order_confirmation_url . 'id_cart=' . $cart->id
                        . '&id_module=' . $payplug->id . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
                    Tools::redirect($link_redirect);
                } elseif ($inst_id = $payplug->getInstallmentByCart((int)$cart_id)) {
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
                        if ($installment->failure) {
                            Tools::redirect($redirect_url_error);
                        }
                    } catch (Exception $e) {
                        Tools::redirect($redirect_url_error);
                    }
                }
            } else {
                try {
                    $payment = \Payplug\Payment::retrieve($pay_id);
                    if ($payment->failure) {
                        Tools::redirect($redirect_url_error);
                    }
                    $is_paid = $payment->is_paid;
                    $is_authorized = isset($payment->authorization->authorized_at) && $payment->authorization->authorized_at > 0;
                } catch (Exception $e) {
                    Tools::redirect($redirect_url_error);
                }

                if ($payment->save_card == 1 || ($payment->card->id != '' && $payment->hosted_payment != '')) {
                    $res_payplug_card = $payplug->saveCard($payment);

                    if (!$res_payplug_card) {
                    }
                }
            }

            $customer = new Customer((int)$cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                Tools::redirect($redirect_url_error);
            }

            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

            PayplugLock::check($cart->id);

            $cart_lock = PayplugLock::createLockG2($cart->id, 'validation');
            if (!$cart_lock) {
            } else {
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

                if ($type == 'payment') {
                    if ($payplug->isTransactionPending((int)$cart_id)) {
                    }
                }
            } else {

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
                    if (!$payplug->registerPendingTransaction((int)$cart_id)) {
                    } else {
                    }
                }

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
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                    } else {
                    }
                    Tools::redirect($redirect_url_error);
                } else {
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

                $req_nb_orders = '
            SELECT o.* 
            FROM ' . _DB_PREFIX_ . 'orders o 
            WHERE o.id_cart = ' . $cart->id;
                $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                if (!$res_nb_orders) {
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                    } else {
                    }
                    Tools::redirect($redirect_url_error);
                } elseif (count($res_nb_orders) > 1) {
                    foreach ($res_nb_orders as $o) {
                    }
                } else {
                }

                $order = new Order((int)$id_order);
                $payments = $order->getOrderPaymentCollection();

                if (!$payments) {
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                    } else {
                    }
                    Tools::redirect($redirect_url_error);
                } elseif (count($payments) > 1) {
                } else {
                }
            }

            $cart_unlock = PayplugLock::deleteLockG2($cart->id);
            if (!$cart_unlock) {
            } else {
            }

            $link_redirect = __PS_BASE_URI__ . $order_confirmation_url . 'id_cart=' . $cart->id . '&id_module=' . $payplug->id
                . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
            Tools::redirect($link_redirect);
        }
    }
}
