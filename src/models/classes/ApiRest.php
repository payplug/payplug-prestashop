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

namespace PayPlug\src\models\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class ApiRest
{
    private $dependencies;
    private $helpers;
    private $validators;
    private $module;

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

        $configurationAction = $this->dependencies
            ->getPlugin()
            ->getConfigurationAction();
        $tools = $this->dependencies
            ->getPlugin()
            ->getTools();
        $this->module = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name);

        switch ($action) {
            case 'login_portal':
                $api = $this->module->getService('payplug.utilities.service.api');
                $context = $this->dependencies
                    ->getPlugin()
                    ->getContext()
                    ->get();
                $register_url = $api->getRegisterUrl($context->link->getAdminLink('AdminPayplug'));
                $json = [
                    'success' => $register_url['result'],
                ];
                if ($register_url['result']) {
                    $json['url'] = $register_url['redirection'];
                } else {
                    $json['message'] = $register_url['message'];
                }

                break;
            case 'login':
                $datas = json_decode($tools->tool('file_get_contents', 'php://input'), false);
                $json = $configurationAction->loginAction($datas);

                break;

            case 'logout':
                $json = $configurationAction->logoutAction();

                break;

            case 'american_express_permissions':
            case 'applepay_permissions':
            case 'bancontact_permissions':
            case 'deferred_permissions':
            case 'installment_permissions':
            case 'integrated_permissions':
            case 'one_click_permissions':
            case 'oney_permissions':
            case 'satispay_permissions':
            case 'mybank_permissions':
            case 'ideal_permissions':
                $datas = json_decode($tools->tool('file_get_contents', 'php://input'), false);
                $payment_method = str_replace('_permissions', '', $action);
                $json = $configurationAction->checkPermissionAction($payment_method, (bool) $datas->env);

                break;

            case 'check_requirements':
                $json = [
                    'status' => $this->getRequirementsSection(),
                ];

                break;

            case 'refresh_keys':
                $datas = json_decode($tools->tool('file_get_contents', 'php://input'), false);
                $json = $configurationAction->submitSandboxAction($datas);

                break;

            case 'save':
                $datas = json_decode($tools->tool('file_get_contents', 'php://input'), false);
                $json = $configurationAction->saveAction($datas);

                break;

            case 'send_telemetry':
                $render_telemetry = $this->dependencies
                    ->getPlugin()
                    ->getMerchantTelemetryAction()
                    ->sendAction('save');
                if (!$render_telemetry) {
                    $this->dependencies
                        ->getPlugin()
                        ->getLogger()
                        ->addLog('ConfigurationAction::saveAction: Error during telemetry sending');
                }
                $json = [
                    'success' => true,
                ];

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
        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();

        $userHelper = $this->helpers['user'];
        $jwt = json_decode($configuration->getValue('jwt'), true);
        $jwt_test = isset($jwt['test']) ? $jwt['test'] : '';
        $is_email = $this->validators['account']->isEmail(
            $configuration->getValue('email')
        );
        $logged = false;
        if ($is_email['result'] && $jwt_test) {
            $logged = $userHelper->isLogged(
                $is_email['result'],
                $jwt_test
            )['result'];
        }

        if (!$logged) {
            $this->dependencies
                ->getPlugin()
                ->getConfigurationAction()
                ->logoutAction();
            $logged = false;
        }

        $enable = $this->validators['module']->canBeShown(
            (bool) $configuration->getValue('enable')
        )['result'];

        $payment_methods = json_decode($configuration->getValue('payment_methods'), true);
        $amounts = json_decode($configuration->getValue('amounts'), true);

        return [
            'logged' => $logged,
            'email' => $configuration->getValue('email'),
            'enable' => $enable,
            'sandbox_mode' => (bool) $configuration->getValue('sandbox_mode'),
            'embedded_mode' => $configuration->getValue('embedded_mode'),
            'standard' => (bool) $payment_methods['standard'],
            'one_click' => (bool) $payment_methods['one_click'],
            'installment' => (bool) $payment_methods['installment'],
            'inst_mode' => $configuration->getValue('inst_mode'),
            'inst_min_amount' => $configuration->getValue('inst_min_amount'),
            'deferred' => (bool) $payment_methods['deferred'],
            'deferred_state' => $configuration->getValue('deferred_state'),
            'oney' => (bool) $payment_methods['oney'],
            'oney_fees' => (bool) $configuration->getValue('oney_fees'),
            'oney_schedule' => (bool) $configuration->getValue('oney_optimized'),
            'oney_product_animation' => (bool) $configuration->getValue('oney_product_cta'),
            'oney_cart_animation' => (bool) $configuration->getValue('oney_cart_cta'),
            'oney_min_amounts' => isset($amounts['oney_x3_with_fees']['min']) ? $amounts['oney_x3_with_fees']['min'] : '',
            'oney_max_amounts' => isset($amounts['oney_x3_with_fees']['max']) ? $amounts['oney_x3_with_fees']['max'] : '',
            'oney_custom_min_amounts' => $configuration->getValue('oney_custom_min_amounts'),
            'oney_custom_max_amounts' => $configuration->getValue('oney_custom_max_amounts'),
            'bancontact' => (bool) $payment_methods['bancontact'],
            'bancontact_country' => (bool) $configuration->getValue('bancontact_country'),
            'applepay' => (bool) $payment_methods['applepay'],
            'applepay_display' => $configuration->getValue('applepay_display'),
            'amex' => (bool) $payment_methods['amex'],
            'satispay' => (bool) $payment_methods['satispay'],
            'ideal' => (bool) $payment_methods['ideal'],
            'mybank' => (bool) $payment_methods['mybank'],
        ];
    }

    /**
     * @description build footer section for api usage
     *
     * @return array
     */
    public function getFooterSection()
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getFooterTranslations();
        $context = $this->dependencies
            ->getPlugin()
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
     * @description build header section of the json file
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

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getHeaderTranslations();

        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
        $default_configuration = [
            'enable' => $configuration->getDefault('enable'),
            'logged' => false,
        ];
        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

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
                'disabled' => !$current_configuration['logged'],
                'options' => [
                    [
                        'value' => 1,
                        'label' => $translation['visible'],
                        'checked' => $current_configuration['enable'],
                    ],
                    [
                        'value' => 0,
                        'label' => $translation['hidden'],
                        'checked' => !$current_configuration['enable'],
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

        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();

        $live_api_key = $configuration->getValue('live_api_key');
        $permissions = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getAccount((string) $live_api_key, false);
        if (!$permissions) {
            return [];
        }

        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getLoggedTranslations();

        $context = $this->dependencies->getPlugin()
            ->getContext()
            ->get();
        $iso_code = $context->language->iso_code;
        $external_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($iso_code);

        if ('pspaylater' == $this->dependencies->name) {
            $active = $live_api_key && $permissions['onboarding_oney_completed'];
        } else {
            $active = is_null($live_api_key) ? false : $live_api_key;
        }

        $default_configuration = [
            'sandbox_mode' => $configuration->getDefault('sandbox_mode'),
        ];
        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        $inactive_description = $translation['inactive']['account']['warning']['description'];
        $link = '<a href="' . $external_url['sandbox'] . '" target="_blank">'
            . $translation['inactive']['account']['warning']['link']
            . '</a>';
        $inactive_description = str_replace('$link', $link, $inactive_description);

        $live_trigger = '<span id="inactiveModalClick">'
            . $translation['inactive']['account']['warning']['trigger']
            . '</span>';
        $inactive_description = str_replace('$trigger', $live_trigger, $inactive_description);

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
                        'url' => $external_url['sandbox'],
                        'target' => '_blank',
                    ],
                    'link_access_portal' => [
                        'text' => $translation['user']['link'],
                        'url' => $external_url['portal'],
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
                        'url' => $external_url['sandbox'],
                        'target' => '_blank',
                    ],
                    'link_access_portal' => [
                        'text' => $translation['user']['link'],
                        'url' => $external_url['portal'],
                        'target' => '_blank',
                    ],
                ],
            ],
            'options' => [
                [
                    'name' => 'payplug_sandbox',
                    'label' => $translation['mode']['options']['sandbox'],
                    'value' => 1,
                    'checked' => (bool) $current_configuration['sandbox_mode'],
                ],
                [
                    'name' => 'payplug_sandbox',
                    'label' => $translation['mode']['options']['live'],
                    'value' => 0,
                    'checked' => !(bool) $current_configuration['sandbox_mode'],
                ],
            ],
            'can_be_disabled' => true,
            'inactive_modal' => [
                'inactive' => !$active,
                'title' => $translation['inactive']['modal']['title'],
                'description' => $translation['inactive']['modal']['description'],
                'password_label' => $translation['inactive']['modal']['password_label'],
                'cancel' => $translation['inactive']['modal']['cancel'],
                'ok' => $translation['inactive']['modal']['ok'],
            ],
            'payment_link' => [
                'active' => true,
                'info' => [
                    'title' => $translation['payment_link']['info']['title'],
                    'description' => $translation['payment_link']['info']['description'],
                ],
            ],
            'inactive_account' => [
                'warning' => [
                    'title' => $translation['inactive']['account']['warning']['title'],
                    'description' => $inactive_description,
                ],
                'error' => [
                    'title' => $translation['inactive']['account']['error']['title'],
                    'description' => $translation['inactive']['account']['error']['description'],
                ],
                'success' => [
                    'title' => $translation['inactive']['account']['success']['title'],
                    'description' => $translation['inactive']['account']['success']['description'],
                ],
            ],
        ];
    }

    /**
     * @description build oauth section for front usage
     *
     * @return array
     */
    public function getOAuthSection()
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getOauthLoginTranslations();
        $register_link = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl()['signup'];

        return [
            'name' => 'oathLogin',
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
                    'portal' => [
                        'text' => $translation['portal']['text'],
                        'button' => $translation['portal']['button'],
                    ],
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
                    'portal' => [
                        'text' => $translation['portal']['text'],
                        'button' => $translation['portal']['button'],
                    ],
                ],

            ],
        ];
    }

    /**
     * @description build paylater section for api usage
     *
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

        return $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getPaymentMethod('oney')
            ->getOption($current_configuration);
    }

    /**
     * @description build payment methods section for api usage
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
            ->getTranslationClass()
            ->getPaymentMethodsTranslations();

        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();

        $default_configuration = [
            'applepay_display' => $configuration->getDefault('applepay_display'),
            'bancontact_country' => $configuration->getDefault('bancontact_country'),
            'embedded_mode' => $configuration->getDefault('embedded_mode'),
            'inst_mode' => $configuration->getDefault('inst_mode'),
            'inst_min_amount' => $configuration->getDefault('inst_min_amount'),
            'deferred_state' => $configuration->getDefault('deferred_state'),
        ];
        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        $default_payment_method = json_decode($configuration->getDefault('payment_methods'), true);
        foreach ($default_payment_method as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        $payment_options = $this->dependencies
            ->getPlugin()
            ->getPaymentMethodClass()
            ->getOptionCollection($current_configuration);
        unset($payment_options['oney']);

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
            'options' => array_values($payment_options),
        ];
    }

    /**
     * @description build requirement section for api usage
     *
     * @return array
     */
    public function getRequirementsSection()
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getRequirementsTranslations();

        $requirements_reports = $this->dependencies
            ->getHelpers()['configuration']
            ->getRequirements();

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
            'enable_debug_check' => false, // todo: to be deleted
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

        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $default_configuration = [
            'email' => $configuration->getDefault('email'),
            'logged' => false,
            'sandbox_mode' => $configuration->getDefault('sandbox_mode'),
        ];
        foreach ($default_configuration as $k => $v) {
            if (!isset($current_configuration[$k])) {
                $current_configuration[$k] = $v;
            }
        }

        return [
            'email' => $current_configuration['email'],
            'logged' => $current_configuration['logged'],
            'mode' => (bool) $current_configuration['sandbox_mode'] ? 1 : 0,
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
            ->getTranslationClass()
            ->getSubscribeTranslations();

        $register_link = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl()['signup'];
        if ('pspaylater' == $this->dependencies->name) {
            $register_link .= '?sponsor=22101';
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
}
