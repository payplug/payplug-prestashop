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

use libphonenumberlight;
use PayPlug\src\utilities\validators\oneyValidator;

class ConfigClass
{
    public $amountCurrencyClass;
    public $configurations;
    public $email;
    public $features_json;
    public $logger;
    public $myLogPHP;
    public $orderStates = [
        'paid' => [
            'cfg' => 'PS_OS_PAYMENT',
            'payplug_cfg' => [
                'ORDER_STATE_PAID',
                'ORDER_STATE_PAID_TEST',
            ],
            'template' => 'payment',
            'logable' => true,
            'send_email' => true,
            'paid' => true,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#04b404',
            'name' => [
                'en' => 'Payment accepted',
                'fr' => 'Paiement effectué',
                'es' => 'Pago efectuado',
                'it' => 'Pagamento effettuato',
            ],
        ],
        'refund' => [
            'cfg' => 'PS_OS_REFUND',
            'payplug_cfg' => [
                'ORDER_STATE_REFUND',
                'ORDER_STATE_REFUND_TEST',
            ],
            'template' => 'refund',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#ea3737',
            'name' => [
                'en' => 'Refunded',
                'fr' => 'Remboursé',
                'es' => 'Reembolsado',
                'it' => 'Rimborsato',
            ],
        ],
        'pending' => [
            'cfg' => 'PS_OS_PENDING',
            'payplug_cfg' => [
                'ORDER_STATE_PENDING',
                'ORDER_STATE_PENDING_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => true,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Payment in progress',
                'fr' => 'Paiement en cours',
                'es' => 'Pago en curso',
                'it' => 'Pagamento in corso',
            ],
        ],
        'error' => [
            'cfg' => 'PS_OS_ERROR',
            'payplug_cfg' => [
                'ORDER_STATE_ERROR',
                'ORDER_STATE_ERROR_TEST',
            ],
            'template' => 'payment_error',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#8f0621',
            'name' => [
                'en' => 'Payment failed',
                'fr' => 'Paiement échoué',
                'es' => 'Payment failed',
                'it' => 'Payment failed',
            ],
        ],
        'cancelled' => [
            'cfg' => 'PS_OS_CANCELED',
            'payplug_cfg' => [
                'ORDER_STATE_CANCELLED',
                'ORDER_STATE_CANCELLED_TEST',
            ],
            'template' => 'order_canceled',
            'logable' => false,
            'send_email' => true,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#2C3E50',
            'name' => [
                'en' => 'Payment cancelled',
                'fr' => 'Paiement annulé',
                'es' => 'Payment cancelled',
                'it' => 'Payment cancelled',
            ],
        ],
        'auth' => [
            'cfg' => null,
            'payplug_cfg' => [
                'ORDER_STATE_AUTH',
                'ORDER_STATE_AUTH_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => true,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#04b404',
            'name' => [
                'en' => 'Payment authorized',
                'fr' => 'Paiement autorisé',
                'es' => 'Pago',
                'it' => 'Pagamento',
            ],
        ],
        'exp' => [
            'cfg' => null,
            'payplug_cfg' => [
                'ORDER_STATE_EXP',
                'ORDER_STATE_EXP_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#8f0621',
            'name' => [
                'en' => 'Autorization expired',
                'es' => 'Autorización vencida',
                'fr' => 'Autorisation expirée',
                'it' => 'Autorizzazione scaduta',
            ],
        ],
    ];
    public $orderStatesOney = [
        'oney_pg' => [
            'cfg' => null,
            'payplug_cfg' => [
                'ORDER_STATE_ONEY_PG',
                'ORDER_STATE_ONEY_PG_TEST',
            ],
            'template' => null,
            'logable' => false,
            'send_email' => false,
            'paid' => false,
            'module_name' => 'payplug',
            'hidden' => false,
            'delivery' => false,
            'invoice' => false,
            'color' => '#a1f8a1',
            'name' => [
                'en' => 'Oney - Pending',
                'fr' => 'Oney - En attente',
                'es' => 'Oney - Pending',
                'it' => 'Oney - Pending',
            ],
        ],
    ];
    public $payplugLanguages = ['en', 'fr', 'es', 'it'];
    public $version;
    public $warning;

    private $api_live;
    private $api_test;
    private $check_configuration;
    private $config;
    private $constant;
    private $context;
    private $country;
    private $dependencies;
    private $html = '';
    private $img_lang;
    private $install;
    private $media;
    private $module;
    private $oney;
    private $payment_status;
    private $query;
    private $tools;
    private $validate;
    private $validationErrors = [];

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->install = $this->dependencies->getPlugin()->getInstall();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->country = $this->dependencies->getPlugin()->getCountry();
        $this->media = $this->dependencies->getPlugin()->getMedia();
        $this->module = $this->dependencies->getPlugin()->getModule();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();

        $this->setLoggers();
        $this->setConfigurationProperties();

        if (file_exists(dirname(__FILE__) . '/../features.json')) {
            $this->features_json = json_decode($this->tools->tool('file_get_contents', dirname(__FILE__) . '/../features.json'));
        } else {
            $this->features_json = [];
        }
    }

    public function getAdapterPrestaClasse()
    {
        return $this->dependencies->loadAdapterPresta();
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
     *
     * @return bool
     */
    public function disable()
    {
        return $this->config->updateValue($this->dependencies->getConfigurationKey('show'), 0);
    }

    /**
     * @description
     *
     * @param $cart
     *
     * @return array
     */
    public function getAvailableOptions($cart)
    {
        if (!$this->isAllowed()) {
            return false;
        }

        $permissions = $this->dependencies->apiClass->getAccountPermissions();

        $available_options = [
            'standard' => (bool) $this->config->get($this->dependencies->getConfigurationKey('standard')),
            'live' => !(bool) $this->config->get($this->dependencies->getConfigurationKey('sandboxMode')),
            'embedded' => (string) $this->config->get($this->dependencies->getConfigurationKey('embeddedMode')),
            'one_click' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oneClick')),
            'installment' => (bool) $this->config->get($this->dependencies->getConfigurationKey('inst')),
            'deferred' => (bool) $this->config->get($this->dependencies->getConfigurationKey('deferred')),
            'oney' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oney')),
            'bancontact' => (bool) $this->config->get($this->dependencies->getConfigurationKey('bancontact')),
            'applepay' => (bool) $this->config->get($this->dependencies->getConfigurationKey('applepay')),
            'amex' => (bool) $this->config->get($this->dependencies->getConfigurationKey('amex')),
        ];

        if ($this->config->get($this->dependencies->getConfigurationKey('email')) === null
            || !$this->dependencies->amountCurrencyClass->checkCurrency($cart)
            || !$this->dependencies->amountCurrencyClass->checkAmount($cart)
        ) {
            $available_options['standard'] = false;
            $available_options['sandbox'] = false;
            $available_options['embedded'] = false;
            $available_options['one_click'] = false;
            $available_options['installment'] = false;
            $available_options['deferred'] = false;
            $available_options['oney'] = false;
            $available_options['bancontact'] = false;
            $available_options['applepay'] = false;
            $available_options['amex'] = false;
        } else {
            if (!$permissions['use_live_mode']
                || $this->config->get($this->dependencies->getConfigurationKey('liveApiKey')) === null
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
            if (!$permissions['can_use_applepay'] || !$available_options['live']) {
                $available_options['applepay'] = false;
            }
            if (!$permissions['can_use_amex'] || !$available_options['live']) {
                $available_options['amex'] = false;
            }
        }

        return $available_options;
    }

    /**
     * @description
     * Check if Payplug is allowed
     *
     * @return bool
     */
    public function isAllowed()
    {
        if (!$this->module->isEnabled($this->dependencies->name)
            || !$this->config->get($this->dependencies->getConfigurationKey('show'))) {
            return false;
        }

        return true;
    }

    /**
     * Check various configurations
     *
     * @todo remove this function which is not used anymore in new BO
     *
     * @return string
     */
    public function getCheckFieldset()
    {
        $this->checkConfiguration();
        $this->html = '';

        $admin_ajax_url = $this->dependencies->adminClass->getAdminAjaxUrl();

        $this->context->smarty->assign([
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => $this->version,
        ]);
        $this->html = $this->fetchTemplate('/views/templates/admin/panel/fieldset.tpl');

        return $this->html;
    }

    /**
     * @return bool
     */
    public function checkConfiguration()
    {
        $payplug_email = $this->config->get($this->dependencies->getConfigurationKey('email'));
        $payplug_test_api_key = $this->config->get($this->dependencies->getConfigurationKey('testApiKey'));
        $payplug_live_api_key = $this->config->get($this->dependencies->getConfigurationKey('liveApiKey'));

        $report = $this->checkRequirements();

        if (empty($payplug_email) || (empty($payplug_test_api_key) && empty($payplug_live_api_key))) {
            $is_payplug_connected = false;
        } else {
            $is_payplug_connected = true;
        }

        $psAccountConnected = $this->checkPsAccount();
        if ($is_payplug_connected && !$psAccountConnected) {
            $is_payplug_connected = false;
        }

        if ($report['curl']['installed']
            && $report['php']['up2date']
            && $report['openssl']['installed']
            && $report['openssl']['up2date']
            && $is_payplug_connected
        ) {
            $is_payplug_configured = true;
        } else {
            $is_payplug_configured = false;
        }

        $this->check_configuration = ['status' => []];

        if ($report['curl']['installed']) {
            $this->check_configuration['status']['curl'] = 'check';
        } else {
            $this->check_configuration['status']['curl'] = 'close';
        }

        if ($report['php']['up2date']) {
            $this->check_configuration['status']['php'] = 'check';
        } else {
            $this->check_configuration['status']['php'] = 'close';
        }

        if ($report['openssl']['installed'] && $report['openssl']['up2date']) {
            $this->check_configuration['status']['ssl'] = 'check';
        } else {
            $this->check_configuration['status']['ssl'] = 'close';
        }

        if ($is_payplug_configured) {
        } else {
            $this->config->get($this->dependencies->getConfigurationKey('show'), 0);
            $this->check_configuration['status']['check'] = 'check';
        }

        return true;
    }

    /**
     * @return bool
     */
    public function checkState()
    {
        $report = $this->checkRequirements();

        if ($report['curl']['installed']
            && $report['php']['up2date']
            && $report['openssl']['installed']
            && $report['openssl']['up2date']
        ) {
            return true;
        }

        return false;
    }

    /**
     * @description check if account
     * is linked to Psaccount
     *
     * @return bool
     */
    public function checkPsAccount()
    {
        if ($this->dependencies->name == 'pspaylater') {
            try {
                $module = $this->module->getInstanceByName($this->dependencies->name);
                $accountsFacade = $module->getService('ps_accounts.facade');
                $accountsService = $accountsFacade->getPsAccountsService();

                return $accountsService->isAccountLinked();
            } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleNotInstalledException $e) {
                return false;
            } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleVersionException $e) {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * Get iso code from language code
     *
     * @param $language
     *
     * @return string
     */
    public function getIsoFromLanguageCode($language)
    {
        if (!$this->validate->validate('isLoadedObject', $language)) {
            return false;
        }
        $parse = explode('-', $language->language_code);

        return $this->tools->tool('strtolower', $parse[0]);
    }

    /**
     * @description  validate custom_oney_max value
     *
     * @param $payplug_oney
     * @param $amount
     * @param $oney_min
     * @param $oney_max
     *
     * @return bool
     */
    public function validateCustomOneyMax($payplug_oney, $amount, $oney_min, $oney_max)
    {
        if ($payplug_oney === 1 && $amount != 0 && $amount > $oney_min / 100 && $amount <= $oney_max / 100) {
            return true;
        }

        return false;
    }

    /**
     * @description validate custom_oney_min value
     *
     * @param $payplug_oney
     * @param $amount
     * @param $oney_min
     * @param $oney_max
     *
     * @return bool
     */
    public function validateCustomOneyMin($payplug_oney, $amount, $oney_min, $oney_max)
    {
        if ($payplug_oney === 1 && $amount != 0 && $amount >= $oney_min / 100 && $amount < $oney_max / 100) {
            return true;
        }

        return false;
    }

    /**
     * save configuration
     */
    public function saveConfiguration()
    {
        $limit_oney = $this->oney->getOneyPriceLimit(false);
        $configurationKeys = [
            $this->dependencies->getConfigurationKey('deferred') => 'payplug_deferred',
            $this->dependencies->getConfigurationKey('deferredState') => 'payplug_deferred_state',
            $this->dependencies->getConfigurationKey('show') => 'payplug_show',
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
            $value = $this->tools->tool('getValue', $config);

            if ($value != null) {
                switch ($config) {
                    case 'payplug_one_click':
                        if ((int) $this->tools->tool('getValue', 'payplug_standard') === 1) {
                            $this->config->updateValue($key, $value);
                        }

                        break;

                    case 'payplug_oney_optimized':
                    case 'payplug_oney_product_cta':
                    case 'payplug_oney_cart_cta':
                    case 'payplug_oney_fees':
                        if ((int) $this->tools->tool('getValue', 'payplug_oney') === 1) {
                            $this->config->updateValue($key, $value);
                        }

                        break;

                    case 'payplug_inst_min_amount':
                    case 'payplug_inst_mode':
                        if ((int) $this->tools->tool('getValue', 'payplug_inst') === 1) {
                            if (((int) $this->tools->tool('getValue', 'payplug_inst_min_amount') >= 4)
                                && ((int) $this->tools->tool('getValue', 'payplug_inst_mode') < 5)
                            && ((int) $this->tools->tool('getValue', 'payplug_inst_mode') > 1)) {
                                $this->config->updateValue($key, $value);
                            }
                        }

                        break;

                    case 'payplug_oney_custom_max_amounts':
                        if ($this->validateCustomOneyMax(
                            (int) $this->tools->tool('getValue', 'payplug_oney'),
                            $this->tools->tool('getValue', $config),
                            $limit_oney['min'],
                            $limit_oney['max']
                        )) {
                            $this->config->updateValue(
                                $key,
                                $this->oney->setCustomOneyLimit(
                                    (int) $this->tools->tool('getValue', $config)
                                )
                            );
                        }

                        break;

                    case 'payplug_oney_custom_min_amounts':
                        if ($this->validateCustomOneyMin(
                            (int) $this->tools->tool('getValue', 'payplug_oney'),
                            (int) $this->tools->tool('getValue', $config),
                            $limit_oney['min'],
                            $limit_oney['max']
                        )) {
                            $this->config->updateValue(
                                $key,
                                $this->oney->setCustomOneyLimit(
                                    (int) $this->tools->tool('getValue', $config)
                                )
                            );
                        }

                        break;

                    case 'payplug_bancontact':
                    case 'payplug_bancontact_country':
                        if ((int) $this->tools->tool('getValue', 'payplug_sandbox') != 1) {
                            $this->config->updateValue($key, $value);
                        }

                        break;

                    default:
                        $this->config->updateValue($key, $value);
                }
            }
            if ($key == 'payplug_show' && $value) {
                $this->module->getInstanceByName($this->dependencies->name)->enable();
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
        if ($this->tools->tool('getValue', 'uninstall_config')) {
            return $this->getUninstallContent();
        }

        $this->checkConfiguration();
        $this->configurations = [
            'show' => $this->config->get($this->dependencies->getConfigurationKey('show')),
            'email' => $this->config->get($this->dependencies->getConfigurationKey('email')),
            'sandbox_mode' => $this->config->get($this->dependencies->getConfigurationKey('sandboxMode')),
            'embedded_mode' => $this->config->get($this->dependencies->getConfigurationKey('embeddedMode')),
            'standard' => $this->config->get($this->dependencies->getConfigurationKey('standard')),
            'one_click' => $this->config->get($this->dependencies->getConfigurationKey('oneClick')),
            'inst' => $this->config->get($this->dependencies->getConfigurationKey('inst')),
            'inst_mode' => $this->config->get($this->dependencies->getConfigurationKey('instMode')),
            'inst_min_amount' => $this->config->get($this->dependencies->getConfigurationKey('instMinAmount')),
            'test_api_key' => $this->config->get($this->dependencies->getConfigurationKey('testApiKey')),
            'live_api_key' => $this->config->get($this->dependencies->getConfigurationKey('liveApiKey')),
            'debug_mode' => $this->config->get($this->dependencies->getConfigurationKey('debugMode')),
            'deferred' => $this->config->get($this->dependencies->getConfigurationKey('deferred')),
            'deferred_state' => $this->config->get($this->dependencies->getConfigurationKey('deferredState')),
            'oney' => $this->config->get($this->dependencies->getConfigurationKey('oney')),
            'oney_fees' => $this->config->get($this->dependencies->getConfigurationKey('oneyFees')),
            'oney_optimized' => $this->config->get($this->dependencies->getConfigurationKey('oneyOptimized')),
            'oney_product_cta' => $this->config->get($this->dependencies->getConfigurationKey('oneyProductCta')),
            'oney_cart_cta' => $this->config->get($this->dependencies->getConfigurationKey('oneyCartCta')),
            'bancontact' => $this->config->get($this->dependencies->getConfigurationKey('bancontact')),
            'bancontact_country' => $this->config->get($this->dependencies->getConfigurationKey('bancontactCountry')),
            'applepay' => $this->config->get($this->dependencies->getConfigurationKey('applepay')),
        ];

        $connected = !empty($this->configurations['email'])
            && (!empty($this->configurations['test_api_key']) || !empty($this->configurations['live_api_key']));

        $psAccountConnected = $this->checkPsAccount();
        if ($connected && !$psAccountConnected) {
            $this->logout();
            $connected = false;
        }

        if (count($this->validationErrors) && !$connected) {
            $this->context->smarty->assign([
                'validationErrors' => $this->validationErrors,
            ]);
        }

        $api_class = $this->dependencies->apiClass;
        $valid_key = $api_class->setAPIKey();
        if (!empty($valid_key)) {
            $permissions = $this->dependencies->apiClass->getAccount($valid_key);
            if (!$permissions) {
                exit('An error occured while getting account');
            }
            $premium = $permissions['can_save_cards'] && $permissions['can_create_installment_plan'];
        } else {
            $premium = false;
        }
        if (!empty($this->configurations['live_api_key'])) {
            $permissions = $this->dependencies->apiClass->getAccount($this->configurations['live_api_key']);
            $verified = !empty($permissions) ? $permissions['is_live'] : false;
        } else {
            $verified = false;
        }
        $is_active = (bool) $this->configurations['show'];

        $this->dependencies->apiClass->getSiteUrl();

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
                'payplug_email' => $this->configurations['email'],
            ]);
        }

        $admin_ajax_url = $this->dependencies->adminClass->getAdminAjaxUrl();

        // @todo : avoid addJsDef with translations (quotes are not escaped on 1.6 and break header)
        $this->media->addJsDef([
            'admin_ajax_url' => $admin_ajax_url,
            'error_installment' => $this->dependencies->l('payplug.assignContentVar.installment', 'configclass'),
            'error_deferred' => $this->dependencies->l('payplug.assignContentVar.deferred', 'configclass'),
            'error_oney' => $this->dependencies->l('payplug.assignContentVar.oney', 'configclass'),
            'errorOneyMax' => addslashes($this->dependencies->l('config.assignContentVar.oney.thresholdsMaxError', 'configclass')),
            'errorOneyMin' => addslashes($this->dependencies->l('config.assignContentVar.oney.thresholdsMinError', 'configclass')),
        ]);

        $login_infos = [];

        $installments_panel_url = 'index.php?controller=AdminPayPlugInstallment';
        $installments_panel_url .= '&token=' . $this->tools->tool('getAdminTokenLite', 'AdminPayPlugInstallment');

        $this->configurations['installments_panel_url'] = $installments_panel_url;

        $this->configurations['faq_links'] = $this->getFAQLinks($this->context->language->iso_code);
        $amounts = $this->oney->getOneyPriceLimit(false);
        $customAmounts = $this->oney->getOneyPriceLimit(true);
        $oney_min_amounts = ($amounts['min'] / 100);
        $oney_max_amounts = ($amounts['max'] / 100);
        $oney_custom_max_amounts = ($customAmounts['max']);
        $oney_custom_min_amounts = ($customAmounts['min']);

        $adapter = $this->dependencies->loadAdapterPresta();
        if ($adapter
            && (method_exists($adapter, 'assignSwitchConfiguration'))
            && $this->isValidFeature('feature_integrated')
            && $this->isValidFeature('feature_standard')
            && $this->config->get(
                $this->dependencies->getConfigurationKey('publishableKey')
                . ($this->configurations['sandbox_mode'] ? '_TEST' : '')
            )
        ) {
            $adapter->assignSwitchConfiguration($this->configurations);
        } else {
            $this->assignSwitchConfiguration($this->configurations);
        }

        $this->media->addJsDef(
            [
                'errorOneyThresholds' => sprintf(
                    $this->dependencies->l('config.assignContentVar.oney.thresholdsError', 'configclass'),
                    $oney_min_amounts,
                    $oney_max_amounts
                ),
                'oney_max_amounts' => $oney_max_amounts,
                'oney_min_amounts' => $oney_min_amounts,
                'errorInstallmentAmount' => $this->dependencies->l('config.assignContentVar.installment.amountError', 'configclass'),
                'inst_min_amount' => $this->config->get($this->dependencies->getConfigurationKey('instMinAmount')),
            ]
        );
        $oneyValidator = new OneyValidator();

        $oney_belgium = $this->isValidFeature('feature_belgium_oney') && $oneyValidator->isOneyAllowedCountry($this->config->get(
            $this->dependencies->getConfigurationKey(
                'oneyAllowedCountries'
            )
        ), 'BE')['result'];

        $oney_spain = $this->isValidFeature('feature_spain_oney') && $oneyValidator->isOneyAllowedCountry($this->config->get(
            $this->dependencies->getConfigurationKey(
                'oneyAllowedCountries'
            )
        ), 'ES')['result'];
        // check if oney CTA should be displayed in the BO
        if ($oney_belgium || $oney_spain) {
            $this->config->updateValue(
                $this->dependencies->getConfigurationKey('oneyProductCta'),
                0
            );
            $this->config->updateValue(
                $this->dependencies->getConfigurationKey('oneyCartCta'),
                0
            );
        }
        $this->configurations['oney_product_cta'] = $this->config->get($this->dependencies->getConfigurationKey('oneyProductCta'));
        $this->configurations['oney_cart_cta'] = $this->config->get($this->dependencies->getConfigurationKey('oneyCartCta'));

        $this->context->smarty->assign([
            'form_action' => (string) ($_SERVER['REQUEST_URI']),
            'url_logo' => $this->constant->get('__PS_BASE_URI__')
                . 'modules/' . $this->dependencies->name . '/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => $this->dependencies->version,
            'connected' => $connected,
            'ps_account' => $psAccountConnected,
            'verified' => $verified,
            'premium' => $premium,
            'is_active' => $is_active,
            'site_url' => $this->dependencies->apiClass->getSiteUrl(),
            'portal_url' => $this->dependencies->apiClass->getPortalUrl(),
            'sandbox_mode' => $this->configurations['sandbox_mode'],
            'embedded_mode' => $this->configurations['embedded_mode'],
            'one_click' => $this->configurations['one_click'],
            'standard' => $this->configurations['standard'],
            'inst' => $this->configurations['inst'],
            'inst_min_amount' => $this->configurations['inst_min_amount'],
            'show' => $this->configurations['show'],
            'debug_mode' => $this->configurations['debug_mode'],
            'deferred' => $this->configurations['deferred'],
            'deferred_state' => $this->configurations['deferred_state'],
            'oney' => $this->configurations['oney'],
            'bancontact' => $this->isValidFeature('feature_bancontact'),
            'applepay' => $this->isValidFeature('feature_applepay'),
            'paylater_isActivated' => $this->isValidFeature('feature_oney'),
            'integrated' => $this->isValidFeature('feature_integrated'),
            'display_mode_isActivated' => $this->isValidFeature('feature_display_mode'),
            'standard_isActivated' => $this->isValidFeature('feature_standard'),
            'installment_isActivated' => $this->isValidFeature('feature_installment'),
            'deferred_isActivated' => $this->isValidFeature('feature_deferred'),
            'ps_account_isActivated' => $this->isValidFeature('feature_ps_account'),
            'login_infos' => $login_infos,
            'order_states' => $this->dependencies->orderClass->getOrderStates(),
            'oney_min_amounts' => $oney_min_amounts,
            'oney_max_amounts' => $oney_max_amounts,
            'oney_custom_max_amounts' => $oney_custom_max_amounts,
            'oney_custom_min_amounts' => $oney_custom_min_amounts,
            'iso' => $this->context->language->iso_code,
            'oney_belgium' => $oney_belgium,
            'oney_spain' => $oney_spain,
            'onboardingOneyCompleted' => $this->isOnboardingOneyCompleted(),
            'paymentMethods' => $this->dependencies->paymentClass->getPaymentMethods(),
            'onBoardingCheck' => false,
        ]);

        return $this->html;
    }

    /**
     * Get live permissions
     *
     * @return array
     */
    public function getLivePermissions()
    {
        $live_api_key = $this->config->get($this->dependencies->getConfigurationKey('liveApiKey'));
        $livepermissions = $this->dependencies->apiClass->getAccount($live_api_key);

        return $livepermissions ? $livepermissions : [];
    }

    /**
     * Is onboarding OneyCompleted
     *
     * @return bool
     */
    public function isOnboardingOneyCompleted()
    {
        $onboardingOneyCompleted = false;
        $livepermissions = $this->getLivePermissions();
        if ($livepermissions != [] && !empty($livepermissions['onboardingOneyCompleted'])) {
            $onboardingOneyCompleted = (bool) $livepermissions['onboardingOneyCompleted'];
        }

        return $onboardingOneyCompleted;
    }

    /**
     * Get FAQ link for given iso lang
     *
     * @param $iso_code
     *
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
            'integrated_payment_page' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021390191',
            'refund' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022214692',
            'sandbox' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142492',
            'guide' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360011715080',
            'support' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/4409698334098',
            'applepay' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/5149384347292',
            'amex' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/5701208563996',
        ];
    }

    /**
     * Check if current device used is mobile
     *
     * @return bool
     */
    public function isMobiledevice()
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

        if (preg_match($reg1, $useragent) || preg_match($reg2, $this->tools->substr($useragent, 0, 4))) {
            return true;
        }

        return false;
    }

    /**
     * Check if given phone number is valid mobile phone number
     *
     * @param string $phone_number
     * @param string $iso_code
     *
     * @throws libphonenumberlight\NumberParseException
     *
     * @return bool
     */
    public function isValidMobilePhoneNumber($iso_code, $phone_number = false)
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return false;
        }

        try {
            $phone_util = libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if ($phone_util->getRegionCodeForCountryCode($parsed->getCountryCode()) != $iso_code) {
                return false;
            }

            $is_mobile = $phone_util->getNumberType($parsed);

            return (bool) (in_array($is_mobile, [1, 2], true));
        } catch (libphonenumberlight\NumberParseException $e) {
            // @todo : Add Log
            return false;
        }
    }

    /**
     * Return international formatted phone number (norm E.164)
     *
     * @param $phone_number
     * @param $country
     *
     * @throws libphonenumberlight\NumberParseException
     *
     * @return null|string
     */
    public function formatPhoneNumber($phone_number, $country)
    {
        if (empty($phone_number) || !preg_match('/^[+0-9. ()\/-]{6,}$/', $phone_number)) {
            return null;
        }
        if (!is_object($country)) {
            $country = $this->country->get((int) $country);
        }
        if (!$this->validate->validate('isLoadedObject', $country)) {
            return null;
        }

        try {
            $iso_code = $this->getIsoCodeByCountryId($country->id);

            if (!$iso_code) {
                return null;
            }

            $phone_util = \libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if (!$phone_util->isValidNumber($parsed)) {
                // todo: add log
                return null;
            }

            return $phone_util->format($parsed, \libphonenumberlight\PhoneNumberFormat::E164);
        } catch (libphonenumberlight\NumberParseException $e) {
            // todo: add log
            return null;
        }
    }

    /**
     * Get the right country iso-code or null if it does'nt fit the ISO 3166-1 alpha-2 norm
     *
     * @param int $country_id
     *
     * @return int | false
     */
    public function getIsoCodeByCountryId($country_id)
    {
        $iso_code_list = $this->getIsoCodeList();
        if (!is_array($iso_code_list) || empty($iso_code_list) || !count($iso_code_list)) {
            return false;
        }
        if (!$this->validate->validate('isInt', $country_id)) {
            return false;
        }
        $country = $this->country->get((int) $country_id);
        if (!$this->validate->validate('isLoadedObject', $country)) {
            return false;
        }
        if (!in_array($this->tools->tool('strtoupper', $country->iso_code), $iso_code_list, true)) {
            return false;
        }

        return $this->tools->tool('strtoupper', $country->iso_code);
    }

    /**
     * Get all country iso-code of ISO 3166-1 alpha-2 norm
     * Source: DB PayPlug
     *
     * @return array | null
     */
    public function getIsoCodeList()
    {
        $country_list_path = _PS_MODULE_DIR_ . $this->dependencies->name . '/lib/iso_3166-1_alpha-2/data.csv';
        $iso_code_list = [];
        if (($handle = fopen($country_list_path, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $iso_code_list[] = $this->tools->tool('strtoupper', $data[0]);
            }
            fclose($handle);

            return $iso_code_list;
        }

        return null;
    }

    public function postProcess()
    {
        if ($this->tools->tool('isSubmit', 'submitAccount')) {
            $this->submitAccount();
        }
        if ($this->tools->tool('isSubmit', 'submitSandbox')) {
            $this->submitSandbox();
        }

        if ($this->tools->tool('getValue', 'submitDisable')) {
            $this->submitDisable();
        }

        if ($this->tools->tool('getValue', 'submitDisconnect')) {
            $this->submitDisconnect();
        }

        if ($this->tools->tool('isSubmit', 'submitSettings')) {
            $this->submitSettings();
        }

        if ($this->tools->tool('isSubmit', 'submitUninstallSettings')) {
            $this->submitUninstallSettings();
        }
    }

    /**
     * @param $id_customer
     *
     * @throws PrestaShopDatabaseException
     *
     * @return null|array|bool
     */
    public function gdprCardExport($id_customer)
    {
        if (!is_int($id_customer) || $id_customer === null) {
            return false;
        }

        $res_payplug_card = $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_card')
            ->where('id_customer = ' . (int) $id_customer)
            ->build()
        ;

        if (!$res_payplug_card) {
            $cards = null;
        } else {
            $i = 1;
            $cards = [];
            foreach ($res_payplug_card as &$card) {
                $card['expiry_date'] = date(
                    'm / y',
                    mktime(0, 0, 0, (int) $card['exp_month'], 1, (int) $card['exp_year'])
                );
                $cards[] = [
                    '#' => $i,
                    $this->dependencies->l('payplug.gdprCardExport.brand', 'configclass') => $card['brand'],
                    $this->dependencies->l('payplug.gdprCardExport.country', 'configclass') => $card['country'],
                    $this->dependencies->l('payplug.gdprCardExport.card', 'configclass') => '**** **** **** ' . $card['last4'],
                    $this->dependencies->l('payplug.gdprCardExport.expiryDate', 'configclass') => $card['expiry_date'],
                ];
                ++$i;
            }
        }

        return $cards;
    }

    /**
     * @description Check if current configuration requirements are respected
     *
     * @return array
     */
    public function checkRequirements()
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
     */
    public function submitAccount()
    {
        $curl_exists = extension_loaded('curl');
        $openssl_exists = extension_loaded('openssl');

        /*
         * We can't use $password = $this->tools->tool('getValue', 'payplug_password');
         * Because pwd with special chars don't work
         */
        $password = $_POST['payplug_password'];
        $email = $this->tools->tool('getValue', 'payplug_email');

        if (!$this->validate->validate('isEmail', $email)
            || !$this->getAdapterPrestaClasse()->isPlaintextPassword($password)) {
            $errorMessage = $this->dependencies->l('payplug.submitAccount.credentialsNotCorrect', 'configclass');
            $this->context->smarty->assign([
                'errorMessage' => $errorMessage,
                'errorData' => 'popinConnexionFailed',
            ]);

            exit(json_encode([
                'content' => false,
                'modal' => $this->fetchTemplate('/views/templates/api/molecules/modal/error.tpl'),
                'error' => $errorMessage,
            ]));
        }
        if ($curl_exists && $openssl_exists) {
            if ($this->dependencies->apiClass->login($email, $password)) {
                $this->config->updateValue(
                    $this->dependencies->getConfigurationKey('email'),
                    $this->tools->tool('getValue', 'payplug_email')
                );
                $this->config->updateValue($this->dependencies->getConfigurationKey('show'), 1);
                $this->assignContentVar();
                $content = $this->fetchTemplate('/views/templates/admin/admin.tpl');

                exit(json_encode(['content' => $content]));
            }
            $errorMessage = $this->dependencies->l('payplug.submitAccount.credentialsNotCorrect', 'configclass');
            $this->context->smarty->assign([
                'errorData' => 'popinConnexionFailed',
                'errorMessage' => $errorMessage,
            ]);

            exit(json_encode([
                'content' => false,
                'modal' => $this->fetchTemplate('/views/templates/api/molecules/modal/error.tpl'),
                'error' => $errorMessage,
            ]));
        }
    }

    /**
     * @description Process password submit to access Live mode
     */
    public function submitSandbox()
    {
        $curl_exists = extension_loaded('curl');
        $openssl_exists = extension_loaded('openssl');

        /*
         * We can't use $password = $this->tools->tool('getValue', 'payplug_password');
         * Because pwd with special chars don't work
         */
        $password = $_POST['payplug_password'];
        $email = $this->config->get($this->dependencies->getConfigurationKey('email'));

        if (!$this->getAdapterPrestaClasse()->isPlaintextPassword($password)) {
            $errorMessage = $this->dependencies->l('payplug.submitSandbox.passwordError', 'configclass');
            $this->context->smarty->assign([
                'errorMessage' => $errorMessage,
                'errorClass' => '-error',
            ]);

            exit(json_encode([
                'content' => false,
                'modal' => $this->fetchTemplate('/views/templates/api/molecules/modal/sandbox.tpl'),
            ]));
        }
        if ($curl_exists && $openssl_exists) {
            if ($this->dependencies->apiClass->login($email, $password)) {
                if ($this->isOnboardingOneyCompleted()) {
                    $this->config->updateValue($this->dependencies->getConfigurationKey('sandboxMode'), 0);
                }
                $this->assignContentVar();
                $this->context->smarty->assign([
                    'onBoardingCheck' => true,
                ]);
                $content = $this->fetchTemplate('/views/templates/admin/admin.tpl');
                // On recharge l'admin avec le message de réussite ou d'erreur dans smarty
                exit(json_encode(['content' => $content]));
            }
            $errorMessage = $this->dependencies->l('payplug.submitSandbox.passwordError', 'configclass');
            $this->context->smarty->assign([
                'errorMessage' => $errorMessage,
                'errorClass' => '-error',
            ]);

            exit(json_encode([
                'content' => false,
                'modal' => $this->fetchTemplate('/views/templates/api/molecules/modal/sandbox.tpl'),
            ]));
        }
    }

    /**
     * @description Process check onBoarding is finished
     */
    public function checkOnboarding()
    {
        exit(json_encode([
            'content' => false,
            'modal' => $this->fetchTemplate('/views/templates/api/molecules/modal/sandbox.tpl'),
        ]));
    }

    /**
     * @description Process disable plugin submit
     */
    public function submitDisable()
    {
        $this->config->updateValue($this->dependencies->getConfigurationKey('show'), false);

        $this->assignContentVar();
        $content = $this->fetchTemplate('/views/templates/admin/admin.tpl');

        $this->context->smarty->assign([
            'title' => '',
            'type' => 'save',
        ]);
        $popin = $this->fetchTemplate('/views/templates/admin/popin.tpl');

        exit(json_encode(['popin' => $popin, 'content' => $content]));
    }

    /**
     * @description Process disconnect submit
     */
    public function submitDisconnect()
    {
        $this->logout();

        $this->assignContentVar();
        $content = $this->fetchTemplate('/views/templates/admin/admin.tpl');

        exit(json_encode(['content' => $content]));
    }

    /**
     * @description Process settings submit
     */
    public function submitSettings()
    {
        if ($this->tools->tool('getValue', $this->dependencies->getConfigurationKey('instMinAmount')) < 4) {
            $this->module->getInstanceByName($this->dependencies->name)->displayError(
                $this->dependencies->l('payplug.submitSettings.settingsNotUpdated', 'configclass')
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
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('keepCards'),
            $this->tools->tool('getValue', $this->dependencies->getConfigurationKey('keepCards'))
        );
    }

    public function isValidFeature($name)
    {
        if (empty($this->features_json)) {
            return false;
        }

        foreach ($this->features_json->features as $feature) {
            if ($feature == $name) {
                return true;
            }
        }

        return false;
    }

    public function fetchTemplate($file)
    {
        if ($this->context->smarty->tpl_vars) {
            foreach (array_keys($this->context->smarty->tpl_vars) as $key) {
                if (strpos($key, 'feature_') !== false && !$this->isValidFeature($key)) {
                    unset($this->context->smarty->tpl_vars[$key]);
                }
            }
        }

        $this->context->smarty->assign([
            'module_name' => $this->dependencies->name,
        ]);

        return $this
            ->module
            ->getInstanceByName($this->dependencies->name)
            ->display(_PS_MODULE_DIR_ . $this->dependencies->name . '/' . $this->dependencies->name . '.php', $file)
        ;
    }

    /**
     * @return string
     */
    public function getUninstallContent()
    {
        $this->configClass->postProcess();
        $this->html = '';

        $KEEP_CARDS = (int) $this->config->get($this->dependencies->getConfigurationKey('keepCards'));

        $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->context->controller->addJS($views_path . '/js/admin.js');
        $this->context->controller->addCSS($views_path . '/css/admin-v' . $this->dependencies->version . '.css');

        $this->context->smarty->assign([
            'form_action' => (string) ($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/' . $this->dependencies->name . '/views/img/logo_payplug.png',
            'site_url' => $this->dependencies->apiClass->getSiteUrl(),
            $this->dependencies->getConfigurationKey('keepCards') => $KEEP_CARDS,
        ]);

        $this->html .= $this->fetchTemplate('/views/templates/admin/admin_uninstall_configuration.tpl');

        return $this->html;
    }

    /**
     * @description Disconnect user
     */
    public function logout()
    {
        $this->install->setConfig();
        $this->config->updateValue($this->dependencies->getConfigurationKey('show'), 0);
        $this->config->loadConfiguration();
    }

    /**
     * Create log files to be used everywhere in PayPlug module
     */
    private function setLoggers()
    {
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->myLogPHP = new MyLogPHP();

        $this->logger->setParams(['process' => 'payplug.php']);
    }

    /**
     * Set very adapter properties
     */
    private function setConfigurationProperties()
    {
        $this->api_live = $this->config->get($this->dependencies->getConfigurationKey('liveApiKey'));
        $this->api_test = $this->config->get($this->dependencies->getConfigurationKey('testApiKey'));
        $this->email = $this->config->get($this->dependencies->getConfigurationKey('email'));

        $available_img_lang = [
            'fr',
            'gb',
            'en',
            'it',
        ];
        $this->img_lang = in_array($this->context->language->iso_code, $available_img_lang)
            ? $this->context->language->iso_code : 'default';
        $this->ssl_enable = $this->config->get('PS_SSL_ENABLED');

        if ((!isset($this->email) || (!isset($this->api_live) && empty($this->api_test)))) {
            $this->warning = $this->dependencies->l('payplug.setConfigurationProperties.configureModule', 'configclass');
        }

        $this->payment_status = [
            1 => $this->dependencies->l('payplug.setConfigurationProperties.notPaid', 'configclass'),
            2 => $this->dependencies->l('payplug.setConfigurationProperties.paid', 'configclass'),
            3 => $this->dependencies->l('payplug.setConfigurationProperties.failed', 'configclass'),
            4 => $this->dependencies->l('payplug.setConfigurationProperties.partiallyRefunded', 'configclass'),
            5 => $this->dependencies->l('payplug.setConfigurationProperties.refunded', 'configclass'),
            6 => $this->dependencies->l('payplug.setConfigurationProperties.onGoing', 'configclass'),
            7 => $this->dependencies->l('payplug.setConfigurationProperties.cancelled', 'configclass'),
            8 => $this->dependencies->l('payplug.setConfigurationProperties.authorized', 'configclass'),
            9 => $this->dependencies->l('payplug.setConfigurationProperties.authorizationExpired', 'configclass'),
            10 => $this->dependencies->l('payplug.setConfigurationProperties.oneyPending', 'configclass'),
            11 => $this->dependencies->l('payplug.setConfigurationProperties.abandoned', 'configclass'),
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
            'name' => 'payplug_show',
            'label' => $this->dependencies->l('payplug.assignSwitchConfiguration.showPayplug', 'configclass'),
            'active' => $connected,
            'small' => true,
            'checked' => $configurations['show'],
        ];

        $switch['sandbox'] = [
            'name' => 'payplug_sandbox',
            'active' => $connected,
            'checked' => $configurations['sandbox_mode'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.test', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.live', 'configclass'),
        ];

        $switch['embedded'] = [
            'name' => 'payplug_embedded',
            'active' => $connected,
            'checked' => $configurations['embedded_mode'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.popup', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.redirected', 'configclass'),
        ];

        $switch['one_click'] = [
            'name' => 'payplug_one_click',
            'active' => $connected,
            'checked' => $configurations['one_click'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['standard'] = [
            'name' => 'payplug_standard',
            'active' => $connected,
            'checked' => $configurations['standard'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['oney'] = [
            'name' => 'payplug_oney',
            'active' => $connected,
            'checked' => $configurations['oney'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['oney_optimized'] = [
            'name' => 'payplug_oney_optimized',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_optimized'],
        ];
        $switch['oney_product_cta'] = [
            'name' => 'payplug_oney_product_cta',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_product_cta'],
        ];
        $switch['oney_cart_cta'] = [
            'name' => 'payplug_oney_cart_cta',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_cart_cta'],
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
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['bancontact_country'] = [
            'name' => 'payplug_bancontact_country',
            'active' => $connected,
            'checked' => $configurations['bancontact_country'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['applepay'] = [
            'name' => 'payplug_applepay',
            'active' => $connected,
            'checked' => $configurations['applepay'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['installment'] = [
            'name' => 'payplug_inst',
            'active' => $connected,
            'checked' => $configurations['inst'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $switch['deferred'] = [
            'name' => 'payplug_deferred',
            'active' => $connected,
            'checked' => $configurations['deferred'],
            'label_left' => $this->dependencies->l('payplug.assignSwitchConfiguration.yes', 'configclass'),
            'label_right' => $this->dependencies->l('payplug.assignSwitchConfiguration.no', 'configclass'),
        ];

        $this->context->smarty->assign([
            'payplug_switch' => $switch,
        ]);
    }
}
