<?php
/**
 * 2013 - 2017 PayPlug SAS
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

class PayplugValidationModuleFrontController extends ModuleFrontController
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
            $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/validation-'.date("Y-m-d").'.csv');
            $log->info('Validation Starting.');
        } else {
            $log = false;
        }

        $payplug = new Payplug();

        $redirect_url_error = 'index.php?controller=order&step=1';
        $order_confirmation_url = 'index.php?controller=order-confirmation&';


        //Cancelling
        /*
        if (!$payplug->active) {
            $this->addLog($debug, $log, 'Payplug module is not enabled.', 'error');
            Tools::redirect($redirect_url_error);
        } elseif (!($cart_id = Tools::getValue('cartid'))) {
        */
        if (!($cart_id = Tools::getValue('cartid'))) {
            $this->addLog($debug, $log, 'No Cart ID.', 'error');
            Tools::redirect($redirect_url_error);
        } elseif (!($ps = Tools::getValue('ps')) || $ps != 1) {
            if ($ps == 2) {
                $this->addLog($debug, $log, 'Order has been cancelled on PayPlug page', 'info');
            } else {
                $this->addLog($debug, $log, 'Wrong GET parameter ps = '.$ps, 'error');
            }
            Tools::redirect($redirect_url_error);
        }


        //Treatment
        $this->addLog($debug, $log, 'Cart ID : '.(int)$cart_id, 'info');
        $cart = new Cart((int)$cart_id);
        if (!Validate::isLoadedObject($cart)) {
            $this->addLog($debug, $log, 'Cart cannot be loaded.', 'error');
            Tools::redirect($redirect_url_error);
        } else {
            if (!$pay_id = $payplug->getPaymentByCart((int)$cart_id)) {
                $this->addLog($debug, $log, 'Payment is not stored or is already consumed.', 'error');
                $id_order = Order::getOrderByCartId($cart->id);
                $customer = new Customer((int)$cart->id_customer);
                $link_redirect = __PS_BASE_URI__.$order_confirmation_url.'id_cart='.$cart->id
                    .'&id_module='.$payplug->id.'&id_order='.$id_order.'&key='.$customer->secure_key;
                Tools::redirect($link_redirect);
            } else {
                $this->addLog($debug, $log, 'Payment is not consumed yet.', 'info');
                try {
                    $payment = \Payplug\Payment::retrieve($pay_id);
                    $this->addLog($debug, $log, 'Retrieving payment...', 'info');
                    if ($payment->failure) {
                        $this->addLog($debug, $log, 'Payment failure : '.$payment->failure->message, 'error');
                        Tools::redirect($redirect_url_error);
                    }
                    $is_paid = $payment->is_paid;
                } catch (Exception $e) {
                    $this->addLog($debug, $log, 'Payment cannot be retrieved.', 'error');
                    Tools::redirect($redirect_url_error);
                }
            }

            if ($payment->save_card == 1 || ($payment->card->id != '' && $payment->hosted_payment != '')) {
                $this->addLog($debug, $log, 'Saving card...', 'info');
                $res_payplug_card = $payplug->saveCard($payment);

                if (!$res_payplug_card) {
                    $this->addLog($debug, $log, 'Card cannot be saved.', 'error');
                }
            }

            $this->addLog($debug, $log, 'Deleting stored payment.', 'info');
            if (!$payplug->deletePayment($payment->id, (int)$cart_id)) {
                $this->addLog($debug, $log, 'Stored payment cannot be deleted.', 'error');
            } else {
                $this->addLog($debug, $log, 'Stored payment successfully deleted.', 'info');
            }

            $customer = new Customer((int)$cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                $this->addLog($debug, $log, 'Customer cannot be loaded.', 'error');
                Tools::redirect($redirect_url_error);
            }

            $total = (float)$cart->getOrderTotal(true, Cart::BOTH);
            $this->addLog($debug, $log, 'Total : '.$total, 'info');

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
            } else {
                $this->addLog($debug, $log, 'Order does\'nt exists yet.', 'info');

                $state_addons = ($payment->is_live ? '' : '_TEST');
                $pending_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING'.$state_addons);
                $paid_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID'.$state_addons);
                if ($is_paid) {
                    $order_state = $paid_state;
                } else {
                    $order_state = $pending_state;
                }
                $this->addLog($debug, $log, 'Order state will be :'.$order_state, 'info');

                $extra_vars = array(
                    'transaction_id' => $payment->id
                );

                $validateOrder_result = $payplug->validateOrder(
                    $cart->id,
                    $order_state,
                    $total,
                    $payplug->displayName,
                    false,
                    $extra_vars,
                    (int)$cart->id_currency,
                    false,
                    $customer->secure_key
                );
                $id_order = $payplug->currentOrder;

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
                }

                $this->addLog($debug, $log, 'Checking number of order passed with this id_cart...', 'info');
                $req_nb_orders = '
            SELECT o.* 
            FROM '._DB_PREFIX_.'orders o 
            WHERE o.id_cart = '.$cart->id;
                $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                if (!$res_nb_orders) {
                    $this->addLog($debug, $log, 'No order can be found using id_cart '.(int)$cart->id, 'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                    } else {
                        $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                    }
                    Tools::redirect($redirect_url_error);
                } elseif (count($res_nb_orders) > 1) {
                    $this->addLog($debug, $log, 'There is more than one order using id_cart '.(int)$cart->id, 'error');
                    foreach ($res_nb_orders as $o) {
                        $this->addLog($debug, $log, 'Order ID : '.$o['id_order'], 'debug');
                    }
                } else {
                    $this->addLog($debug, $log, 'Everything looks good.', 'info');
                }

                $this->addLog($debug, $log, 'Checking number of transaction validated for this order...', 'info');
                $order = new Order((int)$id_order);
                $payments = $order->getOrderPaymentCollection();

                if (!$payments) {
                    $this->addLog($debug, $log, 'No transaction can be found using id_order '.(int)$id_order, 'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->addLog($debug, $log, 'Lock cannot be deleted.', 'error');
                    } else {
                        $this->addLog($debug, $log, 'Lock deleted.', 'debug');
                    }
                    Tools::redirect($redirect_url_error);
                } elseif (count($payments) > 1) {
                    $this->addLog($debug, $log, 'There is more than one transaction using id_order '.(int)$id_order, 'error');
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

            $link_redirect = __PS_BASE_URI__.$order_confirmation_url.'id_cart='.$cart->id.'&id_module='.$payplug->id
                .'&id_order='.$id_order.'&key='.$customer->secure_key;
            $this->addLog($debug, $log, 'Redirecting to :'.$link_redirect, 'info');
            Tools::redirect($link_redirect);
        }
    }
}
