<?php

namespace PayPlug\src\repositories;

class ConfigRepository extends Repository
{
    private function initializeAccessors()
    {
        $this->setPlugin((new PayPlug\src\repositories\PluginRepository($this))->getEntity());

        $this->card = $this->getPlugin()->getCard();
        $this->logger = $this->getPlugin()->getLogger();
        $this->oney = $this->getPlugin()->getOney();
        $this->query = $this->getPlugin()->getQuery();
        $this->tools = $this->getPlugin()->getTools();
        $this->order_state = $this->getPlugin()->getOrderState();
    }

    public function loadSpecificPrestaClasses()
    {
        $this->PrestashopSpecificClass = '\PayPlug\src\specific\PrestashopSpecific' . _PS_VERSION_[0] . _PS_VERSION_[2];
        if (class_exists($this->PrestashopSpecificClass)) {
            $this->PrestashopSpecificObject = new $this->PrestashopSpecificClass($this);
        }
    }

    /**
     * @return bool
     */
    public function checkConfiguration()
    {
        $payplug_email = Configuration::get('PAYPLUG_EMAIL');
        $payplug_test_api_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        $payplug_live_api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');

        $report = $this->checkRequirements();

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

        $curl_warning = $this->l('PHP cURL extension must be enabled on your server');
        if ($report['curl']['installed']) {
            $this->check_configuration['success'][] .= $curl_warning;
        } else {
            $this->check_configuration['error'][] .= $curl_warning;
        }

        $php_warning = $this->l('Your server must run PHP 5.3 or greater');
        if ($report['php']['up2date']) {
            $this->check_configuration['success'][] .= $php_warning;
        } else {
            $this->check_configuration['error'][] .= $php_warning;
        }

        $openssl_warning = $this->l('OpenSSL 1.0.1 or later');
        if ($report['openssl']['installed'] && $report['openssl']['up2date']) {
            $this->check_configuration['success'][] .= $openssl_warning;
        } else {
            $this->check_configuration['error'][] .= $openssl_warning;
        }

        $connexion_warning = $this->l('You must connect your Payplug account');
        if ($is_payplug_connected) {
            $this->check_configuration['success'][] .= $connexion_warning;
        } else {
            $this->check_configuration['error'][] .= $connexion_warning;
        }

        $check_warning = $this->l('Unfortunately at least one issue is preventing you from using Payplug.') . ' '
            . $this->l('Refresh the page or click "Check" once they are fixed');
        if ($is_payplug_configured) {
        } else {
            Configuration::get('PAYPLUG_SHOW', 0);
            $this->check_configuration['warning'][] .= $check_warning;
        }


        // check if oney tos is complete
        $check_oney_tos = $this->l('Please manage the “General terms and conditions” part for Oney');
        if ($is_payplug_connected && Configuration::get('PAYPLUG_ONEY')
            && empty(Configuration::get('PAYPLUG_ONEY_TOS_URL'))) {
            $this->check_configuration['other'][] = [
                'text' => $check_oney_tos,
                'type' => 'warning'
            ];
        }

        return true;
    }

    /**
     * @return array
     */
    protected function checkRequirements()
    {
        $php_min_version = 50300;
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
     * @description
     * Create basic configuration
     *
     * @return bool
     */
    protected function createConfig()
    {
        return (Configuration::updateValue('PAYPLUG_ALLOW_SAVE_CARD', 0)
            && Configuration::updateValue('PAYPLUG_COMPANY_ID', null)
            && Configuration::updateValue('PAYPLUG_COMPANY_STATUS', '')
            && Configuration::updateValue('PAYPLUG_CURRENCIES', 'EUR')
            && Configuration::updateValue('PAYPLUG_DEBUG_MODE', 0)
            && Configuration::updateValue('PAYPLUG_EMAIL', null)
            && Configuration::updateValue('PAYPLUG_EMBEDDED_MODE', 0)
            && Configuration::updateValue('PAYPLUG_INST', null)
            && Configuration::updateValue('PAYPLUG_INST_MIN_AMOUNT', 150)
            && Configuration::updateValue('PAYPLUG_INST_MODE', 3)
            && Configuration::updateValue('PAYPLUG_KEEP_CARDS', 0)
            && Configuration::updateValue('PAYPLUG_LIVE_API_KEY', null)
            && Configuration::updateValue('PAYPLUG_MAX_AMOUNTS', 'EUR:1000000')
            && Configuration::updateValue('PAYPLUG_MIN_AMOUNTS', 'EUR:1')
            && Configuration::updateValue('PAYPLUG_OFFER', '')
            && Configuration::updateValue('PAYPLUG_ONEY', null)
            && Configuration::updateValue('PAYPLUG_ONE_CLICK', null)
            && Configuration::updateValue('PAYPLUG_SANDBOX_MODE', 1)
            && Configuration::updateValue('PAYPLUG_SHOW', 0)
            && Configuration::updateValue('PAYPLUG_TEST_API_KEY', null)
            && Configuration::updateValue('PAYPLUG_DEFERRED', 0)
            && Configuration::updateValue('PAYPLUG_DEFERRED_AUTO', 0)
            && Configuration::updateValue('PAYPLUG_DEFERRED_STATE', 0)
        );
    }

    /**
     * Delete basic configuration
     *
     * @return bool
     */
    private function deleteConfig()
    {
        return (Configuration::deleteByName('PAYPLUG_ALLOW_SAVE_CARD')
            && Configuration::deleteByName('PAYPLUG_COMPANY_ID')
            && Configuration::deleteByName('PAYPLUG_COMPANY_ID_TEST')
            && Configuration::deleteByName('PAYPLUG_COMPANY_STATUS')
            && Configuration::deleteByName('PAYPLUG_CONFIGURATION_OK')
            && Configuration::deleteByName('PAYPLUG_CURRENCIES')
            && Configuration::deleteByName('PAYPLUG_DEBUG_MODE')
            && Configuration::deleteByName('PAYPLUG_EMAIL')
            && Configuration::deleteByName('PAYPLUG_EMBEDDED_MODE')
            && Configuration::deleteByName('PAYPLUG_INST')
            && Configuration::deleteByName('PAYPLUG_INST_MIN_AMOUNT')
            && Configuration::deleteByName('PAYPLUG_INST_MODE')
            && Configuration::deleteByName('PAYPLUG_KEEP_CARDS')
            && Configuration::deleteByName('PAYPLUG_LIVE_API_KEY')
            && Configuration::deleteByName('PAYPLUG_MAX_AMOUNTS')
            && Configuration::deleteByName('PAYPLUG_MIN_AMOUNTS')
            && Configuration::deleteByName('PAYPLUG_OFFER')
            && Configuration::deleteByName('PAYPLUG_ONE_CLICK')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_ERROR')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_ERROR_TEST')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID_TEST')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_PENDING')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_PENDING_TEST')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND_TEST')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_AUTH')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_AUTH_TEST')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_EXP')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_EXP_TEST')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_ONEY_PG')
            && Configuration::deleteByName('PAYPLUG_ORDER_STATE_ONEY_PG_TEST')
            && Configuration::deleteByName('PAYPLUG_ONEY')
            && Configuration::deleteByName('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            && Configuration::deleteByName('PAYPLUG_ONEY_MAX_AMOUNTS')
            && Configuration::deleteByName('PAYPLUG_ONEY_MIN_AMOUNTS')
            && Configuration::deleteByName('PAYPLUG_ONEY_TOS')
            && Configuration::deleteByName('PAYPLUG_ONEY_TOS_URL')
            && Configuration::deleteByName('PAYPLUG_SANDBOX_MODE')
            && Configuration::deleteByName('PAYPLUG_SHOW')
            && Configuration::deleteByName('PAYPLUG_TEST_API_KEY')
            && Configuration::deleteByName('PAYPLUG_DEFERRED')
            && Configuration::deleteByName('PAYPLUG_DEFERRED_AUTO')
            && Configuration::deleteByName('PAYPLUG_DEFERRED_STATE')
        );
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
        parent::disable($force_all);

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
     * @param bool $force_all
     * @return bool
     * @see Module::enable()
     *
     */
    public function enable($force_all = false)
    {
        return parent::enable($force_all);
    }

    /**
     * @description
     * @param $cart
     * @return array
     */
    public function getAvailableOptions($cart)
    {
        if (!$this->isAllowed()) {
            return false;
        }

        $permissions = $this->getAccountPermissions();

        $available_options = [
            'standard' => true,
            'live' => (int)Configuration::get('PAYPLUG_SANDBOX_MODE') === 0,
            'embedded' => (int)Configuration::get('PAYPLUG_EMBEDDED_MODE') === 1,
            'one_click' => (int)Configuration::get('PAYPLUG_ONE_CLICK') === 1,
            'installment' => (int)Configuration::get('PAYPLUG_INST') === 1,
            'deferred' => (int)Configuration::get('PAYPLUG_DEFERRED') === 1,
            'oney' => (int)Configuration::get('PAYPLUG_ONEY') === 1,
        ];

        if (Configuration::get('PAYPLUG_EMAIL') === null
            || !$this->checkCurrency($cart)
            || !$this->checkAmount($cart)
        ) {
            $available_options['standard'] = false;
            $available_options['sandbox'] = false;
            $available_options['embedded'] = false;
            $available_options['one_click'] = false;
            $available_options['installment'] = false;
            $available_options['deferred'] = false;
            $available_options['oney'] = false;
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
        }

        return $available_options;
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

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $this->context->smarty->assign([
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => $this->version,
        ]);
        $this->html = $this->fetchTemplateRC('/views/templates/admin/panel/fieldset.tpl');

        return $this->html;
    }

    /**
     * @return string
     */
    private function getCurrentApiKey()
    {
        if ((int)Configuration::get('PAYPLUG_SANDBOX_MODE') === 1) {
            return Configuration::get('PAYPLUG_TEST_API_KEY');
        } else {
            return Configuration::get('PAYPLUG_LIVE_API_KEY');
        }
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
            'payment_page' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142312',
            'refund' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022214692',
            'sandbox' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142492',
            'guide' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360011715080',
        ];
    }

    /**
     * Get the right country iso-code or null if it does'nt fit the ISO 3166-1 alpha-2 norm
     *
     * @param int $country_id
     * @return int | false
     */
    private function getIsoCodeByCountryId($country_id)
    {
        $iso_code_list = $this->getIsoCodeList();
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
    private function getIsoCodeList()
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
     * Get iso code from language code
     * @param $language
     * @return string
     */
    public function getIsoFromLanguageCode(Language $language)
    {
        if (!Validate::isLoadedObject($language)) {
            return false;
        }
        $parse = explode('-', $language->language_code);
        return Tools::strtolower($parse[0]);
    }

    public function saveConfiguration()
    {
        Configuration::updateValue('PAYPLUG_DEFERRED', Tools::getValue('payplug_deferred'));
        Configuration::updateValue('PAYPLUG_DEFERRED_AUTO', (int)Tools::getValue('payplug_deferred_auto'));
        Configuration::updateValue('PAYPLUG_DEFERRED_STATE', (int)Tools::getValue('payplug_deferred_state'));
        Configuration::updateValue('PAYPLUG_SHOW', Tools::getValue('PAYPLUG_SHOW'));
        Configuration::updateValue('PAYPLUG_EMBEDDED_MODE', Tools::getValue('payplug_embedded'));
        Configuration::updateValue('PAYPLUG_INST', Tools::getValue('payplug_inst'));
        Configuration::updateValue('PAYPLUG_INST_MIN_AMOUNT', Tools::getValue('PAYPLUG_INST_MIN_AMOUNT'));
        Configuration::updateValue('PAYPLUG_INST_MODE', Tools::getValue('PAYPLUG_INST_MODE'));
        Configuration::updateValue('PAYPLUG_ONE_CLICK', Tools::getValue('payplug_one_click'));
        Configuration::updateValue('PAYPLUG_ONEY', Tools::getValue('payplug_oney'));
        if ((int)Tools::getValue('payplug_oney') == 1) {
            $carriers = PayPlugCarrier::getAll();
            foreach ($carriers as $carrier) {
                if ((int)(Tools::getValue('payplug_carrier_' . (int)$carrier->id . '_delay')) < 0) {
                    $this->displayError($this->l('Settings not updated'));
                }
                $carrier->delivery_type = Tools::getValue(
                    'payplug_carrier_' . (int)$carrier->id . '_delivery_type'
                );
                $carrier->delay = (int)Tools::getValue('payplug_carrier_' . (int)$carrier->id . '_delay');
                $carrier->save();
            }
        }
        Configuration::updateValue('PAYPLUG_ONEY_OPTIMIZED', Tools::getValue('payplug_oney_optimized'));
        Configuration::updateValue('PAYPLUG_ONEY_TOS', Tools::getValue('payplug_oney_tos'));
        Configuration::updateValue('PAYPLUG_ONEY_TOS_URL', Tools::getValue('payplug_oney_tos_url'));
        Configuration::updateValue('PAYPLUG_SANDBOX_MODE', Tools::getValue('payplug_sandbox'));
        if (Tools::getValue('PAYPLUG_SHOW')) {
            $this->enable();
        }
    }

    /**
     * Determine wich API key to use
     *
     * @return string
     */
    public static function setAPIKey()
    {
        $sandbox_mode = (int)Configuration::get('PAYPLUG_SANDBOX_MODE');
        $valid_key = null;
        if ($sandbox_mode) {
            $valid_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        } else {
            $valid_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
        }

        return $valid_key;
    }

    /**
     * Register API Keys
     *
     * @param string $json_answer
     * @return bool
     */
    private function setApiKeysbyJsonResponse($json_answer)
    {
        if (isset($json_answer['object']) && $json_answer['object'] == 'error') {
            return false;
        }

        $api_keys = [];
        $api_keys['test_key'] = '';
        $api_keys['live_key'] = '';

        if (isset($json_answer['secret_keys'])) {
            if (isset($json_answer['secret_keys']['test'])) {
                $api_keys['test_key'] = $json_answer['secret_keys']['test'];
            }
            if (isset($json_answer['secret_keys']['live'])) {
                $api_keys['live_key'] = $json_answer['secret_keys']['live'];
            }
        }
        Configuration::updateValue('PAYPLUG_TEST_API_KEY', $api_keys['test_key']);
        Configuration::updateValue('PAYPLUG_LIVE_API_KEY', $api_keys['live_key']);

        $is_sandbox = Configuration::get('PAYPLUG_SANDBOX_MODE');
        if ($is_sandbox) {
            $this->setSecretKey($api_keys['test_key']);
        } else {
            $this->setSecretKey($api_keys['live_key']);
        }

        return true;
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

        // Set the uninstall notice according to the "keep_cards" configuration
        $this->confirmUninstall = $this->l('Are you sure you wish to uninstall this module 
        and delete your settings?') . ' ';
        if ((int)Configuration::get('PAYPLUG_KEEP_CARDS') == 1) {
            $this->confirmUninstall .= $this->l('All the registered cards of your customer will be kept.');
        } else {
            $this->confirmUninstall .= $this->l('All the registered cards of your customer will be deleted.');
        }

        $this->current_api_key = $this->getCurrentApiKey();
        $this->email = Configuration::get('PAYPLUG_EMAIL');
        $this->img_lang = $this->context->language->iso_code === 'it' ? 'it' : 'default';
        $this->ssl_enable = Configuration::get('PS_SSL_ENABLED');

        if ((!isset($this->email) || (!isset($this->api_live) && empty($this->api_test)))) {
            $this->warning = $this->l('In order to accept payments you need to configure your module').' '.
                $this->l('by connecting your PayPlug account.');
        }

        $this->payment_status = [
            1 => $this->l('not paid'),
            2 => $this->l('paid'),
            3 => $this->l('failed'),
            4 => $this->l('partially refunded'),
            5 => $this->l('refunded'),
            6 => $this->l('on going'),
            7 => $this->l('cancelled'),
            8 => $this->l('authorized'),
            9 => $this->l('authorization expired'),
            10 => $this->l('oney pending'),
            11 => $this->l('abandoned'),
        ];
    }

    /**
     * Determine witch environment is used
     *
     * @return void
     */
    private function setEnvironment()
    {
        if (isset($_SERVER['PAYPLUG_API_URL'])) {
            $this->plugin->setApiUrl($_SERVER['PAYPLUG_API_URL']);
        } else {
            $this->plugin->setApiUrl('https://api.payplug.com');
        }

        if (isset($_SERVER['PAYPLUG_SITE_URL'])) {
            $this->site_url = $_SERVER['PAYPLUG_SITE_URL'];
        } else {
            $this->site_url = 'https://www.payplug.com';
        }
    }

    /**
     * @param $error_message
     * @return bool
     */
    private function setError($error_message)
    {
        if (!$error_message) {
            return false;
        }
        $error_key = md5($error_message);

        // push error only if not catched before
        if (!array_key_exists($error_key, $this->errors)) {
            $this->errors[$error_key] = $this->l($error_message);
        }
    }

    /**
     * Create log files to be used everywhere in PayPlug module
     *
     * @return void
     */
    private function setLoggers()
    {
        $this->log_general = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . $this->name . '/log/general-log.csv');
        $this->log_install = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . $this->name . '/log/install-log.csv');

        $this->logger->setParams(['process' => 'payplug.php']);

        if ($this->active) {
            $this->logger->flush();
        }
    }

    /**
     * @description Set the essential properties of a Prestashop module
     *
     * @return void
     */
    private function setPrimaryModuleProperties()
    {
    }

    /**
     * @description Set the current secret key used to interact with PayPlug API
     *
     * @param bool $token
     * @return bool|\Payplug\Payplug
     * @throws \Payplug\Exception\ConfigurationException
     */
    public function setSecretKey($token = false)
    {
        if (!$token && $this->current_api_key != null) {
            $token = $this->current_api_key;
        }

        if (!$token) {
            return false;
        }

        return \Payplug\Payplug::init([
            'secretKey' => $token,
            'apiVersion' => $this->plugin->getApiVersion()
        ]);
    }

    /**
     * Set the user-agent referenced in every API call to identify the module
     *
     * @return void
     */
    private function setUserAgent()
    {
        if ($this->current_api_key != null) {
            \Payplug\Core\HttpClient::addDefaultUserAgentProduct(
                'PayPlug-Prestashop',
                $this->version,
                'Prestashop/' . _PS_VERSION_
            );
        }
    }

    /**
     * Run update module
     */
    public function runUpgradeModule()
    {
        $upgrade = parent::runUpgradeModule();

        $this->checkOrderStates();

        return $upgrade;
    }

    public function setNotification()
    {
        return new PayPlugNotifications();
    }

    public function setValidation()
    {
        return new PayPlugValidation();
    }

    public function getConfiguration($key)
    {
        if (isset($this->_conf[$key])) {
            return $this->_conf[$key]['value'];
        } else {
            return Configuration::get($key);
        }
    }

    /** @var array */
    private $entities = [
        'PayPlugPayment/PayPlugPayment',
        'PayPlugPayment/PayPlugPaymentStandard',
        'PayPlugPayment/PayPlugPaymentOneClick',
        'PayPlugPayment/PayPlugPaymentInstallment',
        'PayPlugPayment/PayPlugPaymentOney',
        'PayPlugCarrier',
        'PayPlugNotifications',
        'PayplugLock',
        'PayPlugValidation',
        'PayPlugAjax',
        'PPPayment',
        'PPPaymentInstallment',
    ];

    /**
     * Load PayPlug entities from props
     *
     * @return bool
     */
    public function loadEntities()
    {
        if (empty($this->entities)) {
            return false;
        }

        foreach ($this->entities as $entity) {
            $entity_path = _PS_MODULE_DIR_ . 'payplug/classes/' . $entity . '.php';
            if (file_exists($entity_path)) {
                include_once($entity_path);
            }
        }

        return true;
    }

    public function initializeApi($sandbox = null)
    {
        if ($sandbox === null) {
            $payplug_key = $this->current_api_key;
        } else {
            $payplug_key = $this->getConfiguration('PAYPLUG_' . ($sandbox ? 'TEST' : 'LIVE') . '_API_KEY');
        }

        try {
            \Payplug\Payplug::init(['secretKey' => $payplug_key, 'apiVersion' => $this->plugin->getApiVersion()]);

            return $payplug_key;
        } catch (Exception $e) {
            // todo: return error log
            return false;
        }
    }

    /**
     * Check Prestashop version for new feature
     * @param string $min
     * @return bool
     */
    public function checkVersion($min = '1.6')
    {
        return (bool)version_compare(_PS_VERSION_, $min, '>=');
    }

    /**
     * @return string
     */
    public function assignContentVar()
    {
        if (Tools::getValue('uninstall_config')) {
            return $this->getUninstallContent();
        }

        $this->checkConfiguration();

        $configurations = [
            'show' => Configuration::get('PAYPLUG_SHOW'),
            'email' => Configuration::get('PAYPLUG_EMAIL'),
            'sandbox_mode' => Configuration::get('PAYPLUG_SANDBOX_MODE'),
            'embedded_mode' => Configuration::get('PAYPLUG_EMBEDDED_MODE'),
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
            'oney_tos' => Configuration::get('PAYPLUG_ONEY_TOS'),
            'oney_tos_url' => Configuration::get('PAYPLUG_ONEY_TOS_URL'),
            'oney_optimized' => Configuration::get('PAYPLUG_ONEY_OPTIMIZED'),
        ];

        $connected = !empty($configurations['email'])
            && (!empty($configurations['test_api_key']) || !empty($configurations['live_api_key']));

        if (count($this->validationErrors) && !$connected) {
            $this->context->smarty->assign([
                'validationErrors' => $this->validationErrors,
            ]);
        }

        $valid_key = self::setAPIKey();
        if (!empty($valid_key)) {
            $permissions = $this->getAccount($valid_key);
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

        $this->site_url;

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

        $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin-old.css');
        $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin.css');

        $admin_ajax_url = $this->getAdminAjaxUrl();

        Media::addJsDef([
            'admin_ajax_url' => $admin_ajax_url,
            'error_installment' => $this->l('Installment: '),
            'error_deferred' => $this->l('Deferred: '),
            'error_oney' => $this->l('Oney: '),
        ]);

        $login_infos = [];

        $installments_panel_url = 'index.php?controller=AdminPayPlugInstallment';
        $installments_panel_url .= '&token=' . Tools::getAdminTokenLite('AdminPayPlugInstallment');

        $faq_links = $this->getFAQLinks(Context::getContext()->language->iso_code);

        $amounts = $this->oney->getOneyPriceLimit();
        $oney_min_amounts = ($amounts['min'] / 100);
        $oney_max_amounts = ($amounts['max'] / 100);

        $this->assignSwitchConfiguration($configurations);

        $this->context->smarty->assign([
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => $this->version,
            'connected' => $connected,
            'verified' => $verified,
            'premium' => $premium,
            'is_active' => $is_active,
            'site_url' => $this->site_url,
            'PAYPLUG_SANDBOX_MODE' => $configurations['sandbox_mode'],
            'PAYPLUG_EMBEDDED_MODE' => $configurations['embedded_mode'],
            'PAYPLUG_ONE_CLICK' => $configurations['one_click'],
            'PAYPLUG_INST' => $configurations['inst'],
            'PAYPLUG_INST_MODE' => $configurations['inst_mode'],
            'PAYPLUG_INST_MIN_AMOUNT' => $configurations['inst_min_amount'],
            'PAYPLUG_SHOW' => $configurations['show'],
            'PAYPLUG_DEBUG_MODE' => $configurations['debug_mode'],
            'PAYPLUG_DEFERRED' => $configurations['deferred'],
            'PAYPLUG_DEFERRED_AUTO' => $configurations['deferred_auto'],
            'PAYPLUG_DEFERRED_STATE' => $configurations['deferred_state'],
            'PAYPLUG_ONEY' => $configurations['oney'],
            'PAYPLUG_ONEY_TOS_URL' => $configurations['oney_tos_url'],
            'login_infos' => $login_infos,
            'installments_panel_url' => $installments_panel_url,
            'order_states' => $this->getOrderStates(),
            'oney_min_amounts' => $oney_min_amounts,
            'oney_max_amounts' => $oney_max_amounts,
            'faq_links' => $faq_links,
            'iso' => $this->context->language->iso_code,
        ]);

        return $this->html;
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
            'label' => $this->l('Show Payplug to my customers'),
            'active' => $connected,
            'small' => true,
            'checked' => $configurations['show'],
        ];

        $switch['sandbox'] = [
            'name' => 'payplug_sandbox',
            'active' => $connected,
            'checked' => $configurations['sandbox_mode'],
            'label_left' => $this->l('test'),
            'label_right' => $this->l('live'),
        ];

        $switch['embedded'] = [
            'name' => 'payplug_embedded',
            'active' => $connected,
            'checked' => $configurations['embedded_mode'],
            'label_left' => $this->l('embedded'),
            'label_right' => $this->l('redirected'),
        ];

        $switch['one_click'] = [
            'name' => 'payplug_one_click',
            'active' => $connected,
            'checked' => $configurations['one_click'],
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        ];

        $switch['oney'] = [
            'name' => 'payplug_oney',
            'active' => $connected,
            'checked' => $configurations['oney'],
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        ];

        $switch['oney_tos'] = [
            'name' => 'payplug_oney_tos',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_tos'],
        ];

        $switch['oney_optimized'] = [
            'name' => 'payplug_oney_optimized',
            'active' => true,
            'small' => true,
            'checked' => $configurations['oney_optimized'],
        ];

        $switch['installment'] = [
            'name' => 'payplug_inst',
            'active' => $connected,
            'checked' => $configurations['inst'],
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        ];

        $switch['deferred'] = [
            'name' => 'payplug_deferred',
            'active' => $connected,
            'checked' => $configurations['deferred'],
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        ];

        $switch['deferred_auto'] = [
            'name' => 'payplug_deferred_auto',
            'active' => $connected,
            'checked' => $configurations['deferred_auto'],
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        ];

        $this->context->smarty->assign([
            'payplug_switch' => $switch
        ]);
    }

    /**
     * Redirection
     *
     * @param string $link
     * @return void
     */
    public static function redirectForVersion($link)
    {
        Tools::redirect($link);
    }

    /**
     * @return bool
     */
    public function hasLiveKey()
    {
        return (bool)Configuration::get('PAYPLUG_LIVE_API_KEY');
    }

    /**
     * @description
     * Check if Payplug is allowed
     * @return bool
     */
    public function isAllowed()
    {
        if (!Module::isEnabled($this->name) || !$this->getConfiguration('PAYPLUG_SHOW')) {
            return false;
        }
        return true;
    }

    /**
     * @return void
     * @see Module::postProcess()
     *
     */
    private function postProcess()
    {
        $curl_exists = extension_loaded('curl');
        $openssl_exists = extension_loaded('openssl');

        if (Tools::getValue('submitDisable')) {
            Configuration::updateValue('PAYPLUG_SHOW', false);

            $this->assignContentVar();
            $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

            $this->context->smarty->assign([
                'title' => '',
                'type' => 'save',
            ]);
            $popin = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

            die(json_encode(['popin' => $popin, 'content' => $content]));
        }

        if (Tools::isSubmit('submitAccount')) {
            /*
             * We can't use $password = Tools::getValue('PAYPLUG_PASSWORD');
             * Because pwd with special chars don't work
             */
            $password = $_POST['PAYPLUG_PASSWORD'];
            $email = Tools::getValue('PAYPLUG_EMAIL');
            if (!Validate::isEmail($email) || !PayPlug\backward\PayPlugBackward::isPlaintextPassword($password)) {
                $this->validationErrors['username_password'] =
                    $this->l('The email and/or password was not correct.');
            } elseif ($curl_exists && $openssl_exists) {
                if ($this->login($email, $password)) {
                    Configuration::updateValue('PAYPLUG_EMAIL', Tools::getValue('PAYPLUG_EMAIL'));
                    Configuration::updateValue('PAYPLUG_SHOW', 1);

                    $this->assignContentVar();
                    $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

                    die(json_encode(['content' => $content]));
                } else {
                    $this->validationErrors['username_password'] =
                        $this->l('The email and/or password was not correct.');
                }
            }
        }

        if (Tools::isSubmit('submitDisconnect')) {
            $this->createConfig();
            Configuration::updateValue('PAYPLUG_SHOW', 0);

            // force reload configuration to be sure all config are reset
            Configuration::loadConfiguration();

            $this->assignContentVar();
            $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

            die(json_encode(['content' => $content]));
        }

        if (Tools::isSubmit('submitSettings')) {
            if (Tools::getValue('PAYPLUG_INST_MIN_AMOUNT') < 4) {
                $this->displayError($this->l('Settings not updated'));
            } else {
                $this->saveConfiguration();
            }
        }

        if (Tools::isSubmit('submitUninstallSettings')) {
            Configuration::updateValue('PAYPLUG_KEEP_CARDS', Tools::getValue('PAYPLUG_KEEP_CARDS'));
        }
    }

    /**
     * Check if current device used is mobile
     *
     * @return bool
     */
    public function isMobiledevice()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match(
                '/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|
                        iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|
                        palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|
                        up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',
                $useragent
            ) || preg_match(
                '/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|
            an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|
                br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|
                dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|
                fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|
                hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|
                ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|
                lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|
                mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|
                n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|
                pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|
                qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|
                sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|
                sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|
                tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|
                vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|
                wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',
                Tools::substr($useragent, 0, 4)
            )) {
            return true;
        }
        return false;
    }

    /**
     * Check if given phone number is valid mobile phone number
     * @param string $phone_number
     * @param string $iso_code
     * @return bool
     */
    public function isValidMobilePhoneNumber($phone_number, $iso_code)
    {
        try {
            $phone_util = libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);
            $is_mobile = $phone_util->getNumberType($parsed);
            return (bool)(in_array($is_mobile, [1, 2], true));
        } catch (Exception $e) {
            // @todo : Add Log
            return false;
        }
    }

    /**
     * Return exeption error form API
     * @param $str
     * @return array
     */
    public function catchErrorsFromApi($str)
    {
        $parses = explode(';', $str);
        $response = null;
        foreach ($parses as $parse) {
            if (strpos($parse, 'HTTP Response') !== false) {
                $parse = str_replace('HTTP Response:', '', $parse);
                $parse = trim($parse);
                $response = json_decode($parse, true);
            }
        }

        $errors = [];
        $errors[] = $str;
        if (!isset($response['details']) || empty($response['details'])) {
            // set a default error message
            $error_key = md5('The transaction was not completed and your card was not charged.');
            $errors[$error_key] = $this->l('The transaction was not completed and your card was not charged.');
            return $errors;
        }

        foreach ($response['details'] as $key => $value) {
            // add specific error message
            switch ($key) {
                default:
                    $error_key = md5('The transaction was not completed and your card was not charged.');
                    // push error only if not catched before
                    if (!array_key_exists($error_key, $errors)) {
                        $errors[$error_key] =
                            $this->l('The transaction was not completed and your card was not charged.');
                    }
            }
        }

        return $errors;
    }

    /**
     * @return string
     */
    public function displayGDPRConsent()
    {
        $this->context->smarty->assign(['id_module' => $this->id]);
        return $this->display(__FILE__, 'customer/gdpr_consent.tpl');
    }

    /**
     * Return international formated phone number (norm E.164)
     *
     * @param $phone_number
     * @param $country
     * @return string|null
     */
    public function formatPhoneNumber($phone_number, $country)
    {
        if (empty($phone_number)) {
            return null;
        }
        if (!is_object($country)) {
            $country = new Country($country);
        }
        if (!Validate::isLoadedObject($country)) {
            return null;
        }

        try {
            $iso_code = $this->getIsoCodeByCountryId($country->id);
            $phone_util = libphonenumberlight\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if (!$phone_util->isValidNumber($parsed)) {
                // todo: add log
                return null;
            }

            $formated = $phone_util->format($parsed, \libphonenumberlight\PhoneNumberFormat::E164);
            return $formated;
        } catch (Exception $e) {
            // todo: add log
            return null;
        }
    }

    /**
     * @param $id_customer
     * @return array|bool|null
     * @throws PrestaShopDatabaseException
     */
    private function gdprCardExport($id_customer)
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
                    $this->l('#') => $i,
                    $this->l('Brand') => $card['brand'],
                    $this->l('Country') => $card['country'],
                    $this->l('Card') => '**** **** **** ' . $card['last4'],
                    $this->l('Expiry date') => $card['expiry_date']
                ];
                $i++;
            }
        }
        return $cards;
    }
}