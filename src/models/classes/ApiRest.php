<?php
/**
 * 2013 - 2023 PayPlug SAS
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
 * @copyright 2013 - 2023 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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

    /**
     * @description build toto section for api usage
     *
     * @param string $action
     */
    public function dispatch($action = '')
    {
        if (!is_string($action) || !$action) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::dispatch: Invalid parameter given, $action must be a non empty string.');

            exit(json_encode([]));
        }

        $configurationAction = $this->dependencies->getPlugin()->getConfigurationAction();

        switch ($action) {
            case 'login':
                $datas = json_decode(file_get_contents('php://input'), false);
                $json = $configurationAction->loginAction($datas);

                break;
            case 'logout':
                $json = $configurationAction->logoutAction();

                break;
            case 'bancontact_permissions':
            case 'american_express_permissions':
            case 'oney_permissions':
            case 'applepay_permissions':
            case 'check_requirements':
            case 'refresh_keys':
            case 'save':
                $datas = json_decode(file_get_contents('php://input'), false);
                $json = $configurationAction->saveAction($datas);

                break;
            case 'init':
            default:
                $json = $configurationAction->renderConfiguration();

                break;
        }

        exit(json_encode($json));
    }

    /**
     * @description build toto section for api usage
     *
     * @return array
     */
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

        $translation = $this->dependencies->getPlugin()->getTranslation();
        $header_translations = $translation->getHeaderTranslations();

        return [
            'title' => $header_translations['header']['title'],
            'descriptions' => [
                'live' => [
                    'description' => $header_translations['header']['text'],
                    'plugin_version' => $module_version,
                ],
                'sandbox' => [
                    'description' => $header_translations['header']['text'],
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
                        'label' => $header_translations['header']['hidden'],
                        'checked' => $enable === true ? true : false,
                    ],
                    [
                        'value' => 0,
                        'label' => $header_translations['header']['visible'],
                        'checked' => $enable === false ? true : false,
                    ],
                ],
            ],
        ];
    }

    /**
     * @description build footer section for api usage
     *
     * @return array
     */
    public function getFooterSection()
    {
        $translation = $this->dependencies->getPlugin()
            ->getTranslation()
            ->getFooterTranslations();
        $context = $this->dependencies->getPlugin()
            ->getContext()
            ->get();

        return [
            'save_changes_text' => $translation['button']['text'],
            'description' => [
                $translation['faq']['top'],
                $translation['faq']['bottom'],
            ],
            'link_help' => [
                'text' => $translation['faq']['link'],
                'url' => $this->dependencies
                    ->getPlugin()
                    ->getRoutes()
                    ->getExternalUrl($context->language->iso_code)['help'],
                'target' => '_blank',
            ],
        ];
    }

    /**
     * @description build logged section for api usage
     *
     * @return array
     */
    public function getLoggedSection()
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getLoggedTranslations();

        $context = $this->dependencies->getPlugin()
            ->getContext()
            ->get();
        $iso_code = $context->language->iso_code;

        $config = $this->dependencies->getPlugin()->getConfiguration();
        $is_sandbox = (bool) $config->get($this->dependencies->getConfigurationKey('sandboxMode'));
        $inactive = (bool) $config->get($this->dependencies->getConfigurationKey('liveApiKey'));

        return [
            'title' => $translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $translation['description'],
                    'logout' => $translation['user']['logout'],
                    'mode' => $translation['mode']['title'],
                    'mode_description' => $translation['mode']['description']['live'],
                    'link_learn_more' => [
                        'text' => $translation['mode']['link']['live'],
                        'url' => $this->dependencies
                            ->getPlugin()
                            ->getRoutes()
                            ->getExternalUrl($iso_code)['sandbox'],
                        'target' => '_blank',
                    ],
                    'link_access_portal' => [
                        'text' => $translation['user']['link'],
                        'url' => $this->dependencies
                            ->getPlugin()
                            ->getRoutes()
                            ->getExternalUrl($iso_code)['portal'],
                        'target' => '_blank',
                    ],
                ],
                'sandbox' => [
                    'description' => $translation['description'],
                    'logout' => $translation['user']['logout'],
                    'mode' => $translation['mode']['title'],
                    'mode_description' => $translation['mode']['description']['sandbox'],
                    'link_learn_more' => [
                        'text' => $translation['mode']['link']['sandbox'],
                        'url' => $this->dependencies
                            ->getPlugin()
                            ->getRoutes()
                            ->getExternalUrl($iso_code)['sandbox'],
                        'target' => '_blank',
                    ],
                    'link_access_portal' => [
                        'text' => $translation['user']['link'],
                        'url' => $this->dependencies
                            ->getPlugin()
                            ->getRoutes()
                            ->getExternalUrl($iso_code)['portal'],
                        'target' => '_blank',
                    ],
                ],
            ],
            'options' => [
                [
                    'name' => 'payplug_sandbox',
                    'label' => $translation['mode']['options']['sandbox'],
                    'value' => 1, //live
                    'checked' => $is_sandbox,
                ],
                [
                    'name' => 'payplug_sandbox',
                    'label' => $translation['mode']['options']['live'],
                    'value' => 0, //test
                    'checked' => !$is_sandbox,
                ],
            ],
            'inactive_modal' => [
                'inactive' => !$inactive,
                'title' => $translation['inactive']['modal']['title'],
                'description' => $translation['inactive']['modal']['description'],
                'password_label' => $translation['inactive']['modal']['password_label'],
                'cancel' => $translation['inactive']['modal']['cancel'],
                'ok' => $translation['inactive']['modal']['ok'],
            ],
            'inactive_account' => [
                'warning' => [
                    'title' => $translation['inactive']['account']['warning']['title'],
                    'description' => $translation['inactive']['account']['warning']['description'],
                ],
                'error' => [
                    'title' => $translation['inactive']['account']['error']['title'],
                    'description' => $translation['inactive']['account']['error']['description'],
                ],
            ],
        ];
    }

    /**
     * @description build login section for api usage
     *
     * @return array
     */
    public function getLoginSection()
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getLoginTranslations();

        return [
            'name' => 'generalLogin',
            'title' => $translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $translation['description'],
                    'not_registered' => $translation['register'],
                    'connect' => $translation['connect'],
                    'email_label' => $translation['email'],
                    'email_placeholder' => $translation['email'],
                    'password_label' => $translation['password'],
                    'password_placeholder' => $translation['password'],
                    'link_forgot_password' => [
                        'text' => $translation['forgot_password'],
                        'url' => $this->dependencies
                            ->getPlugin()
                            ->getRoutes()
                            ->getExternalUrl()['forgot_password'],
                        'target' => '_blank',
                    ],
                ],
                'sandbox' => [
                    'description' => $translation['description'],
                    'not_registered' => $translation['register'],
                    'connect' => $translation['connect'],
                    'email_label' => $translation['email'],
                    'email_placeholder' => $translation['email'],
                    'password_label' => $translation['password'],
                    'password_placeholder' => $translation['password'],
                    'link_forgot_password' => [
                        'text' => $translation['forgot_password'],
                        'url' => $this->dependencies
                            ->getPlugin()
                            ->getRoutes()
                            ->getExternalUrl()['forgot_password'],
                        'target' => '_blank',
                    ],
                ],
            ],
        ];
    }

    /**
     * @description build subscribe section for api usage
     *
     * @return array
     */
    public function getSubscribeSection()
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getSubscribeTranslations();

        $register_link = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl()['signup'];
        if ($this->dependencies->name == 'pspaylater') {
            $register_link .= '/signup?sponsor=22101';
        }

        return [
            'name' => 'generalSubscribe',
            'title' => $translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $translation['description'],
                    'link_create_account' => [
                        'text' => $translation['register'],
                        'url' => $register_link,
                        'target' => '_blank',
                    ],
                    'content_description' => $translation['text'],
                    'already_have_account' => $translation['connect'],
                ],
                'sandbox' => [
                    'description' => $translation['description'],
                    'link_create_account' => [
                        'text' => $translation['register'],
                        'url' => $register_link,
                        'target' => '_blank',
                    ],
                    'content_description' => $translation['text'],
                    'already_have_account' => $translation['connect'],
                ],
            ],
        ];
    }

    /**
     * @description build oney popup section for api usage
     *
     * @param false $active
     *
     * @return array
     */
    public function getOneyPopupProduct($active = false)
    {
        $translation = $this->dependencies->getPlugin()->getTranslation();
        $paylater_translations = $translation->getPaylaterTranslations();

        return [
            'name' => 'oney_product_animation',
            //"image_url" => esc_url( PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/images/product.jpg' ),
            'image_url' => 'assets/images/product.jpg',
            'title' => '', // $paylater_translations['paylater']['oneyPopupProduct']['title'],
            'descriptions' => [[
                'description' => '', // $paylater_translations['paylater']['oneyPopupProduct']['description'],
                'link_know_more' => [
                    'text' => '', // $paylater_translations['paylater']['oneyPopupProduct']['knowMore']['text'],
                    'url' => 'https://support.payplug.com/hc/fr/articles/4408142346002',
                    'target' => '_blank',
                ],
            ]],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description build paylater section for api usage
     *
     * @param array $options
     *
     * @return array
     */
    public function getPaylaterSection($options = [])
    {
        $max = !empty($options['oney_thresholds_max']) ? $options['oney_thresholds_max'] : 3000;
        $min = !empty($options['oney_thresholds_min']) ? $options['oney_thresholds_min'] : 100;
        $product_page = !empty($options['oney_product_animation']) && $options['oney_product_animation'] === 'yes' ? true : false;

        $translation = $this->dependencies->getPlugin()->getTranslation();
        $paylater_translations = $translation->getPaylaterTranslations();

        return [
            'name' => 'paymentMethodsBlock',
            'title' => '', // $paylater_translations['paylater']['title'],
            'descriptions' => [
                'live' => [
                    'description' => '', // $paylater_translations['paylater']['descriptions']['live']['description'],
                ],
                'sandbox' => [
                    'description' => '', // $paylater_translations['paylater']['descriptions']['test']['description'],
                ],
            ],
            'options' => [
                'name' => 'oney',
                'title' => '', // $paylater_translations['paylater']['options']['title'],
                'image' => 'assets/images/lg-oney.png',
                'checked' => !empty($options) && $options['oney'] === 'yes',
                'descriptions' => [
                    'live' => [
                        'description' => '', // $paylater_translations['paylater']['options']['descriptions']['live']['description'],
                        'link_know_more' => [
                            'text' => '', // $paylater_translations['paylater']['options']['descriptions']['live']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/fr/articles/4408142346002',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => '', // $paylater_translations['paylater']['options']['descriptions']['test']['description'],
                        'link_know_more' => [
                            'text' => '', // $paylater_translations['paylater']['options']['descriptions']['test']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/fr/articles/4408142346002',
                            'target' => '_blank',
                        ],
                    ],
                    'advanced' => [
                        '0' => '',
                        'description' => '', // $paylater_translations['paylater']['options']['descriptions']['advanced']['description'],
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_oney_type',
                        'className' => '_paylaterLabel',
                        'label' => '', // $paylater_translations['paylater']['options']['option1']['label'],
                        'subText' => '', // $paylater_translations['paylater']['options']['option1']['subText'],
                        'value' => 'with_fees',
                        'checked' => !empty($options) && $options['oney_type'] === 'with_fees',
                    ],
                    [
                        'name' => 'payplug_oney_type',
                        'className' => '_paylaterLabel',
                        'label' => '', // $paylater_translations['paylater']['options']['option2']['label'],
                        'subText' => '', // $paylater_translations['paylater']['options']['option2']['subText'],
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

    /**
     * @description build payment methods section for api usage
     *
     * @return array
     */
    public function getPaymentMethodsSection()
    {
        $translation = $this->dependencies->getPlugin()->getTranslation();
        $payment_methods_translations = $translation->getPaymentMethodsTranslations();

        return [
            'name' => 'paymentMethodsBlock',
            'title' => '', // $payment_methods_translations['paymentMethods']['title'],
            'descriptions' => [
                'live' => [
                    'description' => '', // $payment_methods_translations['paymentMethods']['descriptions']['live']['description'],
                ],
                'sandbox' => [
                    'description' => '', // $payment_methods_translations['paymentMethods']['descriptions']['test']['description'],
                ],
            ],
            'options' => [
                [
                    'type' => 'payment_option',
                    'sub_type' => 'input',
                    'name' => 'standard_payment_title',
                    'title' => '', // $payment_methods_translations['paymentMethods']['standard']['title']['title'],
                    'value' => '', // $payment_methods_translations['paymentMethods']['standard']['title']['value'],
                    'descriptions' => [
                        'live' => [
                            'description' => '', // $payment_methods_translations['paymentMethods']['standard']['title']['descriptions']['live']['description'],
                            'placeholder' => '', // $payment_methods_translations['paymentMethods']['standard']['title']['descriptions']['live']['placeholder'],
                        ],
                        'sandbox' => [
                            'description' => '', // $payment_methods_translations['paymentMethods']['standard']['title']['descriptions']['test']['description'],
                            'placeholder' => '', // $payment_methods_translations['paymentMethods']['standard']['title']['descriptions']['test']['placeholder'],
                        ],
                    ],
                ],
                [
                    'type' => 'payment_option',
                    'sub_type' => 'input',
                    'name' => 'standard_payment_description',
                    'title' => '', // $payment_methods_translations['paymentMethods']['standard']['description']['title'],
                    'value' => '', // $payment_methods_translations['paymentMethods']['standard']['description']['value'],
                    'descriptions' => [
                        'live' => [
                            'description' => '', // $payment_methods_translations['paymentMethods']['standard']['descriptions']['descriptions']['live']['description'],
                            'placeholder' => '', // $payment_methods_translations['paymentMethods']['standard']['descriptions']['descriptions']['live']['placeholder'],
                        ],
                        'sandbox' => [
                            'description' => '', // $payment_methods_translations['paymentMethods']['standard']['descriptions']['descriptions']['test']['description'],
                            'placeholder' => '', // $payment_methods_translations['paymentMethods']['standard']['descriptions']['descriptions']['test']['placeholder'],
                        ],
                    ],
                ],
                [
                    'type' => 'payment_option',
                    'sub_type' => 'IOptions',
                    'name' => 'embeded',
                    'title' => '', // $payment_methods_translations['paymentMethods']['embedded']['title'],
                    'descriptions' => [
                        'live' => [
                            'description_redirect' => '', // $payment_methods_translations['paymentMethods']['embedded']['descriptions']['live']['descriptionRedirect'],
                            'description_popup' => '', // $payment_methods_translations['paymentMethods']['embedded']['descriptions']['live']['descriptionPopup'],
                            'link_know_more' => [
                                'text' => '', // $payment_methods_translations['paymentMethods']['embedded']['descriptions']['live']['knowMore']['text'],
                                'url' => 'https://support.payplug.com/hc/en-gb/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                        'sandbox' => [
                            'description_redirect' => '', // $payment_methods_translations['paymentMethods']['embedded']['descriptions']['test']['descriptionRedirect'],
                            'description_popup' => '', // $payment_methods_translations['paymentMethods']['embedded']['descriptions']['test']['descriptionPopup'],
                            'link_know_more' => [
                                'text' => '', // $payment_methods_translations['paymentMethods']['embedded']['descriptions']['test']['knowMore']['text'],
                                'url' => 'https://support.payplug.com/hc/en-gb/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                    ],
                    'options' => [
                        [
                            'name' => 'payplug_embedded',
                            'label' => 'Pop-up',
                            'value' => '', // $payment_methods_translations['paymentMethods']['embedded']['popupValue'],
                            'checked' => true,
                        ],
                        [
                            'name' => 'payplug_embedded',
                            'label' => 'Redirected',
                            'value' => '', // $payment_methods_translations['paymentMethods']['embedded']['redirectValue'],
                            'checked' => false,
                        ],
                    ],
                ],
                [
                    'type' => 'payment_option',
                    'sub_type' => 'switch',
                    'name' => 'one_click',
                    'title' => '', // $payment_methods_translations['paymentMethods']['oneClick']['title'],
                    'descriptions' => [
                        'live' => [
                            'description' => '', // $payment_methods_translations['paymentMethods']['oneClick']['descriptions']['live']['description'],
                            'link_know_more' => [
                                'text' => '', // $payment_methods_translations['paymentMethods']['oneClick']['descriptions']['live']['knowMore']['text'],
                                'url' => 'https://support.payplug.com/hc/en-gb/articles/4409698334098',
                                'target' => '_blank',
                            ],
                        ],
                        'sandbox' => [
                            'description' => '', // $payment_methods_translations['paymentMethods']['oneClick']['descriptions']['test']['description'],
                            'link_know_more' => [
                                'text' => '', // $payment_methods_translations['paymentMethods']['oneClick']['descriptions']['test']['knowMore']['text'],
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
                'title' => '', // $payment_methods_translations['paymentMethods']['americanExpress']['title'],
                'image' => 'http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/Amex_logo_color.svg',
                'checked' => true,
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => '', // $payment_methods_translations['paymentMethods']['americanExpress']['descriptions']['live']['description'],
                        'link_know_more' => [
                            'text' => '', // $payment_methods_translations['paymentMethods']['americanExpress']['descriptions']['live']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5701208563996-Collecting-American-Express-Payments-with-PayPlug',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => '', // $payment_methods_translations['paymentMethods']['americanExpress']['descriptions']['test']['description'],
                        'link_know_more' => [
                            'text' => '', // $payment_methods_translations['paymentMethods']['americanExpress']['descriptions']['test']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5701208563996-Collecting-American-Express-Payments-with-PayPlug',
                            'target' => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'payment_method',
                'name' => 'applepay',
                'title' => '', // $payment_methods_translations['paymentMethods']['applePay']['title'],
                'image' => 'http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/applepay.svg',
                'checked' => false,
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => '', // $payment_methods_translations['paymentMethods']['applePay']['descriptions']['live']['description'],
                        'link_know_more' => [
                            'text' => '', // $payment_methods_translations['paymentMethods']['applePay']['descriptions']['live']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5149384347292',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => '', // $payment_methods_translations['paymentMethods']['applePay']['descriptions']['test']['description'],
                        'link_know_more' => [
                            'text' => '', // $payment_methods_translations['paymentMethods']['applePay']['descriptions']['test']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/5149384347292',
                            'target' => '_blank',
                        ],
                    ],
                ],
            ],
            [
                'type' => 'payment_method',
                'name' => 'bancontact',
                'title' => '', // $payment_methods_translations['paymentMethods']['bancontact']['title'],
                'image' => 'http://localhost/wp-content/plugins/payplug-woocommerce/assets/images/bancontact.svg',
                'checked' => false,
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => '', // $payment_methods_translations['paymentMethods']['bancontact']['descriptions']['live']['description'],
                        'link_know_more' => [
                            'text' => '', // $payment_methods_translations['paymentMethods']['bancontact']['descriptions']['live']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/4408157435794',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => '', // $payment_methods_translations['paymentMethods']['bancontact']['descriptions']['test']['description'],
                        'link_know_more' => [
                            'text' => '', // $payment_methods_translations['paymentMethods']['bancontact']['descriptions']['test']['knowMore']['text'],
                            'url' => 'https://support.payplug.com/hc/en-gb/articles/4408157435794',
                            'target' => '_blank',
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @description build requirement section for api usage
     *
     * @param array $options
     *
     * @return array
     */
    public function getRequirementsSection($options = [])
    {
        $checked = !empty($options['debug']) && $options['debug'] === 'yes' ? true : false;

        $translation = $this->dependencies->getPlugin()->getTranslation();
        $requirements_translations = $translation->getRequirementsTranslations();

        return [
            //"error" => !$this->getRequirementsSection(),
            'error' => false,
            'title' => $requirements_translations['requirements']['title'],
            'descriptions' => [
                'live' => [
                    'description' => $requirements_translations['requirements']['descriptions']['live']['description'],
                    'errorMessage' => $requirements_translations['requirements']['descriptions']['live']['errorMessage'],
                    'check' => $requirements_translations['requirements']['descriptions']['live']['check'],
                    //"check_success" => 'Live check success',
                ],
                'sandbox' => [
                    'description' => $requirements_translations['requirements']['descriptions']['test']['description'],
                    'errorMessage' => $requirements_translations['requirements']['descriptions']['test']['errorMessage'],
                    'check' => $requirements_translations['requirements']['descriptions']['test']['check'],
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
                    'text' => $requirements_translations['requirements']['requirements']['curl']['text'],
                ],
                [
                    'status' => true,
                    'text' => $requirements_translations['requirements']['requirements']['php']['text'],
                ],
                [
                    'status' => true,
                    'text' => $requirements_translations['requirements']['requirements']['openssl']['text'],
                ],
                [
                    'status' => true,
                    'text' => $requirements_translations['requirements']['requirements']['currency']['text'],
                ],
                [
                    'status' => true,
                    'text' => $requirements_translations['requirements']['requirements']['account']['text'],
                ],
            ],
            'debug' => [
                'live' => [
                    'title' => $requirements_translations['requirements']['debug']['live']['title'],
                    'description' => $requirements_translations['requirements']['debug']['live']['description'],
                ],
                'sandbox' => [
                    'title' => $requirements_translations['requirements']['debug']['test']['title'],
                    'description' => $requirements_translations['requirements']['debug']['test']['description'],
                ],
            ],
            'enable_debug_check' => $checked,
        ];
    }

    /**
     * @description get settings for api usage
     *
     * @param bool $logged
     *
     * @return array
     */
    public function getSettingsSection($logged = false)
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

    /**
     * @description build oney thresholds section for oney
     *
     * @param int $max
     * @param int $min
     *
     * @return array
     */
    public function getThresholdsOptions($min = 0, $max = 0)
    {
        $translation = $this->dependencies->getPlugin()->getTranslation();
        $paylater_translations = $translation->getPaylaterTranslations();

        return [
            'name' => 'thresholds',
            //"image_url" => esc_url( PAYPLUG_GATEWAY_PLUGIN_URL . 'assets/images/thresholds.jpg' ),
            'image_url' => 'assets/images/thresholds.jpg',
            'title' => '', // $paylater_translations['paylater']['thresholds']['title'],
            'descriptions' => [
                'description' => '', // $paylater_translations['paylater']['thresholds']['description'],
                'min_amount' => [
                    'name' => 'oney_min_amounts',
                    'value' => $min,
                    'placeholder' => $min,
                ],
                'inter' => '', // $paylater_translations['paylater']['thresholds']['inter'],
                'max_amount' => [
                    'name' => 'oney_max_amounts',
                    'value' => $max,
                    'placeholder' => $max,
                ],
                'error' => [
                    'text' => '', // $paylater_translations['paylater']['thresholds']['error']['text'],
                ],
            ],
            'switch' => false,
        ];
    }
}
