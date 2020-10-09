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

class PayPlugValidation
{

    public $logger;
    public $payplug;
    public $debug;
    public $type;
    public $api_key;

    public function __construct()
    {
        $this->payplug = new Payplug();
        $this->debug = $this->payplug->getConfiguration('PAYPLUG_DEBUG_MODE');
        $this->type = 'payment';
    }

    public function setLogger() {
        $this->logger = new PayPlugLogger('validation');
        $this->logger->addLog('New validation');
    }

    public function treat()
    {
        $this->setLogger();
        //todo: split code into different functions
        $this->postProcess();
    }

    public function postProcess()
    {
        $redirect_url_error = 'index.php?controller=order&step=1&error=1';
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $order_confirmation_url = 'order-confirmation.php?';
        } else {
            $order_confirmation_url = 'index.php?controller=order-confirmation&';
        }

        //Cancelling
        if (!($cart_id = Tools::getValue('cartid'))) {
            $this->logger->addLog('No Cart ID.', 'error');
            $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
            Payplug::redirectForVersion($redirect_url_error);
        } elseif (!($ps = Tools::getValue('ps')) || $ps != 1) {
            if ($ps == 2) {
                $this->logger->addLog('Order has been cancelled on PayPlug page', 'info');
            } else {
                $this->logger->addLog('Wrong GET parameter ps = ' . $ps, 'error');
                $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
            }
            Payplug::redirectForVersion($redirect_url_error);
        }
        //Treatment
        $this->logger->addLog('Cart ID : ' . (int)$cart_id, 'info');

        $cart = new Cart((int)$cart_id);

        if (!Validate::isLoadedObject($cart)) {
            $this->logger->addLog('Cart cannot be loaded.', 'error');
            $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
            Payplug::redirectForVersion($redirect_url_error);
        } else {
            $this->logger->addLog('Cart loaded.', 'error');

            // create lock
            $cart_lock = false;
            $datetime1 = date_create(date('Y-m-d H:i:s'));
            do {
                $this->logger->addLog('Check if lock exist', 'info');
                $cart_lock = PayplugLock::check($cart->id);
                if(!$cart_lock) {
                    $datetime2 = date_create(date('Y-m-d H:i:s'));
                    $interval = date_diff($datetime1, $datetime2);
                    $diff = explode('+',$interval->format('%R%s'));
                    if ($diff[1] >= 10) {
                        $this->logger->addLog('Try to create lock (PayplugLock::createLockG2) during '.$diff[1].' seconds, , but can\'t proceed', 'error');
                        break;
                    }
                    if (PayplugLock::createLockG2($cart->id, 'validation')) {
                        $this->logger->addLog('Lock created', 'info');
                        break;
                    }
                }
            } while(!$cart_lock);

            $amount = 0;
            if (!$pay_id = $this->payplug->getPaymentByCart((int)$cart_id)) {
                if (!$inst_id = $this->payplug->getInstallmentByCart((int)$cart_id)) {
                    $this->logger->addLog('Payment is not stored or is already consumed.', 'info');
                    $id_order = Order::getOrderByCartId($cart->id);
                    $customer = new Customer((int)$cart->id_customer);
                    $link_redirect = __PS_BASE_URI__ . $order_confirmation_url . 'id_cart=' . $cart->id
                        . '&id_module=' . $this->payplug->id . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    Payplug::redirectForVersion($link_redirect);
                } elseif ($inst_id = $this->payplug->getInstallmentByCart((int)$cart_id)) {
                    $this->logger->addLog('Installment is not consumed yet.', 'info');
                    $amount = 0;
                    $pay_id = false;
                    $this->type = 'installment';
                    try {
                        $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
                        $this->api_key = (bool)$installment->is_live ? Configuration::get('PAYPLUG_LIVE_API_KEY') : Configuration::get('PAYPLUG_TEST_API_KEY');
                        if (isset($installment->schedule)) {
                            foreach ($installment->schedule as $schedule) {
                                $amount += (int)$schedule->amount;
                                if ($pay_id) {
                                    continue;
                                }
                                $pay_id = !empty($schedule->payment_ids) ? $schedule->payment_ids[0] : $pay_id;
                            }
                        }
                        $this->logger->addLog('Retrieving installment...', 'info');
                        if ($installment->failure) {
                            $this->logger->addLog('Installment failure : ' . $installment->failure->message,
                                'error');
                            $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                            Payplug::redirectForVersion($redirect_url_error);
                        }
                    } catch (Exception $e) {
                        $this->logger->addLog('Installment cannot be retrieved.', 'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                        Payplug::redirectForVersion($redirect_url_error);
                    }
                }
            } else {
                $this->logger->addLog('Payment is not consumed yet.', 'info');
                try {
                    $payment = \Payplug\Payment::retrieve($pay_id);
                    $this->api_key = (bool)$payment->is_live ? Configuration::get('PAYPLUG_LIVE_API_KEY') : Configuration::get('PAYPLUG_TEST_API_KEY');
                    $this->logger->addLog('Retrieving payment...', 'info');
                    if (isset($payment->failure) && $payment->failure !== null) {
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        $this->logger->addLog('Payment failure : ' . $payment->failure->message, 'error');
                        $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        Payplug::redirectForVersion($redirect_url_error);
                    }
                    $is_paid = $payment->is_paid;
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

                    $is_authorized = count($payment->authorization) > 0;

                    $amount = $payment->amount;
                } catch (Exception $e) {
                    $this->logger->addLog('Payment cannot be retrieved.', 'error');
                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                    Payplug::redirectForVersion($redirect_url_error);
                }
                if (
                    ((isset($payment->save_card) && (int)$payment->save_card == 1))
                    ||
                    ((isset($payment->card->id) && $payment->card->id != '')
                        && ((isset($payment->hosted_payment)) && $payment->hosted_payment != ''))
                ) {
                    $this->logger->addLog('Saving card...', 'info');
                    $res_payplug_card = $this->payplug->saveCard($payment);

                    if (!$res_payplug_card) {
                        $this->logger->addLog('Card cannot be saved.', 'error');
                    }
                }
            }

            $amount = $this->payplug->convertAmount($amount, true);

            $customer = new Customer((int)$cart->id_customer);
            if (!Validate::isLoadedObject($customer)) {
                $this->logger->addLog('Customer cannot be loaded.', 'error');
                $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                if (!PayplugLock::deleteLockG2($cart->id)) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
                Payplug::redirectForVersion($redirect_url_error);
            }
            $this->logger->addLog('Total : ' . $amount, 'info');

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
                $this->logger->addLog('Order doesn\'t exists yet.', 'info');

                if ($this->type == 'payment') {
                    $state_addons = ($payment->is_live ? '' : '_TEST');
                } else {
                    $state_addons = ($installment->is_live ? '' : '_TEST');
                }

                $pending_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);
                $paid_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                /*
                * initialy, there was an order state for installment but no it has been removed and we use 'paid' state.
                * We keep this $inst_state to give more readability.
                */
                $inst_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
                $auth_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_AUTH' . $state_addons);
                $oney_state = $this->payplug->getConfiguration('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons);

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
                    $this->logger->addLog('Deleting stored payment.', 'info');
                } elseif ($is_oney) {
                    $order_state = $oney_state;
                    $this->logger->addLog('Deleting stored payment.', 'info');
                } elseif ($is_authorized) {
                    $order_state = $auth_state;
                    $this->logger->addLog('Deleting stored payment.', 'info');
                } else {
                    $order_state = $pending_state;
                    $this->logger->addLog('Stored payment become pending.', 'info');
                    if (!$this->payplug->registerPendingTransaction((int)$cart_id)) {
                        $this->logger->addLog('Stored payment cannot be pending.', 'error');
                    } else {
                        $this->logger->addLog('Stored payment successfully set up to pending.', 'info');
                    }
                }
                $this->logger->addLog('Order state will be : ' . $order_state, 'info');

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
                    if (
                        isset($cart->secure_key)
                        && !empty($cart->secure_key)
                        && $cart->secure_key !== $customer->secure_key
                    ) {
                        $secure_key = $cart->secure_key;
                        $this->logger->addLog('Secure keys do not match.', 'error');
                        $this->logger->addLog('Customer Secure Key: ' . $customer->secure_key, 'error');
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

                if (version_compare(_PS_VERSION_, 1.4, '<')) {
                    $cart_amount = (float)$cart->getOrderTotal(true, 3);
                } else {
                    $cart_amount = (float)$cart->getOrderTotal(true, Cart::BOTH);
                }

                try {
                    if ($amount != $cart_amount) {
                        $this->logger->addLog('Cart amount is different and may occured an error', 'info');
                        $this->logger->addLog('Cart amount:' . $cart_amount, 'info');
                    }

                    $validateOrder_result = $this->payplug->validateOrder(
                        $cart->id,
                        $order_state,
                        $amount,
                        $module_name,
                        false,
                        $extra_vars,
                        (int)$cart->id_currency,
                        false,
                        $secure_key
                    );

                    $id_order = $this->payplug->currentOrder;
                    $order = new Order($id_order);
                } catch (Exception $exception) {
                    $this->logger->addLog('Order cannot be created: ' . $exception->getMessage(), 'error');
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

                if ($this->type == 'payment') {
                    if (!$this->payplug->addPayplugOrderPayment($id_order, $payment->id)) {
                        $this->logger->addLog('Unable to create order payment.', 'error');
                    }
                }

                // Add payment line
                if ($deferred && count($order->getOrderPayments()) == 0) {
                    $this->logger->addLog('Add new orderPayment for deferred - ' . count($order->getOrderPayments()), 'debug');
                    $order->addOrderPayment($payment->amount / 100, null, $payment->id);
                }

                if (!$validateOrder_result) {
                    $this->logger->addLog('Order not validated', 'error');
                    $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                    if (!$cart_unlock) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                    Payplug::redirectForVersion($redirect_url_error);
                } else {
                    $this->logger->addLog('Order validated', 'info');
                    if ($this->type == 'payment') {
                        $data = array();
                        $data['metadata'] = $payment->metadata;
                        $data['metadata']['Order'] = $id_order;
                        $this->payplug->patchPayment($this->api_key, $payment->id, $data);
                    } elseif ($this->type == 'installment') {
                        $this->payplug->addPayplugInstallment($installment->resource, $order);
                    }
                }

                $this->logger->addLog('Checking number of order passed with this id_cart...', 'info');
                $req_nb_orders = 'SELECT o.id_order
                                    FROM ' . _DB_PREFIX_ . 'orders o 
                                    WHERE o.id_cart = ' . (int)$cart->id;
                $res_nb_orders = Db::getInstance()->executeS($req_nb_orders);
                if (!$res_nb_orders) {
                    $this->logger->addLog('No order can be found using id_cart ' . (int)$cart->id, 'error');
                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                    Payplug::redirectForVersion($redirect_url_error);
                } elseif (count($res_nb_orders) > 1) {
                    $this->logger->addLog('There is more than one order using id_cart ' . (int)$cart->id,
                        'error');
                    foreach ($res_nb_orders as $o) {
                        $this->logger->addLog('Order ID : ' . $o['id_order'], 'debug');
                    }
                } else {
                    $this->logger->addLog('Everything looks good.', 'info');
                }

                if ($this->type == 'payment') {
                    $this->logger->addLog('Checking number of transaction validated for this order...', 'info');
                    $has_payment = true;

                    $req_order_payment = 'SELECT pop.* 
                                        FROM ' . _DB_PREFIX_ . 'payplug_order_payment pop  
                                        WHERE pop.id_order = ' . (int)$id_order;
                    $payments = Db::getInstance()->executeS($req_order_payment);
                    if (!$payments) {
                        $has_payment = false;
                    }

                    if (!$has_payment) {
                        $this->logger->addLog('No transaction can be found using id_order ' . (int)$id_order,
                            'error');
                        if (!PayplugLock::deleteLockG2($cart->id)) {
                            $this->logger->addLog('Lock cannot be deleted.', 'error');
                        } else {
                            $this->logger->addLog('Lock deleted.', 'debug');
                        }
                        $this->payplug->setPaymentErrorsCookie(array($this->payplug->l('The transaction was not completed and your card was not charged.')));
                        Payplug::redirectForVersion($redirect_url_error);
                    } elseif (count($payments) > 1) {
                        $this->logger->addLog('There is more than one transaction using id_order ' . (int)$id_order, 'error');
                    } else {
                        $this->logger->addLog('Everything looks good.', 'info');
                    }
                }
            }

            if (!PayplugLock::deleteLockG2($cart->id)) {
                $this->logger->addLog('Lock cannot be deleted.', 'error');
            } else {
                $this->logger->addLog('Lock deleted.', 'debug');
            }

            $link_redirect = __PS_BASE_URI__ . $order_confirmation_url
                . 'id_cart=' . $cart->id . '&id_module=' . $this->payplug->id
                . '&id_order=' . $id_order . '&key=' . $customer->secure_key;
            $this->logger->addLog('Redirecting to :' . $link_redirect, 'info');
            Payplug::redirectForVersion($link_redirect);
        }
    }
}
