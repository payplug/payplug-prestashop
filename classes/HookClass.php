<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

use OrderState;
use Payplug\Exception\ConfigurationException;
use Dispatcher;
use Media;
use Module;
use Product;
use Configuration;
use Symfony\Component\Dotenv\Dotenv;

class HookClass
{
    private $payplug;
    private $assign;
    private $cache;
    private $card;
    private $config;
    private $context;
    private $html;
    private $oney;
    private $order;
    private $orderHistory;
    private $product;
    private $sql;
    private $tools;
    private $validate;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
        $this->assign           = $payplug->getPlugin()->getAssign();
        $this->cache            = $payplug->getPlugin()->getCache();
        $this->card             = $payplug->getPlugin()->getCard();
        $this->cart             = $payplug->getPlugin()->getCart();
        $this->config           = $payplug->getPlugin()->getConfiguration();
        $this->context          = $payplug->getPlugin()->getContext()->getContext();
        $this->currency         = $payplug->getPlugin()->getCurrency();
        $this->oney             = $payplug->getPlugin()->getOney();
        $this->order            = $payplug->getPlugin()->getOrder();
        $this->orderHistory     = $payplug->getPlugin()->getOrderHistory();
        $this->orderState       = $payplug->getPlugin()->getOrderState();
        $this->product          = $payplug->getPlugin()->getProduct();
        $this->sql              = $payplug->getPlugin()->getSql();
        $this->tools            = $payplug->getPlugin()->getTools();
        $this->validate         = $payplug->getPlugin()->getValidate();
    }

    /**
     * @description Flush PayPlugCache (PS 1.6), when PrestaShop cache cleared
     *
     * @param array $params
     * @return boolean
     */
    public function actionAdminPerformanceControllerAfter($params)
    {
        if ($this->sql->checkExistingTable('payplug_cache', 1)) {
            return $this->cache->flushCache();
        }
    }

    /**
     * @description Flush PayPlugCache (PS 1.7), when PrestaShop cache cleared
     *
     * @param array $params
     * @return boolean
     */
    public function actionClearCompileCache($params)
    {
        if ($this->sql->checkExistingTable('payplug_cache', 1)) {
            return $this->cache->flushCache();
        }
    }

    /**
     * @param $customer
     * @return false|string
     */
    public function actionDeleteGDPRCustomer($customer)
    {
        if (!$this->card->deleteCards((int)$customer['id'])) {
            return json_encode($this->payplug->l('hook.actionDeleteGDPRCustomer.unableDelete', 'hookclass'));
        }
        return json_encode(true);
    }

    /**
     * @param $customer
     * @return false|string
     * @throws PrestaShopDatabaseException
     */
    public function actionExportGDPRData($customer)
    {
        if (!$cards = $this->payplug->configClass->gdprCardExport((int)$customer['id'])) {
            return json_encode($this->payplug->l('hook.actionExportGDPRData.unableToExport', 'hookclass'));
        } else {
            return json_encode($cards);
        }
    }

    /**
     * @param $params
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function actionOrderStatusUpdate($params)
    {
        $order = $this->order->get((int)$params['id_order']);
        $active = Module::isEnabled($this->payplug->name);
        if (!$active
            || $order->payment != $this->payplug->displayName
            || !$this->payplug->isDeferredPaymentsActive()
            || !$this->payplug->isDeferredAutoActive()
            || $params['newOrderStatus']->id != $this->config->get('PAYPLUG_DEFERRED_STATE')
        ) {
            return;
        } else {
            $cart = $this->cart->get((int)$order->id_cart);
            $payment_method = $this->payplug->getPaymentMethodByCart($cart);
            if ($payment_method['type'] == 'installment') {
                $installment = new PPPaymentInstallment($payment_method['id']);
                $payment = $installment->getFirstPayment();
            } else {
                $payment = new PPPayment($payment_method['id']);
            }
            if (!$payment->isPaid()) {
                $payment->capture();
                $payment->refresh();
                if ($payment->resource->card->id !== null) {
                    $this->card->saveCard($payment->resource);
                }
            }
        }
    }

    /**
     * @param $params
     * @return bool
     */
    public function actionUpdateLangAfter($params)
    {
        $id_order_states = $this->payplug->orderClass->getPayPlugOrderStates($this->payplug->name);
        $payplug_order_states = explode(',', $id_order_states);

        if (empty($payplug_order_states) || !in_array($params['lang']->iso_code, $this->payplug->payplug_languages)) {
            return true;
        }

        $all_order_states = array_merge($this->payplug->order_states, $this->payplug->oney_order_state);

        foreach ($all_order_states as $order_state) {
            foreach ($order_state['payplug_cfg'] as $payplug_conf) {
                if (in_array($this->config->get($payplug_conf), $payplug_order_states)) {
                    $ps_order_state_name = $order_state['name'][$params['lang']->iso_code];
                    if (strpos($payplug_conf, '_TEST')) {
                        $ps_order_state_name .= ' [TEST]';
                    } else {
                        $ps_order_state_name .= ' [PayPlug]';
                    }

                    $ps_order_state = new OrderState($this->config->get($payplug_conf));
                    $ps_order_state->name[$params['lang']->id] = $ps_order_state_name;
                    $ps_order_state->save();
                }
            }
        }

        return true;
    }

    /**
     * @description retrocompatibility of hookDisplayAdminOrderMain for version before 1.7.7.0
     *
     * @param $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ConfigurationException
     */
    public function adminOrder($params)
    {
        if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            return $this->displayAdminOrderMain($params);
        }
    }

    /**
     * @param $params
     * @return string|void
     */
    public function customerAccount($params)
    {
        if (!ConfigClass::isAllowed()) {
            return false;
        }

        $payplug_cards_url = $this->context->link->getModuleLink(
            $this->payplug->name,
            'cards',
            ['process' => 'cardlist'],
            true
        );

        if ((class_exists($this->payplug->PrestashopSpecificClass))
            && (method_exists($this->payplug->PrestashopSpecificObject, 'customerAccount'))) {
            $this->payplug->PrestashopSpecificObject->customerAccount();
        }

        $this->assign->assign([
            'version' => _PS_VERSION_[0] . '.' . _PS_VERSION_[2],
            'payplug_cards_url' => $payplug_cards_url
        ]);

        return $this->payplug->fetchTemplate('customer/my_account.tpl');
    }

    /**
     * @param array $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws ConfigurationException
     */
    public function displayAdminOrderMain($params)
    {
        if (!$this->payplug->active) {
            return;
        }

        $this->html = '';
        $order = $this->order->get((int)$params['id_order']);
        if (!$this->validate->validate('isLoadedObject', $order)) {
            return false;
        }

        if ($order->module != $this->payplug->name) {
            return false;
        }

        $show_popin = false;
        $display_refund = false;
        $refund_delay_oney = false;
        $show_menu_refunded = false;
        $show_menu_update = false;
        $show_menu_installment = false;
        $show_menu_payment = false;
        $pay_error = '';
        $amount_refunded_payplug = 0;
        $amount_available = 0;

        $admin_ajax_url = AdminClass::getAdminAjaxUrl('AdminModules', (int)$params['id_order']);
        $amount_refunded_presta = RefundClass::getTotalRefunded($order->id);

        $inst_id = null;
        $payment_id = $this->payplug->getPayplugInstallmentCart($order->id_cart);
        // Backward if order validated before
        if (!$payment_id) {
            $payment_id = $this->payplug->getPayplugInstallmentCartBackward($order->id_cart);
        }

        if ($payment_id && strpos($payment_id, 'inst') !== false) {
            $inst_id = $payment_id;
        }
        if ($inst_id) {
            $payment_list = [];
            if (!$inst_id || empty($inst_id) || !$installment = InstallmentClass::retrieveInstallment($inst_id)) {
                if ($this->config->get('PAYPLUG_SANDBOX_MODE') == 1) {
                    ApiClass::setSecretKey($this->config->get('PAYPLUG_LIVE_API_KEY'));
                    if (empty($inst_id) || !$installment = InstallmentClass::retrieveInstallment($inst_id)) {
                        ApiClass::setSecretKey($this->config->get('PAYPLUG_TEST_API_KEY'));
                        return false;
                    }
                } elseif ($this->config->get('PAYPLUG_SANDBOX_MODE') == 0) {
                    ApiClass::setSecretKey($this->config->get('PAYPLUG_TEST_API_KEY'));
                    if (empty($inst_id) || !$installment = InstallmentClass::retrieveInstallment($inst_id)) {
                        ApiClass::setSecretKey($this->config->get('PAYPLUG_LIVE_API_KEY'));
                        return false;
                    }
                }
            }

            $pay_mode = $installment->is_live
                ? $this->payplug->l('hook.displayAdminOrderMain.live', 'hookclass')
                : $this->payplug->l('hook.displayAdminOrderMain.test', 'hookclass');
            $payments = $order->getOrderPaymentCollection();
            $pps = [];
            if (count($payments) > 0) {
                foreach ($payments as $payment) {
                    $pps[] = $payment->transaction_id;
                }
            }

            $payment_list_new = [];
            foreach ($installment->schedule as $schedule) {
                if ($schedule->payment_ids != null) {
                    foreach ($schedule->payment_ids as $pay_id) {
                        $p = $this->payplug->retrievePayment($pay_id);
                        $payment_list_new[] = $this->payplug->buildPaymentDetails($p);
                        if ((int)$p->is_paid == 0) {
                            $amount_refunded_payplug += 0;
                            $amount_available += 0;
                        } elseif ((int)$p->is_refunded == 1) {
                            $amount_refunded_payplug += ($p->amount_refunded) / 100;
                            $amount_available += ($p->amount - $p->amount_refunded) / 100;
                        } elseif ((int)$p->amount_refunded > 0) {
                            $amount_refunded_payplug += ($p->amount_refunded) / 100;
                            $amount_refundable_payment = ($p->amount - $p->amount_refunded);
                            if ($amount_refundable_payment >= 10) {
                                $amount_available += $amount_refundable_payment / 100;
                            }
                        } else {
                            $amount_available += ($p->amount >= 10 ? $p->amount / 100 : 0);
                        }

                        if ($amount_available > 0) {
                            $display_refund = true;
                        }

                        if ($p->amount_refunded > 0) {
                            $show_menu_refunded = true;
                        }
                    }
                } else {
                    $payment_list_new[] = [
                        'id' => null,
                        'status' => $installment->is_active ? $this->payplug->payment_status[6] : $this->payplug->payment_status[7],
                        'status_class' => $installment->is_active ? 'pp_success' : 'pp_error',
                        'status_code' => 'incoming',
                        'amount' => (int)$schedule->amount / 100,
                        'card_brand' => null,
                        'card_mask' => null,
                        'tds' => null,
                        'card_date' => null,
                        'mode' => null,
                        'authorization' => null,
                        'date' => date('d/m/Y', strtotime($schedule->date)),
                    ];
                }
            }

            $id_currency = (int)$this->currency->getIdByIsoCode($installment->currency);
            $show_menu_installment = true;
            $inst_status = $installment->is_active ?
                $this->payplug->l('hook.displayAdminOrderMain.ongoing', 'hookclass') :
                (
                    $installment->is_fully_paid ?
                    $this->payplug->l('hook.displayAdminOrderMain.paid', 'hookclass') :
                    $this->payplug->l('hook.displayAdminOrderMain.suspended', 'hookclass')
                );
            $inst_status_code = $installment->is_active ?
                'ongoing' :
                ($installment->is_fully_paid ? 'paid' : 'suspended');
            $inst_aborted = !$installment->is_active;
            $ppInstallment = new PPPaymentInstallment($installment->id);
            $instPaymentOne = $ppInstallment->getFirstPayment();
            $inst_can_be_aborted = !($inst_aborted || ($instPaymentOne->isDeferred() && !$instPaymentOne->isPaid()));
            $inst_paid = $installment->is_fully_paid;
            $this->assign->assign([
                'inst_id' => $inst_id,
                'inst_status' => $inst_status,
                'inst_status_code' => $inst_status_code,
                'inst_aborted' => $inst_aborted,
                'inst_paid' => $inst_paid,
                'payment_list' => $payment_list,
                'payment_list_new' => $payment_list_new,
                'inst_can_be_aborted' => $inst_can_be_aborted,
            ]);

            $sandbox = ((int)$installment->is_live == 1 ? false : true);
            $state_addons = ($sandbox ? '_TEST' : '');
            $id_new_order_state = (int)$this->config->get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);

            InstallmentClass::updatePayplugInstallment($installment);
        } else {
            if (!$pay_id = $this->payplug->isTransactionPending($order->id_cart)) {
                $pay_id = $this->payplug->orderClass->getPayplugOrderPayment($order->id);

                if (!$pay_id) {
                    $payments = $order->getOrderPaymentCollection();
                    if (count($payments->getResults()) > 1 || !$payments->getFirst()) {
                        return false;
                    } else {
                        $pay_id = $payments->getFirst()->transaction_id;
                    }
                }
            }

            $sandbox = (bool)$this->config->get('PAYPLUG_SANDBOX_MODE');

            if (!$pay_id || empty($pay_id) || !$payment = $this->payplug->retrievePayment($pay_id)) {
                if ($sandbox) {
                    ApiClass::setSecretKey($this->config->get('PAYPLUG_LIVE_API_KEY'));
                    if (empty($pay_id) || !$payment = $this->payplug->retrievePayment($pay_id)) {
                        ApiClass::setSecretKey($this->config->get('PAYPLUG_TEST_API_KEY'));
                        return false;
                    }
                } else {
                    ApiClass::setSecretKey($this->config->get('PAYPLUG_TEST_API_KEY'));
                    if (empty($pay_id) || !$payment = $this->payplug->retrievePayment($pay_id)) {
                        ApiClass::setSecretKey($this->config->get('PAYPLUG_LIVE_API_KEY'));
                        return false;
                    }
                }
            }

            // check if order is from oney payment
            $oney_payment_method = [
                'oney_x3_with_fees',
                'oney_x4_with_fees',
                'oney_x3_without_fees',
                'oney_x4_without_fees',
            ];

            $is_oney = isset($payment->payment_method)
                && isset($payment->payment_method['type'])
                && in_array($payment->payment_method['type'], $oney_payment_method);

            $is_bancontact = isset($payment->payment_method)
                && isset($payment->payment_method['type'])
                && $payment->payment_method['type'] == 'bancontact';

            // Update order state if is pending
            $state_addons = $payment->is_live ? '' : '_TEST';
            $paid_state = $this->config->get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
            $oney_state = $this->config->get('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons);
            $cancelled_state = $this->config->get('PS_OS_CANCELED');

            if ($is_oney) {
                // update order state from payment status
                if ($order->getCurrentState() == $oney_state) {
                    $new_order_state = false;
                    if ($payment->is_paid) {
                        $new_order_state = $paid_state;
                    } elseif (isset($payment->failure) && $payment->failure !== null) {
                        $new_order_state = $cancelled_state;
                    }

                    if ($new_order_state) {
                        $order_history = $this->orderHistory->get();
                        $order_history->id_order = $order->id;
                        $order_history->changeIdOrderState($new_order_state, $order->id, true);
                        $order_history->save();
                    }
                }
            }

            if ($is_bancontact) {
                $this->assign->assign(['pay_tds' => null]);
            }

            $single_payment = $this->payplug->buildPaymentDetails($payment);
            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
            $amount_available_payment = ($payment->amount - $payment->amount_refunded);
            $amount_available = ($amount_available_payment >= 10 ? $amount_available_payment / 100 : 0);
            $id_currency = (int)$this->currency->getIdByIsoCode($payment->currency);
            $state_addons = (!$payment->is_live ? '_TEST' : '');

            $id_new_order_state = (int)$this->config->get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
            $id_pending_order_state = (int)$this->config->get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);

            $current_state = (int)$order->getCurrentState();

            if ((int)$payment->is_paid == 0) {
                if (isset($payment->failure) && isset($payment->failure->message)) {
                    $pay_error = '(' . $payment->failure->message . ')';
                } else {
                    $pay_error = '';
                }
                $display_refund = false;
                if ($current_state != 0 && $current_state == $id_pending_order_state) {
                    $show_menu_update = true;
                }
            } elseif ((((int)$payment->amount_refunded > 0)
                    || $amount_refunded_presta > 0)
                && (int)$payment->is_refunded != 1) {
                $display_refund = true;
            } elseif ((int)$payment->is_refunded == 1) {
                $show_menu_refunded = true;
                $display_refund = false;
            } elseif (time() >= $payment->refundable_until) {
                $display_refund = false;
            } else {
                $display_refund = true;
                if ($is_oney) {
                    $refund_delay_oney = time() <= $payment->refundable_after;
                }
            }

            $conf = (int)$this->tools->tool('getValue', 'conf');
            if ($conf == 30 || $conf == 31) {
                $show_popin = true;

                $admin_ajax_url = AdminClass::getAdminAjaxUrl('AdminModules', (int)$params['id_order']);

                $this->html .= '<a class="pp_admin_ajax_url" href="' . $admin_ajax_url . '"></a>';
            }

            $pay_status = ((int)$payment->is_paid == 1)
                ? $this->payplug->l('hook.displayAdminOrderMain.paid', 'hookclass')
                : $this->payplug->l('hook.displayAdminOrderMain.notPaid', 'hookclass');
            if ((int)$payment->is_refunded == 1) {
                $pay_status = $this->payplug->l('hook.displayAdminOrderMain.refunded', 'hookclass');
            } elseif ((int)$payment->amount_refunded > 0) {
                $pay_status = $this->payplug->l('hook.displayAdminOrderMain.partiallyRefunded', 'hookclass');
            }
            $pay_amount = (int)$payment->amount / 100;
            $pay_date = date('d/m/Y H:i', (int)$payment->created_at);
            if ($payment->card->brand != '') {
                $pay_brand = $payment->card->brand;
            } else {
                $pay_brand = $this->payplug->l('hook.displayAdminOrderMain.unavailable', 'hookclass');
            }
            if ($payment->card->country != '') {
                $pay_brand .= ' ' . $this->payplug->l('hook.displayAdminOrderMain.card', 'hookclass') .
                    ' (' . $payment->card->country . ')';
            }
            if ($payment->card->last4 != '') {
                $pay_card_mask = '**** **** **** ' . $payment->card->last4;
            } else {
                $pay_card_mask = $this->payplug->l('hook.displayAdminOrderMain.unavailable', 'hookclass');
            }

            // Deferred payment does'nt display 3DS option before capture so we have to consider it null
            if ($payment->is_3ds !== null) {
                $pay_tds = $payment->is_3ds
                    ? $this->payplug->l('hook.displayAdminOrderMain.yes', 'hookclass')
                    : $this->payplug->l('hook.displayAdminOrderMain.no', 'hookclass');
                $this->assign->assign(['pay_tds' => $pay_tds]);
            }

            $pay_mode = $payment->is_live
                ? $this->payplug->l('hook.displayAdminOrderMain.live', 'hookclass')
                : $this->payplug->l('hook.displayAdminOrderMain.test', 'hookclass');

            if ($payment->card->exp_month === null) {
                $pay_card_date = $this->payplug->l('hook.displayAdminOrderMain.unavailable', 'hookclass');
            } else {
                $pay_card_date = date(
                    'm/y',
                    strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year)
                );
            }

            $show_menu_payment = true;

            $this->assign->assign([
                'pay_id' => $pay_id,
                'pay_status' => $pay_status,
                'pay_amount' => $pay_amount,
                'pay_date' => $pay_date,
                'pay_brand' => $pay_brand,
                'pay_card_mask' => $pay_card_mask,
                'pay_card_date' => $pay_card_date,
                'pay_error' => $pay_error,
            ]);

            //Deferred payment does'nt display 3DS option before capture so we have to consider it null
            if ($payment->is_3ds !== null) {
                $pay_tds = $payment->is_3ds
                    ? $this->payplug->l('hook.displayAdminOrderMain.yes', 'hookclass')
                    : $this->payplug->l('hook.displayAdminOrderMain.no', 'hookclass');
                $this->assign->assign(['pay_tds' => $pay_tds]);
            }
        }

        $currency = $this->currency->getCurrency($id_currency);
        if (!$this->validate->validate('isLoadedObject', $currency)) {
            return false;
        }

        $amount_suggested = (min($amount_refunded_presta, $amount_available) - $amount_refunded_payplug);
        $amount_suggested = number_format((float)$amount_suggested, 2);
        if ($amount_suggested < 0) {
            $amount_suggested = 0;
        }

        if ($display_refund) {
            $this->assign->assign([
                'order' => $order,
                'amount_refunded_payplug' => $amount_refunded_payplug,
                'amount_available' => $amount_available,
                'amount_refunded_presta' => $amount_refunded_presta,
                'currency' => $currency,
                'amount_suggested' => $amount_suggested,
                'id_new_order_state' => $id_new_order_state,
            ]);
        } elseif ($show_menu_refunded) {
            $this->assign->assign([
                'amount_refunded_payplug' => $amount_refunded_payplug,
                'currency' => $currency,
            ]);
        } elseif ($show_menu_update) {
            $this->assign->assign([
                'admin_ajax_url' => $admin_ajax_url,
                'order' => $order,
            ]);
        }

        $display_single_payment = $show_menu_payment;
        $this->assign->assign([
            'logo_url' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'display_single_payment' => $display_single_payment,
            'display_refund' => $display_refund,
            'refund_delay_oney' => $refund_delay_oney,
            'show_menu_payment' => $show_menu_payment,
            'show_menu_refunded' => $show_menu_refunded,
            'show_menu_update' => $show_menu_update,
            'show_menu_installment' => $show_menu_installment,
            'pay_mode' => $pay_mode,
            'order' => $order,
        ]);

        if ($display_single_payment) {
            $this->assign->assign([
                'single_payment' => $single_payment,
            ]);
        }

        if ($show_popin && $display_refund) {
            $this->payplug->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin_order_popin.js');
        }

        // check order state history
        $undefined_history_states = $this->payplug->getUndefinedOrderHistory($order->id);
        if (!empty($undefined_history_states)) {
            $payplug_order_state_url = 'https://support.payplug.com/hc/'
                . $this->context->language->iso_code
                . '/articles/4406805105298';
            $this->assign->assign([
                'payplug_order_state_url' => $payplug_order_state_url,
                'undefined_history_states' => $undefined_history_states,
            ]);
        }

        $this->html .= $this->payplug->fetchTemplate('/views/templates/admin/order/order.tpl');
        return $this->html;
    }

    /**
     * @param $params
     * @return string
     */
    public function displayBackOfficeFooter($params)
    {
        if (version_compare(_PS_VERSION_, '1.6.1.0', '<')) {
            $this->payplug->assignContentVar();
            $this->assign->assign([
                'js_def' => Media::getJsDef(),
            ]);
            return $this->payplug->fetchTemplate('/views/templates/hook/_partials/javascript.tpl');
        }
    }

    /**
     * Display Oney CTA on Shopping cart page
     *
     * @param array $params
     * @return bool|mixedf
     */
    public function displayBeforeShoppingCartBlock($params)
    {
        if (!$this->oney->isOneyAllowed()) {
            return false;
        }

        $amount = $params['cart']->getOrderTotal(true);
        $is_valid_amount = $this->oney->isValidOneyAmount($amount, $params['cart']->id_currency);

        $this->assign->assign([
            'payplug_oney_amount' => $amount,
            'payplug_oney_allowed' => $is_valid_amount['result'],
            'payplug_oney_error' => $is_valid_amount['error'],
            'use_fees' => (bool)$this->config->get('PAYPLUG_ONEY_FEES'),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
        ]);

        return $this->oney->getOneyCTA('checkout');
    }

    /**
     * @param $params
     * @return string|void
     */
    public function displayExpressCheckout($param)
    {
        if (!$this->oney->isOneyAllowed() ||
            (string)$this->tools->tool('strtoupper', $this->context->language->iso_code) !=
            Configuration::get('PAYPLUG_COMPANY_ISO')
        ) {
            return false;
        }

        $use_taxes = (bool)$this->config->get('PS_TAX');
        $amount = $this->context->cart->getOrderTotal($use_taxes);
        $is_elligible = $this->oney->isValidOneyAmount($amount);
        $is_elligible = $is_elligible['result'];

        $this->assign->assign([
            'env' => 'checkout',
            'payplug_is_oney_elligible' => $is_elligible,
            'use_fees' => (bool)$this->config->get('PAYPLUG_ONEY_FEES'),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
        ]);
        return $this->payplug->fetchTemplate('oney/cta.tpl');
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     */
    public function displayHeader($params)
    {
        if (!ConfigClass::isAllowed()) {
            return false;
        }

        if ($this->tools->tool('getValue', 'error')) {
            Media::addJsDef(['payment_errors' => true]);
        }
        if ((class_exists($this->payplug->PrestashopSpecificClass))
            && (method_exists($this->payplug->PrestashopSpecificObject, 'displayHeader'))) {
            $this->payplug->PrestashopSpecificObject->displayHeader();
        }

        if ((int)$this->tools->tool('getValue', 'lightbox') == 1) {
            $cart = $params['cart'];
            if (!$this->validate->validate('isLoadedObject', $cart)) {
                return;
            }

            $this->payplug->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/embedded.js');

            $payment_options = [
                'id_card' => $this->tools->tool('getValue', 'pc', 'new_card'),
                'is_installment' => (bool)$this->tools->tool('getValue', 'inst'),
                'is_deferred' => (bool)$this->tools->tool('getValue', 'def'),
            ];

            $payment = $this->payplug->preparePayment($payment_options);

            if ($payment['result']) {
                // If payment is paid then redirect
                if ($payment['redirect']) {
                    $this->tools->tool('redirect', $payment['return_url']);
                } else {
                    // else show the popin
                    $this->assign->assign([
                        'payment_url' => $payment['return_url'],
                        'api_url' => $this->payplug->apiClass->getApiUrl(),
                    ]);
                    return $this->payplug->fetchTemplate('checkout/embedded.tpl');
                }
            } else {
                $this->payplug->setPaymentErrorsCookie([
                    $this->payplug->l('hook.header.transactionNotCompleted', 'hookclass')
                ]);
                $error_url = 'index.php?controller=order&step=3&error=1';
                $this->tools->tool('redirect', $error_url);
            }
        }

        if ($this->config->get('PAYPLUG_ONEY')) {
            Media::addJsDef([
                'payplug_oney' => true,
                'payplug_oney_loading_msg' => $this->payplug->l('hook.header.loading', 'hookclass')
            ]);
        }

        $payplug_ajax_url = $this->context->link->getModuleLink($this->payplug->name, 'ajax', [], true);
        $dotenv = new Dotenv();
        $dotenvFile = dirname(dirname(dirname(__FILE__))) . "/payplugroutes/.env";
        if (file_exists($dotenvFile)) {
            $dotenv->load($dotenvFile);
            $payplug_domain = $_ENV['PAYPLUG_DOMAIN'];
        } else {
            $payplug_domain = "https://secure.payplug.com";
        }

        $integratedPaymentError = $this->payplug->l('hook.header.integratedPayment.error', 'hookclass');

        Media::addJsDef(
            [
                'payplug_ajax_url' => $payplug_ajax_url,
                'integratedPaymentError' => $integratedPaymentError,
                'payplug_publishable_key' => $this->payplug->apiClass->publishableKey,
                'PAYPLUG_DOMAIN' => $payplug_domain,
            ]
        );
    }

    /**
     * @param $param
     * @return false
     */
    public function displayProductPriceBlock($param)
    {
        $current_controller = Dispatcher::getInstance()->getController();

        if (!$this->oney->isOneyAllowed()
            || $current_controller != 'product'
            || (string)$this->tools->tool('strtoupper', $this->context->language->iso_code) !=
            Configuration::get('PAYPLUG_COMPANY_ISO')) {
            return false;
        }

        $action = $this->tools->tool('getValue', 'action');
        if ($action == 'quickview') {
            return false;
        }
        if (!isset($param['product'])
            || !isset($param['type'])
            || !in_array($param['type'], ['after_price'])
        ) {
            return false;
        }

        if ($action == 'refresh') {
            $use_taxes = (bool)$this->config->get('PS_TAX');

            $id_product = (int)$this->tools->tool('getValue', 'id_product');
            $group = $this->tools->tool('getValue', 'group');

            // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
            if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
                $id_product_attribute = $group ? (int)$this->product->getIdProductAttributesByIdAttributes(
                    $id_product,
                    $group
                ) : 0;
            } else {
                $id_product_attribute = $group ? (int)$this->product->getIdProductAttributeByIdAttributes(
                    $id_product,
                    $group
                ) : 0;
            }
            $quantity = (int)$this->tools->tool('getValue', 'qty', (int)$this->tools->tool('getValue', 'quantity_wanted', 1));

            $product_price = $this->product->getPriceStatic(
                (int)$id_product,
                $use_taxes,
                $id_product_attribute,
                6,
                null,
                false,
                true,
                $quantity
            );
            $amount = $product_price * $quantity;
            $is_elligible = $this->oney->isValidOneyAmount($amount, $this->context->currency->id);
            $is_elligible = $is_elligible['result'];

            $this->assign->assign([
                'payplug_is_oney_elligible' => $is_elligible,
            ]);
            $this->assign->assign(['popin' => true]);
        }

        $this->assign->assign([
            'env' => 'product',
            'use_fees' => (bool)$this->config->get('PAYPLUG_ONEY_FEES'),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
        ]);
        return $this->payplug->fetchTemplate('oney/cta.tpl');
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     *
     * This hook is not used anymore in PS 1.7 but we have to keep it for retro-compatibility
     */
    public function payment($params)
    {
        if (!ConfigClass::isAllowed()) {
            return false;
        }

        $use_taxes = $this->config->get('PS_TAX');
        $base_total_tax_inc = $params['cart']->getOrderTotal(true);
        $base_total_tax_exc = $params['cart']->getOrderTotal(false);

        if ($use_taxes) {
            $price2display = $base_total_tax_inc;
        } else {
            $price2display = $base_total_tax_exc;
        }

        $cart = $params['cart'];

        $result_currency = $this->currency->getCurrency($cart->id_currency);
        $supported_currencies = explode(';', $this->config->get('PAYPLUG_CURRENCIES'));
        if (!in_array($result_currency->iso_code, $supported_currencies, true)) {
            return false;
        }

        if ($this->config->get('PAYPLUG_ONEY_OPTIMIZED')) {
            $this->oney->assignOneyPaymentOptions($cart);
        }

        $payment_options = $this->payplug->getPaymentOptions($cart);

        // Transforme tableau en TPL
        $paymentOptions = $this->payplug->PrestashopSpecificObject->displayPaymentOption(
            $payment_options,
            $cart
        );

        foreach ($paymentOptions as $paymentOption) {
            $find = 'oney';
            if (strstr($paymentOption['tpl'], $find)) {
                $this->payplug->oneyLogoUrl = $paymentOption['logo_url'];
            }
        }

        $this->assign->assign([
            'use_fees' => (bool)$this->config->get('PAYPLUG_ONEY_FEES'),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
            'payplug_payment_options' => $paymentOptions,
            'spinner_url' => $this->tools->tool('getHttpHost', true) .
                __PS_BASE_URI__ . 'modules/payplug/views/img/admin/spinner.gif',
            'front_ajax_url' => $this->context->link->getModuleLink($this->payplug->name, 'ajax', [], true),
            'api_url' => $this->payplug->apiClass->getApiUrl(),
            'price2display' => $price2display,
            'this_path' => $this->payplug->getPath(),
        ]);

        return $this->payplug->fetchTemplate('checkout/payment/display.tpl');
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     *
     */
    public function paymentOptions($params)
    {
        if (!ConfigClass::isAllowed()) {
            return false;
        }

        $cart = $params['cart'];
        if (!$this->validate->validate('isLoadedObject', $cart)) {
            return false;
        }

        $this->assign->assign([
            'api_url' => $this->payplug->apiClass->getApiUrl(),
        ]);

        $payment_options = $this->payplug->getPaymentOptions($cart); // Données sous forme de tableau (pour 1.6 et 1.7)

        return $this->payplug->PrestashopSpecificObject->displayPaymentOption($payment_options); // Transforme tableau en object
    }

    /**
     * @param array $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function paymentReturn($params)
    {
        if (!ConfigClass::isAllowed()) {
            return false;
        }

        $order_id = $this->tools->tool('getValue', 'id_order');
        $order = $this->order->get((int)$order_id);
        // Check order state to display appropriate message
        $state = null;
        if (isset($order->current_state)
            && $order->current_state == $this->config->get('PAYPLUG_ORDER_STATE_PENDING')
        ) {
            $state = 'pending';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->config->get('PAYPLUG_ORDER_STATE_PAID')
        ) {
            $state = 'paid';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->config->get('PAYPLUG_ORDER_STATE_PENDING_TEST')
        ) {
            $state = 'pending_test';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->config->get('PAYPLUG_ORDER_STATE_PAID_TEST')
        ) {
            $state = 'paid_test';
        }

        $this->assign->assign('state', $state);
        // Get order information for display
        $total_paid = number_format($order->total_paid, 2, ',', '');
        $context = ['totalPaid' => $total_paid];
        if (isset($order->reference)) {
            $context['reference'] = $order->reference;
        }
        $this->assign->assign($context);
        return $this->payplug->fetchTemplate('checkout/order-confirmation.tpl');
    }

    /**
     * @param $params
     */
    public function registerGDPRConsent($params)
    {
    }
}
