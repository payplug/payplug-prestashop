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
    private $validators;
    private $vue;

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
        $this->validators = $this->dependencies->getValidators();
        $this->vue = $this->dependencies->getPlugin()->getVue();
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

        $datas = json_decode(file_get_contents('php://input'));
        if (!$datas) {
            $datas = (object)array();
            $datas->action = 'null';
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

        //if ($this->tools->tool('isSubmit', 'submitAccount')) {
        if (isset($datas->action) && $datas->action == 'payplug_login') {
            $this->dependencies->configClass->submitAccount($datas->payplug_email, $datas->payplug_password);
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

        //if ($this->tools->tool('getValue', 'submitPwd')) {
        if (isset($datas->action) && $datas->action == 'payplug_login') {
            $password = $datas->payplug_password;
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
            $applepay_allowed_domains = $this->validators['payment']->isApplepayAllowedDomain(
                $this->context->shop->domain,
                $permissions['apple_pay_allowed_domains']
            )['result'];

            $return = [
                    'payplug_sandbox' => $this->validators['payment']->hasPermissions($permissions, 'use_live_mode')['result'],
                    'payplug_one_click' => $this->validators['payment']->hasPermissions($permissions, 'can_save_cards')['result'],
                    'payplug_oney' => $this->validators['payment']->hasPermissions($permissions, 'can_use_oney')['result'],
                    'payplug_bancontact' => $this->validators['payment']->hasPermissions($permissions, 'can_use_bancontact')['result'],
                    'payplug_applepay' => $this->validators['payment']->hasPermissions($permissions, 'can_use_applepay')['result'],
                    'payplug_amex' => $this->validators['payment']->hasPermissions($permissions, 'can_use_amex')['result'],
                    'payplug_inst' => $this->validators['payment']->hasPermissions($permissions, 'can_create_installment_plan')['result'],
                    'payplug_deferred' => $this->validators['payment']->hasPermissions($permissions, 'can_create_deferred_payment')['result'],
                    'applepay_allowed_domains' => $applepay_allowed_domains,
                ];

            exit(json_encode($return));
        }

        if ($this->tools->tool('getValue', 'has_live_key')) {
            exit(json_encode(['result' => $this->validators['account']->hasLiveKey(
                $this->config->get(
                    $this->dependencies->getConfigurationKey('liveApiKey')
                )
            )]));
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


        /*echo "<pre>";
        print_r($datas);
        echo "</pre>";
        echo "<pre>";
        print_r(json_decode($datas));
        echo "</pre>";
        echo "<pre>";
        print_r($this->tools->tool('getValue', 'payplug_email'));
        echo "</pre>";
        die;*/

        exit(json_encode($this->vue->init()));

        exit('
       {
          "success": true,
          "data": {
            "settings": {
              "logged": true,
              "mode": 0,
              "WP": {
                "_wpnonce": "0b131d94c4"
              }
            },
            "payplug_wooc_settings": {
              "rest_route": "/payplug_api/login",
              "action": "payplug_login",
              "payplug_email": "testplugin+premium@payplug.com",
              "payplug_password": "testplugin@21",
              "enabled": "yes",
              "title": "Pay by credit card",
              "description": "sedfghj",
              "email": "testplugin+premium@payplug.com",
              "payplug_test_key": "sk_test_5viLdhhYB58UuSH0C49p0g",
              "payplug_merchant_id": "433983",
              "mode": "yes",
              "payment_method": "popup",
              "debug": "no",
              "oneclick": "no",
              "bancontact": "no",
              "apple_pay": "no",
              "american_express": "yes",
              "oney": "no",
              "oney_type": "with_fees",
              "oney_thresholds": "",
              "oney_thresholds_min": 100,
              "oney_thresholds_max": 3000,
              "oney_product_animation": "no",
              "payplug_merchant_country": "FR"
            },
            "header": {
              "title": "The payment solution that increases your turnover.",
              "descriptions": {
                "live": {
                  "description": "PayPlug is the French payment solution for SMEs. Boost your performance thanks to our turnkey, conversion-oriented tools.",
                  "plugin_version": "2.0.1"
                },
                "sandbox": {
                  "description": "PayPlug is the French payment solution for SMEs. Boost your performance thanks to our turnkey, conversion-oriented tools.",
                  "plugin_version": "2.0.1"
                }
              },
              "options": {
                "type": "select",
                "name": "payplug_enable",
                "disabled": false,
                "options": [
                  {
                    "value": 1,
                    "label": "Enabled plugin",
                    "checked": true
                  },
                  {
                    "value": 0,
                    "label": "Disabled Plugin",
                    "checked": false
                  }
                ]
              }
            },
            "login": {
              "name": "generalLogin",
              "title": "General",
              "descriptions": {
                "live": {
                  "description": "Log in to your PayPlug account.",
                  "not_registered": "Not registered to PayPlug yet?",
                  "connect": "Connect account",
                  "email_label": "E-mail address",
                  "email_placeholder": "E-mail address",
                  "password_label": "Password",
                  "password_placeholder": "Password",
                  "link_forgot_password": {
                    "text": "Forgot your password?",
                    "url": "https://www.payplug.com/portal/forgot_password",
                    "target": "_blank"
                  }
                },
                "sandbox": {
                  "description": "Log in to your PayPlug account.",
                  "not_registered": "Not registered to PayPlug yet?",
                  "connect": "Connect account",
                  "email_label": "E-mail address",
                  "email_placeholder": "E-mail address",
                  "password_label": "Password",
                  "password_placeholder": "Password",
                  "link_forgot_password": {
                    "text": "Forgot your password?",
                    "url": "https://www.payplug.com/portal/forgot_password",
                    "target": "_blank"
                  }
                }
              }
            },
            "logged": {
              "title": "General",
              "descriptions": {
                "live": {
                  "description": "General settings of the module",
                  "logout": "Disconnect",
                  "mode": "Environment",
                  "mode_description": "In LIVE mode, the payments will generate real transactions.",
                  "link_learn_more": {
                    "text": "Learn more",
                    "url": "https://support.payplug.com/hc/en-gb/articles/360021142492",
                    "target": "_blank"
                  },
                  "link_access_portal": {
                    "text": "Access my PayPlug portal",
                    "url": "https://www.payplug.com/portal",
                    "target": "_blank"
                  }
                },
                "sandbox": {
                  "description": "General settings of the module",
                  "logout": "Disconnect",
                  "mode": "Environment",
                  "mode_description": "In TEST mode, all payments will be simulations and will not generate real transactions.",
                  "link_learn_more": {
                    "text": "Learn more",
                    "url": "https://support.payplug.com/hc/en-gb/articles/360021142492",
                    "target": "_blank"
                  },
                  "link_access_portal": {
                    "text": "Access my PayPlug portal",
                    "url": "https://www.payplug.com/portal",
                    "target": "_blank"
                  }
                }
              },
              "options": [
                {
                  "name": "payplug_sandbox",
                  "label": "Live",
                  "value": 0,
                  "checked": true
                },
                {
                  "name": "payplug_sandbox",
                  "label": "Test",
                  "value": 1,
                  "checked": false
                }
              ],
              "inactive_modal": {
                "inactive": false,
                "title": "LIVE mode",
                "description": "Please enter your PayPlug account password.",
                "password_label": "Password",
                "cancel": "Cancel",
                "ok": "OK"
              },
              "inactive_account": {
                "warning": {
                  "title": "Congratulations, your account is connected!",
                  "description": "While your application is being reviewed, you can use the <a href=\'https://support.payplug.com/hc/en-gb/articles/360021142492\' target=\'_blank\'>TEST mode</a> to discover our module. <span id=\'inactiveModalClick\'>Click here</span> to switch to LIVE mode and collect payments from your customers."
                },
        "error": {
        "title": "Your application is being processed.",
        "description": "For more information, please contact us at support@payplug.com"
        }
        }
        },
        "payment_methods": {
            "name": "paymentMethodsBlock",
              "title": "Payment methods",
              "descriptions": {
                "live": {
                    "description": "Choose the payment methods you wish to offer your customers."
                },
                "sandbox": {
                    "description": "Choose the payment methods you wish to offer your customers."
                }
              },
              "options": [
                {
                    "type": "payment_method",
                  "name": "standard",
                  "title": "Payment by card",
                  "image": "http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/standard.svg",
                  "checked": true,
                  "hide": true,
                  "available_test_mode": true,
                  "descriptions": {
                    "live": {
                        "description": "Offer payment by credit card to your customers. Only Euro payments can be processed with PayPlug.",
                      "advanced_options": "payplug_section_standard_payment_advanced_options_label"
                    },
                    "sandbox": {
                        "description": "Offer payment by credit card to your customers. Only Euro payments can be processed with PayPlug.",
                      "advanced_options": "payplug_section_standard_payment_advanced_options_label"
                    }
                  },
                  "options": [
                    {
                        "type": "payment_option",
                      "sub_type": "input",
                      "name": "standard_payment_title",
                      "title": "Title",
                      "value": "Pay by credit card",
                      "descriptions": {
                        "live": {
                            "description": "The payment solution title displayed to your customers during checkout",
                          "placeholder": "Pay by credit card"
                        },
                        "sandbox": {
                            "description": "The payment solution title displayed to your customers during checkout",
                          "placeholder": "Pay by credit card"
                        }
                      }
                    },
                    {
                        "type": "payment_option",
                      "sub_type": "input",
                      "name": "standard_payment_description",
                      "title": "Description",
                      "value": "sedfghj",
                      "descriptions": {
                        "live": {
                            "description": "The payment solution description displayed to your customers during checkout",
                          "placeholder": "Description"
                        },
                        "sandbox": {
                            "description": "The payment solution description displayed to your customers during checkout",
                          "placeholder": "Description"
                        }
                      }
                    },
                    {
                        "type": "payment_option",
                      "sub_type": "IOptions",
                      "name": "embeded",
                      "title": "Presentation of the payment page",
                      "descriptions": {
                        "live": {
                            "description_redirect": "Your customers will be redirected to a customizable payment page hosted by PayPlug.",
                          "description_popup": "Your customers will see a customizable payment pop-up window appear on the checkout page of your store.",
                          "link_know_more": {
                                "text": "Find out more.",
                            "url": "https://support.payplug.com/hc/en-gb/articles/4409698334098",
                            "target": "_blank"
                          }
                        },
                        "sandbox": {
                            "description_redirect": "Your customers will be redirected to a customizable payment page hosted by PayPlug.",
                          "description_popup": "Your customers will see a customizable payment pop-up window appear on the checkout page of your store.",
                          "link_know_more": {
                                "text": "Find out more.",
                            "url": "https://support.payplug.com/hc/en-gb/articles/4409698334098",
                            "target": "_blank"
                          }
                        }
                      },
                      "options": [
                        {
                            "name": "payplug_embedded",
                          "label": "Pop-up",
                          "value": "popup",
                          "checked": true
                        },
                        {
                            "name": "payplug_embedded",
                          "label": "Redirected",
                          "value": "redirect",
                          "checked": false
                        }
                      ]
                    },
                    {
                        "type": "payment_option",
                      "sub_type": "switch",
                      "name": "one_click",
                      "title": "Activate one-click payment",
                      "descriptions": {
                        "live": {
                            "description": "Your customers will be able to register their card and make their next purchase in one click.",
                          "link_know_more": {
                                "text": "Find out more.",
                            "url": "https://support.payplug.com/hc/en-gb/articles/4409698334098",
                            "target": "_blank"
                          }
                        },
                        "sandbox": {
                            "description": "Your customers will be able to register their card and make their next purchase in one click.",
                          "link_know_more": {
                                "text": "Find out more.",
                            "url": "https://support.payplug.com/hc/en-gb/articles/4409698334098",
                            "target": "_blank"
                          }
                        }
                      },
                      "checked": false
                    }
                  ]
                },
                {
                    "type": "payment_method",
                  "name": "american_express",
                  "title": "AmEx Payment",
                  "image": "http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/Amex_logo_color.svg",
                  "checked": true,
                  "available_test_mode": false,
                  "descriptions": {
                    "live": {
                        "description": "Allow your customers to pay with their American Express cards.",
                      "link_know_more": {
                            "text": "Find out more.",
                        "url": "https://support.payplug.com/hc/en-gb/articles/5701208563996-Collecting-American-Express-Payments-with-PayPlug",
                        "target": "_blank"
                      }
                    },
                    "sandbox": {
                        "description": "Unavailable in test mode",
                      "link_know_more": {
                            "text": "Find out more.",
                        "url": "https://support.payplug.com/hc/en-gb/articles/5701208563996-Collecting-American-Express-Payments-with-PayPlug",
                        "target": "_blank"
                      }
                    }
                  }
                },
                {
                    "type": "payment_method",
                  "name": "applepay",
                  "title": "Apple Pay payment",
                  "image": "http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/applepay.svg",
                  "checked": false,
                  "available_test_mode": false,
                  "descriptions": {
                    "live": {
                        "description": "Display the Apple Pay payment button on your store",
                      "link_know_more": {
                            "text": "Find out more.",
                        "url": "https://support.payplug.com/hc/en-gb/articles/5149384347292",
                        "target": "_blank"
                      }
                    },
                    "sandbox": {
                        "description": "Unavailable in test mode",
                      "link_know_more": {
                            "text": "Find out more.",
                        "url": "https://support.payplug.com/hc/en-gb/articles/5149384347292",
                        "target": "_blank"
                      }
                    }
                  }
                },
                {
                    "type": "payment_method",
                  "name": "bancontact",
                  "title": "Bancontact payment",
                  "image": "http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/bancontact.svg",
                  "checked": false,
                  "available_test_mode": false,
                  "descriptions": {
                    "live": {
                        "description": "Allow your customers to pay with their Bancontact cards.",
                      "link_know_more": {
                            "text": "Find out more.",
                        "url": "https://support.payplug.com/hc/en-gb/articles/4408157435794",
                        "target": "_blank"
                      }
                    },
                    "sandbox": {
                        "description": "Unavailable in test mode",
                      "link_know_more": {
                            "text": "Find out more.",
                        "url": "https://support.payplug.com/hc/en-gb/articles/4408157435794",
                        "target": "_blank"
                      }
                    }
                  }
                }
              ]
            },
            "payment_paylater": {
            "name": "paymentMethodsBlock",
              "title": "PayLater",
              "descriptions": {
                "live": {
                    "description": "Allow your customers to pay in installments."
                },
                "sandbox": {
                    "description": "Allow your customers to pay in installments."
                }
              },
              "options": {
                "name": "oney",
                "title": "3x 4x Oney payments",
                "image": "http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/lg-oney.png",
                "checked": false,
                "descriptions": {
                    "live": {
                        "description": "Your customers can choose to pay for their orders in 3 or 4 installments. Choose below whether or not you want to pay all the fees.",
                    "link_know_more": {
                            "text": "Find out more.",
                      "url": "https://support.payplug.com/hc/fr/articles/4408142346002",
                      "target": "_blank"
                    }
                  },
                  "sandbox": {
                        "description": "Your customers can choose to pay for their orders in 3 or 4 installments. Choose below whether or not you want to pay all the fees.",
                    "link_know_more": {
                            "text": "Find out more.",
                      "url": "https://support.payplug.com/hc/fr/articles/4408142346002",
                      "target": "_blank"
                    }
                  },
                  "advanced": {
                        "0": "",
                    "description": "Advanced Settings"
                  }
                },
                "options": [
                  {
                      "name": "payplug_oney_type",
                    "className": "_paylaterLabel",
                    "label": "With fees",
                    "subText": "The fees are split between you and your customers.",
                    "value": "with_fees",
                    "checked": true
                  },
                  {
                      "name": "payplug_oney_type",
                    "className": "_paylaterLabel",
                    "label": "Without fees",
                    "subText": "You pay the fees.",
                    "value": "without_fees",
                    "checked": false
                  }
                ],
                "advanced_options": [
                  {
                      "name": "thresholds",
                    "image_url": "http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/thresholds.jpg",
                    "title": "Customise your split payment offer",
                    "descriptions": {
                      "description": "Offer guaranteed split payments for amounts between",
                      "min_amount": {
                          "name": "oney_min_amounts",
                        "value": 100,
                        "placeholder": 100
                      },
                      "inter": "and",
                      "max_amount": {
                          "name": "oney_max_amounts",
                        "value": 3000,
                        "placeholder": 3000
                      },
                      "error": {
                          "text": "payplug_thresholds_error_msg"
                      }
                    },
                    "switch": false
                  },
                  {
                      "name": "oney_product_animation",
                    "image_url": "http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/product.jpg",
                    "title": "Display the installments on the Product page",
                    "descriptions": [
                      {
                          "description": "Your customers can see the details of their 3 or 4 installments.",
                        "link_know_more": {
                          "text": "Find out more.",
                          "url": "https://support.payplug.com/hc/fr/articles/4408142346002",
                          "target": "_blank"
                        }
                      }
                    ],
                    "switch": true,
                    "checked": false
                  }
                ]
              }
            },
            "status": {
            "error": false,
              "title": "Status",
              "descriptions": {
                "live": {
                    "description": "Control your environment to ensure that the module is in perfect working order.",
                  "errorMessage": "The configuration requirements for using the PayPlug module are not met. Once you have corrected the problems, please refresh the page or click on verify.",
                  "check": "Check"
                },
                "sandbox": {
                    "description": "Control your environment to ensure that the module is in perfect working order.",
                  "errorMessage": "The configuration requirements for using the PayPlug module are not met. Once you have corrected the problems, please refresh the page or click on verify.",
                  "check": "Check"
                }
              },
              "requirements": [
                {
                    "status": true,
                  "text": "PHP cURL extension must be enabled on your server."
                },
                {
                    "status": true,
                  "text": "The PHP version on your server is valid."
                },
                {
                    "status": true,
                  "text": "OpenSSL is up to date."
                },
                {
                    "status": true,
                  "text": "Your shop currency has been set up with Euro."
                },
                {
                    "status": true,
                  "text": "You must connect your PayPlug account."
                }
              ],
              "debug": {
                "live": {
                    "title": "Activate debug mode",
                  "description": "Debug mode saves additional information on your server for each operation done via PayPlug plugin (Developer setting)."
                },
                "sandbox": {
                    "title": "Activate debug mode",
                  "description": "Debug mode saves additional information on your server for each operation done via PayPlug plugin (Developer setting)."
                }
              },
              "enable_debug_check": false
            }
          }
        }
        ');
    }

    public function getLogin()
    {
        $this->dependencies->configClass->postProcess();

        $this->dependencies->configClass->assignContentVar();

        $this->html = $this->dependencies->configClass->fetchTemplate('/views/templates/admin/panel/login.tpl');

        return $this->html;
    }
}
