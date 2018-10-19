<?php
/**
 * 2013 - 2018 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2018 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_.'payplug/classes/MyLogPHP.class.php');
require_once(_PS_MODULE_DIR_.'payplug/lib/init.php');

class Payplug extends PaymentModule
{
    const PAYPLUG_PROD_API_URL = 'https://api.payplug.com';
    const PAYPLUG_PROD_SITE_URL = 'https://www.payplug.com';

    /** @var string */
    private $api_live;

    /** @var string */
    private $api_test;

    /** @var string */
    private $api_url;

    /** @var string */
    private $current_api_key;

    /** @var string */
    private $email;

    /** @var string */
    private $html = '';

    /** @var bool */
    private $is_active = 1;

    /** @var MyLogPHP */
    private $log_general;

    /** @var MyLogPHP */
    private $log_install;

    /** @var array */
    private $routes = array(
        'login' => '/v1/keys',
        'account' => '/v1/account',
        'patch' => '/v1/payments'
    );

    /** @var string */
    private $site_url;

    /** @var bool */
    private $ssl_enable;

    /** @var string */
    private $img_lang;

    /** @var array */
    public $check_configuration = array();

    /** @var array */
    public $validationErrors = array();

    /**
     * Constructor
     *
     * @return Payplug
     */
    public function __construct()
    {
        $this->setLoggers();
        $this->setPrimaryModuleProperties();
        parent::__construct();
        $this->setEnvironnement();
        $this->setConfigurationProperties();
        $this->setSecretKey();
        $this->setUserAgent();

        $this->img_lang = $this->context->language->iso_code === 'it' ? 'it' : 'default';
    }

    private function setLoggers()
    {
        $this->log_general = new MyLogPHP(_PS_MODULE_DIR_.$this->name.'/log/general-log.csv');
        $this->log_install = new MyLogPHP(_PS_MODULE_DIR_.$this->name.'/log/install-log.csv');
    }

    private function setPrimaryModuleProperties()
    {
        // Must be set before translations
        $this->name = 'payplug';

        $this->author = 'PayPlug';
        $this->bootstrap = true;
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->description = $this->l('The online payment solution combining simplicity and first-rate support to boost your sales.');
        $this->displayName = 'PayPlug';
        $this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';
        $this->need_instance = true;
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => '1.8');
        $this->tab = 'payments_gateways';
        $this->version = '2.15.0';
    }

    /**
     * Determine witch environnement is used
     *
     * @return void
     */
    private function setEnvironnement()
    {
        if (isset($_SERVER['PAYPLUG_API_URL'])) {
            $this->api_url = $_SERVER['PAYPLUG_API_URL'];
        } else {
            $this->api_url = PAYPLUG_PROD_API_URL;
        }

        if (isset($_SERVER['PAYPLUG_SITE_URL'])) {
            $this->site_url = $_SERVER['PAYPLUG_SITE_URL'];
        } else {
            $this->site_url = PAYPLUG_PROD_SITE_URL;
        }
    }

    private function setConfigurationProperties()
    {
        $this->api_live = Configuration::get('PAYPLUG_LIVE_API_KEY');
        $this->api_test = Configuration::get('PAYPLUG_TEST_API_KEY');
        
        // Set the uninstall notice according to the "keep_cards" configuration
        $this->confirmUninstall = $this->l('Are you sure you wish to uninstall this module and delete your settings?').' ';
        if ((int)Configuration::get('PAYPLUG_KEEP_CARDS') == 1) {
            $this->confirmUninstall .= $this->l('All the registered cards of your customer will be kept.');
        } else {
            $this->confirmUninstall .= $this->l('All the registered cards of your customer will be deleted.');
        }

        $this->current_api_key = $this->getCurrentApiKey();
        $this->email = Configuration::get('PAYPLUG_EMAIL');
        $this->ssl_enable = Configuration::get('PS_SSL_ENABLED');
        
        if ((!isset($this->email) || (!isset($this->api_live) && empty($this->api_test)))) {
            $this->warning = $this->l('In order to accept payments you need to configure your module by connecting your PayPlug account.');
        }
    }

    private function setSecretKey()
    {
        if ($this->current_api_key != null) {
            \Payplug\Payplug::setSecretKey($this->current_api_key);
        }
    }

    private function setUserAgent()
    {
        if ($this->current_api_key != null) {
            \Payplug\Core\HttpClient::addDefaultUserAgentProduct(
                'PayPlug-Prestashop',
                $this->version,
                'Prestashop/'._PS_VERSION_
            );
        }
    }

    /**
     * @see Module::install()
     *
     * @return bool
     */
    public function install()
    {
        $this->log_install->info('Starting installation.');
        $report = $this->checkRequirements();
        if (!$report['php']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('Your server must run PHP 5.3 or greater'));
            $this->log_install->error('Installation failed: PHP Requirement.');
        }
        if (!$report['curl']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('PHP cURL extension must be enabled on your server'));
            $this->log_install->error('Installation failed: cURL Requirement.');
        }
        if (!$report['openssl']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('OpenSSL 1.0.1 or later'));
            $this->log_install->error('Installation failed: OpenSSL Requirement.');
        }

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() ||
            !$this->registerHook('paymentReturn') ||
            !$this->registerHook('header') ||
            !$this->registerHook('adminOrder') ||
            !$this->registerHook('customerAccount') ||
            !$this->createConfig() ||
            !$this->createOrderStates() ||
            !$this->installSQL() ||
            !$this->installTab()
        ) {
            $this->log_install->error('Installation failed: hooks, configs, order states or sql.');
            return false;
        }

        if (!$this->registerHook('paymentOptions')) {
            $this->log_install->error('Installation failed: hooks for 1.7.');
            return false;
        }

        if (!$this->registerHook('registerGDPRConsent') ||
            !$this->registerHook('actionDeleteGDPRCustomer') ||
            !$this->registerHook('actionExportGDPRData')
        ) {
            $this->log_install->error('Installation failed: hooks GDPR.');
            return false;
        }

        $this->log_install->info('Installation complete.');

        return true;
    }

    /**
     * @see Module::uninstall()
     *
     * @return bool
     */
    public function uninstall()
    {
        $keep_cards = (int)Configuration::get('PAYPLUG_KEEP_CARDS');
        if ($keep_cards == 1) {
            $keep_cards == true;
        } else {
            $keep_cards == false;
        }

        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
        $log->info('Starting uninstallation.');

        if (!$keep_cards) {
            $this->uninstallCards();
        }

        if (!parent::uninstall() ||
            !$this->deleteConfig() ||
            !$this->uninstallTab() ||
            !$this->uninstallSQL($keep_cards)
        ) {
            $log->error('Installation failed: configs or sql.');
            return false;
        }
        $log->info('Uninstallation complete.');

        return true;
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminPayplug';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'AdminPayplug';
        }
        $tab->id_parent = -1;
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminPayplug');
        $tab = new Tab($id_tab);
        return $tab->delete();
    }

    private function uninstallCards()
    {
        $test_api_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        $live_api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');

        $req_all_cards = new DbQuery();
        $req_all_cards->select('pc.*');
        $req_all_cards->from('payplug_card', 'pc');
        $res_all_cards = Db::getInstance()->executeS($req_all_cards);

        if (!$res_all_cards) {
            return true;
        } else {
            if (isset($res_all_cards) && !empty($res_all_cards) && sizeof($res_all_cards)) {
                foreach ($res_all_cards as $card) {
                    $id_customer = $card['id_customer'];
                    $id_payplug_card = $card['id_payplug_card'];
                    $api_key = $card['is_sandbox'] == 1 ? $test_api_key : $live_api_key;
                    $this->deleteCard($id_customer, $id_payplug_card, $api_key);
                }
            }
        }
        return true;
    }

    /**
     * Create basic configuration
     *
     * @return bool
     */
    private function createConfig()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');

        if (!Configuration::updateValue('PAYPLUG_CURRENCIES', 'EUR') ||
            !Configuration::updateValue('PAYPLUG_MIN_AMOUNTS', 'EUR:1') ||
            !Configuration::updateValue('PAYPLUG_MAX_AMOUNTS', 'EUR:1000000') ||
            !Configuration::updateValue('PAYPLUG_TEST_API_KEY', null) ||
            !Configuration::updateValue('PAYPLUG_LIVE_API_KEY', null) ||
            !Configuration::updateValue('PAYPLUG_OFFER', '') ||
            !Configuration::updateValue('PAYPLUG_COMPANY_ID', null) ||
            !Configuration::updateValue('PAYPLUG_COMPANY_STATUS', '') ||
            !Configuration::updateValue('PAYPLUG_ALLOW_SAVE_CARD', 0) ||
            !Configuration::updateValue('PAYPLUG_SANDBOX_MODE', 1) ||
            !Configuration::updateValue('PAYPLUG_SHOW', 0) ||
            !Configuration::updateValue('PAYPLUG_EMAIL', null) ||
            !Configuration::updateValue('PAYPLUG_EMBEDDED_MODE', 0) ||
            !Configuration::updateValue('PAYPLUG_ONE_CLICK', null) ||
            !Configuration::updateValue('PAYPLUG_DEBUG_MODE', 1) ||
            !Configuration::updateValue('PAYPLUG_KEEP_CARDS', 0)
        ) {
            $log->error('Installation failed: configurations failed.');
            return false;
        }

        return true;
    }

    /**
     * Delete basic configuration
     *
     * @return bool
     */
    private function deleteConfig()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');

        if (!Configuration::deleteByName('PAYPLUG_CURRENCIES') ||
            !Configuration::deleteByName('PAYPLUG_MIN_AMOUNTS') ||
            !Configuration::deleteByName('PAYPLUG_MAX_AMOUNTS') ||
            !Configuration::deleteByName('PAYPLUG_TEST_API_KEY') ||
            !Configuration::deleteByName('PAYPLUG_LIVE_API_KEY') ||
            !Configuration::deleteByName('PAYPLUG_OFFER') ||
            !Configuration::deleteByName('PAYPLUG_COMPANY_ID') ||
            !Configuration::deleteByName('PAYPLUG_COMPANY_STATUS') ||
            !Configuration::deleteByName('PAYPLUG_ALLOW_SAVE_CARD') ||
            !Configuration::deleteByName('PAYPLUG_SANDBOX_MODE') ||
            !Configuration::deleteByName('PAYPLUG_SHOW') ||
            !Configuration::deleteByName('PAYPLUG_EMAIL') ||
            !Configuration::deleteByName('PAYPLUG_EMBEDDED_MODE') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PENDING') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_ERROR') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PAID_TEST') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_REFUND_TEST') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_PENDING_TEST') ||
            !Configuration::deleteByName('PAYPLUG_ORDER_STATE_ERROR_TEST') ||
            !Configuration::deleteByName('PAYPLUG_CONFIGURATION_OK') ||
            !Configuration::deleteByName('PAYPLUG_ONE_CLICK') ||
            !Configuration::deleteByName('PAYPLUG_DEBUG_MODE') ||
            !Configuration::deleteByName('PAYPLUG_KEEP_CARDS')
        ) {
            $log->error('Uninstallation failed: configurations failed.');
            return false;
        }

        return true;
    }

    /**
     * Create usual status
     *
     * @return bool
     */
    private function createOrderStates()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
        $log->info('Order state creation starting.');
        $state_key = array(
            'paid'    => array(
                'cfg' => '_PS_OS_PAYMENT_',
                'template' => 'payment',
                'logable' => true,
                'send_email' => true,
                'paid' => true,
                'module_name' => 'payplug',
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#04b404',
                'name' => array(
                    'en' => 'Payment successful',
                    'fr' => 'Paiement effectué',
                    'es' => 'Pago efectuado',
                    'it' => 'Pagamento effettuato',
                ),
            ),
            'refund'    => array(
                'cfg' => '_PS_OS_REFUND_',
                'template' => 'refund',
                'logable' => false,
                'send_email' => true,
                'paid' => false,
                'module_name' => 'payplug',
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#ea3737',
                'name' => array(
                    'en' => 'Refunded',
                    'fr' => 'Remboursé',
                    'es' => 'Reembolsado',
                    'it' => 'Rimborsato',
                ),
            ),
            'pending'    => array(
                'cfg' => '_PS_OS_PENDING_',
                'template' => null,
                'logable' => false,
                'send_email' => false,
                'paid' => false,
                'module_name' => 'payplug',
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#a1f8a1',
                'name' => array(
                    'en' => 'Payment in progress',
                    'fr' => 'Paiement en cours',
                    'es' => 'Pago en curso',
                    'it' => 'Pagamento in corso',
                ),
            ),
            'error'    => array(
                'cfg' => '_PS_OS_ERROR_',
                'template' => 'payment_error',
                'logable' => false,
                'send_email' => true,
                'paid' => false,
                'module_name' => 'payplug',
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#8f0621',
                'name' => array(
                    'en' => 'Payment failed',
                    'fr' => 'Paiement échoué',
                    'es' => 'Payment failed',
                    'it' => 'Payment failed',
                ),
            ),
        );

        foreach ($state_key as $key => $values) {
            $key_config = 'PAYPLUG_ORDER_STATE_'.Tools::strtoupper($key);
            $key_config_test = 'PAYPLUG_ORDER_STATE_'.Tools::strtoupper($key.'_test');
            $os = 0;
            $os_test = 0;

            $log->info('Order state: '.$key);

            //LIVE
            $log->info('Live context.');
            if ((int)Configuration::get($key_config) != 0) {
                $os = (int)Configuration::get($key_config);
            } elseif ($val = $this->findOrderState($values['name'], false)) {
                $os = $val;
            } elseif (defined($values['cfg'])) {
                //$os = constant($values['cfg']);
            } elseif ($values['template'] != null) {
                /*
                $req_os_by_template = new DbQuery();
                $req_os_by_template->select('DISTINCT osl.id_order_state');
                $req_os_by_template->from('order_state_lang', 'osl');
                $req_os_by_template->where('osl.template = \''.pSQL($values['template'].'\''));
                $res_os_by_template = Db::getInstance()->getValue($req_os_by_template);

                if ($res_os_by_template) {
                    $os = $res_os_by_template;
                }
                */
            }
            if ((int)$os == 0) {
                $log->info('Creating new order state.');
                $order_state = new OrderState($os);
                $order_state->logable = $values['logable'];
                $order_state->send_email = $values['send_email'];
                $order_state->paid = $values['paid'];
                $order_state->module_name = $values['module_name'];
                $order_state->hidden = $values['hidden'];
                $order_state->delivery = $values['delivery'];
                $order_state->invoice = $values['invoice'];
                $order_state->color = $values['color'];
                foreach (Language::getLanguages(false) as $lang) {
                    $order_state->template[$lang['id_lang']] = $values['template'];
                    if ($lang['iso_code'] == 'en') {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [PayPlug]';
                    } elseif ($lang['iso_code'] == 'fr') {
                        $order_state->name[$lang['id_lang']] = $values['name']['fr'].' [PayPlug]';
                    } elseif ($lang['iso_code'] == 'es') {
                        $order_state->name[$lang['id_lang']] = $values['name']['es'].' [PayPlug]';
                    } elseif ($lang['iso_code'] == 'it') {
                        $order_state->name[$lang['id_lang']] = $values['name']['it'].' [PayPlug]';
                    } else {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [PayPlug]';
                    }
                }
                $order_state->add();
                $os = (int)$order_state->id;
                $log->info('ID: '.$os);
            }
            Configuration::updateValue($key_config, (int)$os);

            //TEST
            $log->info('Test context.');
            if ((int)Configuration::get($key_config_test) != 0) {
                $os_test = (int)Configuration::get($key_config_test);
            } elseif ($val = $this->findOrderState($values['name'], true)) {
                $os_test = $val;
            }
            if ((int)$os_test == 0) {
                $log->info('Creating new order state.');
                $order_state = new OrderState($os_test);
                $order_state->logable = $values['logable'];
                $order_state->send_email = $values['send_email'];
                $order_state->paid = $values['paid'];
                $order_state->module_name = $values['module_name'];
                $order_state->hidden = $values['hidden'];
                $order_state->delivery = $values['delivery'];
                $order_state->invoice = $values['invoice'];
                $order_state->color = $values['color'];
                foreach (Language::getLanguages(false) as $lang) {
                    $order_state->template[$lang['id_lang']] = $values['template'];
                    if ($lang['iso_code'] == 'en') {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [TEST]';
                    } elseif ($lang['iso_code'] == 'fr') {
                        $order_state->name[$lang['id_lang']] = $values['name']['fr'].' [TEST]';
                    } elseif ($lang['iso_code'] == 'es') {
                        $order_state->name[$lang['id_lang']] = $values['name']['es'].' [TEST]';
                    } elseif ($lang['iso_code'] == 'it') {
                        $order_state->name[$lang['id_lang']] = $values['name']['it'].' [TEST]';
                    } else {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [TEST]';
                    }
                }
                $order_state->add();
                $os_test = (int)$order_state->id;
                $log->info('ID: '.$os);
            }
            Configuration::updateValue($key_config_test, (int)$os_test);
        }
        $log->info('Order state creation ended.');
        return true;
    }

    /**
     * Find id_order_state by name
     *
     * @param array $name
     * @param bool $test_mode
     * @return int OR bool
     */
    private function findOrderState($name, $test_mode = false)
    {
        if (!is_array($name) || empty($name)) {
            return false;
        } else {
            $req_order_state = new DbQuery();
            $req_order_state->select('DISTINCT osl.id_order_state');
            $req_order_state->from('order_state_lang', 'osl');
            $req_order_state->where('osl.name LIKE \''.pSQL($name['en'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\' 
				OR osl.name LIKE \''.pSQL($name['fr'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\' 
				OR osl.name LIKE \''.pSQL($name['es'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\' 
				OR osl.name LIKE \''.pSQL($name['it'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\'');
            $res_order_state = Db::getInstance()->getValue($req_order_state);

            if (!$res_order_state) {
                return false;
            } else {
                return (int)$res_order_state;
            }
        }
    }

    /**
     * Install SQL tables used by module
     *
     * @return bool
     */
    private function installSQL()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
        $log->info('Installation SQL Starting.');

        if (!defined('_MYSQL_ENGINE_')) {
            define('_MYSQL_ENGINE_', 'InnoDB');
        }

        $req_payplug_lock = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payplug_lock` (
            `id_payplug_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `id_order` VARCHAR(100),
            `date_add` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\',
            `date_upd` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'
            ) ENGINE='._MYSQL_ENGINE_;
        $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

        if (!$res_payplug_lock) {
            $log->error('Installation SQL failed: PAYPLUG_LOCK.');
            return false;
        }

        $req_payplug_card = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payplug_card` (
            `id_customer` int(11) UNSIGNED NOT NULL,
            `id_payplug_card` int(11) UNSIGNED NOT NULL,
            `id_company` int(11) UNSIGNED NOT NULL,
            `is_sandbox` int(1) UNSIGNED NOT NULL,
            `id_card` varchar(255) NOT NULL,
            `last4` varchar(4) NOT NULL,
            `exp_month` varchar(4) NOT NULL,
            `exp_year` varchar(4) NOT NULL,
            `brand` varchar(255) DEFAULT NULL,
            `country` varchar(3) NOT NULL,
            `metadata` varchar(255) DEFAULT NULL,
            PRIMARY KEY (`id_customer`,`id_payplug_card`, `id_company`, `is_sandbox`)
            ) ENGINE='._MYSQL_ENGINE_;
        $res_payplug_card = DB::getInstance()->Execute($req_payplug_card);

        if (!$res_payplug_card) {
            $log->error('Installation SQL failed: PAYPLUG_CARD.');
            return false;
        }

        $req_payplug_payment_cart = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payplug_payment_cart` (
            `id_payplug_payment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_payment` VARCHAR(255) NOT NULL,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `is_pending` TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE='._MYSQL_ENGINE_;
        $res_payplug_payment_cart = DB::getInstance()->Execute($req_payplug_payment_cart);

        if (!$res_payplug_payment_cart) {
            $log->error('Installation SQL failed: PAYPLUG_PAYMENT_CART.');
            return false;
        }

        $log->info('Installation SQL ended.');
        return true;
    }

    /**
     * Remove SQL tables used by module
     *
     * @return bool
     */
    private function uninstallSQL($keep_cards = false)
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');
        $log->info('Uninstallation SQL starting.');
        $req_payplug_lock = '
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'payplug_lock`';
        $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

        if (!$res_payplug_lock) {
            $log->error('Uninstallation SQL failed: PAYPLUG_LOCK.');
            return false;
        }

        if (!$keep_cards) {
            $req_payplug_card = '
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'payplug_card`';
            $res_payplug_card = DB::getInstance()->Execute($req_payplug_card);

            if (!$res_payplug_card) {
                $log->error('Uninstallation SQL failed: PAYPLUG_CARD.');
                return false;
            }
        } else {
            $log->info('Cards kept in database.');
        }

        $req_payplug_payment_cart = '
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'payplug_payment_cart`';
        $res_payplug_payment_cart = DB::getInstance()->Execute($req_payplug_payment_cart);

        if (!$res_payplug_payment_cart) {
            $log->error('Uninstallation SQL failed: PAYPLUG_PAYMENT_CART.');
            return false;
        }

        $log->info('Uninstallation SQL ended.');
        return true;
    }

    /**
     * Check if amount is correct
     *
     * @param int $amount
     * @param Order $order
     * @return bool
     */
    public static function checkAmountPaidIsCorrect($amount, $order)
    {
        $order_amount = $order->total_paid;

        if ($amount != 0) {
            return abs($order_amount - $amount) / $amount < 0.00001;
        } elseif ($order_amount != 0) {
            return abs($amount - $order_amount) / $order_amount < 0.00001;
        } else {
            return true;
        }
    }

    /**
     * @see Module::postProcess()
     *
     * @return void
     */
    private function postProcess()
    {
        $curl_exists = extension_loaded('curl');
        $openssl_exists = extension_loaded('openssl');
        if (Tools::isSubmit('submitAccount')) {
            if ((!Validate::isEmail(Tools::getValue('PAYPLUG_EMAIL'))
                    || !Validate::isPasswd(Tools::getValue('PAYPLUG_PASSWORD'))
                )
                && (Tools::getValue('PAYPLUG_EMAIL') != false)
            ) {
                $this->validationErrors['username_password'] = $this->l('The email and/or password was not correct.');
            } elseif ($curl_exists && $openssl_exists) {
                if ($this->login(Tools::getValue('PAYPLUG_EMAIL'), Tools::getValue('PAYPLUG_PASSWORD'))) {
                    Configuration::updateValue('PAYPLUG_EMAIL', Tools::getValue('PAYPLUG_EMAIL'));
                } else {
                    $this->validationErrors['username_password']
                        = $this->l('The email and/or password was not correct.');
                }
            }
        }

        if (Tools::isSubmit('submitDisconnect')) {
            $this->createConfig();
        }

        if (Tools::isSubmit('submitSettings')) {
            Configuration::updateValue('PAYPLUG_SANDBOX_MODE', Tools::getValue('PAYPLUG_SANDBOX_MODE'));
            Configuration::updateValue('PAYPLUG_EMBEDDED_MODE', Tools::getValue('PAYPLUG_EMBEDDED_MODE'));
            Configuration::updateValue('PAYPLUG_ONE_CLICK', Tools::getValue('PAYPLUG_ONE_CLICK'));
            Configuration::updateValue('PAYPLUG_SHOW', Tools::getValue('PAYPLUG_SHOW'));
        }

        if (Tools::isSubmit('submitUninstallSettings')) {
            Configuration::updateValue('PAYPLUG_KEEP_CARDS', Tools::getValue('PAYPLUG_KEEP_CARDS'));
        }
    }

    /**
     * Send cURL request to PayPlug to patch a given payment
     *
     * @param String $api_key
     * @param String $pay_id
     * @param Array $data
     * @return Array
     */
    public function patchPayment($api_key, $pay_id, $data)
    {
        $data_string = json_encode($data);
        $url = $this->api_url.$this->routes['patch'].$pay_id;
        $curl_version = curl_version();
        $process = curl_init($url);
        curl_setopt(
            $process,
            CURLOPT_HTTPHEADER,
            array(
                'Authorization: Bearer '.$api_key,
                'Content-Type:application/json',
                'Content-Length: '.Tools::strlen($data_string)
            )
        );
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($process, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLINFO_HEADER_OUT, true);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
        # >= 7.26 to 7.28.1 add a notice message for value 1 will be remove
        curl_setopt(
            $process,
            CURLOPT_SSL_VERIFYHOST,
            (version_compare($curl_version['version'], '7.21', '<') ? true : 2)
        );
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__).'/cacert.pem'));
        $answer = curl_exec($process);
        $error_curl = curl_errno($process);
        curl_close($process);

        $result = array(
            'status' => false,
            'message' => null,
        );

        if ($error_curl == 0) {
            $json_answer = json_decode($answer);

            if (isset($json_answer->object) && $json_answer->object == 'error') {
                $result['status'] = false;
                $result['message'] = $json_answer->message;
            } else {
                $result['status'] = true;
            }
        } else {
            $result['status'] = false;
            $result['message'] = $this->l('Error while executing cURL request.');
        }
        return $result;
    }
    
    /**
     * login to Payplug API
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    private function login($email, $password)
    {
        $data = array
        (
            'email' => $email,
            'password' => $password
        );
        $data_string = json_encode($data);

        $url = $this->api_url.$this->routes['login'];
        $curl_version = curl_version();
        $process = curl_init($url);
        curl_setopt(
            $process,
            CURLOPT_HTTPHEADER,
            array('Content-Type:application/json',
            'Content-Length: '.Tools::strlen($data_string))
        );
        curl_setopt($process, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($process, CURLOPT_POST, true);
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLINFO_HEADER_OUT, true);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
        # >= 7.26 to 7.28.1 add a notice message for value 1 will be remove
        curl_setopt(
            $process,
            CURLOPT_SSL_VERIFYHOST,
            (version_compare($curl_version['version'], '7.21', '<') ? true : 2)
        );
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__).'/cacert.pem')); //work only wiht cURL 7.10+
        $answer = curl_exec($process);
        $error_curl = curl_errno($process);

        curl_close($process);

        if ($error_curl == 0) {
            $json_answer = json_decode($answer);

            if ($this->setApiKeysbyJsonResponse($json_answer)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Get account permission from Payplug API
     *
     * @param string $api_key
     * @return array OR bool
     */
    public function getAccount($api_key)
    {
        $url = $this->api_url.$this->routes['account'];
        $curl_version = curl_version();
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$api_key));
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLINFO_HEADER_OUT, true);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
        # >= 7.26 to 7.28.1 add a notice message for value 1 will be remove
        curl_setopt(
            $process,
            CURLOPT_SSL_VERIFYHOST,
            (version_compare($curl_version['version'], '7.21', '<') ? true : 2)
        );
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__).'/cacert.pem')); //work only wiht cURL 7.10+
        $answer = curl_exec($process);
        $error_curl = curl_errno($process);

        curl_close($process);

        if ($error_curl == 0) {
            $json_answer = json_decode($answer);

            if ($permissions = $this->treatAccountResponse($json_answer)) {
                return $permissions;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Read API response and return permissions
     *
     * @param string $json_answer
     * @return array OR bool
     */
    private function treatAccountResponse($json_answer)
    {
        if ((isset($json_answer->object) && $json_answer->object == 'error')
            || empty($json_answer)
        ) {
            return false;
        }

        $id = $json_answer->id;

        $configuration = array(
            'currencies' => Configuration::get('PAYPLUG_CURRENCIES'),
            'min_amounts' => Configuration::get('PAYPLUG_MIN_AMOUNTS'),
            'max_amounts' => Configuration::get('PAYPLUG_MAX_AMOUNTS'),
        );
        if (isset($json_answer->configuration)) {
            if (isset($json_answer->configuration->currencies)
                && !empty($json_answer->configuration->currencies)
                && sizeof($json_answer->configuration->currencies)
            ) {
                $configuration['currencies'] = array();
                foreach ($json_answer->configuration->currencies as $value) {
                    $configuration['currencies'][] = $value;
                }
            }
            if (isset($json_answer->configuration->min_amounts)
                && !empty($json_answer->configuration->min_amounts)
                && sizeof($json_answer->configuration->min_amounts)
            ) {
                $configuration['min_amounts'] = '';
                foreach ($json_answer->configuration->min_amounts as $key => $value) {
                    $configuration['min_amounts'] .= $key.':'.$value.';';
                }
                $configuration['min_amounts'] = Tools::substr($configuration['min_amounts'], 0, -1);
            }
            if (isset($json_answer->configuration->max_amounts)
                && !empty($json_answer->configuration->max_amounts)
                && sizeof($json_answer->configuration->max_amounts)
            ) {
                $configuration['max_amounts'] = '';
                foreach ($json_answer->configuration->max_amounts as $key => $value) {
                    $configuration['max_amounts'] .= $key.':'.$value.';';
                }
                $configuration['max_amounts'] = Tools::substr($configuration['max_amounts'], 0, -1);
            }
        }

        $permissions = array(
            'use_live_mode' => $json_answer->permissions->use_live_mode,
            'can_save_cards' => $json_answer->permissions->can_save_cards,
        );

        $currencies = implode(';', $configuration['currencies']);
        Configuration::updateValue('PAYPLUG_CURRENCIES', $currencies);
        Configuration::updateValue('PAYPLUG_MIN_AMOUNTS', $configuration['min_amounts']);
        Configuration::updateValue('PAYPLUG_MAX_AMOUNTS', $configuration['max_amounts']);
        Configuration::updateValue('PAYPLUG_COMPANY_ID', $id);

        return $permissions;
    }

    /**
     * Register API Keys
     *
     * @param string $json_answer
     * @return bool
     */
    private function setApiKeysbyJsonResponse($json_answer)
    {
        if (isset($json_answer->object) && $json_answer->object == 'error') {
            return false;
        }

        $api_keys= array();
        $api_keys['test_key'] = '';
        $api_keys['live_key'] = '';

        if (isset($json_answer->secret_keys)) {
            if (isset($json_answer->secret_keys->test)) {
                $api_keys['test_key'] = $json_answer->secret_keys->test;
            }
            if (isset($json_answer->secret_keys->live)) {
                $api_keys['live_key'] = $json_answer->secret_keys->live;
            }
        }
        Configuration::updateValue('PAYPLUG_TEST_API_KEY', $api_keys['test_key']);
        Configuration::updateValue('PAYPLUG_LIVE_API_KEY', $api_keys['live_key']);

        return true;
    }

    /**
     * @see Module::getContent()
     *
     * @return string
     */
    public function getContent()
    {
        if (Tools::getValue('_ajax') == 1) {
            $this->adminAjaxController();
        }

        $this->postProcess();

        if (Tools::getValue('uninstall_config') == 1) {
            return $this->getUninstallContent();
        }

        $this->html = '';
        
        $this->checkConfiguration();

        $PAYPLUG_SHOW = Configuration::get('PAYPLUG_SHOW');
        $PAYPLUG_EMAIL = Configuration::get('PAYPLUG_EMAIL');
        $PAYPLUG_SANDBOX_MODE = Configuration::get('PAYPLUG_SANDBOX_MODE');
        $PAYPLUG_EMBEDDED_MODE = Configuration::get('PAYPLUG_EMBEDDED_MODE');
        $PAYPLUG_ONE_CLICK = Configuration::get('PAYPLUG_ONE_CLICK');
        $PAYPLUG_TEST_API_KEY = Configuration::get('PAYPLUG_TEST_API_KEY');
        $PAYPLUG_LIVE_API_KEY = Configuration::get('PAYPLUG_LIVE_API_KEY');
        $PAYPLUG_DEBUG_MODE = Configuration::get('PAYPLUG_DEBUG_MODE');

        if (!empty($PAYPLUG_EMAIL) && (!empty($PAYPLUG_TEST_API_KEY) || !empty($PAYPLUG_LIVE_API_KEY))) {
            $connected = true;
        } else {
            $connected = false;
        }

        if (count($this->validationErrors && !$connected)) {
            $this->context->smarty->assign(array(
                'validationErrors' => $this->validationErrors,
            ));
        }

        $valid_key = self::setAPIKey();
        if (!empty($valid_key)) {
            $permissions = $this->getAccount($valid_key);
            $premium = $permissions['can_save_cards'];
        } else {
            $verified = false;
            $premium = false;
        }
        if (!empty($PAYPLUG_LIVE_API_KEY)) {
            $verified = true;
        } else {
            $verified = false;
        }

        $is_active = (!empty($PAYPLUG_SHOW) && $PAYPLUG_SHOW == 1) ? true : false;

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
            $this->context->smarty->assign(array(
                'p_error' => $p_error,
            ));
        } else {
            $this->context->smarty->assign(array(
                'PAYPLUG_EMAIL' => $PAYPLUG_EMAIL,
            ));
        }

        $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin.css');

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $login_infos = array(
            //'p_error'	=> $p_error,
        );

        $this->context->smarty->assign(array(
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => $this->version,
            'connected' => $connected,
            'verified' => $verified,
            'premium' => $premium,
            'is_active' => $is_active,
            'site_url' => $this->site_url,
            'PAYPLUG_SANDBOX_MODE' => $PAYPLUG_SANDBOX_MODE,
            'PAYPLUG_EMBEDDED_MODE' => $PAYPLUG_EMBEDDED_MODE,
            'PAYPLUG_ONE_CLICK' => $PAYPLUG_ONE_CLICK,
            'PAYPLUG_SHOW' => $PAYPLUG_SHOW,
            'PAYPLUG_DEBUG_MODE' => $PAYPLUG_DEBUG_MODE,
            'login_infos' => $login_infos,
        ));

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

        return $this->html;
    }

    private function getUninstallContent()
    {
        $this->postProcess();
        $this->html = '';

        $PAYPLUG_KEEP_CARDS = (int)Configuration::get('PAYPLUG_KEEP_CARDS');

        $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin.css');

        $this->context->smarty->assign(array(
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'site_url' => $this->site_url,
            'PAYPLUG_KEEP_CARDS' => $PAYPLUG_KEEP_CARDS,
        ));

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin_uninstall_configuration.tpl');

        return $this->html;
    }

    /**
     * Fetch smarty template
     *
     * @param string $file
     * @return string
     */
    public function fetchTemplateRC($file)
    {
        $output = $this->display(__FILE__, $file);
        return $output;
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

        $this->context->smarty->assign(array(
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'pp_version' => $this->version,
        ));
        $this->html = $this->fetchTemplateRC('/views/templates/admin/fieldset.tpl');

        return $this->html;
    }

    public function getLogin()
    {
        $this->postProcess();

        $this->html = '';

        $this->checkConfiguration();

        $PAYPLUG_SHOW = Configuration::get('PAYPLUG_SHOW');
        $PAYPLUG_EMAIL = Configuration::get('PAYPLUG_EMAIL');
        $PAYPLUG_SANDBOX_MODE = Configuration::get('PAYPLUG_SANDBOX_MODE');
        $PAYPLUG_EMBEDDED_MODE = Configuration::get('PAYPLUG_EMBEDDED_MODE');
        $PAYPLUG_ONE_CLICK = Configuration::get('PAYPLUG_ONE_CLICK');
        $PAYPLUG_TEST_API_KEY = Configuration::get('PAYPLUG_TEST_API_KEY');
        $PAYPLUG_LIVE_API_KEY = Configuration::get('PAYPLUG_LIVE_API_KEY');
        $PAYPLUG_DEBUG_MODE = Configuration::get('PAYPLUG_DEBUG_MODE');

        if (!empty($PAYPLUG_EMAIL) && (!empty($PAYPLUG_TEST_API_KEY) || !empty($PAYPLUG_LIVE_API_KEY))) {
            $connected = true;
        } else {
            $connected = false;
        }

        if (count($this->validationErrors && !$connected)) {
            $this->context->smarty->assign(array(
                'validationErrors' => $this->validationErrors,
            ));
        }

        $valid_key = self::setAPIKey();
        if (!empty($valid_key)) {
            $permissions = $this->getAccount($valid_key);
            $premium = $permissions['can_save_cards'];
        } else {
            $verified = false;
            $premium = false;
        }
        if (!empty($PAYPLUG_LIVE_API_KEY)) {
            $verified = true;
        } else {
            $verified = false;
        }

        $is_active = (!empty($PAYPLUG_SHOW) && $PAYPLUG_SHOW == 1) ? true : false;

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
            $this->context->smarty->assign(array(
                'p_error' => $p_error,
            ));
        } else {
            $this->context->smarty->assign(array(
                'PAYPLUG_EMAIL' => $PAYPLUG_EMAIL,
            ));
        }

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $login_infos = array(
            //'p_error'	=> $p_error,
        );

        $this->context->smarty->assign(array(
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
            'connected' => $connected,
            'verified' => $verified,
            'premium' => $premium,
            'is_active' => $is_active,
            'site_url' => $this->site_url,
            'PAYPLUG_SANDBOX_MODE' => $PAYPLUG_SANDBOX_MODE,
            'PAYPLUG_EMBEDDED_MODE' => $PAYPLUG_EMBEDDED_MODE,
            'PAYPLUG_ONE_CLICK' => $PAYPLUG_ONE_CLICK,
            'PAYPLUG_SHOW' => $PAYPLUG_SHOW,
            'PAYPLUG_DEBUG_MODE' => $PAYPLUG_DEBUG_MODE,
            'login_infos' => $login_infos,
        ));
        $this->html = $this->fetchTemplateRC('/views/templates/admin/login.tpl');

        return $this->html;
    }

    private function checkRequirements()
    {
        $php_min_version = 50300;
        $curl_min_version = '7.21';
        $openssl_min_version = 0x009080ff;
        $report = array(
            'php' => array(
                'version' => 0,
                'installed' => true,
                'up2date' => false,
            ),
            'curl' => array(
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ),
            'openssl' => array(
                'version' => 0,
                'installed' => false,
                'up2date' => false,
            ),
        );

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
            $report['curl']['up2date'] = version_compare($curl_version['version'], $curl_min_version, '>=') ? true : false;
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

        $this->check_configuration = array('warning' => array(), 'error' => array(), 'success' => array());

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

        $check_warning = $this->l('Unfortunately at least one issue is preventing you from using Payplug.').' '
            .$this->l('Refresh the page or click "Check" once they are fixed');
        if ($is_payplug_configured) {
        } else {
            Configuration::get('PAYPLUG_SHOW', 0);
            $this->check_configuration['warning'][] .= $check_warning;
        }

        return true;
    }

    /**
     * Display the right pop-in
     *
     * @param string $type
     * @param array $args
     * @return string
     */
    public function displayPopin($type, $args = null)
    {
        if ($type == 'confirm') {
            $this->context->smarty->assign(array(
                'sandbox' => $args['sandbox'],
                'embedded' => $args['embedded'],
                'one_click' => $args['one_click'],
                'activate' => $args['activate'],
            ));
        }

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $this->context->smarty->assign(array(
            'type' => $type,
            'admin_ajax_url' => $admin_ajax_url,
            'site_url' => $this->site_url,
        ));
        $this->html = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

        die(json_encode(array('content' => $this->html)));
    }

    /**
     * Include js script in template
     *
     * @param string $js_uri
     * @return void
     */
    public function addJsRC($js_uri)
    {
        $this->context->controller->addJS($js_uri);
    }

    /**
     * Include css in template
     *
     * @param string $css_uri
     * @param string $css_media_type
     * @return void
     */
    public function addCSSRC($css_uri, $css_media_type = 'all')
    {
        $this->context->controller->addCSS($css_uri, $css_media_type);
    }

    /**
     * Register payment for later use
     *
     * @param string $pay_id
     * @param int $id_cart
     * @return bool
     */
    private function storePayment($pay_id, $id_cart)
    {
        $req_payment_cart_exists = new DbQuery();
        $req_payment_cart_exists->select('*');
        $req_payment_cart_exists->from('payplug_payment_cart', 'ppc');
        $req_payment_cart_exists->where('ppc.id_cart = '.(int)$id_cart);
        $res_payment_cart_exists = Db::getInstance()->getRow($req_payment_cart_exists);

        if (!$res_payment_cart_exists) {
            //insert
            $req_payment_cart = '
                INSERT INTO '._DB_PREFIX_.'payplug_payment_cart (id_payment, id_cart) 
                VALUES (\''.pSQL($pay_id).'\', '.(int)$id_cart.')';
            $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
            if (!$res_payment_cart) {
                return false;
            }
        } else {
            //update
            $req_payment_cart = '
                UPDATE '._DB_PREFIX_.'payplug_payment_cart ppc  
                SET ppc.id_payment = \''.pSQL($pay_id).'\'
                WHERE ppc.id_cart = '.(int)$id_cart;
            $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
            if (!$res_payment_cart) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve payment stored
     *
     * @param int $cart_id
     * @return int OR bool
     */
    public function getPaymentByCart($cart_id)
    {
        $req_payment_cart = new DbQuery();
        $req_payment_cart->select('ppc.id_payment');
        $req_payment_cart->from('payplug_payment_cart', 'ppc');
        $req_payment_cart->where('ppc.id_cart = '.(int)$cart_id);
        $res_payment_cart = Db::getInstance()->getValue($req_payment_cart);

        if (!$res_payment_cart) {
            return false;
        }

        return $res_payment_cart;
    }

    /**
     * Register transaction as pending to etablish link with order in case of error
     *
     * @param int $id_cart
     * @return bool
     */
    public function registerPendingTransaction($id_cart)
    {
        $req_payment_cart = '
            UPDATE '._DB_PREFIX_.'payplug_payment_cart ppc  
            SET ppc.is_pending = 1
            WHERE ppc.id_cart = '.(int)$id_cart;
        $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get id_payment from a pending transaction for a given cart
     *
     * @param int $id_cart
     * @return string id_payment OR bool
     */
    public function isTransactionPending($id_cart)
    {
        $req_payment_cart = '
            SELECT ppc.id_payment 
            FROM '._DB_PREFIX_.'payplug_payment_cart ppc  
            WHERE ppc.id_cart = '.(int)$id_cart.'
            AND ppc.is_pending = 1';
        $res_payment_cart = Db::getInstance()->getValue($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        } else {
            return $res_payment_cart;
        }
    }

    /**
     * Delete stored payment
     *
     * @param string $pay_id
     * @param array $cart_id
     * @return bool
     */
    public function deletePayment($pay_id, $cart_id)
    {
        $req_payment_cart = '
            DELETE FROM '._DB_PREFIX_.'payplug_payment_cart  
            WHERE id_cart = '.(int)$cart_id.' 
            AND id_payment = \''.pSQL($pay_id).'\'';
        $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        }

        return true;
    }

    /**
     * check if currency is allowed
     *
     * @param Cart $cart
     * @return bool
     */
    private function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);
        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    $supported_currencies = $this->getSupportedCurrencies();
                    if (in_array(Tools::strtoupper($currency_module['iso_code']), $supported_currencies)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if amount is correct
     *
     * @param Cart $cart
     * @return bool
     */
    private function checkAmount($cart)
    {
        $currency = new Currency($cart->id_currency);
        $amounts_by_currency = $this->getAmountsByCurrency($currency->iso_code);
        $amount = $cart->getOrderTotal(true, Cart::BOTH) * 100;
        if ($amount < $amounts_by_currency['min_amount'] || $amount > $amounts_by_currency['max_amount']) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get amounts with the right currency
     *
     * @param string $iso_code
     * @return array
     */
    private function getAmountsByCurrency($iso_code)
    {
        $min_amounts = array();
        $max_amounts = array();
        foreach (explode(';', Configuration::get('PAYPLUG_MIN_AMOUNTS')) as $amount_cur) {
            $cur = array();
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $min_amounts[$cur[1]] = (int)$cur[2];
        }
        foreach (explode(';', Configuration::get('PAYPLUG_MAX_AMOUNTS')) as $amount_cur) {
            $cur = array();
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $max_amounts[$cur[1]] = (int)$cur[2];
        }
        $current_min_amount = $min_amounts[$iso_code];
        $current_max_amount = $max_amounts[$iso_code];

        return array('min_amount' => $current_min_amount, 'max_amount' => $current_max_amount);
    }

    /**
     * Get supported currencies
     *
     * @return array
     */
    private function getSupportedCurrencies()
    {
        $currencies = array();
        foreach (explode(';', Configuration::get('PAYPLUG_MIN_AMOUNTS')) as $amount_cur) {
            $cur = array();
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $currencies[] = Tools::strtoupper($cur[1]);
        }

        return $currencies;
    }

    /**
    * Get all country iso-code of ISO 3166-1 alpha-2 norm
    * Source: DB PayPlug
    *
    * @return array | null
    */
    private function getIsoCodeList()
    {
        $country_list_path = _PS_MODULE_DIR_.'payplug/lib/iso_3166-1_alpha-2/data.csv';
        $iso_code_list = array();
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
    * Get the right country iso-code or null if it does'nt fit the ISO 3166-1 alpha-2 norm
    *
    * @param int $country_id
    * @return int | null
    */
    private function getIsoCodeByCountryId($country_id)
    {
        $iso_code_list = $this->getIsoCodeList();
        if (!is_array($iso_code_list) || empty($iso_code_list) || !count($iso_code_list)) {
            return null;
        }
        if (!Validate::isInt($country_id)) {
            return null;
        }
        $country = new Country((int)$country_id);
        if (!Validate::isLoadedObject($country)) {
            return null;
        }
        if (!in_array(Tools::strtoupper($country->iso_code), $iso_code_list)) {
            return null;
        } else {
            return Tools::strtoupper($country->iso_code);
        }
    }

    /**
     * prepare payment
     *
     * @param int $id_cart
     * @param string $id_card
     * @return mixed
     */
    public function preparePayment($id_cart, $id_card = null)
    {
        $cart = new Cart((int)$id_cart);
        if (!Validate::isLoadedObject($cart)) {
            return false;
        }
        $one_click = (int)Configuration::get('PAYPLUG_ONE_CLICK');
        $embedded_mode = (int)Configuration::get('PAYPLUG_EMBEDDED_MODE');
        $current_card = null;
        if ($id_card != null && $id_card != 'new_card') {
            $current_card = $this->getCardId(
                (int)$cart->id_customer,
                $id_card,
                (int)Configuration::get('PAYPLUG_COMPANY_ID')
            );
        }
        //currency
        $result_currency = array();
        $currency = $cart->id_currency;
        $result_currency = Currency::getCurrency($currency);
        $supported_currencies = explode(';', Configuration::get('PAYPLUG_CURRENCIES'));

        if (!in_array($result_currency['iso_code'], $supported_currencies)) {
            return false;
        }

        $currency = $result_currency['iso_code'];

        //amount
        $amount = $cart->getOrderTotal(true, Cart::BOTH);

        //$amount = round($amount, 2) * 100;
        $amount = (int)(round(($amount * 100), PHP_ROUND_HALF_UP));
        $current_amounts = $this->getAmountsByCurrency($currency);
        $current_min_amount = $current_amounts['min_amount'];
        $current_max_amount = $current_amounts['max_amount'];

        if ($amount < $current_min_amount || $amount > $current_max_amount) {
            return false;
        }

        //customer
        $customer = new Customer((int)$cart->id_customer);
        //$address_invoice = new Address((int)$cart->id_address_invoice);
        $address_delivery = new Address((int)$cart->id_address_delivery);
        //$country = new Country((int)$address_invoice->id_country);
        $country = new Country((int)$address_delivery->id_country);
        $country_iso_code = $this->getIsoCodeByCountryId((int)$country->id);

        $payment_customer = array(
            'first_name'        => !empty($customer->firstname) ? $customer->firstname : null,
            'last_name'         => !empty($customer->lastname) ? $customer->lastname : null,
            'email'             => $customer->email,
            'address1'          => !empty($address_delivery->address1) ? $address_delivery->address1 : null,
            'address2'          => !empty($address_delivery->address2) ? $address_delivery->address2 : null,
            'postcode'          => !empty($address_delivery->postcode) ? $address_delivery->postcode : null,
            'city'              => !empty($address_delivery->city) ? $address_delivery->city : null,
            'country'           => $country_iso_code,
        );

        //hosted payment
        $return_url = $this->context->link->getModuleLink($this->name, 'validation', array('ps' => 1, 'cartid' => (int)$cart->id), true);
        $cancel_url = $this->context->link->getModuleLink($this->name, 'validation', array('ps' => 2, 'cartid' => (int)$cart->id), true);

        if ($one_click != 1 || ($one_click == 1 && ($id_card == null || $id_card == 'new_card'))) {
            $hosted_payment = array(
                'return_url'        => $return_url,
                'cancel_url'        => $cancel_url
            );
        }

        //notification
        $notification_url = $this->context->link->getModuleLink($this->name, 'ipn', array(), true);

        //payment method
        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $payment_method = $current_card;
        }

        //force 3ds
        $force_3ds = false;

        //save card
        //$save_card = false;
        $allow_save_card = false;
        if ($one_click == 1 && Cart::isGuestCartByCartId($cart->id) != 1) {
            //$save_card = true;
            $allow_save_card = true;
        }

        //meta data
        $baseurl = Tools::getShopDomainSsl(true, false);
        /*
        if (Tools::getShopProtocol() == 'https://') {
            $baseurl = _PS_BASE_URL_SSL_;
        } else {
            $baseurl = Tools::getHttpHost(true);
        }
        */
        $metadata = array(
            'customer_id'   => (int)$customer->id,
            'cart_id'       => (int)$cart->id,
            'website'       => $baseurl,
        );

        //payment
        $payment_tab = array(
            'amount'            => $amount,
            'currency'          => $currency,
            'customer'          => array(
                'email'             => $payment_customer['email'],
                'first_name'        => $payment_customer['first_name'],
                'last_name'         => $payment_customer['last_name'],
                'address1'          => $payment_customer['address1'],
                'address2'          => $payment_customer['address2'],
                'postcode'          => $payment_customer['postcode'],
                'city'              => $payment_customer['city'],
                'country'           => $payment_customer['country'],
            ),
            'notification_url'  => $notification_url,
            'force_3ds'         => $force_3ds,
            'metadata'          => array(
                'ID Client'     => $metadata['customer_id'],
                'ID Cart'           => $metadata['cart_id'],
                'Website'           => $metadata['website'],
            )
        );

        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $payment_tab['payment_method'] = $payment_method;
            //$payment_tab['save_card'] = false;
            $payment_tab['allow_save_card'] = false;
        } else {
            $payment_tab['hosted_payment'] = array(
                'return_url'        => $hosted_payment['return_url'],
                'cancel_url'        => $hosted_payment['cancel_url'],
            );
            //$payment_tab['save_card'] = $save_card;
            $payment_tab['allow_save_card'] = $allow_save_card;
        }

        try {
            if (Configuration::get('PAYPLUG_DEBUG_MODE')) {
                $log = new MyLogPHP(_PS_MODULE_DIR_.$this->name.'/log/prepare_payment.csv');
                $log->info('Starting payment.');
                foreach ($payment_tab as $key => $value) {
                    if (is_array($value)) {
                        foreach ($value as $n_key => $n_value) {
                            $log->info($n_key.' : '.$n_value);
                        }
                    } else {
                        $log->info($key.' : '.$value);
                    }
                }
            }
            $payment = \Payplug\Payment::create($payment_tab);
            if (
                ($payment->is_paid == false && $one_click == 1 && $current_card != null && $id_card != 'new_card')
                || ($payment->failure == true && !empty($payment->failure['message']))
            ) {
                $data = array(
                    'result' => false,
                    'response' => $payment->failure['message'],
                );
                return($data);
            }
        } catch (Exception $e) {
            $data = array(
                'result' => false,
                'response' => $e,
            );
            return($data);
        }
        $this->storePayment($payment->id, (int)$cart->id);
        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $data = array(
                'result' => true,
                'validation_url' => $return_url
            );
            return($data);
        } elseif (($one_click == 1 && $id_card == 'new_card') || ($one_click != 1 && $id_card == 'new_card')) {
            $data = array(
                'result' => 'new_card',
                'embedded_mode' => (int)$embedded_mode,
                'payment_url' => $payment->hosted_payment->payment_url,
            );
            die(json_encode($data));
        } else {
            $payment_url = $payment->hosted_payment->payment_url;
            return $payment_url;
        }
    }

    /**
     * get card id
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     * @param int $id_company
     * @return string
     */
    private function getCardId($id_customer, $id_payplug_card, $id_company)
    {
        $is_sandbox = (int)Configuration::get('PAYPLUG_SANDBOX_MODE');

        $req_card_id = new DbQuery();
        $req_card_id->select('pc.id_card');
        $req_card_id->from('payplug_card', 'pc');
        $req_card_id->where('pc.id_customer = '.(int)$id_customer);
        $req_card_id->where('pc.id_payplug_card = '.(int)$id_payplug_card);
        $req_card_id->where('pc.id_company = '.(int)$id_company);
        $req_card_id->where('pc.is_sandbox = '.(int)$is_sandbox);
        $res_card_id =  Db::getInstance()->getValue($req_card_id);

        if (!$res_card_id) {
            return false;
        } else {
            return $res_card_id;
        }
    }

    /**
     * Determine witch environnement is used
     *
     * @param PayplugPayment $payment
     * @return bool
     */
    public function saveCard($payment)
    {
        $brand = $payment->card->brand;
        if (Tools::strtolower($brand) != 'mastercard' && Tools::strtolower($brand) != 'visa') {
            $brand = 'none';
        }

        $customer_id = (int)$payment->metadata['ID Client'];
        $company_id = (int)Configuration::get('PAYPLUG_COMPANY_ID');
        $is_sandbox = (int)Configuration::get('PAYPLUG_SANDBOX_MODE');

        $req_last_id_payplug_card = new DbQuery();
        $req_last_id_payplug_card->select('MAX(pc.id_payplug_card)');
        $req_last_id_payplug_card->from('payplug_card', 'pc');
        $req_last_id_payplug_card->where('pc.id_customer = '.(int)$customer_id);
        $req_last_id_payplug_card->where('pc.id_company = '.(int)$company_id);
        $req_last_id_payplug_card->where('pc.is_sandbox = '.(int)$is_sandbox);
        $res_last_id_payplug_card = Db::getInstance()->getValue($req_last_id_payplug_card);

        if (!$res_last_id_payplug_card) {
            $new_id_payplug_card = 1;
        } else {
            $new_id_payplug_card = (int)$res_last_id_payplug_card +1;
        }

        $req_payplug_card = '
            INSERT INTO '._DB_PREFIX_.'payplug_card (
                id_customer, 
                id_payplug_card, 
                id_company, 
                is_sandbox, 
                id_card, 
                last4, 
                exp_month, 
                exp_year, 
                brand, 
                country, 
                metadata
            ) 
            VALUE(
                '.(int)$customer_id.', 
                '.(int)$new_id_payplug_card.', 
                '.(int)$company_id.', 
                '.(int)$is_sandbox.', 
                \''.pSQL($payment->card->id).'\', 
                \''.pSQL($payment->card->last4).'\', 
                \''.pSQL($payment->card->exp_month).'\', 
                \''.pSQL($payment->card->exp_year).'\', 
                \''.pSQL($brand).'\', 
                \''.pSQL($payment->card->country).'\', 
                \''.serialize($payment->card->metadata).'\'
            )';
        $res_payplug_card = Db::getInstance()->Execute($req_payplug_card);
        if (!$res_payplug_card) {
            return false;
        }

        return true;
    }

    /**
     * Delete card
     *
     * @param int $id_customer
     * @param int $id_payplug_card
     * @param string $api_key
     * @return bool
     */
    public function deleteCard($id_customer, $id_payplug_card, $api_key)
    {
        $id_company = (int)Configuration::get('PAYPLUG_COMPANY_ID');
        $id_card = $this->getCardId($id_customer, $id_payplug_card, $id_company);
        $url = $this->api_url.'/v1/cards/'.$id_card;
        $curl_version = curl_version();

        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$api_key));
        curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLINFO_HEADER_OUT, true);
        // CURL const are in uppercase
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
        # >= 7.26 to 7.28.1 add a notice message for value 1 will be remove
        curl_setopt(
            $process,
            CURLOPT_SSL_VERIFYHOST,
            (version_compare($curl_version['version'], '7.21', '<') ? true : 2)
        );
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__).'/cacert.pem')); //work only wiht cURL 7.10+
        //$answer = curl_exec($process);
        $error_curl = curl_errno($process);

        curl_close($process);

        // if no error
        if ($error_curl == 0) {
            $req_payplug_card = '
            DELETE FROM '._DB_PREFIX_.'payplug_card
            WHERE '._DB_PREFIX_.'payplug_card.id_card = \''.pSQL($id_card).'\'';
            $res_payplug_card = Db::getInstance()->Execute($req_payplug_card);
            if (!$res_payplug_card) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Delete all cards for a given customer
     *
     * @param int $id_customer
     * @param string $api_key
     * @return bool
     */
    private function deleteCards($id_customer)
    {
        $test_api_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        $live_api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
        $cardsToDelete = $this->getCardsByCustomer($id_customer, false);
        if (!isset($cardsToDelete) && !empty($cardsToDelete) && sizeof($cardsToDelete)) {
            foreach ($cardsToDelete as $card) {
                $api_key = $card['is_sandbox'] == 1 ? $test_api_key : $live_api_key;
                if (!$this->deleteCard($id_customer, $card['id_payplug_card'], $api_key)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get collection of cards
     *
     * @param int $id_customer
     * @param bool $active_only
     * @return array OR bool
     */
    public function getCardsByCustomer($id_customer, $active_only = false)
    {
        $is_sandbox = (int)Configuration::get('PAYPLUG_SANDBOX_MODE');

        $req_payplug_card = new DbQuery();
        $req_payplug_card->select('pc.id_customer, pc.id_payplug_card, pc.id_company, pc.last4, 
              pc. exp_month, pc.exp_year, pc.brand, pc.country, pc.metadata');
        $req_payplug_card->from('payplug_card', 'pc');
        $req_payplug_card->where('pc.id_customer = '.(int)$id_customer);
        $req_payplug_card->where('pc.id_company = '.(int)Configuration::get('PAYPLUG_COMPANY_ID'));
        $req_payplug_card->where('pc.is_sandbox = '.(int)$is_sandbox);
        $res_payplug_card =  Db::getInstance()->executeS($req_payplug_card);

        if (!$res_payplug_card) {
            return false;
        } else {
            foreach ($res_payplug_card as $key => &$value) {
                if ((int)$value['exp_year'] < (int)date('Y')
                    || ((int)$value['exp_year'] == (int)date('Y') && (int)$value['exp_month'] < (int)date('m'))) {
                    $value['expired'] = true;
                    if ($active_only) {
                        unset($res_payplug_card[$key]);
                        continue;
                    }
                } else {
                    $value['expired'] = false;
                }
                $value['expiry_date'] = date(
                    'm / y',
                    mktime(0, 0, 0, (int)$value['exp_month'], 1, (int)$value['exp_year'])
                );
            }
            return $res_payplug_card;
        }
    }

    /**
     * @see Module::hookPaymentOptions()
     *
     * @param array $params
     * @return array
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }
        if (Configuration::get('PAYPLUG_SHOW') == 0) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        if (!$this->checkAmount($params['cart'])) {
            return;
        }

        $embedded_mode = (int)Configuration::get('PAYPLUG_EMBEDDED_MODE');
        $one_click = (int)Configuration::get('PAYPLUG_ONE_CLICK');
        $payplug_cards = $this->getCardsByCustomer((int)$params['cart']->id_customer, true);
        if ($one_click == 1 && !empty($payplug_cards)) {
            if ($embedded_mode == 1) {
                $payment_options = $this->getEmbeddedOneClickPaymentOption(
                    $payplug_cards,
                    (int)$params['cart']->id
                );
            } else {
                $payment_options = $this->getRedirectOneClickPaymentOption($payplug_cards);
            }
        } else {
            if ($embedded_mode == 1) {
                $payment_options = array($this->getEmbeddedPaymentOption((int)$params['cart']->id));
            } else {
                $payment_options = array($this->getRedirectPaymentOption());
            }
        }
        return $payment_options;
    }

    /**
     * get redirect payment option
     *
     * @return array
     */
    private function getRedirectPaymentOption()
    {
        $externalOption = new PaymentOption();
        $externalOption
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', array(), true))
            ->setCallToActionText($this->l('Pay with credit card'))
            ->setModuleName('payplug')
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/logos_schemes_'.$this->img_lang.'.png'));

        return $externalOption;
    }

    /**
     * get embedded payment option
     *
     * @param int $cart_id
     * @return array
     */
    private function getEmbeddedPaymentOption($cart_id)
    {
        $lightbox = 0;
        if ((int)Tools::getValue('lightbox') == 1) {
            $lightbox = 1;
            $payment_url = $this->preparePayment((int)$cart_id);
            $this->context->smarty->assign(array(
                'lightbox' => 1,
                'payment_url' => $payment_url,
                'api_url' => $this->api_url,
            ));
        }

        $paymentOption = new PaymentOption();
        $paymentOption
            ->setCallToActionText($this->l('Pay with credit card'))
            ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array(), true))
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/logos_schemes_'.$this->img_lang.'.png'))
            ->setModuleName('payplug')
            ->setInputs(array(
                'lightbox' => array(
                    'name' =>'lightbox',
                    'type' =>'hidden',
                    'value' =>'1',
                ),
                'disp' => array(
                    'name' =>'disp',
                    'type' =>'hidden',
                    'value' =>'1',
                ),
            ));
        if ($lightbox == 1) {
            $paymentOption->setAdditionalInformation(
                $this->context->smarty->fetch('module:payplug/views/templates/front/embedded.tpl')
            );
        }
        return $paymentOption;
    }

    /**
     * get Redirect OneClick Payment Option
     *
     * @param array $payplug_cards
     * @return array
     */
    private function getRedirectOneClickPaymentOption($payplug_cards)
    {
        $pc = 0;
        $error = 0;
        if ((int)Tools::getValue('error') == 1) {
            $pc = (int)Tools::getValue('pc');
            $error = 1;
        }

        $spinner_url = Tools::getHttpHost(true).__PS_BASE_URI__.'modules/payplug/views/img/admin/spinner.gif';
        $this->context->smarty->assign(array(
            'spinner_url' => $spinner_url,
            'error' => $error,
        ));

        $options = array();
        if (is_array($payplug_cards)) {
            foreach ($payplug_cards as $card) {
                if (!$card['expired']) {
                    $paymentOption = new PaymentOption();
                    $brand = $card['brand'] != 'none' ? Tools::ucfirst($card['brand']) : $this->l('Card');
                    $paymentOption
                        ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/'.strtolower($card['brand']).'.png'))
                        ->setCallToActionText($brand.' **** **** **** '.$card['last4'].' - '.$this->l('Expiry date').': '.$card['expiry_date'])
                        ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array(), true))
                        ->setModuleName('payplug')
                        ->setInputs(array(
                            'pc' => array(
                                'name' =>'pc',
                                'type' =>'hidden',
                                'value' =>(int)$card['id_payplug_card'],
                            ),
                            'disp' => array(
                                'name' =>'disp',
                                'type' =>'hidden',
                                'value' =>'1',
                            ),
                            'pay' => array(
                                'name' =>'pay',
                                'type' =>'hidden',
                                'value' =>'1',
                            ),
                            'id_cart' => array(
                                'name' =>'id_cart',
                                'type' =>'hidden',
                                'value' =>(int)$this->context->cart->id,
                            ),
                        ));
                    if ($pc == (int)$card['id_payplug_card']) {
                        $paymentOption->setAdditionalInformation(
                            $this->context->smarty->fetch('module:payplug/views/templates/front/one_click_status.tpl')
                        );
                    }
                    $options[] = $paymentOption;
                }
            }
            $paymentOption = new PaymentOption();
            $paymentOption
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/none.png'))
                ->setCallToActionText($this->l('Pay with a different card'))
                ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array(), true))
                ->setModuleName('payplug')
                ->setInputs(array(
                    'pc' => array(
                        'name' =>'pc',
                        'type' =>'hidden',
                        'value' =>'new_card',
                    ),
                    'disp' => array(
                        'name' =>'disp',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'pay' => array(
                        'name' =>'pay',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'id_cart' => array(
                        'name' =>'id_cart',
                        'type' =>'hidden',
                        'value' =>(int)$this->context->cart->id,
                    ),
                ));
            $options[] = $paymentOption;
        } else {
            $paymentOption = new PaymentOption();
            $paymentOption
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/logos_schemes_'.$this->img_lang.'.png'))
                ->setCallToActionText($this->l('Pay with a credit card'))
                ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array(), true))
                ->setModuleName('payplug')
                ->setInputs(array(
                    'pc' => array(
                        'name' =>'pc',
                        'type' =>'hidden',
                        'value' =>'new_card',
                    ),
                    'disp' => array(
                        'name' =>'disp',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'pay' => array(
                        'name' =>'pay',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'id_cart' => array(
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => (int)$this->context->cart->id,
                    ),
                ));
            $options[] = $paymentOption;
        }
        return $options;
    }

    /**
     * get Embedded OneClick Payment Option
     *
     * @param array $payplug_cards
     * @param int $cart_id
     * @return array
     */
    private function getEmbeddedOneClickPaymentOption($payplug_cards, $cart_id)
    {
        $lightbox = 0;
        if ((int)Tools::getValue('lightbox') == 1) {
            $lightbox = 1;
            $payment_url = $this->preparePayment((int)$cart_id);
            $this->context->smarty->assign(array(
                'lightbox' => 1,
                'payment_url' => $payment_url,
                'api_url' => $this->api_url,
            ));
        }

        $pc = 0;
        $error = 0;
        if ((int)Tools::getValue('error') == 1) {
            $pc = (int)Tools::getValue('pc');
            $error = 1;
        }

        $spinner_url = Tools::getHttpHost(true).__PS_BASE_URI__.'modules/payplug/views/img/admin/spinner.gif';
        $this->context->smarty->assign(array(
            'spinner_url' => $spinner_url,
            'error' => $error,
        ));

        $options = array();
        if (is_array($payplug_cards)) {
            foreach ($payplug_cards as $card) {
                if (!$card['expired']) {
                    $paymentOption = new PaymentOption();
                    $brand = $card['brand'] != 'none' ? Tools::ucfirst($card['brand']) : $this->l('Card');
                    $paymentOption
                        ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/'.strtolower($card['brand']).'.png'))
                        ->setCallToActionText($brand.' **** **** **** '.$card['last4'].' - '.$this->l('Expiry date').': '.$card['expiry_date'])
                        ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array(), true))
                        ->setModuleName('payplug')
                        ->setInputs(array(
                            'pc' => array(
                                'name' =>'pc',
                                'type' =>'hidden',
                                'value' =>(int)$card['id_payplug_card'],
                            ),
                            'disp' => array(
                                'name' =>'disp',
                                'type' =>'hidden',
                                'value' =>'1',
                            ),
                            'pay' => array(
                                'name' =>'pay',
                                'type' =>'hidden',
                                'value' =>'1',
                            ),
                            'id_cart' => array(
                                'name' => 'id_cart',
                                'type' => 'hidden',
                                'value' => (int)$this->context->cart->id,
                            ),
                        ));
                    if ($pc == (int)$card['id_payplug_card']) {
                        $paymentOption->setAdditionalInformation(
                            $this->context->smarty->fetch('module:payplug/views/templates/front/one_click_status.tpl')
                        );
                    }
                    $options[] = $paymentOption;
                }
            }
            $paymentOption = new PaymentOption();
            $paymentOption
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/none.png'))
                ->setCallToActionText($this->l('Pay with a different card'))
                ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array(), true))
                ->setModuleName('payplug')
                ->setInputs(array(
                    'pc' => array(
                        'name' =>'pc',
                        'type' =>'hidden',
                        'value' =>'new_card',
                    ),
                    'disp' => array(
                        'name' =>'disp',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'pay' => array(
                        'name' =>'pay',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'id_cart' => array(
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => (int)$this->context->cart->id,
                    ),
                    'lightbox' => array(
                        'name' =>'lightbox',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                ));
            if ($lightbox == 1) {
                $paymentOption->setAdditionalInformation(
                    $this->context->smarty->fetch('module:payplug/views/templates/front/embedded.tpl')
                );
            }
            $options[] = $paymentOption;
        } else {
            $paymentOption = new PaymentOption();
            $paymentOption
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/logos_schemes_'.$this->img_lang.'.png'))
                ->setCallToActionText($this->l('Pay with a credit card'))
                ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array(), true))
                ->setModuleName('payplug')
                ->setInputs(array(
                    'pc' => array(
                        'name' =>'pc',
                        'type' =>'hidden',
                        'value' =>'new_card',
                    ),
                    'disp' => array(
                        'name' =>'disp',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'pay' => array(
                        'name' =>'pay',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                    'id_cart' => array(
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => (int)$this->context->cart->id,
                    ),
                    'lightbox' => array(
                        'name' =>'lightbox',
                        'type' =>'hidden',
                        'value' =>'1',
                    ),
                ));
            $options[] = $paymentOption;
            if ($lightbox == 1) {
                $paymentOption->setAdditionalInformation(
                    $this->context->smarty->fetch('module:payplug/views/templates/front/embedded.tpl')
                );
            }
        }
        return $options;
    }

    /**
     * @see Module::hookPaymentReturn()
     *
     * @param array $params
     * @return string
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return null;
        }
        if (Configuration::get('PAYPLUG_SHOW') == 0) {
            return null;
        }
        $order_id = Tools::getValue('id_order');
        $order = new Order($order_id);
        // Check order state to display appropriate message
        $state = null;
        if (isset($order->current_state)
            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PENDING')
        ) {
            $state = 'pending';
        } elseif (isset($order->current_state)
            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PAID')
        ) {
            $state = 'paid';
        } elseif (isset($order->current_state)
            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST')
        ) {
            $state = 'pending_test';
        } elseif (isset($order->current_state)
            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PAID_TEST')
        ) {
            $state = 'paid_test';
        }

        $this->smarty->assign('state', $state);
        // Get order information for display
        $total_paid = number_format($order->total_paid, 2, ',', '');
        $context = array('totalPaid' => $total_paid);
        if (isset($order->reference)) {
            $context['reference'] = $order->reference;
        }
        $this->smarty->assign($context);
        return $this->display(__FILE__, 'confirmation.tpl');
    }

    /**
     * @see Module::hookHeader()
     *
     * @param array $params
     * @return string
     */
    public function hookHeader($params)
    {
        if (!$this->active) {
            return;
        }
        if (Configuration::get('PAYPLUG_SHOW') == 0) {
            return;
        }

        $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/front.css');
        $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/front.js');

        if ((int)Tools::getValue('lightbox') == 1) {
            $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/embedded.js');
        }
    }

    /**
     * Make a refund
     *
     * @param string $pay_id
     * @param int $amount
     * @param string $metadata
     * @param string $pay_mode
     * @return string
     */
    public function makeRefund($pay_id, $amount, $metadata, $pay_mode = 'LIVE')
    {
        $data = array(
            'amount' => (int)$amount,
            'metadata' => $metadata
        );
        if ($pay_mode == 'TEST') {
            \Payplug\Payplug::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
        } else {
            \Payplug\Payplug::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
        }
        try {
            $refund = \Payplug\Refund::create($pay_id, $data);
        } catch (Exception $e) {
            return('error');
        }

        return $refund;
    }

    /**
     * @see Module::enable()
     *
     * @param bool $force_all
     * @return bool
     */
    public function enable($force_all = false)
    {
        return parent::enable($force_all);
    }

    /**
     * @see Module::disable()
     *
     * @param bool $force_all
     * @return bool
     */
    public function disable($force_all = false)
    {
        Configuration::updateValue('PAYPLUG_SHOW', 0);
        parent::disable($force_all);

        $req_disable = '
            UPDATE `'._DB_PREFIX_.'module`
            SET `active`= 0
            WHERE `name` = \''.pSQL($this->name).'\'';

        $res_disable = Db::getInstance()->Execute($req_disable);
        if (!$res_disable) {
            return false;
        }

        return true;
    }

    /**
     * submit password
     *
     * @param string $pwd
     * @return string
     */
    public function submitPopinPwd($pwd)
    {
        $email = Configuration::get('PAYPLUG_EMAIL');
        $connected = $this->login($email, $pwd);
        $use_live_mode = false;

        if ($connected) {
            if (Configuration::get('PAYPLUG_LIVE_API_KEY') != '') {
                $use_live_mode = true;

                $valid_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
                $permissions = $this->getAccount($valid_key);
                $can_save_cards = $permissions['can_save_cards'];
            }
        } else {
            die(json_encode(array('content' => 'wrong_pwd')));
        }
        if (!$use_live_mode) {
            die(json_encode(array('content' => 'activate')));
        } elseif ($can_save_cards) {
            die(json_encode(array('content' => 'live_ok')));
        } else {
            die(json_encode(array('content' => 'live_ok_not_premium')));
        }
    }

    /**
     * Check if account is premium
     *
     * @param string $api_key
     * @return bool
     */
    public function checkPremium($api_key = null)
    {
        if ($api_key == null) {
            $api_key = self::setAPIKey();
        }
        $permissions = $this->getAccount($api_key);
        $use_live_mode = $permissions['use_live_mode'];
        $can_save_cards = $permissions['can_save_cards'];
        if (!$use_live_mode || !$can_save_cards) {
            return false;
        }
        return true;
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

    private function getCurrentApiKey()
    {
        if ((int)Configuration::get('PAYPLUG_SANDBOX_MODE') === 1) {
            return Configuration::get('PAYPLUG_TEST_API_KEY');
        } else {
            return Configuration::get('PAYPLUG_LIVE_API_KEY');
        }
    }

    /**
     * @see Module::hookAdminOrder()
     *
     * @param array $params
     * @return string
     */
    public function hookAdminOrder($params)
    {
        if (!$this->active) {
            return;
        }

        $this->html = '';
        $order = new Order((int)$params['id_order']);
        if (!Validate::isLoadedObject($order)) {
            return false;
        }

        if ($order->module != $this->name) {
            return false;
        }

        if (!$pay_id = $this->isTransactionPending((int)$order->id_cart)){
            $payments = $order->getOrderPaymentCollection();
            if (count($payments) > 1 || !isset($payments[0])) {
                return false;
            } else {
                $pay_id = $payments[0]->transaction_id;
            }
        }

        if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
            if (Configuration::get('PAYPLUG_SANDBOX_MODE') == 1) {
                \Payplug\Payplug::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                    \Payplug\Payplug::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                    return false;
                }
            } elseif (Configuration::get('PAYPLUG_SANDBOX_MODE') == 0) {
                \Payplug\Payplug::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                    \Payplug\Payplug::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                    return false;
                }
            }
        }

        $amount_refunded_presta = $this->getTotalRefunded($order->id);
        $amount_refunded_payplug = ($payment->amount_refunded) / 100;
        $amount_available = ($payment->amount - $payment->amount_refunded) / 100;
        $amount_suggested = (min($amount_refunded_presta, $amount_available) - $amount_refunded_payplug);
        $amount_suggested = number_format((float)$amount_suggested, 2);
        if ($amount_suggested < 0) {
            $amount_suggested = 0;
        }

        $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);

        $id_currency = (int)Currency::getIdByIsoCode($payment->currency);

        $sandbox = ((int)$payment->is_live == 1 ? false : true);
        $id_new_order_state = 0;
        $id_pending_order_state = 0;

        if ($sandbox) {
            $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
            $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST');
        } else {
            $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
            $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING');
        }
        $current_state = (int)$order->getCurrentState();

        $show_popin = false;
        $show_menu = true;
        $show_menu_refunded = false;
        $show_menu_update = false;
        $pay_error = '';

        if ((int)$payment->is_paid == 0) {
            if (isset($payment->failure) && isset($payment->failure->message)) {
                $pay_error = '('.$payment->failure->message.')';
            } else {
                $pay_error = '';
            }
            $show_menu = false;
            if ($current_state != 0 && $current_state == $id_pending_order_state) {
                $show_menu_update = true;
            }
        } elseif ((((int)$payment->amount_refunded > 0) || $amount_refunded_presta > 0) && (int)$payment->is_refunded != 1) {
            $show_menu = true;
        } elseif ((int)$payment->is_refunded == 1) {
            $show_menu_refunded = true;
            $show_menu = false;
        }

        $conf = (int)Tools::getValue('conf');
        if ($conf == 30 || $conf == 31) {
            $show_popin = true;

            $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);

            $this->html .= '
                <a class="pp_admin_ajax_url" href="'.$admin_ajax_url.'"></a>
            ';
        }

        if ($show_popin && $show_menu) {
            $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin_order_popin.js');
        }

        $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin_order.js');
        $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin_order.css');

        $currency = new Currency($id_currency);
        if (!Validate::isLoadedObject($currency)) {
            return false;
        }

        $pay_status = (int)$payment->is_paid == 1 ? $this->l('PAID') : $this->l('NOT PAID');
        if ((int)$payment->is_refunded == 1) {
            $pay_status = $this->l('REFUNDED');
        } elseif ((int)$payment->amount_refunded > 0) {
            $pay_status = $this->l('PARTIALLY REFUNDED');
        }
        $pay_amount = (int)$payment->amount / 100;
        $pay_date = date('d/m/Y H:i', (int)$payment->created_at);
        if ($payment->card->brand != '') {
            $pay_brand = $payment->card->brand;
        } else {
            $pay_brand = $this->l('Unavailable in test mode');
        }
        if ($payment->card->country != '') {
            $pay_brand .= ' '.$this->l('Card').' ('.$payment->card->country.')';
        }
        if ($payment->card->last4 != '') {
            $pay_card_mask = '**** **** **** '.$payment->card->last4;
        } else {
            $pay_card_mask = $this->l('Unavailable in test mode');
        }
        $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
        $pay_mode = $payment->is_live ? $this->l('LIVE') : $this->l('TEST');

        if ($payment->card->exp_month === null) {
            $pay_card_date = $this->l('Unavailable in test mode');
        } else {
            $pay_card_date = date('m/y', strtotime('01.'.$payment->card->exp_month.'.'.$payment->card->exp_year));
        }

        $this->context->smarty->assign(array(
            'logo_url' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'pay_id' => $pay_id,
            'pay_status' => $pay_status,
            'pay_amount' => $pay_amount,
            'pay_date' => $pay_date,
            'pay_brand' => $pay_brand,
            'pay_card_mask' => $pay_card_mask,
            'pay_tds' => $pay_tds,
            'pay_mode' => $pay_mode,
            'pay_card_date' => $pay_card_date,
            'show_menu' => $show_menu,
            'show_menu_refunded' => $show_menu_refunded,
            'show_menu_update' => $show_menu_update,
            'pay_error' => $pay_error,
        ));

        if ($show_menu) {
            $this->context->smarty->assign(array(
                'admin_ajax_url' => $admin_ajax_url,
                'order' => $order,
                'amount_refunded_payplug' => $amount_refunded_payplug,
                'amount_available' => $amount_available,
                'amount_refunded_presta' => $amount_refunded_presta,
                'currency' => $currency,
                'amount_suggested' => $amount_suggested,
                'id_new_order_state' => $id_new_order_state,
            ));
        } elseif ($show_menu_refunded) {
            $this->context->smarty->assign(array(
                'amount_refunded_payplug' => $amount_refunded_payplug,
                'currency' => $currency,
            ));
        } elseif ($show_menu_update) {
            $this->context->smarty->assign(array(
                'admin_ajax_url' => $admin_ajax_url,
                'order' => $order,
            ));
        }
        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin_order.tpl');
        return $this->html;
    }

    /**
     * Generate refund form
     *
     * @param int $amount_refunded_payplug
     * @param int $amount_available
     * @return string
     */
    public function getRefundData($amount_refunded_payplug, $amount_available)
    {
        $this->context->smarty->assign(array(
            'amount_refunded_payplug' => $amount_refunded_payplug,
            'amount_available' => $amount_available,
        ));

        $this->html = $this->fetchTemplateRC('/views/templates/admin/admin_order_refund_data.tpl');

        return $this->html;
    }

    /**
     * Retrieve payment informations
     *
     * @param string $pay_id
     * @return PayplugPayment
     */
    public function retrievePayment($pay_id)
    {
        try {
            $payment = \Payplug\Payment::retrieve($pay_id);
        } catch (Exception $e) {
            return false;
        }

        return $payment;
    }

    /**
     * Get total amount already refunded
     *
     * @param int $id_order
     * @return int
     */
    private function getTotalRefunded($id_order)
    {
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) {
            return false;
        } else {
            $amount_refunded_presta = 0;
            $flag_shipping_refunded = false;

            $order_slips = OrderSlip::getOrdersSlip($order->id_customer, $order->id);
            if (isset($order_slips) && !empty($order_slips) && sizeof($order_slips)) {
                foreach ($order_slips as $order_slip) {
                    $amount_refunded_presta += $order_slip['amount'];
                    if (!$flag_shipping_refunded && $order_slip['shipping_cost'] == 1) {
                        $amount_refunded_presta += $order_slip['shipping_cost_amount'];
                        $flag_shipping_refunded = true;
                    }
                }
            }

            return $amount_refunded_presta;
        }
    }

    /**
     * Check amount to refund
     *
     * @param int $amount
     * @return string
     */
    public function checkAmountToRefund($amount)
    {
        $amount = str_replace(',', '.', $amount);
        return is_numeric($amount);
    }

    public function adminAjaxController()
    {
        if (Tools::getValue('_ajax') == 1) {
            if ((int)Tools::getValue('en') == 1 && (int)Configuration::get('PAYPLUG_SHOW') == 0) {
                Configuration::updateValue('PAYPLUG_SHOW', 1);
                $this->enable();
                die(true);
            }
            if (Tools::getIsset('en')
                && (int)Tools::getValue('en') == 0
                && (int)Configuration::get('PAYPLUG_SHOW') == 1
            ) {
                Configuration::updateValue('PAYPLUG_SHOW', 0);
                die(true);
            }
            if (Tools::getIsset('db')) {
                if (Tools::getValue('db') == 'on') {
                    Configuration::updateValue('PAYPLUG_DEBUG_MODE', 1);
                } elseif (Tools::getValue('db') == 'off') {
                    Configuration::updateValue('PAYPLUG_DEBUG_MODE', 0);
                }
                die(true);
            }
            if ((int)Tools::getValue('popin') == 1) {
                $args = null;
                if (Tools::getValue('type') == 'confirm') {
                    $sandbox = (int)Tools::getValue('sandbox');
                    $embedded = (int)Tools::getValue('embedded');
                    $one_click = (int)Tools::getValue('one_click');
                    $activate = (int)Tools::getValue('activate');
                    $args = array(
                        'sandbox' => $sandbox,
                        'embedded' => $embedded,
                        'one_click' => $one_click,
                        'activate' => $activate,
                    );
                }
                $this->displayPopin(Tools::getValue('type'), $args);
            }
            if (Tools::getValue('submit') == 'submitPopin_pwd') {
                $this->submitPopinPwd(Tools::getValue('pwd'));
            }
            if (Tools::getValue('submit') == 'submitPopin_confirm') {
                die(json_encode(array('content' => 'confirm_ok')));
            }
            if (Tools::getValue('submit') == 'submitPopin_confirm_a') {
                die(json_encode(array('content' => 'confirm_ok_activate')));
            }
            if (Tools::getValue('submit') == 'submitPopin_desactivate') {
                die(json_encode(array('content' => 'confirm_ok_desactivate')));
            }
            if ((int)Tools::getValue('check') == 1) {
                $content = $this->getCheckFieldset();
                die(json_encode(array('content' => $content)));
            }
            if ((int)Tools::getValue('log') == 1) {
                $content = $this->getLogin();
                die(json_encode(array('content' => $content)));
            }
            if ((int)Tools::getValue('checkPremium') == 1) {
                $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
                if ($this->checkPremium($api_key)) {
                    die(true);
                } else {
                    die(false);
                }
            }
            if ((int)Tools::getValue('refund') == 1) {
                if (!$this->checkAmountToRefund(Tools::getValue('amount'))) {
                    die(json_encode(array(
                        'status' => 'error',
                        'data' => $this->l('Incorrect amount to refund')
                    )));
                } else {
                    $amount = str_replace(',', '.', Tools::getValue('amount'));
                    $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
                    $amount = (float)($amount / 10); // unless sometimes 17.90 become 17.89
                    $amount = (int)$amount;
                }

                $id_order = Tools::getValue('id_order');
                $pay_id = Tools::getValue('pay_id');
                $metadata = array(
                    'ID Client' => (int)Tools::getValue('id_customer'),
                    'reason' => 'Refunded with Prestashop'
                );
                $pay_mode = Tools::getValue('pay_mode');
                $refund = $this->makeRefund($pay_id, $amount, $metadata, $pay_mode);
                if ($refund == 'error') {
                    die(json_encode(array(
                        'status' => 'error',
                        'data' => $this->l('Cannot refund that amount.')
                    )));
                } else {
                    $payment = $this->retrievePayment($pay_id);
                    $new_state = 7;
                    if ((int)Tools::getValue('id_state') != 0) {
                        $new_state = (int)Tools::getValue('id_state');
                    } elseif ($payment->is_refunded == 1) {
                        if ($payment->is_live == 1) {
                            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                        } else {
                            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
                        }
                    }

                    $reload = false;
                    if ((int)Tools::getValue('id_state') != 0 || $payment->is_refunded == 1) {
                        $order = new Order((int)$id_order);
                        if (Validate::isLoadedObject($order)) {
                            $current_state = (int)$order->getCurrentState();
                            if ($current_state != 0 && $current_state != $new_state) {
                                $history = new OrderHistory();
                                $history->id_order = (int)$order->id;
                                $history->changeIdOrderState($new_state, (int)$order->id);
                                $history->addWithemail();
                            }
                        }
                        $reload = true;
                    }

                    $amount_refunded_payplug = ($payment->amount_refunded) / 100;
                    $amount_available = ($payment->amount - $payment->amount_refunded) / 100;

                    $data = $this->getRefundData(
                        $amount_refunded_payplug,
                        $amount_available
                    );
                    die(json_encode(array(
                        'status' => 'ok',
                        'data' => $data,
                        'message' => $this->l('Amount successfully refunded.'),
                        'reload' => $reload
                    )));
                }
            }
            if ((int)Tools::getValue('popinRefund') == 1) {
                $popin = $this->displayPopin('refund');
                die(json_encode(array('content' => $popin)));
            }
            if ((int)Tools::getValue('update') == 1) {
                $pay_id = Tools::getValue('pay_id');
                $payment = $this->retrievePayment($pay_id);
                $id_order = Tools::getValue('id_order');

                if ((int)$payment->is_paid == 1) {
                    if ($payment->is_live == 1) {
                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID');
                    } else {
                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID_TEST');
                    }
                } elseif ((int)$payment->is_paid == 0) {
                    if ($payment->is_live == 1) {
                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR');
                    } else {
                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_ERROR_TEST');
                    }
                }

                $order = new Order((int)$id_order);
                if (Validate::isLoadedObject($order)) {
                    $current_state = (int)$order->getCurrentState();
                    if ($current_state != 0 && $current_state != $new_state) {
                        $history = new OrderHistory();
                        $history->id_order = (int)$order->id;
                        $history->changeIdOrderState($new_state, (int)$order->id);
                        $history->addWithemail();
                    }
                }

                //$this->deletePayment($pay_id, $order->id_cart);

                die(json_encode(array(
                    'message' => $this->l('Order successfully updated.'),
                    'reload' => true
                )));
            }
        } else {
            exit;
        }
    }

    public function getAdminAjaxUrl($controller_name = 'AdminModules', $id_order = 0)
    {
        if ($controller_name == 'AdminModules') {
            $admin_ajax_url = 'index.php?controller='.$controller_name.'&configure='.$this->name
                .'&tab_module=payments_gateways&module_name=payplug&token='.Tools::getAdminTokenLite($controller_name);
        } elseif ($controller_name == 'AdminOrders') {
            $admin_ajax_url = 'index.php?controller='.$controller_name.'&id_order='.$id_order
                .'&vieworder&token='.Tools::getAdminTokenLite($controller_name);
        }
        return $admin_ajax_url;
    }

    public function hookCustomerAccount($params)
    {
        if (!$this->active) {
            return;
        }
        if (Configuration::get('PAYPLUG_SHOW') == 0) {
            return;
        }

        $payplug_cards_url = $this->context->link->getModuleLink($this->name, 'cards', array('process' => 'cardlist'), true);

        $this->smarty->assign(array(
            'payplug_cards_url' => $payplug_cards_url
        ));

        return $this->display(__FILE__, 'my_account.tpl');
    }

    public function hookActionDeleteGDPRCustomer ($customer)
    {
        if (!$this->deleteCards((int)$customer['id'])) {
            return json_encode($this->l('PayPlug : Unable to delete customer saved cards.'));
        }
        return json_encode(true);
    }

    public function hookActionExportGDPRData ($customer)
    {
        if (!$cards = $this->gdprCardExport((int)$customer['id'])) {
            return json_encode($this->l('PayPlug : Unable to export customer saved cards.'));
        } else {
            return json_encode($cards);
        }
    }

    public function displayGDPRConsent ()
    {
        $this->context->smarty->assign(array('id_module' => $this->id));
        return $this->display(__FILE__, 'gdpr_consent.tpl');
    }

    public function hookRegisterGDPRConsent () {}

    private function gdprCardExport($id_customer)
    {
        if (!is_int($id_customer) || $id_customer === null) {
            return false;
        } else {
            $req_payplug_card = '
                SELECT pc.last4, pc. exp_month, pc.exp_year, pc.brand, pc.country
                FROM '._DB_PREFIX_.'payplug_card pc
                WHERE pc.id_customer = '.(int)$id_customer;
            $res_payplug_card = Db::getInstance()->ExecuteS($req_payplug_card);
            if (!$res_payplug_card) {
                $cards = null;
            } else {
                $i = 1;
                $cards = array();
                foreach ($res_payplug_card as &$card) {
                    $card['expiry_date'] = date(
                        'm / y',
                        mktime(0, 0, 0, (int)$card['exp_month'], 1, (int)$card['exp_year'])
                    );
                    $cards[] = array(
                        $this->l('#') => $i,
                        $this->l('Brand') => $card['brand'],
                        $this->l('Country') => $card['country'],
                        $this->l('Card') => '**** **** **** '.$card['last4'],
                        $this->l('Expiry date') => $card['expiry_date']
                    );
                    $i ++;
                }
            }
            return $cards;
        }
    }
}
