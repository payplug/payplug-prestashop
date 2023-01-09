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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\models\classes;

class ApiRest
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function dispatch($action = '')
    {
        if (!is_string($action) || !$action) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::dispatch: Invalid parameter given, $action must be a non empty string.');

            return false;
        }

        $configurationAction = $this->dependencies->getPlugin()->getConfigurationAction();

        switch ($action) {
            case '/payplug_api/login':
                $datas = json_decode(file_get_contents('php://input'), false);
                $json = $configurationAction->loginAction($datas);

                break;
            case '/payplug_api/logout':
                $json = $configurationAction->logoutAction();

                break;
            case 'payplug_api/bancontact_permissions':
            case '/payplug_api/american_express_permissions':
            case '/payplug_api/oney_permissions':
            case '/payplug_api/applepay_permissions':
            case '/payplug_api/check_requirements':
            case '/payplug_api/refresh_keys':
            case '/payplug_api/save':
            case '/payplug_api/init':
            default:
                $json = $configurationAction->renderConfiguration();

                break;
        }

        exit(json_encode($json));
    }

    public function getDataFields()
    {
        $config = $this->dependencies->getPlugin()->getConfiguration();

        return [
            'rest_route' => '/payplug_api/login',
            'action' => 'payplug_login',
            'payplug_email' => $config->get($this->dependencies->getConfigurationKey('email')),
            'payplug_password' => 'testplugin@21',
            'enabled' => 'yes',
            'title' => 'Pay by credit card',
            'description' => 'sedfghj',
            'email' => $config->get($this->dependencies->getConfigurationKey('email')),
            'payplug_test_key' => 'sk_test_5viLdhhYB58UuSH0C49p0g',
            'payplug_merchant_id' => '433983',
            'mode' => 'yes',
            'payment_method' => 'popup',
            'debug' => 'no',
            'oneclick' => 'no',
            'bancontact' => 'no',
            'apple_pay' => 'no',
            'american_express' => 'yes',
            'oney' => 'no',
            'oney_type' => 'with_fees',
            'oney_thresholds' => '',
            'oney_thresholds_min' => 100,
            'oney_thresholds_max' => 3000,
            'oney_product_animation' => 'no',
            'payplug_merchant_country' => 'FR',
        ];
    }

    public function getHeaderSection()
    {
        $module_version = '3.12.0';
        $disabled = false;
        $enable = true;

        return [
            'title' => 'La solution de paiement qui augmente vos ventes.',
            'descriptions' => [
                'live' => [
                    'description' => 'PayPlug, c\'est la solution de paiement française des PME. Faites décoller votre performance grâce à nos outils orientés conversion parfaitement clés en main.',
                    'plugin_version' => $module_version,
                ],
                'sandbox' => [
                    'description' => 'TEST description',
                    'plugin_version' => $module_version,
                ],
            ],
            'options' => [
                'type' => 'select',
                'name' => 'payplug_enable',
                'disabled' => $disabled,
                'options' => [
                    [
                        'value' => 1,
                        'label' => 'Module masqué',
                        'checked' => $enable === true ? true : false,
                    ],
                    [
                        'value' => 0,
                        'label' => 'Module visible',
                        'checked' => $enable === false ? true : false,
                    ],
                ],
            ],
        ];
    }

    public function getHelpSection()
    {
        return [
            'description1' => 'help description 1',
            'description2' => 'help description 2',
            /*"link_help" => Component::link(
                'link help text',
                'link help url',
                "_blank"
            ),*/
            'link_help' => 'link help text',
        ];
    }

    public function getLoggedSection()
    {
        return [
            'title' => 'Logged title',
            'descriptions' => [
                'live' => [
                    'description' => 'Live description',
                    'logout' => 'Live logout',
                    'mode' => 'Live mode',
                    'mode_description' => 'Live mode description',
                    'link_learn_more' => [
                        'text' => 'Live learn more text',
                        'url' => 'Live learn more url',
                        'target' => '_blank',
                    ],
                    'link_access_portal' => [
                        'text' => 'Live access portal text',
                        'url' => 'https://www.payplug.com/portal',
                        'target' => '_blank',
                    ],
                ],
                'sandbox' => [
                    'description' => 'Test description',
                    'logout' => 'Test logout',
                    'mode' => 'Test mode',
                    'mode_description' => 'Test mode description',
                    'link_learn_more' => [
                        'text' => 'Test learn more text',
                        'url' => 'Test learn more url',
                        'target' => '_blank',
                    ],
                    'link_access_portal' => [
                        'text' => 'Test access portal text',
                        'url' => 'https://www.payplug.com/portal',
                        'target' => '_blank',
                    ],
                ],
            ],
            'options' => [
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'Live',
                    'value' => 0, //live
                    //"checked" => PayplugWoocommerceHelper::check_mode()
                    'checked' => true,
                ],
                [
                    'name' => 'payplug_sandbox',
                    'label' => 'Test',
                    'value' => 1, //test
                    //"checked" => !PayplugWoocommerceHelper::check_mode()
                    'checked' => false,
                ],
            ],
            'inactive_modal' => [
                //"inactive" => $inactive,
                'inactive' => false,
                'title' => 'Inactive modal title',
                'description' => 'Inactive modal description',
                'password_label' => 'Inactive modal password label',
                'cancel' => 'Inactive modal cancel',
                'ok' => 'Inactive modal ok',
            ],
            'inactive_account' => [
                'warning' => [
                    'title' => 'Inactive account title',
                    'description' => 'Inactive account warning description1' .
                        'Inactive account warning description2' .
                        'Inactive account warning description3',
                ],
                'error' => [
                    'title' => 'Inactive account error title',
                    'description' => 'Inactive account error description',
                ],
            ],
        ];
    }

    public function getLoginSection()
    {
        return [
            'name' => 'generalLogin',
            'title' => 'Login title',
            'descriptions' => [
                'live' => [
                    'description' => 'Live description',
                    'not_registered' => 'Live not registered',
                    'connect' => 'Live connect',
                    'email_label' => 'Live email label',
                    'email_placeholder' => 'Live email placeholder',
                    'password_label' => 'Live password label',
                    'password_placeholder' => 'Live password placeholder',
                    'link_forgot_password' => [
                        'text' => 'Live link forgot  password text',
                        'url' => 'https://www.payplug.com/portal/forgot_password',
                        'target' => '_blank',
                    ],
                ],
                'sandbox' => [
                    'description' => 'Test description',
                    'not_registered' => 'Test not registered',
                    'connect' => 'Test connect',
                    'email_label' => 'Test email label',
                    'email_placeholder' => 'Test email placeholder',
                    'password_label' => 'Test password label',
                    'password_placeholder' => 'Test password placeholder',
                    'link_forgot_password' => [
                        'text' => 'Test link forgot  password text',
                        'url' => 'https://www.payplug.com/portal/forgot_password',
                        'target' => '_blank',
                    ],
                ],
            ],
        ];
    }

    public function getOneyPopupProduct($active = false)
    {
        return [
            'name' => 'oney_product_animation',
            //"image_url" => esc_url( PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/images/product.jpg' ),
            'image_url' => 'assets/images/product.jpg',
            'title' => 'show oney popup product title',
            'descriptions' => [[
                'description' => 'show oney popup product description',
                'link_know_more' => [
                    'text' => 'Find out more.',
                    'url' => 'https://support.payplug.com/hc/fr/articles/4408142346002',
                    'target' => '_blank',
                ],
            ]],
            'switch' => true,
            'checked' => $active,
        ];
    }

    public function getPaylaterSection($options = [])
    {
        $max = !empty($options['oney_thresholds_max']) ? $options['oney_thresholds_max'] : 3000;
        $min = !empty($options['oney_thresholds_min']) ? $options['oney_thresholds_min'] : 100;
        $product_page = !empty($options['oney_product_animation']) && $options['oney_product_animation'] === 'yes' ? true : false;

        return [
            'name' => 'paymentMethodsBlock',
            'title' => 'paylater title',
            'descriptions' => [
                'live' => [
                    'description' => 'Live description',
                ],
                'sandbox' => [
                    'description' => 'Test description',
                ],
            ],
            'options' => [
                'name' => 'oney',
                'title' => 'paylater option title',
                'image' => 'assets/images/lg-oney.png',
                'checked' => !empty($options) && $options['oney'] === 'yes',
                'descriptions' => [
                    'live' => [
                        'description' => 'paylater description live description',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/fr/articles/4408142346002',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => 'paylater description test description',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/fr/articles/4408142346002',
                            'target' => '_blank',
                        ],
                    ],
                    'advanced' => [
                        '0' => '',
                        'description' => 'paylater description advanced description',
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_oney_type',
                        'className' => '_paylaterLabel',
                        'label' => 'paylater option 1 label',
                        'subText' => 'paylater option 1 subText',
                        'value' => 'with_fees',
                        'checked' => !empty($options) && $options['oney_type'] === 'with_fees',
                    ],
                    [
                        'name' => 'payplug_oney_type',
                        'className' => '_paylaterLabel',
                        'label' => 'paylater option 2 label',
                        'subText' => 'paylater option 2 label',
                        'value' => 'without_fees',
                        'checked' => !empty($options) && $options['oney_type'] === 'without_fees',
                    ],
                ],
                'advanced_options' => [
                    $this->getThresholdsOptions($max, $min),
                    $this->getOneyPopupProduct($product_page),
                ],
            ],
        ];
    }

    public function getPaymentMethodsSection()
    {
        return [
            'name' => 'paymentMethodsBlock',
            'title' => 'payment methods title',
            'descriptions' => [
                'live' => [
                    'description' => 'Live description',
                ],
                'sandbox' => [
                    'description' => 'Test description',
                ],
            ],
            'options' => [
                [
                    'type' => 'payment_option',
                    'sub_type' => 'input',
                    'name' => 'standard_payment_title',
                    'title' => 'Title',
                    'value' => 'Pay by credit card',
                    'descriptions' => [
                        'live' => [
                            'description' => 'The payment solution title displayed to your customers during checkout',
                            'placeholder' => 'Pay by credit card',
                        ],
                        'sandbox' => [
                            'description' => 'The payment solution title displayed to your customers during checkout',
                            'placeholder' => 'Pay by credit card',
                        ],
                    ],
                ],
                [
                    'type' => 'payment_option',
                    'sub_type' => 'input',
                    'name' => 'standard_payment_description',
                    'title' => 'Description',
                    'value' => 'sedfghj',
                    'descriptions' => [
                        'live' => [
                            'description' => 'The payment solution description displayed to your customers during checkout',
                            'placeholder' => 'Description',
                        ],
                        'sandbox' => [
                            'description' => 'The payment solution description displayed to your customers during checkout',
                            'placeholder' => 'Description',
                        ],
                    ],
                ],
                [
                    'type' => 'payment_option',
                    'sub_type' => 'IOptions',
                    'name' => 'embeded',
                    'title' => 'Presentation of the payment page',
                    'descriptions' => [
                        'live' => [
                            'description_redirect' => 'Your customers will be redirected to a customizable payment page hosted by PayPlug.',
                            'description_popup' => 'Your customers will see a customizable payment pop-up window appear on the checkout page of your store.',
                            'link_know_more' => [
                                'text' => 'Find out more.',
                                'url' => 'https://support.payplug.com/hc/en-gb/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                        'sandbox' => [
                            'description_redirect' => 'Your customers will be redirected to a customizable payment page hosted by PayPlug.',
                            'description_popup' => 'Your customers will see a customizable payment pop-up window appear on the checkout page of your store.',
                            'link_know_more' => [
                                'text' => 'Find out more.',
                                'url' => 'https://support.payplug.com/hc/en-gb/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                    ],
                    'options' => [
                        [
                            'name' => 'payplug_embedded',
                            'label' => 'Pop-up',
                            'value' => 'popup',
                            'checked' => true,
                        ],
                        [
                            'name' => 'payplug_embedded',
                            'label' => 'Redirected',
                            'value' => 'redirect',
                            'checked' => false,
                        ],
                    ],
                ],
                [
                    'type' => 'payment_option',
                    'sub_type' => 'switch',
                    'name' => 'one_click',
                    'title' => 'Activate one-click payment',
                    'descriptions' => [
                        'live' => [
                            'description' => 'Your customers will be able to register their card and make their next purchase in one click.',
                            'link_know_more' => [
                                'text' => 'Find out more.',
                                'url' => 'https://support.payplug.com/hc/en-gb/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                        'sandbox' => [
                            'description' => 'Your customers will be able to register their card and make their next purchase in one click.',
                            'link_know_more' => [
                                'text' => 'Find out more.',
                                'url' => 'https://support.payplug.com/hc/en-gb/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                    ],
                    'checked' => false,
                ],
            ],
            [
                'type' => 'payment_method',
                'name' => 'american_express',
                'title' => 'AmEx Payment',
                'image' => 'http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/Amex_logo_color.svg',
                'checked' => true,
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => 'Allow your customers to pay with their American Express cards.',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5701208563996-Collecting-American-Express-Payments-with-PayPlug',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => 'Unavailable in test mode',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5701208563996-Collecting-American-Express-Payments-with-PayPlug',
                            'target' => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'payment_method',
                'name' => 'applepay',
                'title' => 'Apple Pay payment',
                'image' => 'http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/applepay.svg',
                'checked' => false,
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => 'Display the Apple Pay payment button on your store',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5149384347292',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => 'Unavailable in test mode',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5149384347292',
                            'target' => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'payment_method',
                'name' => 'bancontact',
                'title' => 'Bancontact payment',
                'image' => 'http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/bancontact.svg',
                'checked' => false,
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => 'Allow your customers to pay with their Bancontact cards.',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/4408157435794',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => 'Unavailable in test mode',
                        'link_know_more' => [
                            'text' => 'Find out more.',
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/4408157435794',
                            'target' => '_blank',
                        ],
                    ],
                ],
            ],
        ];
    }

    public function getSettingsSection($logged)
    {
        return [
            'email' => 'blablabla@blabalabl.com',
            'WP' => [
                'ajax_url' => 'http://localhost:9000/',
                'nonce' => 'xxxxxxxxx',
                'login_action' => 'payplug_login',
                'logout_action' => 'payplug_logout',
                'check_permission_action' => 'payplug_check_permission',
                'check_requirements_action' => 'payplug_check_requirements',
                'save_action' => 'payplug_save',
                '_wpnonce' => '0b131d94c4',
            ],
            'logged' => $logged,
            'mode' => 0,
        ];
    }

    public function getStatusSection($options = [])
    {
        //$getRequirementsSection = new PayplugGatewayRequirements(new PayplugGateway());
        $checked = !empty($options['debug']) && $options['debug'] === 'yes' ? true : false;

        return [
            //"error" => !$this->getRequirementsSection(),
            'error' => false,
            'title' => 'status title',
            'descriptions' => [
                'live' => [
                    'description' => 'Live description',
                    'errorMessage' => 'Live error message',
                    'check' => 'Live check',
                    //"check_success" => 'Live check success',
                ],
                'sandbox' => [
                    'description' => 'Test description',
                    'errorMessage' => 'Test error message',
                    'check' => 'Test check',
                    //"check_success" => 'Test check success',
                ],
            ],
            'requirements' => [
                /*$getRequirementsSection->curl_requirement(),
                $getRequirementsSection->php_requirement(),
                $getRequirementsSection->openssl_requirement(),
                $getRequirementsSection->currency_requirement(), //MISSING THIS MESSAGES
                $getRequirementsSection->account_requirement(),*/
                [
                    'status' => true,
                    'text' => 'PHP cURL extension must be enabled on your server.',
                ],
                [
                    'status' => true,
                    'text' => 'The PHP version on your server is valid.',
                ],
                [
                    'status' => true,
                    'text' => 'OpenSSL is up to date.',
                ],
                [
                    'status' => true,
                    'text' => 'Your shop currency has been set up with Euro.',
                ],
                [
                    'status' => true,
                    'text' => 'You must connect your PayPlug account.',
                ],
            ],
            'debug' => [
                'live' => [
                    'title' => 'Live debug title',
                    'description' => 'Live debug description',
                ],
                'sandbox' => [
                    'title' => 'Test debug title',
                    'description' => 'Test debug description',
                ],
            ],
            'enable_debug_check' => $checked,
        ];
    }

    public function getThresholdsOptions($max, $min)
    {
        return [
            'name' => 'thresholds',
            //"image_url" => esc_url( PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/images/thresholds.jpg' ),
            'image_url' => 'assets/images/thresholds.jpg',
            'title' => 'thresholds option title',
            'descriptions' => [
                'description' => 'thresholds option desription',
                'min_amount' => [
                    'name' => 'oney_min_amounts',
                    'value' => $min,
                    'placeholder' => $min,
                ],
                'inter' => 'thresholds option and',
                'max_amount' => [
                    'name' => 'oney_max_amounts',
                    'value' => $max,
                    'placeholder' => $max,
                ],
                'error' => [
                    'text' => 'thresholds option error text',
                ],
            ],
            'switch' => false,
        ];
    }

    private function getRequirementsSection()
    {
        $getRequirementsSection = new PayplugGatewayRequirements(new PayplugGateway());

        return $getRequirementsSection->satisfy_requirements();
    }
}
