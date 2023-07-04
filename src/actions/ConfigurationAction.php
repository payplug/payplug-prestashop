<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
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
     * @description check permission for a given payment method
     *
     * @param string $payment_method
     * @param bool   $sandbox_mode
     *
     * @return array
     */
    public function checkPermissionAction($payment_method = '', $sandbox_mode = null)
    {
        // todo: add unit tests
        if ((!is_string($payment_method) || !$payment_method)
            || !is_bool($sandbox_mode)) {
            return [
                'success' => false,
                'data' => [
                    'msg' => 'An error occured while getting the permissions',
                ],
            ];
        }

        $config = $this->dependencies->getPlugin()->getConfiguration();

        if ($sandbox_mode) {
            $permissions = $this->dependencies->apiClass->getAccountPermissions(
                $config->get($this->dependencies->getConfigurationKey('testApiKey'))
            );
        } else {
            $permissions = $this->dependencies->apiClass->getAccountPermissions(
                $config->get($this->dependencies->getConfigurationKey('liveApiKey'))
            );
        }

        $allowed_methods = [
            'american_express' => 'can_use_amex',
            'applepay' => 'can_use_applepay',
            'bancontact' => 'can_use_bancontact',
            'deferred' => 'can_create_deferred_payment',
            'giropay' => 'can_use_giropay',
            'ideal' => 'can_use_ideal',
            'installment' => 'can_create_installment_plan',
            'integrated' => 'can_use_integrated_payments',
            'mybank' => 'can_use_mybank',
            'onboarding_oney_completed' => 'onboarding_oney_completed',
            'one_click' => 'can_save_cards',
            'oney' => 'can_use_oney',
            'satispay' => 'can_use_satispay',
            'sofort' => 'can_use_sofort',
            'use_live_mode' => 'use_live_mode',
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

        $has_permission = $this->dependencies
            ->getValidators()['payment']
            ->hasPermissions($permissions, $allowed_methods[$payment_method])['result'];

        $message = $translation['premium']['description']['unavailable'];
        switch ($payment_method) {
            case 'american_express':
            case 'bancontact':
            case 'giropay':
            case 'ideal':
            case 'mybank':
            case 'satispay':
            case 'sofort':
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
                    // no break
            case 'integrated':
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
                    // todo:  add translation
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
                    // todo: add translation
                    'message' => $translation['inactive']['modal']['error'],
                ],
            ];
        }
        $permissions = 'pspaylater' == $this->dependencies->name ? 'onboarding_oney_completed' : 'use_live_mode';
        if (!$this->checkPermissionAction($permissions, $datas->env)['success']) {
            return [
                'success' => false,
                'data' => [
                    'still_inactive' => !$this->checkPermissionAction($permissions, $datas->env)['success'],
                    'message' => '',
                ],
            ];
        }

        return [
            'success' => true,
            'data' => [
                'still_inactive' => !$this->checkPermissionAction($permissions, $datas->env)['success'],
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
                    // todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        if (!isset($datas->action) || 'payplug_login' != $datas->action) {
            $logger->addLog('ConfigurationAction::loginAction: Invalid parameter given, $datas->action is invalid.');

            return [
                'success' => false,
                'data' => [
                    // todo: add translation
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
                    // todo: add translation
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
                    // todo: add translation
                    'message' => $translation['login_error'],
                ],
            ];
        }

        if (!$this->dependencies->apiClass->login($email, $password)) {
            $logger->addLog('ConfigurationAction::loginAction: invalid email and/or password.');

            return [
                'success' => false,
                'data' => [
                    // todo: add translation
                    'message' => $translation['login_error'],
                ],
            ];
        }

        $config = $this->dependencies->getPlugin()->getConfiguration();
        $config->updateValue($this->dependencies->getConfigurationKey('email'), $email);
        $config->updateValue($this->dependencies->getConfigurationKey('enable'), 1);

        // Update global configuration
        $permissions = $this->dependencies->apiClass->getAccountPermissions(
            $config->get($this->dependencies->getConfigurationKey('liveApiKey'))
        );

        if ('pspaylater' == $this->dependencies->name) {
            if ((bool) $permissions['can_use_oney']
                && (bool) $permissions['onboarding_oney_completed']) {
                $config->updateValue($this->dependencies->getConfigurationKey('sandboxMode'), 0);
            }
        } elseif ((bool) $config->get($this->dependencies->getConfigurationKey('liveApiKey'))) {
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
                'subscribe' => $api_rest->getSubscribeSection(),
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
                'payment_paylater' => $api_rest->getPaylaterSection($current_configuration),
                'status' => $api_rest->getRequirementsSection(),
                'footer' => $footer,
            ];

            // Add payment_methods section if module is payplug
            if ('payplug' == $this->dependencies->name) {
                $datas['payment_methods'] = $api_rest->getPaymentMethodsSection($current_configuration);
            }
        } else {
            $datas = [
                'settings' => $api_rest->getSettingsSection(),
                'header' => $header,
                'login' => $api_rest->getLoginSection(),
                'subscribe' => $api_rest->getSubscribeSection(),
                'payment_paylater' => $api_rest->getPaylaterSection(),
                'status' => $api_rest->getRequirementsSection(),
                'footer' => $footer,
            ];

            // Add payment_methods section if module is payplug
            if ('payplug' == $this->dependencies->name) {
                $datas['payment_methods'] = $api_rest->getPaymentMethodsSection();
            }
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
                    // todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }

        if (!isset($datas->action) || 'payplug_save_data' != $datas->action) {
            $logger->addLog('ConfigurationAction::saveAction: Invalid parameter given, $datas->action is invalid.');

            return [
                'success' => false,
                'data' => [
                    // todo: add translation
                    'message' => 'An error has occurred',
                ],
            ];
        }
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $configuration_keys = [
            'deferred_state' => 'payplug_deferred_state',
            'enable' => 'payplug_enable',
            'embedded_mode' => 'payplug_embeded',
            'inst_min_amount' => 'payplug_inst_min_amount',
            'inst_mode' => 'payplug_inst_mode',
            'oney_optimized' => 'enable_oney_schedule',
            'oney_product_cta' => 'enable_oney_product_animation',
            'oney_cart_cta' => 'enable_oney_cart_animation',
            'oney_fees' => 'payplug_oney',
            'sandbox_mode' => 'payplug_sandbox',
            'oney_custom_min_amounts' => 'oney_min_amounts',
            'oney_custom_max_amounts' => 'oney_max_amounts',
            'bancontact_country' => 'enable_bancontact_country',
        ];

        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();

        $get_account = $this->dependencies->apiClass->getAccount($configuration->getValue('live_api_key'), false, false);
        $amounts = [
            'default' => [
                'min' => 'EUR:100',
                'max' => 'EUR:2000000',
            ],
        ];
        $countries = [];
        foreach ($get_account['payment_methods'] as $key => $payment_method) {
            if (array_key_exists('min_amounts', $payment_method)) {
                $amounts[$key]['min'] = 'EUR:' . $payment_method['min_amounts']['EUR'];
            }
            if (array_key_exists('max_amounts', $payment_method)) {
                $amounts[$key]['max'] = 'EUR:' . $payment_method['max_amounts']['EUR'];
            }
            if (array_key_exists('allowed_countries', $payment_method)) {
                $countries[$key] = $payment_method['allowed_countries'];
            }
        }

        $configuration_get_account = [
            'amounts' => json_encode($amounts),
            'countries' => json_encode($countries),
        ];
        foreach ($configuration_get_account as $key => $config) {
            if (!$configuration->set($key, $config)) {
                return [
                    'success' => false,
                    'data' => [
                        // todo: add translation
                        'message' => 'An error has occurred while register ' . $key,
                    ],
                ];
            }
        }

        foreach ($configuration_keys as $key => $config) {
            if (isset($datas->{$config})) {
                $value = $datas->{$config};
                switch ($config) {
                    case 'payplug_oney':
                    case 'enable_oney_product_animation':
                    case 'enable_oney_cart_animation':
                    case 'enable_oney_schedule':
                        if (((bool) $datas->enable_oney || 'pspaylater' == $this->dependencies->name) && !$configuration->set($key, (int) $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
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
                            && !$configuration->set($key, (int) $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
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
                        if ($is_valid_amount && !$configuration->set($key, (string) $formated_amount)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    case 'payplug_bancontact_country':
                        if ((bool) $datas->enable_bancontact && !$configuration->set($key, (int) $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    default:
                        switch ($configuration->getType($key)) {
                            case 'integer':
                                $value = (int) $value;

                                break;
                            default:
                            case 'string':
                                $value = (string) $value;

                                break;
                        }
                        if (!$configuration->set($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
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

        $payment_methods = [];
        $payment_method_keys = [
            'amex' => 'enable_american_express',
            'applepay' => 'enable_applepay',
            'bancontact' => 'enable_bancontact',
            'deferred' => 'enable_payplug_deferred',
            'giropay' => 'enable_giropay',
            'inst' => 'enable_payplug_inst',
            'ideal' => 'enable_ideal',
            'mybank' => 'enable_mybank',
            'one_click' => 'enable_one_click',
            'oney' => 'enable_oney',
            'satispay' => 'enable_satispay',
            'sofort' => 'enable_sofort',
            'standard' => 'enable_standard',
        ];
        foreach ($payment_method_keys as $key => $config) {
            $payment_methods[$key] = isset($datas->{$config}) ? $datas->{$config} : false;
        }
        if (!$configuration->set('payment_methods', json_encode($payment_methods))) {
            return [
                'success' => false,
                'data' => [
                    // todo: add translation
                    'message' => 'An error has occurred while register ' . $config,
                ],
            ];
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
