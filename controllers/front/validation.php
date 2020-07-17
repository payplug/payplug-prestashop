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

class PayplugValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @var bool $debug
     */
    public $debug;
    /**
     * @var object $log
     */
    public $log;
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

    public $url = [];

    public $type;

    public $logger;

    /**
     * Set Config to process the notification
     * @throws Exception
     */
    private function setConfig()
    {
        $this->payplug = new Payplug();
        $this->debug = (int)Configuration::get('PAYPLUG_DEBUG_MODE');
        $this->type = 'payment';
        $this->setLogger();

        $this->url = [
            'error' => 'index.php?controller=order&step=1',
            'valid' => 'index.php?controller=order-confirmation&',
        ];
    }

    /**
     * Set the log method
     */
    private function setLogger()
    {
        $this->logger = new PayPlugLogger('validation');
        $this->logger->addLog('New validation');
    }

    public function postProcess()
    {
        //Settings
        $this->setConfig();

        $this->logger->addLog('OK');


        // Cancelling
        if (!($cart_id = Tools::getValue('cartid'))) {
            $this->logger->addLog('No Cart ID.', 'error');
            Tools::redirect($this->url['error']);
        } elseif (!($ps = Tools::getValue('ps')) || $ps != 1) {
            if ($ps == 2) {
                $this->logger->addLog('Order has been cancelled on PayPlug page', 'info');
            } else {
                $this->logger->addLog('Wrong GET parameter ps = ' . $ps, 'error');
            }
            Tools::redirect($this->url['error']);
        }


        // Treatment
        $this->logger->addLog('Cart ID : ' . (int)$cart_id, 'info');
        $cart = new Cart((int)$cart_id);
        if (!Validate::isLoadedObject($cart)) {
            $this->logger->addLog('Cart cannot be loaded.', 'error');
            Tools::redirect($this->url['error']);
        } else {
            $amount = 0;
            if (!$pay_id = $this->payplug->getPaymentByCart((int)$cart_id)) {
                if (!$inst_id = $this->payplug->getInstallmentByCart((int)$cart_id)) {
                    $this->logger->addLog('Payment is not stored or is already consumed.', 'error');
                    $id_order = Order::getIdByCartId($cart->id);
                    $customer = new Customer((int)$cart->id_customer);
                    $link_redirect = __PS_BASE_URI__ . $this->url['valid'] . 'id_cart=' . $cart->id
                        . '&id_module=' . $this->payplug->id . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
                    Tools::redirect($link_redirect);
                } elseif ($inst_id = $this->payplug->getInstallmentByCart((int)$cart_id)) {
                    $this->logger->addLog('Installment is not consumed yet.', 'info');
                    $pay_id = false;
                    $this->type = 'installment';
                    try {
                        $this->logger->addLog('Retrieving installment...', 'info');
                        $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
                        $this->logger->addLog('Current amount: ' . $amount, 'info');
                        $pay_id = false;
                        if (isset($installment->schedule)) {
                            foreach ($installment->schedule as $k => $schedule) {
                                $schedule_amount = (int)$schedule->amount;
                                $this->logger->addLog('Schedule n.' . $k . ': ' . $schedule_amount, 'info');
                                $amount += $schedule_amount;
                                $this->logger->addLog('Current amount: ' . $amount, 'info');
                                if ($pay_id) {
                                    continue;
                                }
                                $pay_id = $schedule->payment_ids[0];
                            }
                        }
                        if ($installment->failure) {
                            $this->logger->addLog('Installment failure : ' . $installment->failure->message,
                                'error');
                            Tools::redirect($this->url['error']);
                        }
                    } catch (Exception $e) {
                        $this->logger->addLog('Installment cannot be retrieved.', 'error');
                        Tools::redirect($this->url['error']);
                    }
                }
            } else {
                $this->logger->addLog('Payment is not consumed yet.', 'info');
                try {
                    $payment = \Payplug\Payment::retrieve($pay_id);
                    $this->logger->addLog('Retrieving payment...', 'info');
                    if ($payment->failure) {
                        $this->logger->addLog('Payment failure : ' . $payment->failure->message, 'error');
                        Tools::redirect($this->url['error']);
                    }
                    $is_paid = $payment->is_paid;

                    $oney_payment_methods = ['oney_x3_with_fees', 'oney_x4_with_fees'];
                    $is_oney = isset($payment->payment_method) && isset($payment->payment_method['type']) && in_array($payment->payment_method['type'],
                            $oney_payment_methods);
                    $is_authorized = isset($payment->authorization) && isset($payment->authorization->authorized_at);

                    $amount = (int)$payment->amount;
                } catch (Exception $e) {
                    $this->logger->addLog('Payment cannot be retrieved payment: ' . $pay_id, 'error');
                    Tools::redirect($this->url['error']);
                }

                if ($payment->save_card == 1 || ($payment->card->id != '' && $payment->hosted_payment != '')) {
                    $this->logger->addLog('Saving card...', 'info');
                    $res_payplug_card = $this->payplug->saveCard($payment);

                    if (!$res_payplug_card) {
                        $this->logger->addLog('Card cannot be saved.', 'error');
                    }
                }
            }

            $amount = (float)($amount / 100);

            $customer = new Customer((int)$cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                $this->logger->addLog('Customer cannot be loaded.', 'error');
                Tools::redirect($this->url['error']);
            }

            $this->logger->addLog('Total : ' . $amount, 'info');

            $cart_lock = false;
            do {
                $cart_lock = PayplugLock::createLockG2($cart->id, 'ipn');
                if (!$cart_lock) {
                    PayplugLock::check($cart->id);
                } else {
                    $this->logger->addLog('Lock created');
                }
            } while (!$cart_lock);

            $id_order = Order::getOrderByCartId($cart->id);

            if ($id_order) {
                $this->logger->addLog('Order already exists.', 'info');

                if ($this->type == 'payment') {
                    $this->logger->addLog('Deleting stored payment.', 'info');
                    if ($this->payplug->isTransactionPending((int)$cart_id)) {
                        $this->logger->addLog('Transaction is pending so stored payment will not be deleted.',
                            'info');
                    }
                }
            } else {
                $this->logger->addLog('Order does\'nt exists yet.', 'info');

                if ($this->type == 'payment') {
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
                $auth_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_AUTH' . $state_addons);
                $oney_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons);
                if ($this->type == 'installment') {
                    $installment = new PPPaymentInstallment($inst_id);
                    $first_payment = $installment->getFirstPayment();
                    if ($first_payment->isDeferred()) {
                        $order_state = $auth_state;
                    } else {
                        $order_state = $inst_state;
                    }
                } elseif ($is_paid) {
                    $order_state = $paid_state;
                } elseif ($is_oney) {
                    $order_state = $oney_state;
                    $this->logger->addLog('Deleting stored payment.', 'info');
                } elseif ($is_authorized) {
                    $order_state = $auth_state;
                } else {
                    $order_state = $pending_state;
                    $this->logger->addLog('Stored payment become pending.', 'info');
                    if (!$this->payplug->registerPendingTransaction((int)$cart_id)) {
                        $this->logger->addLog('Stored payment cannot be pending.', 'error');
                    } else {
                        $this->logger->addLog('Stored payment successfully set up to pending.', 'info');
                    }
                }
                $this->logger->addLog('Order state will be :' . $order_state, 'info');

                $transaction_id = null;
                if ($this->type == 'payment') {
                    $transaction_id = $payment->id;
                } elseif ($this->type == 'installment') {
                    $transaction_id = $inst_id;
                }
                $extra_vars = array(
                    'transaction_id' => $transaction_id
                );

                /*
                 * For some reasons, secure key form cart can differ from secure key from customer
                 * Maybe due to migration or Prestashop's Update
                 */
                $secure_key = false;
                if (isset($customer->secure_key) && !empty($customer->secure_key)) {
                    if (isset($cart->secure_key) && !empty($cart->secure_key) && $cart->secure_key !== $customer->secure_key) {
                        $secure_key = $cart->secure_key;
                        $this->logger->addLog('Secure keys do not match.', 'error');
                        $this->logger->addLog('Customer Secure Key: ' . $customer->secure_key, 'error');
                        $this->logger->addLog('Cart Secure Key: ' . $cart->secure_key, 'error');
                    } else {
                        $secure_key = $customer->secure_key;
                    }
                }

                $total = (float)$cart->getOrderTotal(true, Cart::BOTH);

                switch (Tools::getValue('isoney')) {
                    case 'x3_with_fees' :
                    case 'x3_without_fees' :
                        $module_name = $this->payplug->l('Oney 3x');
                        break;
                    case 'x4_with_fees' :
                    case 'x4_without_fees' :
                        $module_name = $this->payplug->l('Oney 4x');
                        break;
                    default:
                        $module_name = $this->payplug->displayName;
                        break;
                }

                if ($amount != $total) {
                    $this->logger->addLog('Cart amount is different and may occured an error', 'info');
                    $this->logger->addLog('Order create with amount:' . $total, 'info');

                    $validateOrder_result = $this->payplug->validateOrder(
                        $cart->id,
                        $order_state,
                        $total,
                        $module_name,
                        false,
                        $extra_vars,
                        (int)$cart->id_currency,
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

                    $sql = 'UPDATE `
}' . _DB_PREFIX_ . 'order_payment` SET `amount` = ' . (float)$amount . ' WHERE  `transaction_id` = "' . pSQL($pay_id) . '"';
                    Db::getInstance()->execute($sql);

                    $this->logger->addLog('Order amount is patched' . $total, 'info');
                } else {
                    $validateOrder_result = $this->payplug->validateOrder(
                        $cart->id,
                        $order_state,
                        $total,
                        $module_name,
                        false,
                        $extra_vars,
                        (int)$cart->id_currency,
                        false,
                        $secure_key
                    );
                }

                $id_order = $this->payplug->currentOrder;
                $order = new Order($id_order);

                if (!$validateOrder_result) {
                    $this->logger->addLog('Order not validated', 'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    Tools::redirect($this->url['error']);
                } else {
                    $this->logger->addLog('Order validated', 'info');
                    if ($this->type == 'payment') {
                        $api_key = Payplug::setAPIKey();
                        $data = array();
                        $data['metadata'] = $payment->metadata;
                        $data['metadata']['Order'] = $id_order;
                        $this->payplug->patchPayment($api_key, $payment->id, $data);
                    } elseif ($this->type == 'installment') {
                        $this->payplug->addPayplugInstallment($installment->resource, $order);
                    }

                    //
                    if ($order_state == $oney_state) {
                        $order_payments = OrderPayment::getByOrderReference($order->reference);
                        if ($order_payments) {
                            $order_payment = end($order_payments);
                            if (!$order_payment->transaction_id) {
                                $order_payment->transaction_id = $transaction_id;
                                $order_payment->update();
                            }
                        } else {
                            $order->addOrderPayment($order->total_paid, null, $transaction_id);
                        }
                    }
                }

                $this->logger->addLog('Checking number of order passed with this id_cart...', 'info');
                $req_nb_orders = '
            SELECT o.* 
            FROM ' . _DB_PREFIX_ . 'orders o 
            WHERE o.id_cart = ' . $cart->id;
                $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                if (!$res_nb_orders) {
                    $this->logger->addLog('No order can be found using id_cart ' . (int)$cart->id, 'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    Tools::redirect($this->url['error']);
                } elseif (count($res_nb_orders) > 1) {
                    $this->logger->addLog('There is more than one order using id_cart ' . (int)$cart->id,
                        'error');
                    foreach ($res_nb_orders as $o) {
                        $this->logger->addLog('Order ID : ' . $o['id_order'], 'debug');
                    }
                } else {
                    $this->logger->addLog('Everything looks good.', 'info');
                }

                $this->logger->addLog('Checking number of transaction validated for this order...', 'info');
                $order = new Order((int)$id_order);
                $payments = $order->getOrderPaymentCollection();

                if (!$payments) {
                    $this->logger->addLog('No transaction can be found using id_order ' . (int)$id_order,
                        'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    Tools::redirect($this->url['error']);
                } elseif (count($payments) > 1) {
                    $this->logger->addLog('There is more than one transaction using id_order ' . (int)$id_order,
                        'error');
                } else {
                    $this->logger->addLog('Everything looks good.', 'info');
                }
            }

            $cart_unlock = PayplugLock::deleteLockG2($cart->id);
            if (!$cart_unlock) {
                $this->logger->addLog('Lock cannot be deleted.', 'error');
            } else {
                $this->logger->addLog('Lock deleted.', 'debug');
            }

            $link_redirect = __PS_BASE_URI__ . $this->url['valid'] . 'id_cart=' . $cart->id . '&id_module=' . $this->payplug->id
                . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
            $this->logger->addLog('Redirecting to :' . $link_redirect, 'info');
            Tools::redirect($link_redirect);
        }
    }
}
