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
     * @description Process the login of the merchant
     *
     * @param object $datas
     *
     * @return array
     */
    public function loginAction($datas = null)
    {
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
                    'message' => 'The email you entered is invalid.',
                ],
            ];
        }

        $password = $datas->payplug_password;
        $isPlaintextPassword = $this->dependencies->configClass->getAdapterPrestaClasse()->isPlaintextPassword($password);
        if (!$password || !$isPlaintextPassword) {
            $logger->addLog('ConfigurationAction::loginAction: invalid password.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'The password you entered is invalid.',
                ],
            ];
        }

        if (!$this->dependencies->apiClass->login($email, $password)) {
            $logger->addLog('ConfigurationAction::loginAction: invalid email and/or password.');

            return [
                'success' => false,
                'data' => [
                    // @todo: add translation
                    'message' => 'The email and/or password was not correct.',
                ],
            ];
        }

        $config = $this->dependencies->getPlugin()->getConfiguration();
        $config->updateValue($this->dependencies->getConfigurationKey('email'), $email);
        $config->updateValue($this->dependencies->getConfigurationKey('show'), 1);
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
        $config = $this->dependencies->getPlugin()->getConfiguration();
        $payplug_email = $config->get($this->dependencies->getConfigurationKey('email'));

        $header = $api_rest->getHeaderSection();
        $footer = $api_rest->getFooterSection();

        if ((bool) $payplug_email) {
            $payplug_wooc_settings = $api_rest->getDataFields($config);
            unset($payplug_wooc_settings['payplug_live_key'],
                $payplug_wooc_settings['payplug_test_key'],
                $payplug_wooc_settings['payplug_password'],
                $payplug_wooc_settings['payplug_merchant_id']);

            $datas = [
                'payplug_wooc_settings' => $payplug_wooc_settings,
                'settings' => $api_rest->getSettingsSection(true),
                'header' => $header,
                'login' => $api_rest->getLoginSection(),
                'logged' => $api_rest->getLoggedSection(),
                'payment_methods' => $api_rest->getPaymentMethodsSection($payplug_wooc_settings),
                'payment_paylater' => $api_rest->getPaylaterSection($payplug_wooc_settings),
                'status' => $api_rest->getRequirementsSection($payplug_wooc_settings),
                'footer' => $footer,
            ];
        } else {
            $datas = [
                'settings' => $api_rest->getSettingsSection(),
                'header' => $header,
                'login' => $api_rest->getLoginSection(),
                'subscribe' => $api_rest->getSubscribeSection(),
                'payment_methods' => $api_rest->getPaymentMethodsSection(),
                'payment_palate' => $api_rest->getPaylaterSection(),
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
            $this->dependencies->getConfigurationKey('deferred') => 'payplug_deferred',
            $this->dependencies->getConfigurationKey('deferredState') => 'payplug_deferred_state',
            $this->dependencies->getConfigurationKey('enable') => 'payplug_enable',
            $this->dependencies->getConfigurationKey('embeddedMode') => 'payplug_embedded_mode',
            $this->dependencies->getConfigurationKey('inst') => 'payplug_inst',
            $this->dependencies->getConfigurationKey('instMinAmount') => 'payplug_inst_min_amount',
            $this->dependencies->getConfigurationKey('instMode') => 'payplug_inst_mode',
            $this->dependencies->getConfigurationKey('oneClick') => 'payplug_one_click',
            $this->dependencies->getConfigurationKey('oney') => 'payplug_oney',
            $this->dependencies->getConfigurationKey('oneyOptimized') => 'payplug_oney_optimized',
            $this->dependencies->getConfigurationKey('oneyProductCta') => 'payplug_oney_product_cta',
            $this->dependencies->getConfigurationKey('oneyCartCta') => 'payplug_oney_cart_cta',
            $this->dependencies->getConfigurationKey('oneyFees') => 'payplug_oney_fees',
            $this->dependencies->getConfigurationKey('sandboxMode') => 'payplug_sandbox',
            $this->dependencies->getConfigurationKey('standard') => 'payplug_standard',
            $this->dependencies->getConfigurationKey('oneyCustomMaxAmounts') => 'payplug_oney_custom_max_amounts',
            $this->dependencies->getConfigurationKey('oneyCustomMinAmounts') => 'payplug_oney_custom_min_amounts',
            $this->dependencies->getConfigurationKey('bancontact') => 'payplug_bancontact',
            $this->dependencies->getConfigurationKey('bancontactCountry') => 'payplug_bancontact_country',
            $this->dependencies->getConfigurationKey('applepay') => 'payplug_applepay',
            $this->dependencies->getConfigurationKey('amex') => 'payplug_amex',
        ];

        foreach ($configurationKeys as $key => $config) {
            if (isset($datas->{$config})) {
                $value = $datas->{$config};
                switch ($config) {
                    case 'payplug_one_click':
                        if ((bool) $datas->payplug_standard && !$configuration->updateValue($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    case 'payplug_oney_optimized':
                    case 'payplug_oney_product_cta':
                    case 'payplug_oney_cart_cta':
                    case 'payplug_oney_fees':
                        if (((bool) $datas->payplug_oney || $this->dependencies->name == 'pspaylater') && !$configuration->updateValue($key, $value)) {
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
                        if ((int) $datas->payplug_inst_min_amount >= 4 && (int) $datas->payplug_inst_mode < 5 && (int) $datas->payplug_inst_mode > 1 && !$configuration->updateValue($key, $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                    case 'payplug_oney_custom_min_amounts':
                    case 'payplug_oney_custom_max_amounts':
                        $oney = $this->dependencies->getPlugin()->getOney();
                        $limit_oney = $oney->getOneyPriceLimit(false);
                        $amount = $datas->{$config};
                        $amount_to_cent = $this->dependencies->amountCurrencyClass->convertAmount($amount);
                        $is_valid_amount = $this->dependencies->getValidators['payment']->isAmount((int) $amount_to_cent, $limit_oney);
                        if ($is_valid_amount && !$configuration->updateValue($key, $oney->setCustomOneyLimit((int) $amount_to_cent))) {
                            return [
                                'success' => false,
                                'data' => [
                                    // @todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;

                    case 'payplug_bancontact':
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

        return $this->renderConfiguration();
    }
}
