<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

use Db;
use Cart;
use Configuration;
use Customer;
use Exception;
use Order;
use Tools;
use Validate;

class PayPlugValidation
{
    public $apiClass;
    public $context;
    public $logger;
    public $paymentClass;
    public $debug;
    public $type;
    public $api_key;
    private $amountCurrencyClass;
    private $dependencies;
    private $moduleInstance;
    private $isDeferred;
    private $isOney;
    private $isBancontact;
    private $isApplepay;
    private $orderClass;
    private $plugin;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
        $this->apiClass = $this->dependencies->apiClass;
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->orderClass = $this->dependencies->orderClass;
        $this->paymentClass = $this->dependencies->paymentClass;
        $this->installmentClass = $this->dependencies->installmentClass;
        $this->debug = Configuration::get(
            $this->dependencies->getConfigurationKey('debugMode')
        );
        $this->plugin = $this->dependencies->getPlugin();
        $this->setConfig();
        $this->moduleInstance = $this
            ->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name);
    }

    public function setConfig()
    {
        $this->amountCurrencyClass = $this->dependencies->amountCurrencyClass;
        $this->isDeferred = false;
        $this->isOney = false;
        $this->isBancontact = false;
        $this->isApplepay = false;
        $this->type = 'payment';
        $this->setLogger();
    }

    public function setLogger()
    {
        $this->logger = $this->plugin->getLogger();
        $this->logger->setParams(['process' => 'validation']);
        $this->logger->addLog('New validation');
    }

    public function treat()
    {
        //todo: split code into different functions
        $this->postProcess();
    }

    public function postProcess()
    {
        $redirect_url_error = 'index.php?controller=order&step=3&has_error=1&modulename=' . $this->dependencies->name;
        $cancel_url = 'index.php?controller=order&step=3';
        $order_confirmation_url = 'index.php?controller=order-confirmation&';

        //Cancelling
        if (!($cart_id = Tools::getValue('cartid'))) {
            $this->logger->addLog('No Cart ID.', 'error');
            $this->paymentClass->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
            ]);
            Tools::redirect($redirect_url_error);
        } elseif (!($ps = Tools::getValue('ps')) || $ps != 1) {
            if ($ps == 2) {
                $this->logger->addLog('Order has been cancelled on PayPlug page');
                Tools::redirect($cancel_url);
            }

            $this->logger->addLog('Wrong GET parameter ps = ' . $ps, 'error');
            $this->paymentClass->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
            ]);
            Tools::redirect($redirect_url_error);
        }
        //Treatment
        $this->logger->addLog('Cart ID : ' . (int)$cart_id);

        $cart = new Cart((int)$cart_id);

        // Check if valid cart
        if (!Validate::isLoadedObject($cart)) {
            $this->logger->addLog('Cart cannot be loaded.', 'error');
            $this->paymentClass->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
            ]);
            Tools::redirect($redirect_url_error);
        }
        $this->logger->addLog('Cart loaded.', 'error');

        // Create lock
        $cart_lock = false;
        $datetime1 = date_create(date('Y-m-d H:i:s'));
        $this->logger->addLog('Check lock');
        do {
            $cart_lock = PayplugLock::check($cart->id);
            if (!$cart_lock) {
                $datetime2 = date_create(date('Y-m-d H:i:s'));
                $interval = date_diff($datetime1, $datetime2);
                $diff = explode('+', $interval->format('%R%s'));
                if ($diff[1] >= 10) {
                    $this->logger->addLog('Try to create lock (PayplugLock::createLockG2) during ' . $diff[1] . ' sec,'
                        . ' but can\'t proceed', 'error');
                    break;
                }
                if (PayplugLock::createLockG2($cart->id, 'validation')) {
                    $this->logger->addLog('Lock created');
                    break;
                }
            }
        } while (!$cart_lock);

        $amount = 0;
        if (!$pay_id = $this->paymentClass->getPaymentByCart((int)$cart_id)) {
            if (!$inst_id = $this->installmentClass->getInstallmentByCart((int)$cart_id)) {
                $this->logger->addLog('Payment is not stored or is already consumed.');
                $id_order = Order::getOrderByCartId($cart->id);
                $customer = new Customer((int)$cart->id_customer);
                $link_redirect = __PS_BASE_URI__ . $order_confirmation_url . 'id_cart=' . $cart->id
                    . '&id_module=' . $this->moduleInstance->id . '&id_order=' . $id_order
                    . '&key=' . $customer->secure_key;
                if (!PayplugLock::deleteLockG2($cart->id)) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
                Tools::redirect($link_redirect);
            } elseif ($inst_id = $this->installmentClass->getInstallmentByCart((int)$cart_id)) {
                $this->logger->addLog('Installment is not consumed yet.');
                $amount = 0;
                $pay_id = false;
                $this->type = 'installment';

                $installment = $this->apiClass->retrieveInstallment($inst_id);
                if (!$installment['result']) {
                    $this->logger->addLog('Installment cannot be retrieved.', 'error');
                    if (!PayplugLock::deleteLockG2($cart->id)) {
                        $this->logger->addLog('Lock cannot be deleted.', 'error');
                    } else {
                        $this->logger->addLog('Lock deleted.', 'debug');
                    }
                    $this->paymentClass->setPaymentErrorsCookie([
                        $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
                    ]);
                    Tools::redirect($redirect_url_error);
                }

                $installment = $installment['resource'];
                $this->api_key = (bool)$installment->is_live ?
                    Configuration::get($this->dependencies->getConfigurationKey('liveApiKey')) :
                    Configuration::get($this->dependencies->getConfigurationKey('testApiKey'));

                if (isset($installment->schedule)) {
                    foreach ($installment->schedule as $schedule) {
                        $amount += (int)$schedule->amount;
                        if ($pay_id) {
                            continue;
                        }
                        $pay_id = !empty($schedule->payment_ids) ? $schedule->payment_ids[0] : $pay_id;
                    }
                }
                $this->logger->addLog('Retrieving installment...');

                if ($installment->failure) {
                    $this->logger->addLog('Installment failure : ' . $installment->failure->message, 'error');
                    $this->paymentClass->setPaymentErrorsCookie([
                        $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
                    ]);
                    Tools::redirect($redirect_url_error);
                }
            }
        } else {
            $this->logger->addLog('Payment is not consumed yet.');

            $payment = $this->apiClass->retrievePayment($pay_id);
            if (!$payment['result']) {
                $this->logger->addLog('Payment cannot be retrieved. Exception : ' . $payment['message'], 'error');
                if (!PayplugLock::deleteLockG2($cart->id)) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
                $this->paymentClass->setPaymentErrorsCookie([
                    $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
                ]);
                Tools::redirect($redirect_url_error);
            }

            $payment = $payment['resource'];
            $this->api_key = (bool)$payment->is_live ?
                Configuration::get(
                    $this->dependencies->getConfigurationKey('liveApiKey')
                ) :
                Configuration::get(
                    $this->dependencies->getConfigurationKey('testApiKey')
                );
            $this->logger->addLog('Retrieving payment: ' . $payment->id);
            if (isset($payment->failure) && $payment->failure !== null) {
                if (!PayplugLock::deleteLockG2($cart->id)) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
                $this->logger->addLog('Payment failure : ' . $payment->failure->message, 'error');
                $this->paymentClass->setPaymentErrorsCookie([
                    $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
                ]);
                if (!PayplugLock::deleteLockG2($cart->id)) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
                Tools::redirect($redirect_url_error);
            }
            $is_paid = $payment->is_paid;
            if (isset($payment->payment_method) && isset($payment->payment_method['type'])) {
                switch ($payment->payment_method['type']) {
                    case 'oney_x3_with_fees':
                    case 'oney_x4_with_fees':
                    case 'oney_x3_without_fees':
                    case 'oney_x4_without_fees':
                        $this->isOney = true;
                        break;
                    case 'bancontact':
                        $this->isBancontact = true;
                        break;
                    case 'apple_pay':
                        $this->isApplepay = true;
                        break;
                    default:
                        $this->isOney = false;
                        $this->isBancontact = false;
                        $this->isApplepay = false;
                }
            }

            $is_authorized = false;

            if (($payment->authorization !== null) && isset($payment->authorization->authorized_amount)) {
                $is_authorized = true;
                if (!$this->isOney) {
                    $this->isDeferred = true;
                }
            }

            $amount = $payment->amount;

            if (((isset($payment->save_card) && (int)$payment->save_card == 1))
                ||
                ((isset($payment->card->id) && $payment->card->id != ''))
            ) {
                $this->logger->addLog('[Save Card] Saving card...');
                $res_payplug_card = $this->plugin->getCard()->saveCard($payment);

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
                } else {
                    $this->logger->addLog('[Save Card] Card saved', 'debug');
                }
            }
        }
        $amount = $this->amountCurrencyClass->convertAmount($amount, true);

        // Check if valid customer
        $customer = new Customer((int)$cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            $this->logger->addLog('Customer cannot be loaded.', 'error');
            $this->paymentClass->setPaymentErrorsCookie([
                $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
            ]);
            if (!PayplugLock::deleteLockG2($cart->id)) {
                $this->logger->addLog('Lock cannot be deleted.', 'error');
            } else {
                $this->logger->addLog('Lock deleted.', 'debug');
            }
            Tools::redirect($redirect_url_error);
        }

        $this->logger->addLog('Total : ' . $amount);

        $id_order = Order::getOrderByCartId($cart->id);

        if ($id_order) {
            $this->logger->addLog('Order already exists.');
            if ($this->type == 'payment') {
                $this->logger->addLog('Deleting stored payment.');
                if ($this->paymentClass->isTransactionPending((int)$cart_id)) {
                    $this->logger->addLog('Transaction is pending so stored payment will not be deleted.');
                }
            }
        } else {
            $this->logger->addLog('Order doesn\'t exists yet.');

            if ($this->type == 'payment') {
                $state_addons = ($payment->is_live ? '' : '_TEST');
            } else {
                $state_addons = ($installment->is_live ? '' : '_TEST');
            }

            $pending_state = Configuration::get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PENDING') . $state_addons
            );
            $paid_state = Configuration::get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID') . $state_addons
            );
            /*
            * initialy, there was an order state for installment but no it has been removed and we use 'paid' state.
            * We keep this $inst_state to give more readability.
            */
            $inst_state = Configuration::get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID' . $state_addons)
            );
            $auth_state = Configuration::get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_AUTH' . $state_addons)
            );
            $oney_state = Configuration::get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ONEY_PG' . $state_addons)
            );

            if ($this->type == 'installment') {
                $installment = new PPPaymentInstallment($inst_id, $this->dependencies);
                $first_payment = $installment->getFirstPayment();
                if ($first_payment->isDeferred()) {
                    $order_state = $auth_state;
                } else {
                    $order_state = $inst_state;
                }
            } elseif ($is_paid) {
                $order_state = $paid_state;
                $this->logger->addLog('Deleting stored payment.');
            } elseif ($this->isOney) {
                $order_state = $oney_state;
                $this->logger->addLog('Deleting stored payment.');
            } elseif ($is_authorized) {
                $order_state = $auth_state;
                $this->logger->addLog('Deleting stored payment.');
            } else {
                $order_state = $pending_state;
                $this->logger->addLog('Stored payment become pending.');
                if (!$this->paymentClass->registerPendingTransaction((int)$cart_id)) {
                    $this->logger->addLog('Stored payment cannot be pending.', 'error');
                } else {
                    $this->logger->addLog('Stored payment successfully set up to pending.');
                }
            }
            $this->logger->addLog('Order state will be : ' . $order_state);

            $transaction_id = null;
            if ($this->type == 'payment') {
                $transaction_id = $payment->id;
            } elseif ($this->type == 'installment') {
                $transaction_id = $inst_id;
            }
            $extra_vars = [
                'transaction_id' => $transaction_id
            ];

            /*
             * For some reasons, secure key form cart can differ from secure key from customer
             * Maybe due to migration or Prestashop's Update
             */
            $secure_key = false;
            if (isset($customer->secure_key) && !empty($customer->secure_key)) {
                if (isset($cart->secure_key)
                    && !empty($cart->secure_key)
                    && $cart->secure_key !== $customer->secure_key
                ) {
                    $secure_key = $cart->secure_key;
                    $this->logger->addLog('Secure keys do not match.', 'error');
                } else {
                    $secure_key = $customer->secure_key;
                }
            }

            $module_name = $this->moduleInstance->displayName;
            if ($this->isOney) {
                switch ($payment->payment_method['type']) {
                    case 'oney_x3_with_fees':
                        $module_name = $this->dependencies->l('Oney 3x', 'payplugvalidation');
                        break;
                    case 'oney_x4_with_fees':
                        $module_name = $this->dependencies->l('Oney 4x', 'payplugvalidation');
                        break;
                    case 'oney_x3_without_fees':
                        $module_name = $this->dependencies->l('validation.createOrder.oneyX3WithoutFees', 'payplugvalidation');
                        break;
                    case 'oney_x4_without_fees':
                        $module_name = $this->dependencies->l('validation.createOrder.oneyX4WithoutFees', 'payplugvalidation');
                        break;
                    default:
                        break;
                }
            } elseif ($this->isBancontact) {
                $module_name = $this->dependencies->l('validation.createOrder.bancontact', 'payplugvalidation');
            } elseif ($this->isApplepay) {
                $module_name = $this->dependencies->l('validation.createOrder.applepay', 'payplugvalidation');
            }

            $cart_amount = (float)$cart->getOrderTotal(true, Cart::BOTH);

            if ($this->isApplepay) {
                $token = Tools::getValue('token');

                $apple_pay = array();
                $apple_pay['payment_token'] = $token;
                $data = [];
                $data['apple_pay'] = $apple_pay;

                $patchPayment = $this->apiClass->patchPayment($payment->id, $data);

                if ($patchPayment['resource']->is_paid !== true) {
                    if (!empty($patchPayment['resource']->failure)) {
                        $this->logger->addLog($patchPayment['resource']->failure->message, 'error');
                    } else {
                        $this->logger->addLog('Error during payment patch', 'error');
                    }

                    die(json_encode([
                        'result' => false,
                        'message' => 'Error during payment patch'
                    ]));
                }
            }

            try {
                if ($amount != $cart_amount) {
                    $this->logger->addLog('Cart amount is different and may occurred an error');
                    $this->logger->addLog('Cart amount:' . $cart_amount);
                }

                $validateOrder_result = $this->moduleInstance->validateOrder(
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

                $id_order = $this->moduleInstance->currentOrder;
                $order = new Order($id_order);
            } catch (Exception $exception) {
                $this->logger->addLog('Order cannot be created: ' . $exception->getMessage(), 'error');
                $this->response = [
                    'exception' => $exception->getMessage(),
                ];
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

            if (!$validateOrder_result) {
                $this->logger->addLog('Order not validated', 'error');
                $cart_unlock = PayplugLock::deleteLockG2($cart->id);
                if (!$cart_unlock) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
                $this->paymentClass->setPaymentErrorsCookie([
                    $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
                ]);
                Tools::redirect($redirect_url_error);
            }

            $this->logger->addLog('Order validated');

            // Add payplug orderPayment / Installment
            if ($this->type == 'payment') {
                $data = [];
                $data['metadata'] = $payment->metadata;
                $data['metadata']['Order'] = $id_order;

                $patchPayment = $this->apiClass->patchPayment($payment->id, $data);
                if (!$patchPayment['result']) {
                    $this->logger->addLog('Payment cannot be patched: ' . $patchPayment['message'], 'error');
                }

                if (!$this->orderClass->addPayplugOrderPayment($id_order, $payment->id)) {
                    $this->logger->addLog('Unable to create order payment.', 'error');
                }
            } elseif ('installment' == $this->type) {
                $this->installmentClass->addPayplugInstallment($installment->resource, $order);
            }

            // Add payment line
            $order_payment = $order->getOrderPayments();
            if (!$order_payment) {
                $this->logger->addLog('Add new orderPayment for deferred - ' . count($order_payment), 'debug');
                $order->addOrderPayment($amount, null, $transaction_id);
            }

            // Check number of order using this cart
            $this->logger->addLog('Checking number of order passed with this id_cart...');
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
                $this->paymentClass->setPaymentErrorsCookie([
                    $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
                ]);
                Tools::redirect($redirect_url_error);
            } elseif (count($res_nb_orders) > 1) {
                $this->logger->addLog(
                    'There is more than one order using id_cart ' . (int)$cart->id,
                    'error'
                );
                foreach ($res_nb_orders as $o) {
                    $this->logger->addLog('Order ID : ' . $o['id_order'], 'debug');
                }
            } else {
                $this->logger->addLog('Everything looks good.');
            }

            // Check number of orderPayment using this cart
            $this->logger->addLog('Checking number of transaction validated for this order...', 'info');
            $payments = $order->getOrderPayments();
            if (!$payments) {
                $this->logger->addLog(
                    'No transaction can be found using id_order ' . (int)$id_order,
                    'error'
                );
                if (!PayplugLock::deleteLockG2($cart->id)) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'debug');
                }
                $this->paymentClass->setPaymentErrorsCookie([
                    $this->dependencies->l('The transaction was not completed and your card was not charged.', 'payplugvalidation')
                ]);
                Tools::redirect($redirect_url_error);
            } elseif (count($payments) > 1) {
                $this->logger->addLog('There is more than one transaction using id_order ' . (int)$id_order, 'error');
            } else {
                $this->logger->addLog('Everything looks good.', 'info');
            }
        }

        if (!PayplugLock::deleteLockG2($cart->id)) {
            $this->logger->addLog('Lock cannot be deleted.', 'error');
        } else {
            $this->logger->addLog('Lock deleted.', 'debug');
        }

        $link_redirect = $this->context->link->getPageLink('order-confirmation', true, $this->context->language->id, [
            'id_cart' => $cart->id,
            'id_module' => $this->moduleInstance->id,
            'id_order' => $id_order,
            'key' => $customer->secure_key
        ]);
        $this->logger->addLog('Redirecting to order-confirmation page');

        if ($this->isApplepay) {
            die(json_encode([
                'result' => true,
                'link_redirect' => $link_redirect
            ]));
        }

        Tools::redirect($link_redirect);
    }
}
