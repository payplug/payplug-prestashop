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

use PayPlug\classes\MyLogPHP;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
     * todo: add coverage to this method
     *
     * @param string $payment_method
     * @param bool $sandbox_mode
     *
     * @return array
     */
    public function checkPermissionAction($payment_method = '', $sandbox_mode = null)
    {
        if ((!is_string($payment_method) || !$payment_method)
            || !is_bool($sandbox_mode)) {
            return [
                'success' => false,
                'data' => [
                    'msg' => 'An error occured while getting the permissions',
                ],
            ];
        }

        $permissions = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getAccount();

        $allowed_methods = [
            'american_express' => 'can_use_american_express',
            'applepay' => 'can_use_apple_pay',
            'bancontact' => 'can_use_bancontact',
            'deferred' => 'can_create_deferred_payment',
            'ideal' => 'can_use_ideal',
            'installment' => 'can_create_installment_plan',
            'integrated' => 'can_use_integrated_payments',
            'mybank' => 'can_use_mybank',
            'onboarding_oney_completed' => 'onboarding_oney_completed',
            'one_click' => 'can_save_cards',
            'oney' => 'can_use_oney',
            'satispay' => 'can_use_satispay',
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
            ->getTranslationClass()
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
        $target_blank = '" target="_blank">';
        $link_prefix = '<a href="';

        switch ($payment_method) {
            case 'oney':
                $message .= sprintf(
                    $translation['premium']['description']['oney'],
                    $translation['premium']['feature'][$payment_method]
                );
                $link = $link_prefix . $external_url['oney_cgv'] . $target_blank . $translation['premium']['link']['oney'] . '</a>';
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
            default:
                $message .= sprintf(
                    $translation['premium']['description']['contact'],
                    $translation['premium']['feature'][$payment_method]
                );
                $link = $link_prefix . $external_url['help'] . $target_blank . $translation['premium']['link']['contact'] . '</a>';
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
     * @description Process the deactivation of the module
     *
     * @return bool
     */
    public function disableAction()
    {
        return (bool) $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->set('enable', 0);
    }

    /**
     * @description Process the install of the module
     *
     * @return array
     */
    public function installAction()
    {
        $txt_log = new MyLogPHP($this->dependencies->getPlugin()->getConstant()->get('_PS_MODULE_DIR_') . $this->dependencies->name . '/log/install-log.csv');
        $txt_log->info('Starting to install');

        // check requirement
        $txt_log->info('Check requirement');
        $report = $this->dependencies->getHelpers()['configuration']->getRequirements();
        if (!$report['php']['up2date']) {
            $txt_log->info('Install failed: PHP Requirement.');

            return [
                'result' => false,
                'message' => 'Install failed: PHP Requirement.',
            ];
        }
        if (!$report['curl']['up2date']) {
            $txt_log->info('Install failed: cURL Requirement.');

            return [
                'result' => false,
                'message' => 'Install failed: cURL Requirement.',
            ];
        }
        if (!$report['openssl']['up2date']) {
            $txt_log->info('Install failed: OpenSSL Requirement.');

            return [
                'result' => false,
                'message' => 'Install failed: OpenSSL Requirement.',
            ];
        }
        $txt_log->info('Check requirement: OK');

        // Check if multishop feature is active then set the context
        if ($this->dependencies->getPlugin()->getShop()->isFeatureActive()) {
            $txt_log->info('Set context');
            $this->dependencies->getPlugin()->getShop()->setContext();
        }

        // Set payplug config
        $txt_log->info('Set configuration');
        if (!$this->dependencies->getPlugin()->getConfigurationClass()->initialize()) {
            $txt_log->info('Install failed: Set configuration');

            return [
                'result' => false,
                'message' => 'Install failed: Set configuration',
            ];
        }
        $txt_log->info('Set configuration: OK');

        // Install SQL
        $txt_log->info('Install SQL');

        if (!$this->dependencies->getPlugin()->getEntityRepository()->initialize()) {
            $txt_log->info('Install failed: Install SQL tables.');

            return [
                'result' => false,
                'message' => 'Install failed: Install SQL tables.',
            ];
        }
        $txt_log->info('Install SQL: OK');

        // Install order state
        $txt_log->info('Install order state');
        if (!$this->installOrderStateAction()) {
            $txt_log->info('Install failed: Install order state.');

            return [
                'result' => false,
                'message' => 'Install failed: Install order state.',
            ];
        }
        $txt_log->info('Install order state: OK');

        // Install order state type
        $txt_log->info('Install order state type');
        if (!$this->dependencies->getPlugin()->getOrderStateAction()->installTypeAction()) {
            $txt_log->info('Install failed: Create order states type.');

            return [
                'result' => false,
                'message' => 'Install failed: Create order states type.',
            ];
        }
        $txt_log->info('Install order state type: OK');

        // Install tab
        $txt_log->info('Install tab');
        if (!$this->installTabAction()) {
            $txt_log->info('Install failed: Install Tab.');

            return [
                'result' => false,
                'message' => 'Install failed: Install Tab.',
            ];
        }
        $txt_log->info('Install tab: OK');

        // Set hook
        $txt_log->info('Install hook');
        if (!$this->installHookAction()) {
            $txt_log->info('Install failed: Install hook.');

            return [
                'result' => false,
                'message' => 'Install failed: Install hook.',
            ];
        }
        $txt_log->info('Install tab: OK');

        // Clean external files
        $txt_log->info('Files cleaning');
        $helpers = $this->dependencies->getHelpers();
        $file_cleaned = $helpers['files']::clean();
        $txt_log->info('Files cleaning: ' . ($file_cleaned ? 'ok' : 'ko'));

        return [
            'result' => true,
            'message' => 'Install successful',
        ];
    }

    /**
     * @description Install tab
     *
     * todo: move this section in the appropriate files
     *
     * @return bool
     */
    public function installTabAction()
    {
        $installed = true;
        $tab_adapter = $this->dependencies
            ->getPlugin()
            ->getTabAdapter();
        $module = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name);

        if (isset($module->adminControllers) && !empty($module->adminControllers)) {
            foreach ($module->adminControllers as $adminController) {
                if ($tab_adapter->getIdFromClassName($adminController['className'])) {
                    continue;
                }

                $tab = $tab_adapter->get();

                $languages_adatper = $this->dependencies
                    ->getPlugin()
                    ->getLanguage();

                if (isset($adminController['name'])) {
                    foreach ($languages_adatper->getLanguages(false) as $language) {
                        $id_lang = (int) $language['id_lang'];
                        $iso_code = $this->dependencies
                            ->getPlugin()
                            ->getTools()
                            ->tool('strtolower', $language['iso_code']);
                        if (isset($adminController['name'][$iso_code])) {
                            $tab->name[$id_lang] = $adminController['name'][$iso_code];
                        } else {
                            $tab->name[$id_lang] = $adminController['name']['en'];
                        }
                    }
                } else {
                    $tab->name = array_fill_keys($languages_adatper->getIDs(false), $this->dependencies->name);
                }

                if (isset($adminController['parent'])) {
                    if (is_int($adminController['parent'])) {
                        $tab->id_parent = $adminController['parent'];
                    } else {
                        $tab->id_parent = $tab_adapter->getIdFromClassName($adminController['parent']);
                    }
                }

                $tab->class_name = $adminController['className'];
                $tab->active = true;
                $tab->module = $this->dependencies->name;
                $installed = $installed && $tab->add();
            }
        }

        return $installed;
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
            ->getTranslationClass()
            ->getLoginTranslations();
        $logger = $this->dependencies->getPlugin()->getLogger();

        if (!$datas) {
            $logger->addLog('ConfigurationAction::loginAction: Invalid parameter given, $datas must be a non null object.');

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

        $password = mb_convert_encoding(base64_decode($datas->payplug_password), 'UTF-8', 'ISO-8859-1');
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

        if (!$this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->login($email, $password)) {
            $logger->addLog('ConfigurationAction::loginAction: invalid email and/or password.');

            return [
                'success' => false,
                'data' => [
                    // todo: add translation
                    'message' => $translation['login_error'],
                ],
            ];
        }

        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $configuration->set('email', $email);
        $configuration->set('enable', '1');

        // Update global configuration
        $permissions = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getAccount();

        if (empty($permissions)) {
            $logger->addLog('ConfigurationAction::loginAction: No permissions found for this account');

            return [
                'success' => false,
                'data' => [
                    'message' => $translation['login_error'],
                ],
            ];
        }

        if ((bool) $configuration->getValue('live_api_key')) {
            $configuration->set('sandbox_mode', 0);
        }

        return $this->renderConfiguration();
    }

    /**
     * @description Login and generate jwt from a given session
     *
     * @param string $authorization_code
     *
     * @return array
     */
    public function oauthLoginAction($authorization_code = '')
    {
        if (!is_string($authorization_code) || '' == $authorization_code) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ConfigurationAction::OauthLoginAction - Invalid parameter given, $authorization_code must be a non empty string.');

            return [
                'message' => 'ConfigurationAction::OauthLoginAction - Invalid parameter given, $authorization_code must be a non empty string.',
                'result' => false,
            ];
        }

        // Set usage variable
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $context = $this->dependencies->getPlugin()->getContext()->get();
        $module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $api = $module->getService('payplug.utilities.service.api');

        // Get the JWT one shot
        $jwt = $api->generateJWTOneShot(
            $authorization_code,
            $context->link->getAdminLink('AdminPayplug'),
            $configuration->getValue('oauth_client_id'),
            $configuration->getValue('oauth_code_verifier')
        );

        $email = $jwt['email'];

        if (empty($jwt) || !$jwt['result']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ConfigurationAction::OauthLoginAction - JWT one shot can\'t be got.');

            return [
                'message' => 'ConfigurationAction::OauthLoginAction - JWT one shot can\'t be got.',
                'result' => false,
            ];
        }

        // Get the client data
        $merchant = $module->getService('payplug.models.classes.merchant');
        $company_id = $configuration->getValue('oauth_company_id');
        $client_data = $merchant->getClientData($jwt['data'], $company_id);
        if (empty($client_data) || !$client_data['result']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ConfigurationAction::OauthLoginAction - Client data shot can\'t be got.');

            return [
                'message' => 'ConfigurationAction::OauthLoginAction - Client data shot can\'t be got.',
                'result' => false,
            ];
        }
        $configuration->set('client_data', json_encode($client_data['data']));

        // If jwt doesn't exists, we generate one.
        $jwt = $merchant->generateJWT($client_data['data']);
        if (empty($jwt) || !$jwt['result']) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ConfigurationAction::OauthLoginAction - JWT can\'t be got.');

            return [
                'message' => 'ConfigurationAction::OauthLoginAction - JWT can\'t be got.',
                'result' => false,
            ];
        }

        $configuration->set('jwt', json_encode($jwt['data']));

        // Finalize the login
        $configuration->set('email', $email);
        $configuration->set('enable', 1);

        // Update global configuration
        $permissions = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getAccount();

        if (empty($permissions)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ConfigurationAction::OauthLoginAction: No permissions found for this account');

            return [
                'message' => 'No permissions found for this account.',
                'result' => false,
            ];
        }

        if (!empty($jwt['data']['live'])) {
            $configuration->set('sandbox_mode', 0);
        }

        return [
            'message' => 'User connected',
            'result' => true,
        ];
    }

    /**
     * @description Process the logout of the merchant
     *
     * todo: add coverage to this method
     *
     * @return array
     */
    public function logoutAction()
    {
        $this->dependencies->configClass->logout();
        $api_rest = $this->dependencies->getPlugin()->getApiRestClass();

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
     * @description Register merchant information to log through unify authentication
     *
     * @param string $client_id
     * @param string $company_id
     *
     * @return bool
     */
    public function registerOauthRequestAction($client_id = '', $company_id = '')
    {
        if (!is_string($client_id) || '' == $client_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ConfigurationAction::registerOauthAction - Invalid parameter given, $client_id must be a non empty string.');

            return false;
        }

        if (!is_string($company_id) || '' == $company_id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('ConfigurationAction::registerOauthAction - Invalid parameter given, $company_id must be a non empty string.');

            return false;
        }

        // Set usage variable
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $api = $module->getService('payplug.utilities.service.api');

        $configuration->set('oauth_client_id', $client_id);
        $configuration->set('oauth_company_id', $company_id);
        $code_verifier = bin2hex(openssl_random_pseudo_bytes(50));
        $configuration->set('oauth_code_verifier', $code_verifier);

        return $api->initiateOAuth(
            $client_id,
            $this->dependencies->getPlugin()->getContext()->get()->link->getAdminLink('AdminPayplug'),
            $code_verifier
        );
    }

    /**
     * @description render the module configuration section
     *
     * todo: add coverage to this method
     *
     * @return array
     */
    public function renderConfiguration()
    {
        $api_rest = $this->dependencies->getPlugin()->getApiRestClass();
        $current_configuration = $api_rest->getDataFields();

        $setting = $api_rest->getSettingsSection($current_configuration);
        $header = $api_rest->getHeaderSection($current_configuration);
        $footer = $api_rest->getFooterSection();

        $is_logged = isset($current_configuration['logged']) ? $current_configuration['logged'] : false;

        $logged_section = [];

        if ((bool) $is_logged) {
            $logged_section = $api_rest->getLoggedSection($current_configuration);
        }
        if (!empty($logged_section)) {
            $datas = [
                'payplug_wooc_settings' => $current_configuration,
                'settings' => $setting,
                'header' => $header,
                'oauth_login' => $api_rest->getOAuthSection(),
                'logged' => $logged_section,
                'payment_methods' => $api_rest->getPaymentMethodsSection($current_configuration),
                'payment_paylater' => $api_rest->getPaylaterSection($current_configuration),
                'status' => $api_rest->getRequirementsSection(),
                'footer' => $footer,
            ];
        } else {
            $datas = [
                'settings' => $api_rest->getSettingsSection(),
                'header' => $header,
                'oauth_login' => $api_rest->getOAuthSection(),
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
            ->getTranslationClass()
            ->getModalTranslations();

        $logger = $this->dependencies->getPlugin()->getLogger();

        if (!$datas) {
            $logger->addLog('ConfigurationAction::saveAction: Invalid parameter given, $datas must be a non null object.');

            return [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => $translation['error']['text'],
                    'close' => $translation['error']['submit'],
                ],
            ];
        }

        if (!isset($datas->action) || 'payplug_save_data' != $datas->action) {
            $logger->addLog('ConfigurationAction::saveAction: Invalid parameter given, $datas->action is invalid.');

            return [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => $translation['error']['text'],
                    'close' => $translation['error']['submit'],
                ],
            ];
        }

        $applepay_cart = false;
        $applepay_product = false;
        $applepay_checkout = false;

        if (isset($datas->enable_applepay)) {
            $applepay_cart = isset($datas->enable_applepay_cart) && (bool) $datas->enable_applepay_cart;
            $applepay_product = isset($datas->enable_applepay_product) && (bool) $datas->enable_applepay_product;
            $applepay_checkout = isset($datas->enable_applepay_checkout) && (bool) $datas->enable_applepay_checkout;

            if ($datas->enable_applepay
                && !(bool) $applepay_cart
                && !(bool) $applepay_product
                && !(bool) $applepay_checkout) {
                return [
                    'success' => false,
                    'data' => [
                        'title' => null,
                        'msg' => $translation['applepay']['display']['text'],
                        'close' => $translation['applepay']['display']['submit'],
                        'class' => '-error',
                    ],
                ];
            }

            $need_carrier = $datas->enable_applepay && ((bool) $applepay_cart || (bool) $applepay_product);
            if ($datas->enable_applepay && empty($datas->applepay_carriers) && $need_carrier) {
                return [
                    'success' => false,
                    'data' => [
                        'title' => null,
                        'msg' => $translation['applepay']['carrier']['text'],
                        'close' => $translation['applepay']['carrier']['submit'],
                        'class' => '-error',
                    ],
                ];
            }
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
            'applepay_carriers' => 'applepay_carriers',
            'applepay_display' => 'enable_applepay',
        ];

        foreach ($configuration_keys as $key => $config) {
            if (isset($datas->{$config})) {
                $value = $datas->{$config};

                switch ($config) {
                    case 'payplug_oney':
                    case 'enable_oney_product_animation':
                    case 'enable_oney_cart_animation':
                    case 'enable_oney_schedule':
                        if (((bool) $datas->enable_oney || 'pspaylater' == $this->dependencies->name) && !$configuration->set($key, (string) $value)) {
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
                            && !$configuration->set($key, (string) $value)) {
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
                        if ((bool) $datas->enable_oney || 'pspaylater' == $this->dependencies->name) {
                            $limit_oney = $this->dependencies
                                ->getPlugin()
                                ->getPaymentMethodClass()
                                ->getPaymentMethod('oney')
                                ->getOneyPriceLimit(false);
                            $amount = $datas->{$config};
                            $amount_to_cent = $this->dependencies->amountCurrencyClass->convertAmount($amount);
                            $is_valid_amount = $this->dependencies
                                ->getValidators()['payment']
                                ->isAmount((int) $amount_to_cent, $limit_oney);
                            $formated_amount = $this->dependencies
                                ->getPlugin()
                                ->getPaymentMethodClass()
                                ->getPaymentMethod('oney')
                                ->setCustomOneyLimit((int) $amount_to_cent);

                            if ($is_valid_amount && !$configuration->set($key, (string) $formated_amount)) {
                                return [
                                    'success' => false,
                                    'data' => [
                                        // todo: add translation
                                        'message' => 'An error has occurred while register ' . $config,
                                    ],
                                ];
                            }
                        }

                        break;

                    case 'enable_bancontact_country':
                        if ((bool) $datas->enable_bancontact && !$configuration->set($key, (string) $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;

                    case 'enable_applepay':
                        $applepay_display = [
                            'cart' => (bool) $applepay_cart,
                            'checkout' => (bool) $applepay_checkout,
                            'product' => (bool) $applepay_product,
                        ];
                        if ((bool) $datas->enable_applepay && !$configuration->set($key, json_encode($applepay_display))) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
                                    'message' => 'An error has occurred while register applepay display',
                                ],
                            ];
                        }

                        break;

                    case 'applepay_carriers':
                        if ((bool) $datas->enable_applepay && !$configuration->set($key, json_encode($value))) {
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
                        if (!$configuration->set($key, (string) $value)) {
                            return [
                                'success' => false,
                                'data' => [
                                    // todo: add translation
                                    'message' => 'An error has occurred while register ' . $config,
                                ],
                            ];
                        }

                        break;
                }
            }

            if ('payplug_enable' == $key && (bool) $datas->payplug_enable) {
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
            'installment' => 'enable_payplug_inst',
            'ideal' => 'enable_ideal',
            'mybank' => 'enable_mybank',
            'one_click' => 'enable_one_click',
            'oney' => 'enable_oney',
            'satispay' => 'enable_satispay',
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

        $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->initialize(!(bool) $configuration->getValue('sandbox_mode'));

        return [
            'success' => true,
            'data' => [
                'title' => null,
                'msg' => $translation['confirmation']['text'],
                'close' => $translation['confirmation']['submit'],
            ],
            'send_telementry' => true,
        ];
    }

    /**
     * @description Check merchant is onboarded
     *
     * @param object $datas
     *
     * @return array
     */
    public function submitSandboxAction($datas)
    {
        $translation = $this->dependencies
            ->getPlugin()
            ->getTranslationClass()
            ->getLoggedTranslations();
        $logger = $this->dependencies->getPlugin()->getLogger();
        if (!is_object($datas) || null == $datas) {
            $logger->addLog('ConfigurationAction::submitSandboxAction: Invalid parameter given, $datas must be a non null object.');

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

        if (!$this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->login($email, $password)) {
            $logger->addLog('ConfigurationAction::submitSandboxAction: invalid email and/or password.');

            return [
                'success' => false,
                'data' => [
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
     * @description Process the uninstall of the module
     *
     * @return array
     */
    public function uninstallAction()
    {
        $txt_log = new MyLogPHP($this->dependencies->getPlugin()->getConstant()->get('_PS_MODULE_DIR_') . $this->dependencies->name . '/log/install-log.csv');
        $txt_log->info('Starting to uninstall.');

        $txt_log->info('Deleted saved card');
        if (!$this->dependencies
            ->getPlugin()
            ->getCardAction()
            ->uninstallAction()) {
            $txt_log->info('Uninstall failed: Unable to delete saved cards.');

            return [
                'result' => false,
                'message' => 'Uninstall failed: Unable to delete saved cards.',
            ];
        }
        $txt_log->info('Saved cards successfully deleted.');

        $txt_log->info('Remove module configuration');
        if (!$this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->deleteAll()) {
            $txt_log->info('Uninstall failed: Can\'t remove module configuration');

            return [
                'result' => false,
                'message' => 'Uninstall failed: Can\'t remove module configuration',
            ];
        }
        $txt_log->info('Remove module configuration successful');

        $txt_log->info('Drop module table');
        if (!$this->dependencies
            ->getPlugin()
            ->getEntityRepository()
            ->uninstall()) {
            $txt_log->info('Uninstall failed: Drop module table.');

            return [
                'result' => false,
                'message' => 'Uninstall failed: Drop module table.',
            ];
        }
        $txt_log->info('Drop module table successful');

        $txt_log->info('uninstall tab');
        if (!$this->uninstallTabAction()) {
            $txt_log->info('Uninstall failed: Tab.');

            return [
                'result' => false,
                'message' => 'Uninstall failed: Tab.',
            ];
        }
        $txt_log->info('uninstall tab successful');

        $txt_log->info('Uninstall successful.');

        return [
            'result' => true,
            'message' => 'Uninstall successful',
        ];
    }

    /**
     * @description Install hook
     *
     * todo: move this section in the appropriate files
     * todo: add coverage to this method
     *
     * @return bool
     */
    protected function installHookAction()
    {
        $module = $this->dependencies->getPlugin()->getModule()->getInstanceByName($this->dependencies->name);
        $hook_list = $module->getHookList();
        $hook_install = true;
        if (!$hook_list) {
            return $hook_install;
        }
        foreach ($hook_list as $hook) {
            if ($hook_install) {
                $hook_install = $module->registerHook($hook);
            } else {
                break;
            }
        }

        return $hook_install;
    }

    /**
     * @description Install order state
     *
     * todo: move this section in the validates actions OrderStateAction/OrderStateClass
     * todo: add coverage to this method
     *
     * @return bool
     */
    protected function installOrderStateAction()
    {
        $order_state = $this->dependencies->getPlugin()->getOrderState();
        $order_states_list = $this->dependencies->getPlugin()->getConfigurationClass()->order_states;
        $install_order_state = true;

        foreach ($order_states_list as $key => $state) {
            if ($install_order_state) {
                $install_order_state = $order_state->create($key, $state, true);
                $install_order_state = $install_order_state && $order_state->create($key, $state, false);
            } else {
                break;
            }
        }

        if ($install_order_state) {
            $install_order_state = $order_state->removeIdsUnusedByPayPlug();
        }

        return $install_order_state;
    }

    /**
     * @description uninstall tab
     *
     * todo: move this section in the appropriate files
     * todo: add coverage to this method
     *
     * @return bool
     */
    protected function uninstallTabAction()
    {
        $flag = true;
        $tab_adapter = $this->dependencies
            ->getPlugin()
            ->getTabAdapter();
        $module = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name);

        if (!isset($module->adminControllers) || empty($module->adminControllers)) {
            return $flag;
        }

        foreach ($module->adminControllers as $adminController) {
            if ($idTab = $tab_adapter->getIdFromClassName($adminController['className'])) {
                $tab = $tab_adapter->get($idTab);
                if (!$this->dependencies
                    ->getPlugin()
                    ->getValidate()
                    ->validate('isLoadedObject', $tab)) {
                    $flag = false;

                    continue;
                }
                $flag = $flag && $tab->delete();
                unset($idTab);
            }
        }

        return $flag;
    }
}
