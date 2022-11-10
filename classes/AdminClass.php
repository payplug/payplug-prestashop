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

class AdminClass
{
    private $assign;
    private $context;
    private $dependencies;
    private $config;
    private $html = '';
    private $order;
    private $orderHistory;
    private $orderState;
    private $paymentRepository;
    private $tools;
    private $validate;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->assign = $this->dependencies->getPlugin()->getAssign();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->order = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistory = $this->dependencies->getPlugin()->getOrderHistory();
        $this->orderState = $this->dependencies->getPlugin()->getOrderStateAdapter();
        $this->paymentRepository = $this->dependencies->getPlugin()->getPayment();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
    }

    /**
     * @param string $controller_name
     * @param int    $id_order
     *
     * @return string
     */
    public function getAdminAjaxUrl($controller_name = 'AdminModules', $id_order = 0)
    {
        if ($controller_name == 'AdminModules') {
            switch ($this->dependencies->name) {
                case 'pspaylater':
                    $admin_ajax_url = $this->context->link->getAdminLink('AdminPsPayLater');

                    break;

                case 'payplug':
                    $admin_ajax_url = $this->context->link->getAdminLink('AdminPayplug');

                    break;
            }
        } elseif ($controller_name == 'AdminOrders') {
            $admin_ajax_url = $this->context->link->getAdminLink($controller_name) . '&id_order=' . $id_order
                . '&vieworder';
        }

        return $admin_ajax_url;
    }

    /**
     * @param string $controller_name
     * @param int    $id_order
     * @param mixed  $params
     *
     * @return string
     */
    public function getAdminUrl($controller_name = 'AdminModules', $params = [])
    {
        if (!empty($params) && !is_array($params)) {
            return false;
        }

        $admin_url = $this->context->link->getAdminLink($controller_name);
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $admin_url .= '&' . $key . (empty($value) ? '' : '=' . $value);
            }
        }

        return $admin_url;
    }

    /**
     * @return string
     *
     * @see Module::getContent()
     */
    public function getContent()
    {
        if ($this->tools->tool('getValue', '_ajax')) {
            $this->adminAjaxController();
        }

        $this->dependencies->configClass->postProcess();

        $this->dependencies->configClass->assignContentVar();

        if ($this->tools->tool('getValue', 'show_components')) {
            return $this->dependencies->configClass->fetchTemplate('/views/templates/admin/components.tpl');
        }

        $this->html .= $this->dependencies->configClass->fetchTemplate('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function adminAjaxController()
    {
        if (!$this->tools->tool('getValue', '_ajax', false)) {
            return;
        }

        if ($this->tools->tool('getValue', 'popin')) {
            $args = null;
            if ($this->tools->tool('getValue', 'type') == 'confirm') {
                $keys = [
                    'activate',
                    'sandbox',
                    'embedded',
                    'standard',
                    'one_click',
                    'oney',
                    'bancontact',
                    'installment',
                    'deferred',
                ];
                $args = [];
                foreach ($keys as $key) {
                    if ($key !== 'embedded') {
                        $args[$key] = (int) $this->tools->tool('getValue', $key);
                    } else {
                        $args[$key] = (string) $this->tools->tool('getValue', $key);
                    }
                }
            }
            if ($this->tools->tool('getValue', 'permissionsModal')) {
                if ($this->tools->tool('getValue', 'type') == 'oneyPremium') {
                    $link = 'https://portal.payplug.com/#/configuration/oney';
                } elseif ($this->tools->tool('getValue', 'type') == 'bancontactPremium') {
                    switch ($this->context->language->iso_code) {
                        case 'fr':
                            $link = 'https://support.payplug.com/hc/fr/requests/new?ticket_form_id=4583813991452';

                            break;

                        case 'it':
                            $link = 'https://support.payplug.com/hc/it/requests/new?ticket_form_id=4583813991452';

                            break;

                        default:
                            $link = 'https://support.payplug.com/hc/en-gb/requests/new?ticket_form_id=4583813991452';

                            break;
                    }
                } elseif ($this->tools->tool('getValue', 'type') == 'applepayPremium') {
                    $link = 'mailto:support@payplug.com';
                } elseif ($this->tools->tool('getValue', 'type') == 'amexPremium') {
                    switch ($this->context->language->iso_code) {
                        case 'fr':
                            $link = 'https://support.payplug.com/hc/fr/requests/new';

                            break;

                        case 'it':
                            $link = 'https://support.payplug.com/hc/it/requests/new';

                            break;

                        default:
                            $link = 'https://support.payplug.com/hc/en-gb/requests/new';

                            break;
                    }
                } else {
                    $link = 'https://www.payplug.com/contact';
                }
                $title = $this->dependencies->l('payplug.adminAjaxController.enableFeature', 'adminclass');
                $this->assign->assign(
                    [
                        'premiumContent' => [
                            'link' => $link,
                            'type' => $this->tools->tool('getValue', 'type'),
                            'title' => $title,
                        ],
                    ]
                );
                $htmlPopin = $this->dependencies->configClass->fetchTemplate(
                    '/views/templates/api/molecules/modal/premium.tpl'
                );

                exit(json_encode(['content' => $htmlPopin]));
            }

            $this->dependencies->mediaClass->displayPopin($this->tools->tool('getValue', 'type'), $args);
        }

        if ($this->tools->tool('getValue', 'submitSettings')) {
            if ($this->tools->tool('getValue', 'payplug_deferred_state')
                && $this->tools->tool('getValue', 'payplug_deferred_state') != $this->config->get(
                    $this->dependencies->getConfigurationKey('deferredState')
                )) {
                $id_order_state = $this->tools->tool('getValue', 'payplug_deferred_state');
                $order_state = $this->orderState->get((int) $id_order_state, $this->context->language->id);
                if ($this->tools->tool('getValue', 'payplug_deferred')) {
                    $this->context->smarty->assign([
                        'updated_deferred_state' => true,
                        'updated_deferred_state_id' => $this->tools->tool('getValue', 'payplug_deferred_state'),
                        'updated_deferred_state_name' => $order_state->name,
                        'admin_orders_link' => $this->dependencies->configClass
                            ->getAdapterPrestaClasse()
                            ->getOrdersByStateLink(
                                $this->tools->tool('getValue', 'payplug_deferred_state')
                            ),
                    ]);
                }
            }

            $this->dependencies->configClass->saveConfiguration();

            $this->dependencies->configClass->assignContentVar();
            $content = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/admin.tpl');

            $this->context->smarty->assign([
                'title' => '',
                'type' => 'save',
            ]);
            $popin = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/popin.tpl');

            exit(json_encode(['popin' => $popin, 'content' => $content]));
        }

        if ($this->tools->tool('isSubmit', 'submitAccount')) {
            $this->dependencies->configClass->submitAccount();
        }

        if ($this->tools->tool('isSubmit', 'checkOnboarding')) {
            $this->dependencies->configClass->checkOnboarding();
        }

        if ($this->tools->tool('isSubmit', 'checkState')) {
            $content = $this->dependencies->configClass->checkState();
            if ($content) {
                exit(json_encode(['content' => $content]));
            }

            exit(json_encode(['content' => false]));
        }

        if ($this->tools->tool('getValue', 'submitPwd')) {
            $password = $this->tools->tool('getValue', 'password');
            $isPlaintextPassword = $this->dependencies->configClass
                ->getAdapterPrestaClasse()
                ->isPlaintextPassword($password)
            ;

            if (!$password || !$isPlaintextPassword) {
                exit(json_encode([
                    'content' => null,
                    'error' => $this->dependencies->l('payplug.adminAjaxController.passwordInvalid', 'adminclass'),
                ]));
            }

            $email = $this->config->get(
                $this->dependencies->getConfigurationKey('email')
            );
            if ($this->dependencies->apiClass->login($email, $password)) {
                $api_key = $this->config->get(
                    $this->dependencies->getConfigurationKey('liveApiKey')
                );
                if ((bool) $api_key) {
                    $this->config->updateValue($this->dependencies->getConfigurationKey('sandboxMode'), 0);
                    $this->dependencies->configClass->assignContentVar();
                    $content = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/admin.tpl');

                    exit(json_encode(['content' => $content]));
                }
                $this->context->smarty->assign([
                    'title' => '',
                    'type' => 'activate',
                ]);
                $popin = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/popin.tpl');

                exit(json_encode(['popin' => $popin]));
            }

            exit(json_encode([
                'content' => null,
                'error' => $this->dependencies->l('payplug.adminAjaxController.credentialsNotCorrect', 'adminclass'),
            ]));

            $this->submitPopinPwd($password);
        }

        if ($this->tools->tool('getValue', 'submit') == 'submitPopin_abort') {
            $this->dependencies->paymentClass->abortPayment();
        }

        if ((int) $this->tools->tool('getValue', 'check') == 1) {
            $content = $this->dependencies->configClass->getCheckFieldset();

            exit(json_encode(['content' => $content]));
        }

        if ((int) $this->tools->tool('getValue', 'log') == 1) {
            $content = $this->getLogin();

            exit(json_encode(['content' => $content]));
        }

        if ((int) $this->tools->tool('getValue', 'checkPremium') == 1) {
            $api_key = $this->config->get($this->dependencies->getConfigurationKey('liveApiKey'));
            $permissions = $this->dependencies->apiClass->getAccountPermissions($api_key);
            $return = [];
            if (isset($permissions) && !is_bool($permissions)) {
                $applepay_allowed_domains = false;
                if (isset($permissions['apple_pay_allowed_domains'])) {
                    if (in_array($this->context->shop->domain, $permissions['apple_pay_allowed_domains'])) {
                        $applepay_allowed_domains = true;
                    }
                }

                $return = [
                    'payplug_sandbox' => $permissions['use_live_mode'],
                    'payplug_one_click' => $permissions['can_save_cards'],
                    'payplug_oney' => $permissions['can_use_oney'],
                    'payplug_bancontact' => $permissions['can_use_bancontact'],
                    'payplug_applepay' => $permissions['can_use_applepay'],
                    'payplug_amex' => $permissions['can_use_amex'],
                    'payplug_inst' => $permissions['can_create_installment_plan'],
                    'payplug_deferred' => $permissions['can_create_deferred_payment'],
                    'applepay_allowed_domains' => $applepay_allowed_domains,
                ];
            }

            exit(json_encode($return));
        }

        if ($this->tools->tool('getValue', 'has_live_key')) {
            exit(json_encode(['result' => $this->dependencies->apiClass->hasLiveKey()]));
        }

        if ((int) $this->tools->tool('getValue', 'refund') == 1) {
            $this->dependencies->refundClass->refundPayment();
        }

        if ((int) $this->tools->tool('getValue', 'capture') == 1) {
            $this->dependencies->paymentClass->capturePayment();
        }

        if ((int) $this->tools->tool('getValue', 'popinRefund') == 1) {
            $popin = $this->dependencies->mediaClass->displayPopin('refund');

            exit(json_encode(['content' => $popin]));
        }

        if ((int) $this->tools->tool('getValue', 'update') == 1) {
            $pay_id = $this->tools->tool('getValue', 'pay_id');
            $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
            if (!$payment['result']) {
                exit(json_encode([
                    'data' => $this->dependencies->l('payplug.adminAjaxController.errorOccurred', 'adminclass'),
                    'status' => 'error',
                ]));
            }

            $payment = $payment['resource'];

            $id_order = $this->tools->tool('getValue', 'id_order');

            if ((int) $payment->is_paid == 1) {
                if ($payment->is_live == 1) {
                    $new_state = (int) $this->config->get(
                        $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID')
                    );
                } else {
                    $new_state = (int) $this->config->get(
                        $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID_TEST')
                    );
                }
            } elseif ((int) $payment->is_paid == 0) {
                if ($payment->is_live == 1) {
                    $new_state = (int) $this->config->get(
                        $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ERROR')
                    );
                } else {
                    $new_state = (int) $this->config->get(
                        $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ERROR_TEST')
                    );
                }
            }

            $order = $this->order->get((int) $id_order);
            if ($this->validate->validate('isLoadedObject', $order)) {
                $current_state = (int) $order->getCurrentState();
                if ($current_state != 0 && $current_state != $new_state) {
                    $history = $this->orderHistory->get();
                    $history->id_order = (int) $order->id;
                    $history->changeIdOrderState($new_state, (int) $order->id, true);
                    $history->addWithemail();
                }
            }

            exit(json_encode([
                'message' => $this->dependencies->l('payplug.adminAjaxController.orderUpdated', 'adminclass'),
                'reload' => true,
            ]));
        }

        if ($this->tools->tool('getValue', 'modal')) {
            switch ($this->tools->tool('getValue', 'type')) {
                case 'error':
                    $this->assign->assign([
                        'errorData' => 'popinErrorConfiguration',
                        'errorMessage' => $this->tools->tool('getValue', 'errorMessage'),
                    ]);
                    $tpl = '/views/templates/api/molecules/modal/error.tpl';

                    exit(json_encode([
                        'modal' => $this->dependencies->configClass->fetchTemplate($tpl),
                    ]));
            }
        }

        if ($this->tools->tool('getValue', 'save')) {
            $connected = $this->config->get($this->dependencies->getConfigurationKey('email'))
                && ($this->config->get($this->dependencies->getConfigurationKey('testApiKey'))
                    || $this->config->get($this->dependencies->getConfigurationKey('liveApiKey')));

            if ($connected) {
                $this->dependencies->configClass->saveConfiguration();
                $tpl = '/views/templates/api/molecules/modal/confirmation.tpl';

                exit(json_encode([
                    'modal' => $this->dependencies->configClass->fetchTemplate($tpl),
                    'result' => true,
                ]));
            }

            $this->assign->assign([
                'errorData' => 'popinErrorConfiguration',
                'errorMessage' => $this->dependencies->l('payplug.adminAjaxController.needLogin', 'adminclass'),
            ]);
            $tpl = '/views/templates/api/molecules/modal/error.tpl';

            exit(json_encode([
                'modal' => $this->dependencies->configClass->fetchTemplate($tpl),
                'result' => false,
            ]));
        }

        if ($this->tools->tool('getValue', 'alert')) {
            switch ($this->tools->tool('getValue', 'type')) {
                case 'orderState':
                    $idOrderState = $this->tools->tool('getValue', 'idOrderState');
                    $order_state = $this->orderState->get($idOrderState, $this->context->language->id);
                    if ($order_state->id) {
                        $this->assign->assign([
                            'orderStateName' => $order_state->name,
                        ]);
                        $tpl = '/views/templates/api/molecules/alert/orderState.tpl';

                        exit(json_encode([
                            'alert' => $this->dependencies->configClass->fetchTemplate($tpl),
                        ]));
                    }

                    exit(json_encode([
                        'alert' => false,
                    ]));
            }
        }
    }

    /**
     * @description submit password
     *
     * @param string $pwd
     *
     * @return string
     */
    public function submitPopinPwd($pwd)
    {
        $email = $this->config->get($this->dependencies->getConfigurationKey('email'));
        $connected = $this->dependencies->apiClass->login($email, $pwd);
        $use_live_mode = false;

        if ($connected) {
            if ($this->config->get($this->dependencies->getConfigurationKey('liveApiKey')) != '') {
                $use_live_mode = true;

                $valid_key = $this->config->get($this->dependencies->getConfigurationKey('liveApiKey'));
                $permissions = $this->dependencies->apiClass->getAccount($valid_key);
                $can_save_cards = $permissions['can_save_cards'];
                $can_create_installment_plan = $permissions['can_create_installment_plan'];
            }
        } else {
            exit(json_encode(['content' => 'wrong_pwd']));
        }
        if (!$use_live_mode) {
            exit(json_encode(['content' => 'activate']));
        }
        if ($can_save_cards && $can_create_installment_plan) {
            exit(json_encode(['content' => 'live_ok']));
        }
        if ($can_save_cards && !$can_create_installment_plan) {
            exit(json_encode(['content' => 'live_ok_no_inst']));
        }
        if (!$can_save_cards && $can_create_installment_plan) {
            exit(json_encode(['content' => 'live_ok_no_oneclick']));
        }

        exit(json_encode(['content' => 'live_ok_not_premium']));
    }

    public function getLogin()
    {
        $this->dependencies->configClass->postProcess();

        $this->dependencies->configClass->assignContentVar();

        $this->html = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/panel/login.tpl');

        return $this->html;
    }
}
