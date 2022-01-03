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

use Configuration;
use Country;
use Db;
use Language;
use libphonenumberlight;
use Media;
use Module;
use PayPlug\backward\PayPlugBackward;
use Payplug\Exception\BadRequestException;
use Payplug\Exception\ConfigurationException;
use Payplug\Exception\ConfigurationNotSetException;
use PayPlug\src\repositories\LoggerRepository;
use PayPlug\src\specific\ConstantSpecific;
use PayPlug\src\specific\ContextSpecific;
use Tools;
use Validate;

class ConfigClass
{
    private $amountCurrencyClass;
    private $payplugClass;
    private $apiClass;
    private $mediaClass;
    private $oney;
    private $orderClass;
    private $validationErrors = [];
    private $html = '';
    private $constantSpecific;
    protected $context;
    public $logger;
    public $myLogPHP;
    private $install;
    private $api_live;
    private $api_test;
    public $email;
    private $img_lang;
    private $ssl_enable;
    public $warning;
    private $payment_status;
    private $check_configuration;
    public $version;
    private $PrestashopSpecificObject;

    public function __construct($payplug)
    {
        $this->amountCurrencyClass = $payplug->amountCurrencyClass;
        $this->apiClass = $payplug->apiClass;
        $this->install = $payplug->install;
        $this->context = $payplug->context;
        $this->mediaClass = $payplug->mediaClass;
        $this->oney = $payplug->oney;
        $this->orderClass = $payplug->orderClass;
        $this->constantSpecific = new ConstantSpecific();
        $this->context = (new ContextSpecific())->getContext();

        $this->payplugClass = $payplug;

        $this->setLoggers();
        $this->setConfigurationProperties();
        $this->loadSpecificPrestaClasses();
    }

    /**
     * Create log files to be used everywhere in PayPlug module
     *
     * @return void
     */
    private function setLoggers()
    {
        $this->logger = new LoggerRepository();
        $this->myLogPHP = new MyLogPHP();

        $this->logger->setParams(['process' => 'payplug.php']);

//        if ($this->active) {
//            $this->logger->flush();
//        }
    }

    public function loadSpecificPrestaClasses()
    {
        $PrestashopSpecificClass = '\PayPlug\src\specific\PrestashopSpecific' . _PS_VERSION_[0] . _PS_VERSION_[2];
        if (class_exists($PrestashopSpecificClass)) {
            $this->PrestashopSpecificObject = new $PrestashopSpecificClass($this->payplugClass);
        }
    }

    public function getSpecificPrestaClasse()
    {
        if ($this->PrestashopSpecificObject) {
            return $this->PrestashopSpecificObject;
        }
    }

    /**
     * Return specific constant
     * @param string $constant
     * @return mixed
     */
    public function getConstant($constant)
    {
        return $this->constantSpecific->get($constant);
    }

    /**
     * Set very specific properties
     *
     * @return void
     */
    private function setConfigurationProperties()
    {
        $this->api_live = Configuration::get('PAYPLUG_LIVE_API_KEY');
        $this->api_test = Configuration::get('PAYPLUG_TEST_API_KEY');

        $this->email = Configuration::get('PAYPLUG_EMAIL');
        $available_img_lang = [
            'fr',
            'gb',
            'en',
            'it'
        ];
        $this->img_lang = in_array($this->context->language->iso_code, $available_img_lang)
            ? $this->context->language->iso_code : 'default';
        $this->ssl_enable = Configuration::get('PS_SSL_ENABLED');

        if ((!isset($this->email) || (!isset($this->api_live) && empty($this->api_test)))) {
            $this->warning = $this->payplugClass->l(
                'payplug.setConfigurationProperties.configureModule',
                'configclass'
            );
        }

        $this->payment_status = [
            1 => $this->payplugClass->l('payplug.setConfigurationProperties.notPaid', 'configclass'),
            2 => $this->payplugClass->l('payplug.setConfigurationProperties.paid', 'configclass'),
            3 => $this->payplugClass->l('payplug.setConfigurationProperties.failed', 'configclass'),
            4 => $this->payplugClass->l('payplug.setConfigurationProperties.partiallyRefunded', 'configclass'),
            5 => $this->payplugClass->l('payplug.setConfigurationProperties.refunded', 'configclass'),
            6 => $this->payplugClass->l('payplug.setConfigurationProperties.onGoing', 'configclass'),
            7 => $this->payplugClass->l('payplug.setConfigurationProperties.cancelled', 'configclass'),
            8 => $this->payplugClass->l('payplug.setConfigurationProperties.authorized', 'configclass'),
            9 => $this->payplugClass->l('payplug.setConfigurationProperties.authorizationExpired', 'configclass'),
            10 => $this->payplugClass->l('payplug.setConfigurationProperties.oneyPending', 'configclass'),
            11 => $this->payplugClass->l('payplug.setConfigurationProperties.abandoned', 'configclass'),
        ];
    }

    public function getImgLang()
    {
        return $this->img_lang;
    }

    public function getPaymentStatus()
    {
        return $this->payment_status;
    }

    /**
     * @param bool $force_all
     * @return bool
     * @see Module::disable()
     *
     */
    public function disable($force_all = false)
    {
        Configuration::updateValue('PAYPLUG_SHOW', 0);
        $this->payplugClass->disable($force_all);

        $req_disable = '
            UPDATE `' . _DB_PREFIX_ . 'module`
            SET `active`= 0
            WHERE `name` = \'' . pSQL($this->name) . '\'';

        $res_disable = Db::getInstance()->Execute($req_disable);
        if (!$res_disable) {
            return false;
        }

        return true;
    }

    /**
     * @description
     * @param $cart
     * @return array
     */
    public static function getAvailableOptions($cart)
    {
        if (!self::isAllowed()) {
            return false;
        }

        $permissions = ApiClass::getAccountPermissions();

        $available_options = [
            'standard' => (int)Configuration::get('PAYPLUG_STANDARD') === 1,
            'live' => (int)Configuration::get('PAYPLUG_SANDBOX_MODE') === 0,
            'embedded' => (string)Configuration::get('PAYPLUG_EMBEDDED_MODE'),
            'one_click' => (int)Configuration::get('PAYPLUG_ONE_CLICK') === 1,
            'installment' => (int)Configuration::get('PAYPLUG_INST') === 1,
            'deferred' => (int)Configuration::get('PAYPLUG_DEFERRED') === 1,
            'oney' => (int)Configuration::get('PAYPLUG_ONEY') === 1,
            'bancontact' => (int)Configuration::get('PAYPLUG_BANCONTACT') === 1,
        ];

        if (Configuration::get('PAYPLUG_EMAIL') === null
            || !AmountCurrencyClass::checkCurrency($cart)
            || !AmountCurrencyClass::checkAmount($cart)
        ) {
            $available_options['standard'] = false;
            $available_options['sandbox'] = false;
            $available_options['embedded'] = false;
            $available_options['one_click'] = false;
            $available_options['installment'] = false;
            $available_options['deferred'] = false;
            $available_options['oney'] = false;
            $available_options['bancontact'] = false;
        } else {
            if (!$permissions['use_live_mode']
                || Configuration::get('PAYPLUG_LIVE_API_KEY') === null
            ) {
                $available_options['live'] = false;
            }
            if (!$permissions['can_save_cards']) {
                $available_options['one_click'] = false;
            }
            if (!$permissions['can_create_installment_plan']) {
                $available_options['installment'] = false;
            }
            if (!$permissions['can_create_deferred_payment']) {
                $available_options['deferred'] = false;
            }
            if (!$permissions['can_use_oney']) {
                $available_options['oney'] = false;
            }
            if (!$permissions['can_use_bancontact']) {
                $available_options['bancontact'] = false;
            }
        }

        return $available_options;
    }

    /**
     * @description
     * Check if Payplug is allowed
     * @return bool
     */
    public static function isAllowed()
    {
        if (!Module::isEnabled('payplug') || !Configuration::get('PAYPLUG_SHOW')) {
            return false;
        }
        return true;
    }

    /**
     * Check various configurations
     *
     * @return string
     */
    public function getCheckFieldset()
    {
        $this->checkConfiguration();
        $this->html = '';

        $admin_ajax_url = AdminClass::getAdminAjaxUrl();

        $this->context->smarty->assign([
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => $this->version,
        ]);
        $this->html = $this->payplugClass->fetchTemplate('/views/templates/admin/panel/fieldset.tpl');

        return $this->html;
    }

    /**
     * @return bool
     */
    public function checkConfiguration()
    {
        $payplug_email = Configuration::get('PAYPLUG_EMAIL');
        $payplug_test_api_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        $payplug_live_api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');

        $report = self::checkRequirements();

        if (empty($payplug_email) || (empty($payplug_test_api_key) && empty($payplug_live_api_key))) {
            $is_payplug_connected = false;
        } else {
            $is_payplug_connected = true;
        }

        if ($report['curl']['installed'] &&
            $report['php']['up2date'] &&
            $report['openssl']['installed'] &&
            $report['openssl']['up2date'] &&
            $is_payplug_connected
        ) {
            $is_payplug_configured = true;
        } else {
            $is_payplug_configured = false;
        }

        $this->check_configuration = ['warning' => [], 'error' => [], 'success' => []];

        $curl_warning = $this->payplugClass->l('payplug.checkConfiguration.curlExtension', 'configclass');
        if ($report['curl']['installed']) {
            $this->check_configuration['success'][] .= $curl_warning;
        } else {
            $this->check_configuration['error'][] .= $curl_warning;
        }

        $php_warning = $this->payplugClass->l('payplug.checkConfiguration.phpVersion', 'configclass');
        if ($report['php']['up2date']) {
            $this->check_configuration['success'][] .= $php_warning;
        } else {
            $this->check_configuration['error'][] .= $php_warning;
        }

        $openssl_warning = $this->payplugClass->l('payplug.checkConfiguration.openssl', 'configclass');
        if ($report['openssl']['installed'] && $report['openssl']['up2date']) {
            $this->check_configuration['success'][] .= $openssl_warning;
        } else {
            $this->check_configuration['error'][] .= $openssl_warning;
        }

        $connexion_warning = $this->payplugClass->l('payplug.checkConfiguration.payplugAccount', 'configclass');
        if ($is_payplug_connected) {
            $this->check_configuration['success'][] .= $connexion_warning;
        } else {
            $this->check_configuration['error'][] .= $connexion_warning;
        }

        $check_warning = $this->payplugClass->l('payplug.checkConfiguration.issue', 'configclass');
        if ($is_payplug_configured) {
        } else {
            Configuration::get('PAYPLUG_SHOW', 0);
            $this->check_configuration['warning'][] .= $check_warning;
        }

        return true;
    }

    /**
     * Get iso code from language code
     * @param $language
     * @return string
     */
    public static function getIsoFromLanguageCode(Language $language)
    {
        if (!Validate::isLoadedObject($language)) {
            return false;
        }
        $parse = explode('-', $language->language_code);
        return Tools::strtolower($parse[0]);
    }

    /**
     * @description  validate custom_oney_max value
     * @param $payplug_oney
     * @param $amount
     * @param $oney_min
     * @param $oney_max
     * @return bool
     */
    public function validateCustomOneyMax($payplug_oney, $amount, $oney_min, $oney_max)
    {
        if ($payplug_oney === 1 && $amount != 0 && $amount > $oney_min / 100 && $amount <= $oney_max / 100) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @description validate custom_oney_min value
     * @param $payplug_oney
     * @param $amount
     * @param $oney_min
     * @param $oney_max
     * @return bool
     */
    public function validateCustomOneyMin($payplug_oney, $amount, $oney_min, $oney_max)
    {
        if ($payplug_oney === 1 && $amount != 0 && $amount >= $oney_min / 100 && $amount < $oney_max / 100) {
            return true;
        } else {
            return false;
        }
    }
    /**
     * save configuration
     *
     * @return void
     */
    public function saveConfiguration()
    {
        $limit_oney = $this->oney->getOneyPriceLimit(false);
        $configurationKeys = [

            'PAYPLUG_DEFERRED' => 'payplug_deferred',
            'PAYPLUG_DEFERRED_AUTO' => 'payplug_deferred_auto',
            'PAYPLUG_DEFERRED_STATE' => 'payplug_deferred_state',
            'PAYPLUG_SHOW' => 'PAYPLUG_SHOW',
            'PAYPLUG_EMBEDDED_MODE' => 'payplug_embedded',
            'PAYPLUG_INST' => 'payplug_inst',
            'PAYPLUG_INST_MIN_AMOUNT' => 'PAYPLUG_INST_MIN_AMOUNT',
            'PAYPLUG_INST_MODE' => 'PAYPLUG_INST_MODE',
            'PAYPLUG_ONE_CLICK' => 'payplug_one_click',
            'PAYPLUG_ONEY' => 'payplug_oney',
            'PAYPLUG_ONEY_OPTIMIZED' => 'payplug_oney_optimized',
            'PAYPLUG_ONEY_FEES' => 'payplug_oney_fees',
            'PAYPLUG_SANDBOX_MODE' => 'payplug_sandbox',
            'PAYPLUG_STANDARD' => 'payplug_standard',
            'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS' => 'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS',
            'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS' => 'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS',
            'PAYPLUG_BANCONTACT' => 'payplug_bancontact'
        ];

        foreach ($configurationKeys as $key => $config) {
            $value = Tools::getValue($config);


            if ($value != null) {
                switch ($config) {
                    case 'payplug_one_click':
                        if ((int)Tools::getValue('payplug_standard') === 1) {
                            Configuration::updateValue($key, $value);
                        }
                        break;
                    case 'payplug_oney_optimized':
                    case 'payplug_oney_fees':
                        if ((int)Tools::getValue('payplug_oney') === 1) {
                            Configuration::updateValue($key, $value);
                        }
                        break;
                    case 'PAYPLUG_INST_MIN_AMOUNT':
                    case 'PAYPLUG_INST_MODE':
                        if ((int)Tools::getValue('payplug_inst') === 1) {
                            Configuration::updateValue($key, $value);
                        }
                        break;
                    case 'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS':
                        if ($this->validateCustomOneyMax(
                            (int)Tools::getValue('payplug_oney'),
                            Tools::getValue('PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS'),
                            $limit_oney['min'],
                            $limit_oney['max']
                        )) {
                            Configuration::updateValue(
                                $key,
                                $this->oney->setCustomOneyLimit(
                                    (int)Tools::getValue('PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS')
                                )
                            );
                        }
                        break;
                    case 'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS':
                        if ($this->validateCustomOneyMin(
                            (int)Tools::getValue('payplug_oney'),
                            (int)Tools::getValue('PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS'),
                            $limit_oney['min'],
                            $limit_oney['max']
                        )) {
                            Configuration::updateValue(
                                $key,
                                $this->oney->setCustomOneyLimit(
                                    (int)Tools::getValue('PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS')
                                )
                            );
                        }

                        break;
                    case 'payplug_bancontact':
                        if ((int)Tools::getValue('payplug_sandbox') != 1) {
                            Configuration::updateValue($key, $value);
                        }
                        break;
                    default:
                        Configuration::updateValue($key, $value);
                }
            }
            if ($key == 'PAYPLUG_SHOW' && $value) {
                $this->payplugClass->enable();
            }
        }
    }

    public static function setNotification()
    {
        return new PayPlugNotifications();
    }

    public static function setValidation()
    {
        return new PayPlugValidation();
    }

    /**
     * @return string
     */
    public function assignContentVar()
    {
        if (Tools::getValue('uninstall_config')) {
            return $this->payplugClass->getUninstallContent();
        }

        $this->checkConfiguration();

        $configurations = [
            'show' => Configuration::get('PAYPLUG_SHOW'),
            'email' => Configuration::get('PAYPLUG_EMAIL'),
            'sandbox_mode' => Configuration::get('PAYPLUG_SANDBOX_MODE'),
            'embedded_mode' => Configuration::get('PAYPLUG_EMBEDDED_MODE'),
            'standard' => Configuration::get('PAYPLUG_STANDARD'),
            'one_click' => Configuration::get('PAYPLUG_ONE_CLICK'),
            'inst' => Configuration::get('PAYPLUG_INST'),
            'inst_mode' => Configuration::get('PAYPLUG_INST_MODE'),
            'inst_min_amount' => Configuration::get('PAYPLUG_INST_MIN_AMOUNT'),
            'test_api_key' => Configuration::get('PAYPLUG_TEST_API_KEY'),
            'live_api_key' => Configuration::get('PAYPLUG_LIVE_API_KEY'),
            'debug_mode' => Configuration::get('PAYPLUG_DEBUG_MODE'),
            'deferred' => Configuration::get('PAYPLUG_DEFERRED'),
            'deferred_auto' => Configuration::get('PAYPLUG_DEFERRED_AUTO'),
            'deferred_state' => Configuration::get('PAYPLUG_DEFERRED_STATE'),
            'oney' => Configuration::get('PAYPLUG_ONEY'),
            'oney_fees' => Configuration::get('PAYPLUG_ONEY_FEES'),
            'oney_optimized' => Configuration::get('PAYPLUG_ONEY_OPTIMIZED'),
            'bancontact' => Configuration::get('PAYPLUG_BANCONTACT')
        ];

        $connected = !empty($configurations['email'])
            && (!empty($configurations['test_api_key']) || !empty($configurations['live_api_key']));

        if (count($this->validationErrors) && !$connected) {
            $this->context->smarty->assign([
                'validationErrors' => $this->validationErrors,
            ]);
        }

        $api_class = $this->apiClass;
        $valid_key = $api_class::setAPIKey();
        if (!empty($valid_key)) {
            try {
                $permissions = $this->apiClass->getAccount($valid_key);
            } catch (ConfigurationNotSetException $e) {
//                @todo Add Log
                die('ConfigurationNotSetException'.$e->getMessage());
            } catch (ConfigurationException $e) {
//                @todo Add Log
                die('ConfigurationException'.$e->getMessage());
            }
            $premium = $permissions['can_save_cards'] && $permissions['can_create_installment_plan'];
        } else {
            $verified = false;
            $premium = false;
        }
        if (!empty($configurations['live_api_key'])) {
            $verified = true;
        } else {
            $verified = false;
        }

        $is_active = (bool)$configurations['show'];

        $this->apiClass->getSiteUrl();

        $p_error = '';
        if (!$connected) {
            if (isset($this->validationErrors['username_password'])) {
                $p_error .= $this->validationErrors['username_password'];
            } elseif (isset($this->validationErrors['login'])) {
                if (isset($this->validationErrors['username_password'])) {
                    $p_error .= ' ';
                }
                $p_error .= $this->validationErrors['login'];
            }
            $this->context->smarty->assign([
                'p_error' => $p_error,
            ]);
        } else {
            $this->context->smarty->assign([
                'PAYPLUG_EMAIL' => $configurations['email'],
            ]);
        }

        $this->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin.js');
        $this->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/utilities.js');
        $this->mediaClass->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin.css');

        $admin_ajax_url = AdminClass::getAdminAjaxUrl();

        // @todo : avoid addJsDef with translations (quotes are not escaped on 1.6 and break header)
        Media::addJsDef([
            'admin_ajax_url' => $admin_ajax_url,
            'error_installment' => $this->payplugClass->l('payplug.assignContentVar.installment', 'configclass'),
            'error_deferred' => $this->payplugClass->l('payplug.assignContentVar.deferred', 'configclass'),
            'error_oney' => $this->payplugClass->l('payplug.assignContentVar.oney', 'configclass'),
            'errorOneyMax' => addslashes($this->payplugClass->l('config.assignContentVar.oney.thresholdsMaxError', 'configclass')),
            'errorOneyMin' => addslashes($this->payplugClass->l('config.assignContentVar.oney.thresholdsMinError', 'configclass')),
        ]);

        $login_infos = [];

        $installments_panel_url = 'index.php?controller=AdminPayPlugInstallment';
        $installments_panel_url .= '&token=' . Tools::getAdminTokenLite('AdminPayPlugInstallment');

        $faq_links = $this->getFAQLinks($this->context->language->iso_code);
        $amounts = $this->oney->getOneyPriceLimit(false);
        $customAmounts = $this->oney->getOneyPriceLimit(true);
        $oney_min_amounts = ($amounts['min'] / 100);
        $oney_max_amounts = ($amounts['max'] / 100);
        $oney_custom_max_amounts = ($customAmounts['max']);
        $oney_custom_min_amounts = ($customAmounts['min']);

        if ((class_exists($this->payplugClass->PrestashopSpecificClass))
            && (method_exists($this->payplugClass->PrestashopSpecificObject, 'assignSwitchConfiguration'))
            && $this->payplugClass->isValidFeature('feature_integrated')
            && Configuration::get('PAYPLUG_PUBLISHABLE_KEY' . ($configurations['sandbox_mode'] ? '_TEST' : ''))
        ) {
            $this->payplugClass->PrestashopSpecificObject->assignSwitchConfiguration($configurations);
        } else {
            $this->assignSwitchConfiguration($configurations);
        }

        Media::addJsDef(
            [
                'errorOneyThresholds' => sprintf(
                    addslashes($this->payplugClass->l('config.assignContentVar.oney.thresholdsError', 'configclass')),
                    $oney_min_amounts,
                    $oney_max_amounts
                ),
                'oney_max_amounts' => $oney_max_amounts,
                'oney_min_amounts' => $oney_min_amounts,
            ]
        );

        $this->context->smarty->assign([
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => Module::getInstanceByName($this->payplugClass->name)->version,
            'connected' => $connected,
            'verified' => $verified,
            'premium' => $premium,
            'is_active' => $is_active,
            'site_url' => $this->apiClass->getSiteUrl(),
            'sandbox_mode' => $configurations['sandbox_mode'],
            'embedded_mode' => $configurations['embedded_mode'],
            'one_click' => $configurations['one_click'],
            'standard' => $configurations['standard'],
            'inst' => $configurations['inst'],
            'inst_mode' => $configurations['inst_mode'],
            'inst_min_amount' => $configurations['inst_min_amount'],
            'show' => $configurations['show'],
            'debug_mode' => $configurations['debug_mode'],
            'deferred' => $configurations['deferred'],
            'deferred_auto' => $configurations['deferred_auto'],
            'deferred_state' => $configurations['deferred_state'],
            'oney' => $configurations['oney'],
            'bancontact' => $this->payplugClass->isValidFeature('feature_bancontact'),
            'integrated' => $this->payplugClass->isValidFeature('feature_integrated'),
            'login_infos' => $login_infos,
            'installments_panel_url' => $installments_panel_url,
            'order_states' => $this->orderClass->getOrderStates(),
            'oney_min_amounts' => $oney_min_amounts,
            'oney_max_amounts' => $oney_max_amounts,
            'oney_custom_max_amounts' => $oney_custom_max_amounts ,
            'oney_custom_min_amounts' => $oney_custom_min_amounts  ,
            'faq_links' => $faq_links,
            'iso' => $this->context->language->iso_code,
        ]);

        return $this->html;
    }

    /**
     * Get FAQ link for given iso lang
     * @param $iso_code
     * @return array
     */
    public function getFAQLinks($iso_code)
    {
        if ($iso_code == 'en') {
            $iso_code = 'en-gb';
        }

        return [
            'activation' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021328991',
            'deferred' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360010088420',
            'install' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021389891',
            'installments' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022447972',
            'one_click' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022213892',
            'oney' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360013071080',
            'bancontact' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/4408157435794',
            'payment_page' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142312',
            'integrated_payment_page' => 'https://support.payplug.com/hc/'. $iso_code .'/articles/360021390191',
            'refund' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022214692',
            'sandbox' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142492',
            'guide' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360011715080',
            'support' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/4409698334098',
        ];
    }

    private function assignSwitchConfiguration($configurations)
    {
        $switch = [];

        // defined if user is connected
        $connected = !empty($configurations['email'])
            && (!empty($configurations['test_api_key'])
                || !empty($configurations['live_api_key']));

        // show module to the customer
        $switch['show'] = [
            'name' => 'PAYPLUG_SHOW',
            'label' => $this->payplugClass->l('payplug.assignSwitchConfiguration.showPayplug', 'configclass'),
            'active' => $connected,
            'small' => true,
            'checked' => $configurations['show'],
        ];

        $switch['sandbox'] = [
            'name' => 'payplug_sandbox',
            'active' => $connected,
            'checked' => $configurations['sandbox_mode'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.test', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.live', 'configclass'),
        ];

        $switch['embedded'] = [
            'name' => 'payplug_embedded',
            'active' => $connected,
            'checked' => $configurations['embedded_mode'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.popup', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.redirected', 'configclass'),
        ];

        $switch['one_click'] = [
            'name' => 'payplug_one_click',
            'active' => $connected,
            'checked' => $configurations['one_click'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['standard'] = [
            'name' => 'payplug_standard',
            'active' => $connected,
            'checked' => $configurations['standard'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['oney'] = [
            'name' => 'payplug_oney',
            'active' => $connected,
            'checked' => $configurations['oney'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['oney_optimized'] = [
            'name' => 'payplug_oney_optimized',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_optimized'],
        ];

        $switch['oney_fees'] = [
            'name' => 'payplug_oney_fees',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_fees'],
        ];

        $switch['bancontact'] = [
            'name' => 'payplug_bancontact',
            'active' => $connected,
            'checked' => $configurations['bancontact'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['installment'] = [
            'name' => 'payplug_inst',
            'active' => $connected,
            'checked' => $configurations['inst'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['deferred'] = [
            'name' => 'payplug_deferred',
            'active' => $connected,
            'checked' => $configurations['deferred'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['deferred_auto'] = [
            'name' => 'payplug_deferred_auto',
            'active' => $connected,
            'checked' => $configurations['deferred_auto'],
            'label_left' => $this->payplugClass->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->payplugClass->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $this->context->smarty->assign([
            'payplug_switch' => $switch
        ]);
    }

    /**
     * Check if current device used is mobile
     *
     * @return bool
     */
    public static function isMobiledevice()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        $reg1 = '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|';
        $reg1 .= 'iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|';
        $reg1 .= 'palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|';
        $reg1 .= 'up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i';

        $reg2 = '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|';
        $reg2 .= 'an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|';
        $reg2 .= 'br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|';
        $reg2 .= 'dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|';
        $reg2 .= 'ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|';
        $reg2 .= 'hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|';
        $reg2 .= 'iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|';
        $reg2 .= 'klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|';
        $reg2 .= 'ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|';
        $reg2 .= 'mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|';
        $reg2 .= 'ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|';
        $reg2 .= 'pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|';
        $reg2 .= 'qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|';
        $reg2 .= 'sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|';
        $reg2 .= 'sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|';
        $reg2 .= 'tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|';
        $reg2 .= 'vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|';
        $reg2 .= 'wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i';

        if (preg_match($reg1, $useragent) || preg_match($reg2, Tools::substr($useragent, 0, 4))) {
            return true;
        }
        return false;
    }

    /**
     * Check if given phone number is valid mobile phone number
     * @param string $phone_number
     * @param string $iso_code
     * @return bool
     * @throws libphonenumberlight\NumberParseException
     */
    public static function isValidMobilePhoneNumber($iso_code, $phone_number = false)
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return false;
        }

        try {
            $phone_util = libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);
            $is_mobile = $phone_util->getNumberType($parsed);
            return (bool)(in_array($is_mobile, [1, 2], true));
        } catch (libphonenumberlight\NumberParseException $e) {
            // @todo : Add Log
            return false;
        }
    }

//    /**
//     * @return string
//     */
//    public function displayGDPRConsent()
//    {
//        $this->context->smarty->assign(['id_module' => $this->id]);
//        return $this->payplugClass->fetchTemplate('customer/gdpr_consent.tpl');
//    }

    /**
     * Return international formatted phone number (norm E.164)
     *
     * @param $phone_number
     * @param $country
     * @return string|null
     * @throws libphonenumberlight\NumberParseException
     */
    public static function formatPhoneNumber($phone_number, $country)
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return null;
        }
        if (!is_object($country)) {
            $country = new Country($country);
        }
        if (!Validate::isLoadedObject($country)) {
            return null;
        }

        try {
            $iso_code = self::getIsoCodeByCountryId($country->id);

            if (!$iso_code) {
                return null;
            }

            $phone_util = \libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if (!$phone_util->isValidNumber($parsed)) {
                // todo: add log
                return null;
            }

            $formated = $phone_util->format($parsed, \libphonenumberlight\PhoneNumberFormat::E164);
            return $formated;
        } catch (libphonenumberlight\NumberParseException $e) {
            // todo: add log
            return null;
        }
    }

    /**
     * Get the right country iso-code or null if it does'nt fit the ISO 3166-1 alpha-2 norm
     *
     * @param int $country_id
     * @return int | false
     */
    public static function getIsoCodeByCountryId($country_id)
    {
        $iso_code_list = self::getIsoCodeList();
        if (!is_array($iso_code_list) || empty($iso_code_list) || !count($iso_code_list)) {
            return false;
        }
        if (!Validate::isInt($country_id)) {
            return false;
        }
        $country = new Country((int)$country_id);
        if (!Validate::isLoadedObject($country)) {
            return false;
        }
        if (!in_array(Tools::strtoupper($country->iso_code), $iso_code_list, true)) {
            return false;
        } else {
            return Tools::strtoupper($country->iso_code);
        }
    }

    /**
     * Get all country iso-code of ISO 3166-1 alpha-2 norm
     * Source: DB PayPlug
     *
     * @return array | null
     */
    public static function getIsoCodeList()
    {
        $country_list_path = _PS_MODULE_DIR_ . 'payplug/lib/iso_3166-1_alpha-2/data.csv';
        $iso_code_list = [];
        if (($handle = fopen($country_list_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $iso_code_list[] = Tools::strtoupper($data[0]);
            }
            fclose($handle);
            return $iso_code_list;
        } else {
            return null;
        }
    }

    /**
     * @return void
     * @see Module::postProcess()
     *
     */
    public function postProcess()
    {
        if (Tools::isSubmit('submitAccount')) {
            $this->submitAccount();
        }

        if (Tools::getValue('submitDisable')) {
            $this->submitDisable();
        }

        if (Tools::getValue('submitDisconnect')) {
            $this->submitDisconnect();
        }

        if (Tools::isSubmit('submitSettings')) {
            $this->submitSettings();
        }

        if (Tools::isSubmit('submitUninstallSettings')) {
            $this->submitUninstallSettings();
        }
    }

    /**
     * @param $id_customer
     * @return array|bool|null
     * @throws PrestaShopDatabaseException
     */
    public function gdprCardExport($id_customer)
    {
        if (!is_int($id_customer) || $id_customer === null) {
            return false;
        }
        $req_payplug_card = '
            SELECT pc.last4, pc.exp_month, pc.exp_year, pc.brand, pc.country
            FROM ' . _DB_PREFIX_ . 'payplug_card pc
            WHERE pc.id_customer = ' . (int)$id_customer;
        $res_payplug_card = Db::getInstance()->ExecuteS($req_payplug_card);
        if (!$res_payplug_card) {
            $cards = null;
        } else {
            $i = 1;
            $cards = [];
            foreach ($res_payplug_card as &$card) {
                $card['expiry_date'] = date(
                    'm / y',
                    mktime(0, 0, 0, (int)$card['exp_month'], 1, (int)$card['exp_year'])
                );
                $cards[] = [
                    '#' => $i,
                    $this->payplugClass->l('payplug.gdprCardExport.brand', 'configclass') => $card['brand'],
                    $this->payplugClass->l('payplug.gdprCardExport.country', 'configclass') => $card['country'],
                    $this->payplugClass->l(
                        'payplug.gdprCardExport.card',
                        'configclass'
                    ) => '**** **** **** ' . $card['last4'],
                    $this->payplugClass->l('payplug.gdprCardExport.expiryDate', 'configclass') => $card['expiry_date']
                ];
                $i++;
            }
        }
        return $cards;
    }

    /**
     * @description Check if current configuration requirements are respected
     * @return array
     */
    public static function checkRequirements()
    {
        $php_min_version = 50600;
        $curl_min_version = '7.21';
        $openssl_min_version = 0x1000100f;
        $report = [
            'php' => [
                'version' => 0,
                'installed' => true,
                'up2date' => false,
            ],
            'curl' => [
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ],
            'openssl' => [
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ],
        ];

        //PHP
        if (!defined('PHP_VERSION_ID')) {
            $report['php']['version'] = PHP_VERSION;
            $php_version = explode('.', PHP_VERSION);
            define('PHP_VERSION_ID', ($php_version[0] * 10000 + $php_version[1] * 100 + $php_version[2]));
        }
        $report['php']['up2date'] = PHP_VERSION_ID >= $php_min_version ? true : false;

        //cURL
        $curl_exists = extension_loaded('curl');
        if ($curl_exists) {
            $curl_version = curl_version();
            $report['curl']['version'] = $curl_version['version'];
            $report['curl']['installed'] = true;
            $report['curl']['up2date'] = version_compare(
                $curl_version['version'],
                $curl_min_version,
                '>='
            ) ? true : false;
        }

        //OpenSSl
        $openssl_exists = extension_loaded('openssl');
        if ($openssl_exists) {
            $report['openssl']['version'] = OPENSSL_VERSION_NUMBER;
            $report['openssl']['installed'] = true;
            $report['openssl']['up2date'] = OPENSSL_VERSION_NUMBER >= $openssl_min_version ? true : false;
        }

        return $report;
    }

    /**
     * @description Process account submit
     * @throws BadRequestException
     */
    public function submitAccount()
    {
        $curl_exists = extension_loaded('curl');
        $openssl_exists = extension_loaded('openssl');

        /*
         * We can't use $password = Tools::getValue('PAYPLUG_PASSWORD');
         * Because pwd with special chars don't work
         */
        $password = $_POST['PAYPLUG_PASSWORD'];
        $email = Tools::getValue('PAYPLUG_EMAIL');

        if (!Validate::isEmail($email) || !PayPlugBackward::isPlaintextPassword($password)) {
            die(json_encode([
                'content' => false,
                'error' => $this->payplugClass->l('payplug.submitAccount.credentialsNotCorrect', 'configclass')
            ]));
        } elseif ($curl_exists && $openssl_exists) {
            if ($this->apiClass->login($email, $password)) {
                Configuration::updateValue('PAYPLUG_EMAIL', Tools::getValue('PAYPLUG_EMAIL'));
                Configuration::updateValue('PAYPLUG_SHOW', 1);

                $this->assignContentVar();
                $content = $this->payplugClass->fetchTemplate('/views/templates/admin/admin.tpl');

                die(json_encode(['content' => $content]));
            } else {
                die(json_encode([
                    'content' => false,
                    'error' => $this->payplugClass->l('payplug.submitAccount.credentialsNotCorrect', 'configclass')
                ]));
            }
        }
    }

    /**
     * @description Process disable plugin submit
     */
    public function submitDisable()
    {
        Configuration::updateValue('PAYPLUG_SHOW', false);

        $this->assignContentVar();
        $content = $this->payplugClass->fetchTemplate('/views/templates/admin/admin.tpl');

        $this->context->smarty->assign([
            'title' => '',
            'type' => 'save',
        ]);
        $popin = $this->payplugClass->fetchTemplate('/views/templates/admin/popin.tpl');

        die(json_encode(['popin' => $popin, 'content' => $content]));
    }

    /**
     * @description Process disconnect submit
     */
    public function submitDisconnect()
    {
        $this->install->setConfig();
        Configuration::updateValue('PAYPLUG_SHOW', 0);

        // force reload configuration to be sure all config are reset
        Configuration::loadConfiguration();

        $this->assignContentVar();
        $content = $this->payplugClass->fetchTemplate('/views/templates/admin/admin.tpl');

        die(json_encode(['content' => $content]));
    }

    /**
     * @description Process settings submit
     */
    public function submitSettings()
    {
        if (Tools::getValue('PAYPLUG_INST_MIN_AMOUNT') < 4) {
            $this->payplugClass->displayError(
                $this->payplugClass->l('payplug.submitSettings.settingsNotUpdated', 'configclass')
            );
        } else {
            $this->saveConfiguration();
        }
    }

    /**
     * @description Process uninstall submit
     */
    public function submitUninstallSettings()
    {
        Configuration::updateValue('PAYPLUG_KEEP_CARDS', Tools::getValue('PAYPLUG_KEEP_CARDS'));
    }
}
