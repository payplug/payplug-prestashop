<?php
/**
 * 2013 - 2022 PayPlug SAS.
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

use Symfony\Component\Dotenv\Dotenv;

class HookClass
{
    private $assign;
    private $cache;
    private $card;
    private $config;
    private $constant;
    private $context;
    private $dependencies;
    private $dispatcher;
    private $html;
    private $language;
    private $media;
    private $oney;
    private $order;
    private $orderHistory;
    private $orderState;
    private $orderStateAdapter;
    private $product;
    private $query;
    private $sql;
    private $tools;
    private $validate;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->assign = $this->dependencies->getPlugin()->getAssign();
        $this->cache = $this->dependencies->getPlugin()->getCache();
        $this->card = $this->dependencies->getPlugin()->getCard();
        $this->cart = $this->dependencies->getPlugin()->getCart();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->currency = $this->dependencies->getPlugin()->getCurrency();
        $this->dispatcher = $this->dependencies->getPlugin()->getDispatcher();
        $this->language = $this->dependencies->getPlugin()->getLanguage();
        $this->media = $this->dependencies->getPlugin()->getMedia();
        $this->module = $this->dependencies->getPlugin()->getModule();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->order = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistory = $this->dependencies->getPlugin()->getOrderHistory();
        $this->orderState = $this->dependencies->getPlugin()->getOrderState();
        $this->orderStateAdapter = $this->dependencies->getPlugin()->getOrderStateAdapter();
        $this->product = $this->dependencies->getPlugin()->getProduct();
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->sql = $this->dependencies->getPlugin()->getSql();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
    }

    public function actionAdminLanguagesControllerSaveAfter($params)
    {
        $language = $params['return'];

        if (!\in_array($language->iso_code, $this->dependencies->configClass->payplugLanguages)) {
            return true;
        }

        // clear Language cache
        $this->language->loadLanguages();

        $all_order_states = \array_merge(
            $this->configClass->orderStates,
            $this->dependencies->configClass->orderStatesOney
        );
        $id_order_states = $this->dependencies->orderClass->getPayPlugOrderStates($this->dependencies->name);
        $payplug_order_states = \explode(',', $id_order_states);

        foreach ($all_order_states as $state) {
            foreach ($state['payplug_cfg'] as $config) {
                $name = $state['name'][$language->iso_code];
                $id_order_state = $this->config->get(
                    $this->dependencies->concatenateModuleNameTo($config)
                );
                if (\in_array($id_order_state, $payplug_order_states)) {
                    if (\strpos($this->dependencies->concatenateModuleNameTo($config), '_TEST')) {
                        $name .= ' [TEST]';
                    } else {
                        $name .= ' [PayPlug]';
                    }
                }

                $order_state = $this->orderStateAdapter->get((int) $id_order_state);
                $order_state->name[$language->id] = $name;
                $order_state->save();
            }
        }

        return true;
    }

    /**
     * @description Flush PayPlugCache (PS 1.6), when PrestaShop cache cleared
     *
     * @return bool
     */
    public function actionAdminPerformanceControllerAfter()
    {
        if ($this->sql->checkExistingTable($this->dependencies->name . '_cache', 1)) {
            return $this->cache->flushCache();
        }
    }

    /**
     * @description Flush PayPlugCache (PS 1.7), when PrestaShop cache cleared
     *
     * @return bool
     */
    public function actionClearCompileCache()
    {
        if ($this->sql->checkExistingTable($this->dependencies->name . '_cache', 1)) {
            return $this->cache->flushCache();
        }
    }

    /**
     * @param $customer
     *
     * @return false|string
     */
    public function actionDeleteGDPRCustomer($customer)
    {
        if (!$this->card->deleteCards((int) $customer['id'])) {
            return \json_encode($this->dependencies->l('hook.actionDeleteGDPRCustomer.unableDelete', 'hookclass'));
        }

        return \json_encode(true);
    }

    /**
     * @param $customer
     *
     * @throws PrestaShopDatabaseException
     *
     * @return false|string
     */
    public function actionExportGDPRData($customer)
    {
        if (!$cards = $this->dependencies->configClass->gdprCardExport((int) $customer['id'])) {
            return \json_encode($this->dependencies->l('hook.actionExportGDPRData.unableToExport', 'hookclass'));
        }

        return \json_encode($cards);
    }

    /**
     * @param $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function actionOrderStatusUpdate($params)
    {
        $order = $this->order->get((int) $params['id_order']);
        $active = $this->module->isEnabled($this->dependencies->name);
        if (!$active
            || $order->payment != $this->module->getInstanceByName($this->dependencies->name)->displayName
            || !$this->config->get(
                $this->dependencies->getConfigurationKey('deferred')
            )
            || !$this->config->get(
                $this->dependencies->getConfigurationKey('deferredAuto')
            )
            || $params['newOrderStatus']->id != $this->config->get(
                $this->dependencies->getConfigurationKey('deferredState')
            )
        ) {
            return;
        }
        $cart = $this->cart->get((int) $order->id_cart);
        $payment_method = $this->dependencies->paymentClass->getPaymentMethodByCart($cart);
        if ($payment_method['type'] == 'installment') {
            $installment = new PPPaymentInstallment($payment_method['id'], $this->dependencies);
            $payment = $installment->getFirstPayment();
        } else {
            $payment = new PPPayment($payment_method['id'], $this->dependencies);
        }
        if (!$payment->isPaid()) {
            $capture = $this->dependencies->apiClass->capturePayment($payment->id);
            if ($capture['result']) {
                $payment = $capture['resource'];
                if ($payment->card->id !== null) {
                    $this->card->saveCard($payment);
                }
            }
        }
    }

    /**
     * @param $params
     *
     * @return bool
     */
    public function actionUpdateLangAfter($params)
    {
        $id_order_states = $this->dependencies->orderClass->getPayPlugOrderStates($this->dependencies->name);
        $payplug_order_states = \explode(',', $id_order_states);

        if (empty($payplug_order_states)
            || !\in_array($params['lang']->iso_code, $this->dependencies->configClass->payplugLanguages)) {
            return true;
        }

        $all_order_states = \array_merge(
            $this->dependencies->configClass->orderStates,
            $this->dependencies->configClass->orderStatesOney
        );

        foreach ($all_order_states as $order_state) {
            foreach ($order_state['payplug_cfg'] as $payplug_conf) {
                if (\in_array($this->config->get(
                    $this->dependencies->concatenateModuleNameTo($payplug_conf)
                ), $payplug_order_states)) {
                    $ps_order_state_name = $order_state['name'][$params['lang']->iso_code];
                    if (\strpos($this->dependencies->concatenateModuleNameTo(
                        $payplug_conf
                    ), '_TEST')) {
                        $ps_order_state_name .= ' [TEST]';
                    } else {
                        $ps_order_state_name .= ' [PayPlug]';
                    }

                    $ps_order_state = $this->orderStateAdapter->get((int) $this->config->get(
                        $this->dependencies->concatenateModuleNameTo($payplug_conf)
                    ));
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
     *
     * @return false|string|void
     */
    public function adminOrder($params)
    {
        if (\version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
            return $this->displayAdminOrderMain($params);
        }
    }

    /**
     * @return string|void
     */
    public function customerAccount()
    {
        if (!$this->dependencies->configClass->isAllowed()) {
            return false;
        }

        $payplug_cards_url = $this->context->link->getModuleLink(
            $this->dependencies->name,
            'cards',
            ['process' => 'cardlist'],
            true
        );

        $adapter = $this->dependencies->loadAdapterPresta();
        if ($adapter
            && (\method_exists($adapter, 'customerAccount'))) {
            $adapter->customerAccount();
        }

        $this->assign->assign([
            'version' => _PS_VERSION_[0] . '.' . _PS_VERSION_[2],
            'payplug_cards_url' => $payplug_cards_url,
        ]);

        return $this->dependencies->configClass->fetchTemplate('customer/my_account.tpl');
    }

    /**
     * @param array $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return string
     */
    public function displayAdminOrderMain($params)
    {
        $this->html = '';
        $order = $this->order->get((int) $params['id_order']);
        if (!$this->validate->validate('isLoadedObject', $order)) {
            return false;
        }

        if ($order->module != $this->dependencies->name) {
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

        $admin_ajax_url = $this->dependencies->adminClass->getAdminAjaxUrl('AdminModules', (int) $params['id_order']);
        $amount_refunded_presta = $this->dependencies->refundClass->getTotalRefunded($order->id);

        $inst_id = null;
        $payment_id = $this->dependencies->cartClass->getPayplugInstallmentCart((int) $order->id_cart);
        $sandbox = (bool) $this->config->get($this->dependencies->getConfigurationKey('sandboxMode'));

        // Backward if order validated before
        if (!$payment_id) {
            $payment_id = $this->dependencies->cartClass->getPayplugInstallmentCartBackward((int) $order->id_cart);
        }

        if ($payment_id && \strpos($payment_id, 'inst') !== false) {
            $inst_id = $payment_id;
        }

        if ($inst_id) {
            $payment_list = [];

            if ($sandbox) {
                $this->dependencies->apiClass->setSecretKey(
                    $this->config->get(
                        $this->dependencies->getConfigurationKey('testApiKey')
                    )
                );
            } else {
                $this->dependencies->apiClass->setSecretKey(
                    $this->config->get(
                        $this->dependencies->getConfigurationKey('liveApiKey')
                    )
                );
            }

            // If no installment plan id, return false
            if (!$inst_id || empty($inst_id)) {
                return false;
            }

            // Get the installment plan resource
            $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);

            // If No installment plan resource, test the other live mode configuration
            if (!$installment['result']) {
                if ($sandbox) {
                    $this->dependencies->apiClass->setSecretKey($this->config->get(
                        $this->dependencies->getConfigurationKey('liveApiKey')
                    ));
                    $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);
                } else {
                    $this->dependencies->apiClass->setSecretKey($this->config->get(
                        $this->dependencies->getConfigurationKey('testApiKey')
                    ));
                    $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);
                }
            }

            // If we still don't have a valid Installment plan resource, return false
            if (!$installment['result']) {
                return false;
            }
            $installment = $installment['resource'];

            $pay_mode = $installment->is_live
                ? $this->dependencies->l('hook.displayAdminOrderMain.live', 'hookclass')
                : $this->dependencies->l('hook.displayAdminOrderMain.test', 'hookclass');
            $payments = $order->getOrderPaymentCollection();
            $pps = [];
            if (\count($payments) > 0) {
                foreach ($payments as $payment) {
                    $pps[] = $payment->transaction_id;
                }
            }

            $payment_list_new = [];
            foreach ($installment->schedule as $schedule) {
                if ($schedule->payment_ids != null) {
                    foreach ($schedule->payment_ids as $pay_id) {
                        $p = $this->dependencies->apiClass->retrievePayment($pay_id);
                        if (!$p['result']) {
                            return false;
                        }
                        $p = $p['resource'];
                        $payment_list_new[] = $this->dependencies->paymentClass->buildPaymentDetails($p);
                        if ((int) $p->is_paid == 0) {
                            $amount_refunded_payplug += 0;
                            $amount_available += 0;
                        } elseif ((int) $p->is_refunded == 1) {
                            $amount_refunded_payplug += ($p->amount_refunded) / 100;
                            $amount_available += ($p->amount - $p->amount_refunded) / 100;
                        } elseif ((int) $p->amount_refunded > 0) {
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
                    if ($installment->is_active) {
                        $status = $this->dependencies->configClass->getPaymentStatus()[6];
                    } else {
                        $status = $this->dependencies->configClass->getPaymentStatus()[7];
                    }
                    $payment_list_new[] = [
                        'id' => null,
                        'status' => $status,
                        'status_class' => $installment->is_active ? 'pp_success' : 'pp_error',
                        'status_code' => 'incoming',
                        'amount' => (int) $schedule->amount / 100,
                        'card_brand' => null,
                        'card_mask' => null,
                        'tds' => null,
                        'card_date' => null,
                        'mode' => null,
                        'authorization' => null,
                        'date' => \date('d/m/Y', \strtotime($schedule->date)),
                    ];
                }
            }

            $id_currency = (int) $this->currency->getIdByIsoCode($installment->currency);
            $show_menu_installment = true;
            $inst_status = $installment->is_active ?
                $this->dependencies->l('hook.displayAdminOrderMain.ongoing', 'hookclass') :
                (
                    $installment->is_fully_paid ?
                    $this->dependencies->l('hook.displayAdminOrderMain.paid', 'hookclass') :
                    $this->dependencies->l('hook.displayAdminOrderMain.suspended', 'hookclass')
                );
            $inst_status_code = $installment->is_active ?
                'ongoing' :
                ($installment->is_fully_paid ? 'paid' : 'suspended');
            $inst_aborted = !$installment->is_active;
            $ppInstallment = new PPPaymentInstallment($installment->id, $this->dependencies);
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

            $sandbox = ((int) $installment->is_live == 1 ? false : true);
            $state_addons = ($sandbox ? '_TEST' : '');
            $id_new_order_state = (int) $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_REFUND') . $state_addons
            );

            $this->dependencies->installmentClass->updatePayplugInstallment($installment);
        } else {
            if (!$pay_id = $this->dependencies->paymentClass->isTransactionPending($order->id_cart)) {
                $pay_id = $this->dependencies->orderClass->getPayplugOrderPayment($order->id);

                if (!$pay_id) {
                    $payments = $order->getOrderPaymentCollection();
                    if (\count($payments->getResults()) > 1 || !$payments->getFirst()) {
                        return false;
                    }
                    $pay_id = $payments->getFirst()->transaction_id;
                }
            }

            // If no payment id, return false
            if (!$pay_id || empty($pay_id)) {
                return false;
            }

            // Get the Payment resource
            $payment = $this->dependencies->apiClass->retrievePayment($pay_id);

            // If No Payment resource, test the other live mode configuration
            if (!$payment['result']) {
                if ($sandbox) {
                    $this->dependencies->apiClass->setSecretKey($this->config->get(
                        $this->dependencies->getConfigurationKey('liveApiKey')
                    ));
                    $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
                } else {
                    $this->dependencies->apiClass->setSecretKey($this->config->get(
                        $this->dependencies->getConfigurationKey('testApiKey')
                    ));
                    $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
                }
            }

            // If we still don't have a valid Payment resource, return false
            if (!$payment['result']) {
                return false;
            }
            $payment = $payment['resource'];

            // check if order is from oney payment
            $oney_payment_method = [
                'oney_x3_with_fees',
                'oney_x4_with_fees',
                'oney_x3_without_fees',
                'oney_x4_without_fees',
            ];

            $is_oney = isset($payment->payment_method, $payment->payment_method['type'])

                && \in_array($payment->payment_method['type'], $oney_payment_method);

            $is_bancontact = isset($payment->payment_method, $payment->payment_method['type'])

                && $payment->payment_method['type'] == 'bancontact';

            // Update order state if is pending
            $state_addons = $payment->is_live ? '' : '_TEST';
            $paid_state = $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID') . $state_addons
            );
            $oney_state = $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ONEY_PG') . $state_addons
            );
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

            $single_payment = $this->dependencies->paymentClass->buildPaymentDetails($payment);

            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
            $amount_available_payment = ($payment->amount - $payment->amount_refunded);
            $amount_available = ($amount_available_payment >= 10 ? $amount_available_payment / 100 : 0);
            $id_currency = (int) $this->currency->getIdByIsoCode($payment->currency);
            $state_addons = (!$payment->is_live ? '_TEST' : '');

            $id_new_order_state = (int) $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_REFUND') . $state_addons
            );
            $id_pending_order_state = (int) $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PENDING') . $state_addons
            );

            $current_state = (int) $order->getCurrentState();

            if ((int) $payment->is_paid == 0) {
                if (isset($payment->failure, $payment->failure->message)) {
                    $pay_error = '(' . $payment->failure->message . ')';
                } else {
                    $pay_error = '';
                }
                $display_refund = false;
                if ($current_state != 0 && $current_state == $id_pending_order_state && !$is_bancontact) {
                    $show_menu_update = true;
                }
            } elseif ((((int) $payment->amount_refunded > 0)
                    || $amount_refunded_presta > 0)
                && (int) $payment->is_refunded != 1) {
                $display_refund = true;
                if ($is_oney) {
                    $refund_delay_oney = \time() <= $payment->refundable_after;
                }
            } elseif ((int) $payment->is_refunded == 1) {
                $show_menu_refunded = true;
                $display_refund = false;
            } elseif (\time() >= $payment->refundable_until) {
                $display_refund = false;
            } else {
                $display_refund = true;
                if ($is_oney) {
                    $refund_delay_oney = \time() <= $payment->refundable_after;
                }
            }

            $conf = (int) $this->tools->tool('getValue', 'conf');
            if ($conf == 30 || $conf == 31) {
                $show_popin = true;

                $admin_ajax_url = $this->dependencies->adminClass->getAdminAjaxUrl('AdminModules', (int) $params['id_order']);

                $this->html .= '<a class="pp_admin_ajax_url" href="' . $admin_ajax_url . '"></a>';
            }

            $pay_status = ((int) $payment->is_paid == 1)
                ? $this->dependencies->l('hook.displayAdminOrderMain.paid', 'hookclass')
                : $this->dependencies->l('hook.displayAdminOrderMain.notPaid', 'hookclass');
            if ((int) $payment->is_refunded == 1) {
                $pay_status = $this->dependencies->l('hook.displayAdminOrderMain.refunded', 'hookclass');
            } elseif ((int) $payment->amount_refunded > 0) {
                $pay_status = $this->dependencies->l('hook.displayAdminOrderMain.partiallyRefunded', 'hookclass');
            }
            $pay_amount = (int) $payment->amount / 100;
            $pay_date = \date('d/m/Y H:i', (int) $payment->created_at);
            if ($payment->card->brand != '') {
                $pay_brand = $payment->card->brand;
            } else {
                $pay_brand = $this->dependencies->l('hook.displayAdminOrderMain.unavailable', 'hookclass');
            }
            if ($payment->card->country != '') {
                $pay_brand .= ' ' . $this->dependencies->l('hook.displayAdminOrderMain.card', 'hookclass') .
                    ' (' . $payment->card->country . ')';
            }
            if ($payment->card->last4 != '') {
                $pay_card_mask = '**** **** **** ' . $payment->card->last4;
            } else {
                $pay_card_mask = $this->dependencies->l('hook.displayAdminOrderMain.unavailable', 'hookclass');
            }

            // Deferred payment does'nt display 3DS option before capture so we have to consider it null
            if ($payment->is_3ds !== null) {
                $pay_tds = $payment->is_3ds
                    ? $this->dependencies->l('hook.displayAdminOrderMain.yes', 'hookclass')
                    : $this->dependencies->l('hook.displayAdminOrderMain.no', 'hookclass');
                $this->assign->assign(['pay_tds' => $pay_tds]);
            }

            $pay_mode = $payment->is_live
                ? $this->dependencies->l('hook.displayAdminOrderMain.live', 'hookclass')
                : $this->dependencies->l('hook.displayAdminOrderMain.test', 'hookclass');

            if ($payment->card->exp_month === null) {
                $pay_card_date = $this->dependencies->l('hook.displayAdminOrderMain.unavailable', 'hookclass');
            } else {
                $pay_card_date = \date(
                    'm/y',
                    \strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year)
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
                    ? $this->dependencies->l('hook.displayAdminOrderMain.yes', 'hookclass')
                    : $this->dependencies->l('hook.displayAdminOrderMain.no', 'hookclass');
                $this->assign->assign(['pay_tds' => $pay_tds]);
            }
        }

        $currency = $this->currency->get((int) $id_currency);
        if (!$this->validate->validate('isLoadedObject', $currency)) {
            return false;
        }

        $amount_suggested = (\min($amount_refunded_presta, $amount_available) - $amount_refunded_payplug);
        $amount_suggested = \number_format((float) $amount_suggested, 2);
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

        $views_path = __PS_BASE_URI__ . 'modules/' . $this->dependencies->name . '/views/';
        $display_single_payment = $show_menu_payment;
        $this->assign->assign([
            'logo_url' => [
                'payplug' => $views_path . 'img/payplug.svg',
                'pspaylater' => $views_path . 'img/pspaylater.svg',
            ],
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
            $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
            $this->context->controller->addJS(
                $views_path . 'js/admin_order_popin-v' . $this->dependencies->version . '.js'
            );
        }

        // check order state history
        $undefined_history_states = $this->dependencies->orderClass->getUndefinedOrderHistory($order->id);
        if (!empty($undefined_history_states)) {
            $payplug_order_state_url = 'https://support.payplug.com/hc/'
                . $this->context->language->iso_code
                . '/articles/4406805105298';
            $this->assign->assign([
                'payplug_order_state_url' => $payplug_order_state_url,
                'undefined_history_states' => $undefined_history_states,
            ]);
        }

        $this->html .= $this->dependencies->configClass->fetchTemplate('/views/templates/admin/order/order.tpl');

        return $this->html;
    }

    public function actionAdminControllerSetMedia()
    {
        $controller = $this->dispatcher->getInstance()->getController();
        if ($controller
            && 'adminorders' == strtolower($controller)
            && $this->tools->tool('getValue', 'id_order')
        ) {
            $id_order = $this->tools->tool('getValue', 'id_order');
            $order = $this->order->get((int) $id_order);

            if ($order->module == $this->dependencies->name) {
                $module_url = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/';
                $this->dependencies->mediaClass->setMedia([
                    $module_url . 'views/css/admin_order-v' . $this->dependencies->version . '.css',
                    $module_url . 'views/js/admin_order-v' . $this->dependencies->version . '.js',
                    $module_url . 'views/js/utilities-v' . $this->dependencies->version . '.js',
                ]);
            }
        }
    }

    /**
     * @return string
     */
    public function displayBackOfficeFooter()
    {
        if (\version_compare(_PS_VERSION_, '1.6.1.0', '<')) {
            $this->dependencies->configClass->assignContentVar();
            $this->assign->assign([
                'js_def' => $this->media->getJsDef(),
            ]);

            return $this->dependencies->configClass->fetchTemplate('/views/templates/hook/_partials/javascript.tpl');
        }
    }

    /**
     * Display Oney CTA on Shopping cart page.
     *
     * @param array $params
     *
     * @return bool|mixedf
     */
    public function displayBeforeShoppingCartBlock($params)
    {
        if (!$this->oney->isOneyAllowed()
            || (string) $this->tools->tool('strtoupper', $this->context->language->iso_code) !=
            $this->config->get(
                $this->dependencies->getConfigurationKey('companyIso')
            )) {
            return false;
        }

        $amount = $params['cart']->getOrderTotal(true);
        $is_valid_amount = $this->oney->isValidOneyAmount($amount, $params['cart']->id_currency);

        $this->assign->assign([
            'payplug_oney_amount' => $amount,
            'payplug_oney_allowed' => $is_valid_amount['result'],
            'payplug_oney_error' => $is_valid_amount['error'],
            'use_fees' => (bool) $this->config->get(
                $this->dependencies->getConfigurationKey('oneyFees')
            ),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
        ]);

        return $this->oney->getOneyCTA('checkout');
    }

    /**
     * @return string|void
     */
    public function displayExpressCheckout()
    {
        if (!$this->oney->isOneyAllowed()
            || (string) $this->tools->tool('strtoupper', $this->context->language->iso_code) !=
            $this->config->get($this->dependencies->getConfigurationKey('companyIso'))
        ) {
            return false;
        }

        $use_taxes = (bool) $this->config->get('PS_TAX');
        $amount = $this->context->cart->getOrderTotal($use_taxes);
        $is_elligible = $this->oney->isValidOneyAmount($amount);
        $is_elligible = $is_elligible['result'];

        $this->assign->assign([
            'env' => 'checkout',
            'payplug_is_oney_elligible' => $is_elligible,
            'use_fees' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oneyFees')),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/cta.tpl');
    }

    /**
     * @param array $params
     *
     * @throws Exception
     *
     * @return string
     */
    public function displayHeader($params)
    {
        if (!$this->dependencies->configClass->isAllowed()) {
            return false;
        }

        $moduleName = $this->tools->tool('getValue', 'modulename');

        if ($this->tools->tool('getValue', 'has_error')
            && $this->dependencies->name == $moduleName) {
            $this->media->addJsDef(['check_errors' => true]);
        }

        $adapter = $this->dependencies->loadAdapterPresta();

        if ($adapter
            && (\method_exists($adapter, 'displayHeader'))) {
            $this->media->addJsDef([
                'module_name' => $this->dependencies->name,
            ]);
            $adapter->displayHeader();
        }

        $id_card = $this->tools->tool('getValue', 'pc', 'new_card');

        // Is embeddedMode configured to show the lightbox..
        $show_lightbox = 'popup' == $this->config->get($this->dependencies->getConfigurationKey('embeddedMode'));
        // ... or is the payment with one click
        $show_lightbox = $show_lightbox || 'new_card' != $id_card;

        $show_lightbox = $show_lightbox
            && $this->tools->tool('getValue', 'embedded')
            && $this->dependencies->name == $moduleName;

        if ($show_lightbox) {
            $cart = $params['cart'];
            if (!$this->validate->validate('isLoadedObject', $cart)) {
                return;
            }
            $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
            $this->context->controller->addJS($views_path . 'js/embedded-v' . $this->dependencies->version . '.js');

            $payment_options = [
                'id_card' => $id_card,
                'is_installment' => (bool) $this->tools->tool('getValue', 'inst'),
                'is_deferred' => (bool) $this->tools->tool('getValue', 'def'),
            ];

            $payment = $this->dependencies->paymentClass->preparePayment($payment_options);

            $dotenv = new Dotenv();
            $dotenvFile = \dirname(__FILE__, 4) . '/payplugroutes/.env';
            if (\file_exists($dotenvFile)) {
                $dotenv->load($dotenvFile);
                $integrated_payment_js_url = $_ENV['INTEGRATED_PAYMENT_DOMAIN'];
            } else {
                $integrated_payment_js_url = 'https://cdn.payplug.com/js/integrated-payment/v0/index.js';
            }

            if ($payment['result']) {
                // If payment is paid then redirect
                if ($payment['redirect']) {
                    $this->tools->tool('redirect', $payment['return_url']);
                } else {
                    // else show the popin

                    if ($this->config->get(
                        $this->dependencies->getConfigurationKey('embeddedMode')
                    ) == 'integrated') {
                        $api_url = $integrated_payment_js_url;
                        $this->media->addJsDef([
                            'isIntegratedPayment' => true,
                        ]);
                    } else {
                        $api_url = $this->dependencies->apiClass->getApiUrl() . '/js/1/form.latest.js';
                    }

                    $this->assign->assign([
                        'payment_url' => $payment['return_url'],
                        'api_url' => $api_url,
                    ]);

                    return $this->dependencies->configClass->fetchTemplate('checkout/embedded.tpl');
                }
            } else {
                $this->dependencies->paymentClass->setPaymentErrorsCookie([
                    $this->dependencies->l('hook.header.transactionNotCompleted', 'hookclass'),
                ]);
                $error_url = 'index.php?controller=order&step=3&has_error=1&modulename=' . $this->dependencies->name;
                $this->tools->tool('redirect', $error_url);
            }
        }

        if ($this->config->get(
            $this->dependencies->getConfigurationKey('oney')
        )) {
            $this->media->addJsDef([
                $this->dependencies->name . '_oney' => true,
                $this->dependencies->name . '_oney_loading_msg' => $this->dependencies->l('hook.header.loading', 'hookclass'),
            ]);
        }

        $payplug_ajax_url = $this->context->link->getModuleLink($this->dependencies->name, 'ajax', [], true);
        $dotenv = new Dotenv();
        $dotenvFile = \dirname(\dirname(\dirname(__FILE__))) . '/payplugroutes/.env';
        if (\file_exists($dotenvFile)) {
            $dotenv->load($dotenvFile);
            $payplug_domain = $_ENV['PAYPLUG_DOMAIN'];
        } else {
            $payplug_domain = 'https://secure.payplug.com';
        }

        if ('integrated' == $this->config->get($this->dependencies->getConfigurationKey('embeddedMode'))) {
            $integratedPaymentError = $this->dependencies->l('hook.header.integratedPayment.error', 'hookclass');
            $sandbox = $this->config->get($this->dependencies->getConfigurationKey('sandboxMode'));
            $this->media->addJsDef([
                'integratedPaymentError' => $integratedPaymentError,
                'payplug_publishable_key' => $this->config->get(
                    $this->dependencies->getConfigurationKey('publishableKey') . ($sandbox ? '_TEST' : '')
                ),
            ]);
        }

        if ($this->config->get(
            $this->dependencies->getConfigurationKey('applepay')
        )) {
            $this->media->addJsDef([
                'applePayPaymentRequestAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'applepaypaymentrequest', [], true),
                'applePayMerchantSessionAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'dispatcher', [], true),
                'applePayPaymentAjaxURL' => $this->context->link->getModuleLink($this->dependencies->name, 'validation', [], true),
                'applePayIdCart' => $this->context->cart->id,
            ]);
        }

        $this->media->addJsDef(
            [
                $this->dependencies->name . '_ajax_url' => $payplug_ajax_url,
                'PAYPLUG_DOMAIN' => $payplug_domain,
            ]
        );
    }

    /**
     * @param $param
     *
     * @return false
     */
    public function displayProductPriceBlock($param)
    {
        $current_controller = $this->dispatcher->getInstance()->getController();

        if (!$this->oney->isOneyAllowed()
            || $current_controller != 'product'
            || (string) $this->tools->tool('strtoupper', $this->context->language->iso_code) !=
            $this->config->get($this->dependencies->getConfigurationKey('companyIso'))) {
            return false;
        }

        $action = $this->tools->tool('getValue', 'action');
        if ($action == 'quickview') {
            return false;
        }
        if (!isset($param['product'])
            || !isset($param['type'])
            || !\in_array($param['type'], ['after_price'])
        ) {
            return false;
        }

        if ($action == 'refresh') {
            $use_taxes = (bool) $this->config->get('PS_TAX');

            $id_product = (int) $this->tools->tool('getValue', 'id_product');
            $group = $this->tools->tool('getValue', 'group');

            // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
            if (\version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
                $id_product_attribute = $group ? (int) $this->product->getIdProductAttributesByIdAttributes(
                    $id_product,
                    $group
                ) : 0;
            } else {
                $id_product_attribute = $group ? (int) $this->product->getIdProductAttributeByIdAttributes(
                    $id_product,
                    $group
                ) : 0;
            }
            $quantity = (int) $this->tools->tool(
                'getValue',
                'qty',
                (int) $this->tools->tool('getValue', 'quantity_wanted', 1)
            );

            $product_price = $this->product->getPriceStatic(
                (int) $id_product,
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
            'use_fees' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oneyFees')),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
        ]);

        return $this->dependencies->configClass->fetchTemplate('oney/cta.tpl');
    }

    /**
     * @param array $params
     *
     * @throws Exception
     *
     * This hook is not used anymore in PS 1.7 but we have to keep it for retro-compatibility
     *
     * @return string
     */
    public function payment($params)
    {
        if (!$this->dependencies->configClass->isAllowed()) {
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

        $result_currency = $this->currency->get((int) $cart->id_currency);
        $supported_currencies = \explode(';', $this->config->get(
            $this->dependencies->getConfigurationKey('currencies')
        ));
        if (!\in_array($result_currency->iso_code, $supported_currencies, true)) {
            return false;
        }

        if ($this->config->get(
            $this->dependencies->getConfigurationKey('oneyOptimized')
        )) {
            $this->oney->assignOneyPaymentOptions($cart);
        }

        $payment_options = $this->dependencies->paymentClass->getPaymentOptions();

        // Transforme tableau en TPL
        $paymentOptions = $this->dependencies->loadAdapterPresta()->displayPaymentOption(
            $payment_options,
            $cart
        );

        $this->assign->assign([
            'use_fees' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oneyFees')),
            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
            'payplug_payment_options' => $paymentOptions,
            'spinner_url' => $this->tools->tool('getHttpHost', true) .
                __PS_BASE_URI__ . 'modules/' . $this->dependencies->name . '/views/img/admin/spinner.gif',
            'front_ajax_url' => $this->context->link->getModuleLink($this->dependencies->name, 'ajax', [], true),
            'api_url' => $this->dependencies->apiClass->getApiUrl(),
            'price2display' => $price2display,
        ]);

        return $this->dependencies->configClass->fetchTemplate('checkout/payment/display.tpl');
    }

    /**
     * @param array $params
     *
     * @throws Exception
     *
     * @return array
     */
    public function paymentOptions()
    {
        if (!$this->dependencies->configClass->isAllowed()) {
            return false;
        }

        $this->assign->assign([
            'api_url' => $this->dependencies->apiClass->getApiUrl(),
        ]);

        // Données sous forme de tableau (pour 1.6 et 1.7)
        $payment_options = $this->dependencies->paymentClass->getPaymentOptions();

        // Transforme tableau en object
        return $this->dependencies->loadAdapterPresta()->displayPaymentOption($payment_options);
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return string
     */
    public function paymentReturn()
    {
        if (!$this->dependencies->configClass->isAllowed()) {
            return false;
        }

        $order_id = $this->tools->tool('getValue', 'id_order');
        $order = $this->order->get((int) $order_id);
        // Check order state to display appropriate message
        $state = null;
        if (isset($order->current_state)
            && $order->current_state == $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PENDING')
            )
        ) {
            $state = 'pending';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID')
            )
        ) {
            $state = 'paid';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PENDING_TEST')
            )
        ) {
            $state = 'pending_test';
        } elseif (isset($order->current_state)
            && $order->current_state == $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID_TEST')
            )
        ) {
            $state = 'paid_test';
        }

        $this->assign->assign('state', $state);
        // Get order information for display
        $total_paid = \number_format($order->total_paid, 2, ',', '');
        $context = ['totalPaid' => $total_paid];
        if (isset($order->reference)) {
            $context['reference'] = $order->reference;
        }
        $this->assign->assign($context);

        return $this->dependencies->configClass->fetchTemplate('checkout/order-confirmation.tpl');
    }
}
