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
    private $validators;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->validators = $this->dependencies->getValidators();
        $this->helpers = $this->dependencies->getHelpers();
    }

    /**
     * @description Dispatch renders for a given action
     *
     * @param string $action
     *
     * @return array
     */
    public function dispatch($action = '')
    {
        if (!is_string($action) || !$action) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::dispatch: Invalid parameter given, $action must be a non empty string.');

            return [];
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
            case 'deferred_permissions':
            case 'installment_permissions':
            case 'one_click_permissions':
            case 'bancontact_permissions':
            case 'american_express_permissions':
            case 'oney_permissions':
            case 'applepay_permissions':
                $payment_method = str_replace('_permissions', '', $action);
                $json = $configurationAction->checkPermissionAction($payment_method);

                break;
            case 'check_requirements':
                $json = [
                    'status' => $this->getRequirementsSection(),
                ];

               break;

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

        return $json;
    }

    /**
     * @description Get current configuration from database
     *
     * @return array
     */
    public function getDataFields()
    {
        $config = $this->dependencies->getPlugin()->getConfiguration();

        $userHelper = $this->helpers['user'];
        $is_api_key = $this->validators['account']->isApiKey(
            $config->get($this->dependencies->getConfigurationKey('testApiKey'))
        );
        $is_email = $this->validators['account']->isEmail(
            $config->get($this->dependencies->getConfigurationKey('email'))
        );
        $logged = false;
        if ($is_api_key['result'] && $is_email['result']) {
            $logged = $userHelper->isLogged(
                $is_email['result'],
                $is_api_key['result']
            )['result'];
        }

        $psAccountConnected = $this->dependencies->configClass->checkPsAccount();
        if ($logged && !$psAccountConnected) {
            $this->dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->logoutAction();
            $logged = false;
        }

        $enable = $this->validators['module']->canBeShown(
            (bool) $config->get($this->dependencies->getConfigurationKey('enable'))
        )['result'];

        return [
            'logged' => $logged,
            'email' => $config->get($this->dependencies->getConfigurationKey('email')),
            'enable' => $enable,
            'sandbox_mode' => $config->get($this->dependencies->getConfigurationKey('sandboxMode')),
            'embedded_mode' => $config->get($this->dependencies->getConfigurationKey('embeddedMode')),
            'standard' => $config->get($this->dependencies->getConfigurationKey('standard')),
            'one_click' => $config->get($this->dependencies->getConfigurationKey('oneClick')),
            'inst' => $config->get($this->dependencies->getConfigurationKey('inst')),
            'inst_mode' => $config->get($this->dependencies->getConfigurationKey('instMode')),
            'inst_min_amount' => $config->get($this->dependencies->getConfigurationKey('instMinAmount')),
            'deferred' => $config->get($this->dependencies->getConfigurationKey('deferred')),
            'deferred_state' => $config->get($this->dependencies->getConfigurationKey('deferredState')),
            'oney' => (bool) $config->get($this->dependencies->getConfigurationKey('oney')),
            'oney_fees' => (bool) $config->get($this->dependencies->getConfigurationKey('oneyFees')),
            'oney_schedule' => (bool) $config->get($this->dependencies->getConfigurationKey('oneyOptimized')),
            'oney_product_animation' => (bool) $config->get($this->dependencies->getConfigurationKey('oneyProductCta')),
            'oney_cart_animation' => (bool) $config->get($this->dependencies->getConfigurationKey('oneyCartCta')),
            'oney_thresholds_min' => $config->get($this->dependencies->getConfigurationKey('oneyMinAmounts')),
            'oney_thresholds_max' => $config->get($this->dependencies->getConfigurationKey('oneyMaxAmounts')),
            'oney_custom_thresholds_min' => $config->get($this->dependencies->getConfigurationKey('oneyCustomMinAmounts')),
            'oney_custom_thresholds_max' => $config->get($this->dependencies->getConfigurationKey('oneyCustomMaxAmounts')),
            'bancontact' => (bool) $config->get($this->dependencies->getConfigurationKey('bancontact')),
            'bancontact_country' => (bool) $config->get($this->dependencies->getConfigurationKey('bancontactCountry')),
            'applepay' => (bool) $config->get($this->dependencies->getConfigurationKey('applepay')),
        ];
    }

    public function getDeferredState($deferred_state = 0)
    {
        if (!is_int($deferred_state)) {
            return [];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaymentMethodsTranslations();

        $order_states = $this->dependencies
            ->orderClass
            ->getOrderStates();

        $order_states_values = [
            0 => [
                'value' => 0,
                'label' => $translation['deferred']['states']['default'],
                'checked' => (int) $deferred_state ? false : true,
            ],
        ];
        if ($order_states) {
            foreach ($order_states as $order_state) {
                $order_states_values[$order_state['id_order_state']] = [
                    'value' => $order_state['id_order_state'],
                    'label' => sprintf(
                        $translation['deferred']['states']['state'],
                        $order_state['name']
                    ),
                    'checked' => $order_state['id_order_state'] == $deferred_state ? true : false,
                    'warning_msg' => sprintf(
                        $translation['deferred']['states']['alert'],
                        $order_state['name']
                    ),
                ];
            }
        }
        ksort($order_states_values);

        return $order_states_values;
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
     * @description  build header section of the json file
     *
     * @param mixed $current_configuration
     *
     * @return array
     */
    public function getHeaderSection($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            return [];
        }

        $translation = $this->dependencies->getPlugin()->getTranslation()->getHeaderTranslations();

        $is_logged = isset($current_configuration['logged']) ? $current_configuration['logged'] : false;
        $enable = isset($current_configuration['enable']) ? $current_configuration['enable'] : false;

        return [
            'title' => $translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $translation['text'],
                    'plugin_version' => $this->dependencies->version,
                ],
                'sandbox' => [
                    'description' => $translation['text'],
                    'plugin_version' => $this->dependencies->version,
                ],
            ],
            'options' => [
                'type' => 'select',
                'name' => 'payplug_enable',
                'disabled' => !$this->dependencies->configClass->checkPsAccount() || !$is_logged,
                'options' => [
                    [
                        'value' => 1,
                        'label' => $translation['visible'],
                        'checked' => $enable,
                    ],
                    [
                        'value' => 0,
                        'label' => $translation['hidden'],
                        'checked' => !$enable,
                    ],
                ],
            ],
        ];
    }

    /**
     * @description build logged section for api usage
     *
     * @param mixed $current_configuration
     *
     * @return array
     */
    public function getLoggedSection($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            return [];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getLoggedTranslations();

        $context = $this->dependencies->getPlugin()
            ->getContext()
            ->get();
        $iso_code = $context->language->iso_code;

        $config = $this->dependencies
            ->getPlugin()
            ->getConfiguration();

        $is_sandbox = isset($current_configuration['sandbox_mode']) ? $current_configuration['sandbox_mode'] : true;
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
                    'value' => 1,
                    'checked' => $is_sandbox,
                ],
                [
                    'name' => 'payplug_sandbox',
                    'label' => $translation['mode']['options']['live'],
                    'value' => 0,
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
     * @description build oney schedule section for api usage
     *
     * @param false $active
     *
     * @return array
     */
    public function getOneySchedule($active = false)
    {
        if (!is_bool($active)) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::getOneySchedule: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaylaterTranslations();

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/admin/screen/';

        $iso_code = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->language->iso_code;

        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($iso_code);

        return [
            'name' => 'oney_schedule',
            'image_url' => $img_path . $this->dependencies->name . '-optimized.jpg',
            'title' => $translation['oneySchedule']['title'],
            'descriptions' => [[
                'description' => $translation['oneySchedule']['description'],
                'link_know_more' => [
                    'text' => $translation['link'],
                    'url' => $external_url['oney'] . '#h_2595dd3d-a281-43ab-a51a-4986fecde5ee',
                    'target' => '_blank',
                ],
            ]],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description build oney cart popup section for api usage
     *
     * @param false $active
     *
     * @return array
     */
    public function getOneyPopupCart($active = false)
    {
        if (!is_bool($active)) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::getOneyPopupCart: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaylaterTranslations();

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/admin/screen/';

        return [
            'name' => 'oney_cart_animation',
            'image_url' => $img_path . $this->dependencies->name . '-cartOneyCta.jpg',
            'title' => $translation['oneyPopupCart']['title'],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description build oney product popup section for api usage
     *
     * @param false $active
     *
     * @return array
     */
    public function getOneyPopupProduct($active = false)
    {
        if (!is_bool($active)) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::getOneyPopupProduct: Invalid parameter given, $active must be a boolean.');

            return [];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaylaterTranslations();

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/admin/screen/';

        return [
            'name' => 'oney_product_animation',
            'image_url' => $img_path . $this->dependencies->name . '-productOneyCta.jpg',
            'title' => $translation['oneyPopupProduct']['title'],
            'switch' => true,
            'checked' => $active,
        ];
    }

    /**
     * @description build paylater section for api usage
     *
     * @param array $options
     * @param mixed $current_configuration
     *
     * @return array
     */
    public function getPaylaterSection($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::getPaylaterSection: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        // todo: get this default value from dependenciesClass (alike)
        $default_configuration = [
            'oney' => false,
            'oney_custom_thresholds_min' => 'EUR:10000',
            'oney_custom_thresholds_max' => 'EUR:300000',
            'oney_product_animation' => true,
            'oney_cart_animation' => true,
            'oney_schedule' => false,
            'oney_fees' => true,
        ];

        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        $advanced_options = [];
        $thresholds = $this->getThresholdsOptions($current_configuration);
        if ($thresholds) {
            $advanced_options[] = $thresholds;
        }
        $schedules = $this->getOneySchedule((bool) $current_configuration['oney_schedule']);
        if ($schedules) {
            $advanced_options[] = $schedules;
        }

        $config = $this->dependencies
            ->getPlugin()
            ->getConfiguration();
        $can_use_cta = !in_array(
            $config->get($this->dependencies->getConfigurationKey('oneyAllowedCountries')),
            ['ES', 'BE']
        );
        if ($can_use_cta) {
            $product = $this->getOneyPopupProduct((bool) $current_configuration['oney_product_animation']);
            if ($product) {
                $advanced_options[] = $product;
            }
            $cart = $this->getOneyPopupCart((bool) $current_configuration['oney_cart_animation']);
            if ($cart) {
                $advanced_options[] = $cart;
            }
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaylaterTranslations();

        $iso_code = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->language->iso_code;

        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($iso_code);

        return [
            'name' => 'paymentMethodsBlock',
            'title' => $translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $translation['description'],
                ],
                'sandbox' => [
                    'description' => $translation['description'],
                ],
            ],
            'options' => [
                'name' => 'oney',
                'title' => $translation['options']['title'],
                'image' => 'assets/images/lg-oney.png',
                'checked' => $current_configuration['oney'],
                'descriptions' => [
                    'live' => [
                        'description' => $translation['options']['description'],
                        'link_know_more' => [
                            'text' => $translation['link'],
                            'url' => $external_url['oney'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $translation['options']['description'],
                        'link_know_more' => [
                            'text' => $translation['link'],
                            'url' => $external_url['oney'],
                            'target' => '_blank',
                        ],
                    ],
                    'advanced' => [
                        'description' => $translation['advanced'],
                    ],
                ],
                'options' => [
                    [
                        'name' => 'payplug_oney_type',
                        'className' => '_paylaterLabel',
                        'label' => $translation['options']['with_fees']['label'],
                        'subText' => $translation['options']['with_fees']['subtext'],
                        'value' => 'with_fees',
                        'checked' => $current_configuration['oney_fees'],
                    ],
                    [
                        'name' => 'payplug_oney_type',
                        'className' => '_paylaterLabel',
                        'label' => $translation['options']['without_fees']['label'],
                        'subText' => $translation['options']['without_fees']['subtext'],
                        'value' => 'without_fees',
                        'checked' => !$current_configuration['oney_fees'],
                    ],
                ],
                'advanced_options' => $advanced_options,
            ],
        ];
    }

    /**
     * @description build payment methods section for api usage
     * @todo: divide the get of the different payment method in relative class
     *
     * @param mixed $current_configuration
     *
     * @return array
     */
    public function getPaymentMethodsSection($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::getPaymentMethodsSection: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaymentMethodsTranslations();

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/svg/payment/';

        $iso_code = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get()->language->iso_code;

        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($iso_code);

        // todo: get this default value from dependenciesClass (alike)
        $default_configuration = [
            'standard' => true,
            'embedded_mode' => 'redirect',
            'one_click' => false,
            'inst' => false,
            'inst_mode' => 3,
            'inst_min_amount' => 150,
            'deferred' => false,
            'deferred_state' => 0,
            'american_express' => false,
            'bancontact' => false,
            'bancontact_country' => false,
            'applepay' => false,
        ];
        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        $payment_options = [];
        if ($this->dependencies->configClass->isValidFeature('feature_standard')) {
            $advanced_settings = [];

            $embedded_mode = [];
            if ($this->dependencies->configClass->isValidFeature('feature_integrated')) {
                $embedded_mode[] = [
                    'name' => 'payplug_embedded',
                    'label' => $translation['embedded']['options']['integrated'],
                    'value' => 'integrated',
                    'checked' => 'integrated' == $current_configuration['embedded_mode'],
                ];
            }
            $embedded_mode[] = [
                'name' => 'payplug_embedded',
                'label' => $translation['embedded']['options']['popup'],
                'value' => 'popup',
                'checked' => 'popup' == $current_configuration['embedded_mode'],
            ];
            $embedded_mode[] = [
                'name' => 'payplug_embedded',
                'label' => $translation['embedded']['options']['redirect'],
                'value' => 'redirect',
                'checked' => 'redirect' == $current_configuration['embedded_mode'],
            ];

            if ($this->dependencies->configClass->isValidFeature('feature_installment')) {
                $advanced_settings[] = [
                    'name' => 'fractional',
                    'title' => $translation['installment']['title'],
                    'class' => '-installment',
                    'enabled' => [
                        'name' => 'payplug_inst',
                        'checked' => $current_configuration['inst'],
                    ],
                    'descriptions' => [
                        'live' => [
                            'description_1' => $translation['installment']['descriptions']['description_1'],
                            'text_from' => $translation['installment']['descriptions']['text_from'],
                            'description_2' => $translation['installment']['descriptions']['description_2'],
                            'links' => [
                                [
                                    'text' => $translation['installment']['descriptions']['controller_link'],
                                    'url' => '#some_url',
                                    'target' => '_blank',
                                ],
                                [
                                    'text' => $translation['installment']['link'],
                                    'url' => $external_url['installments'],
                                    'target' => '_blank',
                                ],
                            ],
                            'notes' => [
                                'type' => '-warning',
                                'description' => $translation['installment']['descriptions']['alert'],
                            ],
                        ],
                        'sandbox' => [
                            'description_1' => $translation['installment']['descriptions']['description_1'],
                            'text_from' => $translation['installment']['descriptions']['text_from'],
                            'description_2' => $translation['installment']['descriptions']['description_2'],
                            'links' => [
                                [
                                    'text' => $translation['installment']['descriptions']['controller_link'],
                                    'url' => '#some_url',
                                    'target' => '_blank',
                                ],
                                [
                                    'text' => $translation['installment']['link'],
                                    'url' => $external_url['installments'],
                                    'target' => '_blank',
                                ],
                            ],
                            'notes' => [
                                'type' => '-warning',
                                'description' => $translation['installment']['descriptions']['alert'],
                            ],
                        ],
                    ],
                    'options' => [
                        [
                            'name' => 'payplug_inst_mode',
                            'type' => 'select',
                            'disabled' => !$current_configuration['inst'],
                            'options' => [
                                [
                                    'value' => 2,
                                    'label' => $translation['installment']['select']['2_schedules'],
                                    'checked' => 2 == (int) $current_configuration['inst_mode'],
                                ],
                                [
                                    'value' => 3,
                                    'label' => $translation['installment']['select']['3_schedules'],
                                    'checked' => 3 == (int) $current_configuration['inst_mode'],
                                ],
                                [
                                    'value' => 4,
                                    'label' => $translation['installment']['select']['4_schedules'],
                                    'checked' => 4 == (int) $current_configuration['inst_mode'],
                                ],
                            ],
                        ],
                        [
                            'type' => 'input',
                            'name' => 'payplug_inst_min_amount',
                            'disabled' => !$current_configuration['inst'],
                            'value' => (int) $current_configuration['inst_min_amount'],
                            'min' => 4,
                            'step' => 1,
                            'max' => 20000,
                            'out_of_bound_msg' => $translation['installment']['error_limit'],
                        ],
                    ],
                    'notes' => [
                        'type' => '-warning',
                        'description' => $translation['installment']['descriptions']['alert'],
                    ],
                ];
            }

            if ($this->dependencies->configClass->isValidFeature('feature_deferred')) {
                $advanced_settings[] = [
                    'name' => 'deferred',
                    'title' => $translation['deferred']['title'],
                    'class' => '-deferred',
                    'enabled' => [
                        'name' => 'payplug_deferred',
                        'checked' => $current_configuration['deferred'],
                    ],
                    'descriptions' => [
                        'live' => [
                            'description_1' => $translation['deferred']['descriptions']['description_1'],
                            'description_2' => $translation['deferred']['descriptions']['description_2'],
                            'links' => [
                                [
                                    'text' => $translation['deferred']['link'],
                                    'url' => $external_url['deferred'],
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                        'sandbox' => [
                            'description_1' => $translation['deferred']['descriptions']['description_1'],
                            'description_2' => $translation['deferred']['descriptions']['description_2'],
                            'links' => [
                                [
                                    'text' => $translation['deferred']['link'],
                                    'url' => $external_url['deferred'],
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                    ],
                    'options' => [
                        'disabled' => !$current_configuration['deferred'],
                        'name' => 'payplug_deferred_state',
                        'type' => 'select',
                        'options' => $this->getDeferredState((int) $current_configuration['deferred_state']),
                    ],
                ];
            }

            $payment_options[] = [
                'type' => 'payment_method',
                'name' => 'standard',
                'title' => $translation['standard']['title'],
                'image' => $img_path . 'standard.svg',
                'checked' => $current_configuration['standard'],
                'available_test_mode' => true,
                'descriptions' => [
                    'live' => [
                        'description' => $translation['standard']['descriptions']['live'],
                        'advanced_options' => $translation['standard']['advanced'],
                    ],
                    'sandbox' => [
                        'description' => $translation['standard']['descriptions']['live'],
                        'advanced_options' => $translation['standard']['advanced'],
                    ],
                ],
                'options' => [
                    [
                        'type' => 'payment_option',
                        'sub_type' => 'IOptions',
                        'name' => 'embeded',
                        'title' => $translation['embedded']['title'],
                        'descriptions' => [
                            'live' => [
                                'description_popup' => $translation['embedded']['descriptions']['popup'],
                                'description_redirect' => $translation['embedded']['descriptions']['redirect'],
                                'link_know_more' => [
                                    'text' => $translation['embedded']['link'],
                                    'url' => $external_url['embedded'],
                                    'target' => '_blank',
                                ],
                            ],
                            'sandbox' => [
                                'description_popup' => $translation['embedded']['descriptions']['popup'],
                                'description_redirect' => $translation['embedded']['descriptions']['redirect'],
                                'link_know_more' => [
                                    'text' => $translation['embedded']['link'],
                                    'url' => $external_url['embedded'],
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                        'options' => $embedded_mode,
                    ],
                    [
                        'type' => 'payment_option',
                        'sub_type' => 'switch',
                        'name' => 'one_click',
                        'title' => $translation['one_click']['title'],
                        'descriptions' => [
                            'live' => [
                                'description' => $translation['one_click']['descriptions']['live'],
                                'link_know_more' => [
                                    'text' => $translation['one_click']['link'],
                                    'url' => $external_url['one_click'],
                                    'target' => '_blank',
                                ],
                            ],
                            'sandbox' => [
                                'description' => $translation['one_click']['descriptions']['live'],
                                'link_know_more' => [
                                    'text' => $translation['one_click']['link'],
                                    'url' => $external_url['one_click'],
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                        'checked' => $current_configuration['one_click'],
                    ],
                ],
                'advanced_settings' => $advanced_settings ? [
                    'title' => $translation['standard']['advanced'],
                    'options' => $advanced_settings,
                ] : [],
            ];
        }
        if ($this->dependencies->configClass->isValidFeature('feature_amex')) {
            $payment_options[] = [
                'type' => 'payment_method',
                'name' => 'american_express',
                'title' => $translation['amex']['title'],
                'image' => $img_path . 'amex.svg',
                'checked' => $current_configuration['american_express'],
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => $translation['amex']['descriptions']['live'],
                        'link_know_more' => [
                            'text' => $translation['amex']['link'],
                            'url' => $external_url['amex'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $translation['amex']['descriptions']['sandbox'],
                        'link_know_more' => [
                            'text' => $translation['amex']['link'],
                            'url' => $external_url['amex'],
                            'target' => '_blank',
                        ],
                    ],
                ],
            ];
        }
        if ($this->dependencies->configClass->isValidFeature('feature_applepay')) {
            $payment_options[] = [
                'type' => 'payment_method',
                'name' => 'applepay',
                'title' => $translation['applepay']['title'],
                'image' => $img_path . 'apple_pay.svg',
                'checked' => $current_configuration['applepay'],
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => $translation['applepay']['descriptions']['live'],
                        'link_know_more' => [
                            'text' => $translation['applepay']['link'],
                            'url' => $external_url['applepay'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $translation['applepay']['descriptions']['sandbox'],
                        'link_know_more' => [
                            'text' => $translation['applepay']['link'],
                            'url' => $external_url['applepay'],
                            'target' => '_blank',
                        ],
                    ],
                ],
            ];
        }
        if ($this->dependencies->configClass->isValidFeature('feature_bancontact')) {
            $payment_options[] = [
                'type' => 'payment_method',
                'name' => 'bancontact',
                'title' => $translation['bancontact']['title'],
                'image' => $img_path . 'bancontact.svg',
                'checked' => $current_configuration['bancontact'],
                'available_test_mode' => false,
                'descriptions' => [
                    'live' => [
                        'description' => $translation['bancontact']['descriptions']['live'],
                        'link_know_more' => [
                            'text' => $translation['bancontact']['link'],
                            'url' => $external_url['bancontact'],
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => $translation['bancontact']['descriptions']['sandbox'],
                        'link_know_more' => [
                            'text' => $translation['bancontact']['link'],
                            'url' => $external_url['bancontact'],
                            'target' => '_blank',
                        ],
                    ],
                ],
                'options' => [
                    [
                        'type' => 'payment_option',
                        'sub_type' => 'switch',
                        'name' => 'payplug_bancontact_country',
                        'title' => $translation['bancontact']['user']['title'],
                        'descriptions' => [
                            'live' => [
                                'description' => $translation['bancontact']['user']['description'],
                                'link_know_more' => [
                                    'text' => $translation['one_click']['link'],
                                    'url' => $external_url['one_click'],
                                    'target' => '_blank',
                                ],
                            ],
                            'sandbox' => [
                                'description' => $translation['bancontact']['user']['description'],
                                'link_know_more' => [
                                    'text' => $translation['one_click']['link'],
                                    'url' => $external_url['one_click'],
                                    'target' => '_blank',
                                ],
                            ],
                        ],
                        'checked' => $current_configuration['one_click'],
                    ],
                ],
            ];
        }

        if (!$payment_options) {
            return [];
        }

        return [
            'name' => 'paymentMethodsBlock',
            'title' => $translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $translation['description'],
                ],
                'sandbox' => [
                    'description' => $translation['description'],
                ],
            ],
            'options' => $payment_options,
        ];
    }

    /**
     * @description build requirement section for api usage
     *
     * @return array
     */
    public function getRequirementsSection()
    {
        $translation = $this->dependencies->getPlugin()->getTranslation()->getRequirementsTranslations();

        $requirements_reports = $this->dependencies->configClass->getReportRequirements();

        $is_requirements_checked = $this->validators['module']->isAllRequirementsChecked(
            $requirements_reports
        );
        $php_error = $curl_error = $openssl_error = false;
        if (!$is_requirements_checked['result']) {
            $error_code = $is_requirements_checked['code'];
            switch ($error_code) {
                case 'format':
                    $php_error = true;
                    $curl_error = true;
                    $openssl_error = true;

                    break;
                case 'php_format':
                case 'php_requirements':
                    $php_error = true;

                    break;
                case 'openssl_format':
                case 'openssl_requirements':
                    $openssl_error = true;

                    break;
                case 'curl_format':
                case 'curl_requirements':
                    $curl_error = true;

                    break;
            }
        }

        return [
            'error' => !$is_requirements_checked['result'],
            'title' => $translation['title'],
            'descriptions' => [
                'live' => [
                    'description' => $translation['descriptions']['description'],
                    'errorMessage' => $translation['descriptions']['errorMessage'],
                    'check' => $translation['descriptions']['check'],
                    'check_success' => $translation['descriptions']['successMessage'],
                ],
                'sandbox' => [
                    'description' => $translation['descriptions']['description'],
                    'errorMessage' => $translation['descriptions']['errorMessage'],
                    'check' => $translation['descriptions']['check'],
                    'check_success' => $translation['descriptions']['successMessage'],
                ],
            ],
            'requirements' => [
                [
                    'status' => !$openssl_error && $requirements_reports['openssl']['installed'] && $requirements_reports['openssl']['up2date'],
                    'text' => $translation['requirements']['openssl']['text'],
                ],
                [
                    'status' => !$php_error ? $requirements_reports['php']['up2date'] : false,
                    'text' => $translation['requirements']['php']['text'],
                ],
                [
                    'status' => !$curl_error ? $requirements_reports['curl']['installed'] : false,
                    'text' => $translation['requirements']['curl']['text'],
                ],
            ],
            'enable_debug_check' => false, //TODO: to be deleted
        ];
    }

    /**
     * @description get settings for api usage
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getSettingsSection($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            return [];
        }

        $email = isset($current_configuration['email']) ? $current_configuration['email'] : '';
        $is_logged = isset($current_configuration['logged']) ? $current_configuration['logged'] : false;

        return [
            'email' => $email,
            'logged' => $is_logged,
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
     * @description build oney thresholds section for oney
     *
     * @param array $current_configuration
     *
     * @return array
     */
    public function getThresholdsOptions($current_configuration = [])
    {
        if (!is_array($current_configuration)) {
            $logger = $this->dependencies->getPlugin()->getLogger();
            $logger->addLog('ApiRest::getThresholdsOptions: Invalid parameter given, $current_configuration must be an array.');

            return [];
        }

        // todo: get this default value from dependenciesClass (alike)
        $default_configuration = [
            'oney_thresholds_min' => 'EUR:10000',
            'oney_thresholds_max' => 'EUR:300000',
            'oney_custom_thresholds_min' => 'EUR:10000',
            'oney_custom_thresholds_max' => 'EUR:300000',
        ];

        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        // todo: Create an helper to handle the two following line of logic
        $custom_min = explode(':', $current_configuration['oney_custom_thresholds_min']);
        $custom_min = $this->helpers['amount']->formatOneyAmount((int) $custom_min[1])['result'];

        $custom_max = explode(':', $current_configuration['oney_custom_thresholds_max']);
        $custom_max = $this->helpers['amount']->formatOneyAmount((int) $custom_max[1])['result'];

        $min = explode(':', $current_configuration['oney_thresholds_min']);
        $min = $this->helpers['amount']->formatOneyAmount((int) $min[1])['result'];

        $max = explode(':', $current_configuration['oney_thresholds_max']);
        $max = $this->helpers['amount']->formatOneyAmount((int) $max[1])['result'];

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getPaylaterTranslations();

        $img_path = $this->dependencies
            ->getPlugin()
            ->getConstant()
            ->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/img/admin/screen/';

        return [
            'name' => 'thresholds',
            'image_url' => $img_path . $this->dependencies->name . '-thresholds.jpg',
            'title' => $translation['thresholds']['title'],
            'descriptions' => [
                'description' => $translation['thresholds']['description'],
                'min_amount' => [
                    'name' => 'oney_min_amounts',
                    'value' => $custom_min,
                    'placeholder' => $custom_min,
                    'min' => $min,
                    'max' => $max,
                ],
                'inter' => $translation['thresholds']['inter'],
                'max_amount' => [
                    'name' => 'oney_max_amounts',
                    'value' => $custom_max,
                    'placeholder' => $custom_max,
                    //'min' => $min,
                    //'max' => $max,
                ],
                'error' => [
                    'text' => sprintf(
                        $translation['thresholds']['error']['text'],
                        $min,
                        $max
                    ),
                ],
            ],
            'switch' => false,
        ];
    }
}
