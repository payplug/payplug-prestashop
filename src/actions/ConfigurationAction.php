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

namespace PayPlug\src\actions;

class ConfigurationAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @param string $payment_method
     *
     * @return array
     */
    public function checkPermissionAction($payment_method = '')
    {
        if (!is_string($payment_method) || !$payment_method) {
            return [
                'success' => false,
                'data' => [
                    'msg' => 'An error occured while getting the permissions',
                ],
            ];
        }

        $allowed_methods = [
            'one_click' => 'can_save_cards',
            'installment' => 'can_create_installment_plan',
            'deferred' => 'can_create_deferred_payment',
            'oney' => 'can_use_oney',
            'bancontact' => 'can_use_bancontact',
            'applepay' => 'can_use_applepay',
            'american_express' => 'can_use_amex',
            'use_live_mode' => 'use_live_mode',
            'onboarding_oney_completed' => 'onboarding_oney_completed',
        ];

        if (!$this->dependencies
            ->getValidators()['payment']
            ->hasPermissions($allowed_methods, $payment_method)['result']) {
            return [
                'success' => false,
                'data' => [
                    'msg' => 'We can\'t check the permissions of the given feature',
                ],
            ];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getModalTranslations();

        $context = $this->dependencies
            ->getPlugin()
            ->getContext()
            ->get();

        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($context->language->iso_code);

        $permissions = $this->dependencies->apiClass->getAccountPermissions();
        $has_permission = $this->dependencies
            ->getValidators()['payment']
            ->hasPermissions($permissions, $allowed_methods[$payment_method])['result'];
        $message = $translation['premium']['description']['unavailable'];
        switch ($payment_method) {
            case 'american_express':
            case 'bancontact':
                $message .= sprintf(
                    $translation['premium']['description']['form'],
                    $translation['premium']['feature'][$payment_method]
                );
                $link = '<a href="' . $external_url['contact'] . '" target="_blank">' . $translation['premium']['link']['form'] . '</a>';
                $message = str_replace('$link', $link, $message);

                break;
            case 'applepay':
                $has_permission = $has_permission ? $this->dependencies
                    ->getValidators()['payment']
                    ->isApplepayAllowedDomain(
                        $context->shop->domain,
                        $permissions['apple_pay_allowed_domains']
                    )['result'] : false;

                $message .= sprintf(
                    $translation['premium']['description']['contact'],
                    $translation['premium']['feature'][$payment_method]
                );
                $link = '<a href="' . $external_url['mail'] . '" target="_blank">' . $translation['premium']['link']['contact'] . '</a>';
                $message = str_replace('$link', $link, $message);

                break;
            case 'oney':
                $message .= $translation['premium']['description']['oney'];
                $link = '<a href="' . $external_url['oney_cgv'] . '" target="_blank">' . $translation['premium']['link']['oney'] . '</a>';
                $message = str_replace('$link', $link, $message);

                break;

            default:
                $message .= $translation['premium']['description']['default'];
                $link = '<a href="' . $external_url['contact'] . '" target="_blank">' . $translation['premium']['link']['default'] . '</a>';
                $message = str_replace('$link', $link, $message);

                break;
        }

        return [
            'success' => $has_permission,
            'data' => $has_permission ? [] : [
                'title' => $translation['premium']['title'],
                'msg' => $message,
                'close' => $translation['premium']['submit'],
            ],
        ];
    }

    /**
     * @description check merchant is onboarded
     *
     * @param $datas
     *
     * @return array
     */
    public function submitSandboxAction($datas)
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getLoggedTranslations();
        $logger = $this->dependencies->getPlugin()->getLogger();
        if (!is_object($datas) || !$datas) {
            $logger->addLog('ConfigurationAction::submitSandboxAction: Invalid parameter given, $datas must be a non empty object.');

            return [
                'success' => false,
                'data' => [
                    // TODO  add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }
        $email = $datas->payplug_email;
        $password = $datas->payplug_password;
        $is_valid_password = $this->dependencies
            ->getValidators()['account']
            ->isPassword($password)['result'];

        if (!$is_valid_password) {
            $logger->addLog('ConfigurationAction::submitSandboxAction: invalid password.');

            return [
                'success' => false,
                'data' => [
                    'message' => $translation['inactive']['modal']['error'],
                ],
            ];
        }

        $password = base64_decode($datas->payplug_password);

        if (!$this->dependencies->apiClass->login($email, $password)) {
            $logger->addLog('ConfigurationAction::submitSandboxAction: invalid email and/or password.');

            return [
                'success' => false,
                'data' => [
                    //TODO add translation
                    'message' => $translation['inactive']['modal']['error'],
                ],
            ];
        }
        $permissions = $this->dependencies->name == 'pspaylater' ? 'onboarding_oney_completed' : 'use_live_mode';
        if (!$this->checkPermissionAction($permissions)['success']) {
            return [
                    'success' => false,
                    'data' => [
                        'still_inactive' => !$this->checkPermissionAction($permissions)['success'],
                        'message' => '',
                    ],
                ];
        }

        return [
                    'success' => true,
                    'data' => [
                        'still_inactive' => !$this->checkPermissionAction($permissions)['success'],
                        'message' => '',
                    ],
                ];
    }

    /**
     * @description Process the login of the merchant
     *
     * @param object $datas
     *
     * @return array
     */
    public function loginAction($datas = null)
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getLoginTranslations();
        $logger = $this->dependencies->getPlugin()->getLogger();

        if (!is_object($datas) || !$datas) {
            $logger->addLog('ConfigurationAction::loginAction: Invalid parameter given, $datas must be a non empty object.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        if (!isset($datas->action) || 'payplug_login' != $datas->action) {
            $logger->addLog('ConfigurationAction::loginAction: Invalid parameter given, $datas->action is invalid.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        $email = $datas->payplug_email;
        if (!$email) {
            $logger->addLog('ConfigurationAction::loginAction: invalid email.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => $translation['login_error'],
                ],
            ];
        }

        $password = base64_decode($datas->payplug_password);
        $is_valid_password = $this->dependencies
            ->getValidators()['account']
            ->isPassword($password)['result'];

        if (!$password || !$is_valid_password) {
            $logger->addLog('ConfigurationAction::loginAction: invalid password.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => $translation['login_error'],
                ],
            ];
        }

        if (!$this->dependencies->apiClass->login($email, $password)) {
            $logger->addLog('ConfigurationAction::loginAction: invalid email and/or password.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => $translation['login_error'],
                ],
            ];
        }

        $config = $this->dependencies->getPlugin()->getConfiguration();
        $config->updateValue($this->dependencies->getConfigurationKey('email'), $email);
        $config->updateValue($this->dependencies->getConfigurationKey('enable'), 1);
        if ((bool) $config->get($this->dependencies->getConfigurationKey('liveApiKey'))) {
            $config->updateValue($this->dependencies->getConfigurationKey('sandboxMode'), 0);
        }

        return $this->renderConfiguration();
    }

    /**
     * @description Process the logout of the merchant
     *
     * @return array
     */
    public function logoutAction()
    {
        $this->dependencies->configClass->logout();
        $api_rest = $this->dependencies->getPlugin()->getApiRest();

        return [
            'success' => true,
            'data' => [
                'message' => 'Successfully logged out.',
                'settings' => $api_rest->getSettingsSection(),
                'status' => $api_rest->getRequirementsSection(),
            ],
        ];
    }

    /**
     * @description render the module configuration section
     *
     * @return array
     */
    public function renderConfiguration()
    {
        $api_rest = $this->dependencies->getPlugin()->getApiRest();
        $current_configuration = $api_rest->getDataFields();

        $setting = $api_rest->getSettingsSection($current_configuration);
        $header = $api_rest->getHeaderSection($current_configuration);
        $footer = $api_rest->getFooterSection();

        $is_logged = isset($current_configuration['logged']) ? $current_configuration['logged'] : false;

        if ((bool) $is_logged) {
            $datas = [
                'payplug_wooc_settings' => $current_configuration,
                'settings' => $setting,
                'header' => $header,
                'login' => $api_rest->getLoginSection(),
                'logged' => $api_rest->getLoggedSection($current_configuration),
                'payment_methods' => $api_rest->getPaymentMethodsSection($current_configuration),
                'payment_paylater' => $api_rest->getPaylaterSection($current_configuration),
                'status' => $api_rest->getRequirementsSection(),
                'footer' => $footer,
            ];
        } else {
            $datas = [
                'settings' => $api_rest->getSettingsSection(),
                'header' => $header,
                'login' => $api_rest->getLoginSection(),
                'subscribe' => $api_rest->getSubscribeSection(),
                'payment_methods' => $api_rest->getPaymentMethodsSection(),
                'payment_paylater' => $api_rest->getPaylaterSection(),
                'status' => $api_rest->getRequirementsSection(),
                'footer' => $footer,
            ];
        }

        return [
            'success' => true,
            'data' => $datas,
        ];
    }

    /**
     * @description Process the save the configuration
     *
     * @param object $datas
     *
     * @return array
     */
    public function saveAction($datas = null)
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslation()
            ->getModalTranslations();

        $logger = $this->dependencies->getPlugin()->getLogger();

        if (!is_object($datas) || !$datas) {
            $logger->addLog('ConfigurationAction::saveAction: Invalid parameter given, $datas must be a non empty object.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        if (!isset($datas->action) || 'payplug_save_data' != $datas->action) {
            $logger->addLog('ConfigurationAction::saveAction: Invalid parameter given, $datas->action is invalid.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        $configuration = $this->dependencies->getPlugin()->getConfiguration();

        $configurationKeys = [
            $this->dependencies->getConfigurationKey('deferred') => 'enable_payplug_deferred',
            $this->dependencies->getConfigurationKey('deferredState') => 'payplug_deferred_state',
            $this->dependencies->getConfigurationKey('enable') => 'payplug_enable',
            $this->dependencies->getConfigurationKey('embeddedMode') => 'payplug_embeded',
            $this->dependencies->getConfigurationKey('inst') => 'enable_payplug_inst',
            $this->dependencies->getConfigurationKey('instMinAmount') => 'payplug_inst_min_amount',
            $this->dependencies->getConfigurationKey('instMode') => 'payplug_inst_mode',
            $this->dependencies->getConfigurationKey('oneClick') => 'enable_one_click',
            $this->dependencies->getConfigurationKey('oney') => 'enable_oney',
            $this->dependencies->getConfigurationKey('oneyOptimized') => 'enable_oney_schedule',
            $this->dependencies->getConfigurationKey('oneyProductCta') => 'enable_oney_product_animation',
            $this->dependencies->getConfigurationKey('oneyCartCta') => 'enable_oney_cart_animation',
            $this->dependencies->getConfigurationKey('oneyFees') => 'payplug_oney',
            $this->dependencies->getConfigurationKey('sandboxMode') => 'payplug_sandbox',
            $this->dependencies->getConfigurationKey('standard') => 'enable_standard',
            $this->dependencies->getConfigurationKey('oneyCustomMinAmounts') => 'oney_min_amounts',
            $this->dependencies->getConfigurationKey('oneyCustomMaxAmounts') => 'oney_max_amounts',
            $this->dependencies->getConfigurationKey('bancontact') => 'enable_bancontact',
            $this->dependencies->getConfigurationKey('bancontactCountry') => 'enable_bancontact_country',
            $this->dependencies->getConfigurationKey('applepay') => 'enable_applepay',
            $this->dependencies->getConfigurationKey('amex') => 'enable_american_express',
        ];

        foreach ($configurationKeys as $key => $config) {
            if (isset($datas->{$config})) {
                $value = $datas->{$config};
                switch ($config) {
                    case 'enable_one_click':
                        if ((bool) $datas->enable_standard && !$configuration->updateValue($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    case 'payplug_oney_type':
                    case 'enable_oney_product_animation':
                    case 'enable_oney_cart_animation':
                    case 'enable_oney_schedule':
                        if (((bool) $datas->enable_oney || 'pspaylater' == $this->dependencies->name) && !$configuration->updateValue($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    case 'payplug_inst_min_amount':
                    case 'payplug_inst_mode':
                        if ((int) $datas->payplug_inst_min_amount >= 4
                            && (int) $datas->payplug_inst_mode < 5
                            && (int) $datas->payplug_inst_mode > 1
                            && !$configuration->updateValue($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    case 'oney_min_amounts':
                    case 'oney_max_amounts':
                        $oney = $this->dependencies->getPlugin()->getOney();
                        $limit_oney = $oney->getOneyPriceLimit(false);
                        $amount = $datas->{$config};
                        $amount_to_cent = $this->dependencies->amountCurrencyClass->convertAmount($amount);
                        $is_valid_amount = $this->dependencies
                            ->getValidators()['payment']
                            ->isAmount((int) $amount_to_cent, $limit_oney);
                        $formated_amount = $oney->setCustomOneyLimit((int) $amount_to_cent);
                        if ($is_valid_amount && !$configuration->updateValue($key, $formated_amount)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    case 'enable_bancontact':
                    case 'payplug_bancontact_country':
                        if (!(bool) $datas->payplug_sandbox && !$configuration->updateValue($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    default:
                        if (!$configuration->updateValue($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }
                }
            }

            if ('payplug_enable' == $key && (bool) $value) {
                $module = $this->dependencies->getPlugin()->getModule();
                $module->getInstanceByName($this->dependencies->name)->enable();
            }
        }

        return [
            'success' => true,
            'data' => [
                'title' => null,
                'msg' => $translation['confirmation']['text'],
                'close' => $translation['confirmation']['submit'],
            ],
        ];
    }
}
