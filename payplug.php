<?php
/**
 * 2013 - 2016 PayPlug SAS
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
 *  @copyright 2013 - 2016 PayPlug SAS
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

if (!defined('_PAYPLUG_API_MODE_')) {
    //define('_PAYPLUG_API_MODE_', 'local');
    define('_PAYPLUG_API_MODE_', 'dev');
    //define('_PAYPLUG_API_MODE_', 'prod');
}

include_once(_PS_MODULE_DIR_.'payplug/classes/PayplugTools.php');
include_once(_PS_MODULE_DIR_.'payplug/classes/PayplugBackward.php');
include_once(_PS_MODULE_DIR_.'payplug/classes/MyLogPHP.class.php');
require_once(_PS_MODULE_DIR_.'payplug/lib/init.php');

class Payplug extends PaymentModule
{
    /** @var array */
    public $available_types = array(
        'paid',
        'refund',
        'pending',
        'error',
        'paid_test',
        'refund_test',
        'pending_test',
        'error_test'
    );

    /** @var string */
    private $html = '';

    /** @var array */
    public $validationErrors = array();

    /** @var PayplugAdmin */
    public $payplugAdmin;

    /** @var bool */
    public static $is_active    = 1;

    /** @var int */
    const PAYMENT_STATUS_PAID   = 0;

    /** @var int */
    const PAYMENT_STATUS_REFUND = 4;

    /** @var array */
    public $routes = array(
        'login' => '/v1/keys',
        'account' => '/v1/account'
    );

    /** @var string */
    public $api_url = '';

    /** @var array */
    public $check_configuration = array();

    /** @var string */
    public $email;

    /** @var string */
    public $api_live;

    /** @var string */
    public $api_test;

    /**
     * Constructor
     *
     * @return Payplug
     */
    public function __construct()
    {
        $this->setEnvironnement();

        $this->name = 'payplug';
        $this->tab = 'payments_gateways';
        $this->version = '2.0.2';
        $this->author = 'PayPlug';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.4', 'max' => '1.7');
        $this->need_instance = true;
        $this->bootstrap = true;
        $this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';

        parent::__construct();

        $this->displayName = $this->l('PayPlug');

        $this->description =
            $this->l('The simplest online payment solution: no setup fees, no fixed fees, and no merchant account required!');
        if ((int)PayplugBackward::getConfiguration('PAYPLUG_KEEP_CARDS') == 1) {
            $this->confirmUninstall =
                $this->l('Are you sure you wish to uninstall this module and delete your settings?');
            $this->confirmUninstall .= ' ';
            $this->confirmUninstall .= $this->l('All the registered cards of your customer will be kept.');
        } else {
            $this->confirmUninstall =
                $this->l('Are you sure you wish to uninstall this module and delete your settings?');
            $this->confirmUninstall .= ' ';
            $this->confirmUninstall .= $this->l('All the registered cards of your customer will be deleted.');
        }
        $this->email = PayplugBackward::getConfiguration('PAYPLUG_EMAIL');
        $this->api_live = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');
        $this->api_test = PayplugBackward::getConfiguration('PAYPLUG_TEST_API_KEY');
        if ((!isset($this->email) || (!isset($this->api_live) && empty($this->api_test)))) {
            $this->warning =
                $this->l('In order to accept payments you need to configure your module by connecting your PayPlug account.');
        }

        // Backward compatibility
        require_once(_PS_MODULE_DIR_.$this->name.'/backward_compatibility/backward.php');
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $cookie_admin = new Cookie(
                'psAdmin',
                PayplugBackward::substr($_SERVER['PHP_SELF'], PayplugBackward::strlen(__PS_BASE_URI__), -10)
            );
            if (
                Tools::getValue('tab') == 'AdminPayment'
                && Tools::getValue('token') != Tools::getAdminTokenLite('AdminPayment')
            ) {
                // Force admin status
                $this->context->cookie->profile = $cookie_admin->profile;
                $url  = 'index.php?tab=AdminPayment';
                $url .= '&token='.Tools::getAdminTokenLite('AdminPayment');
                Tools::redirectAdmin($url);
            }
        }
        $valid_key = self::setAPIKey();
        if ($valid_key != null) {
            \Payplug\Payplug::setSecretKey(self::setAPIKey());
            \Payplug\Core\HttpClient::addDefaultUserAgentProduct(
                'PayPlug-Prestashop',
                $this->version,
                'Prestashop/'._PS_VERSION_
            );
        }
    }

    /**
     * Determine witch environnement is used
     *
     * @return void
     */
    public function setEnvironnement()
    {
        $this->api_url = '';
        $this->payplug_url = '';
        $this->premium_url = array();

        switch (_PAYPLUG_API_MODE_) {
            case 'local':
                $this->api_url = 'http://localhost:8080';
                $this->payplug_url = 'http://www.local.payplug.com:9999';
                break;
            case 'dev':
                $this->api_url = 'https://api-dev.payplug.com';
                $this->payplug_url = 'https://www-dev.payplug.com';
                break;
            case 'prod':
                $this->api_url = 'https://api.payplug.com';
                $this->payplug_url = 'https://www.payplug.com';
                break;
            default:
                break;
        }
    }

    /**
     * @see Module::install()
     *
     * @return bool
     */
    public function install($do_parent_install = true)
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.$this->name.'/log/install-log.csv');
        $log->info('Starting installation.');
        $report = $this->checkRequirements();
        if (!$report['php']['up2date'])
        {
            $this->_errors[] = Tools::displayError($this->l('Your server must run PHP 5.3 or greater'));
            $log->error('Installation failed: PHP Requirement.');
        }
        if (!$report['curl']['up2date'])
        {
            $this->_errors[] = Tools::displayError($this->l('PHP cURL extension must be enabled on your server'));
            $log->error('Installation failed: cURL Requirement.');
        }
        if (!$report['openssl']['up2date'])
        {
            $this->_errors[] = Tools::displayError($this->l('OpenSSL 1.0.1 or later'));
            $log->error('Installation failed: OpenSSL Requirement.');
        }

        if (version_compare(_PS_VERSION_, '1.5', '>=')) {
            if (Shop::isFeatureActive()) {
                Shop::setContext(Shop::CONTEXT_ALL);
            }
        }

        if ($do_parent_install) {
            if (!parent::install()) {
                $log->error('Installation failed: parent install.');
                return false;
            }
        }

        if (!$this->registerHook('paymentReturn') ||
            !$this->registerHook('header') ||
            !$this->registerHook('adminOrder') ||
            !$this->registerHook('customerAccount') ||
            !$this->createConfig() ||
            !$this->createOrderStates() ||
            !$this->installSQL()
        ) {
            $log->error('Installation failed: hooks, configs, order states or sql.');
            return false;
        }

        if (!$this->registerHook('payment')) {
            $log->error('Installation failed: hook payment.');
            return false;
        }
        $log->info('Installation complete.');

        return true;
    }

    /**
     * @see Module::uninstall()
     *
     * @return bool
     */
    public function uninstall()
    {
        $keep_cards = (int)PayplugBackward::getConfiguration('PAYPLUG_KEEP_CARDS');
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
            !$this->uninstallSQL($keep_cards)
        ) {
            $log->error('Installation failed: configs or sql.');
            return false;
        }
        $log->info('Uninstallation complete.');

        return true;
    }

    public function uninstallCards()
    {
        $test_api_key = PayplugBackward::getConfiguration('PAYPLUG_TEST_API_KEY');
        $live_api_key = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');

        $req_all_cards = '
            SELECT pc.* 
            FROM '._DB_PREFIX_.'payplug_card pc';
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
    public function createConfig()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');

        if (!PayplugBackward::updateConfiguration('PAYPLUG_CURRENCIES', 'EUR') ||
            !PayplugBackward::updateConfiguration('PAYPLUG_MIN_AMOUNTS', 'EUR:1') ||
            !PayplugBackward::updateConfiguration('PAYPLUG_MAX_AMOUNTS', 'EUR:1000000') ||
            !PayplugBackward::updateConfiguration('PAYPLUG_TEST_API_KEY', null) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_LIVE_API_KEY', null) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_COMPANY_ID', null) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_COMPANY_STATUS', '') ||
            !PayplugBackward::updateConfiguration('PAYPLUG_ALLOW_SAVE_CARD', 0) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_SANDBOX_MODE', 1) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_SHOW', 0) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_EMAIL', null) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_EMBEDDED_MODE', 0) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_ONE_CLICK', null) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_DEBUG_MODE', 0) ||
            !PayplugBackward::updateConfiguration('PAYPLUG_KEEP_CARDS', 0)
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
    public function deleteConfig()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_.'payplug/log/install-log.csv');

        if (!Configuration::deleteByName('PAYPLUG_CURRENCIES') ||
            !Configuration::deleteByName('PAYPLUG_MIN_AMOUNTS') ||
            !Configuration::deleteByName('PAYPLUG_MAX_AMOUNTS') ||
            !Configuration::deleteByName('PAYPLUG_TEST_API_KEY') ||
            !Configuration::deleteByName('PAYPLUG_LIVE_API_KEY') ||
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
    public function createOrderStates()
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
            if ((int)PayplugBackward::getConfiguration($key_config) != 0) {
                $os = (int)PayplugBackward::getConfiguration($key_config);
            } elseif ($val = $this->findOrderState($values['name'], false)) {
                $os = $val;
            } elseif (defined($values['cfg'])) {
                $os = constant($values['cfg']);
            } elseif ($values['template'] != null) {
                $req_os_by_template = '
                    SELECT DISTINCT osl.id_order_state 
                    FROM '._DB_PREFIX_.'order_state_lang osl 
                    WHERE osl.template = \''.pSQL($values['template']).'\'';
                $res_os_by_template = Db::getInstance()->getValue($req_os_by_template);
                if ($res_os_by_template) {
                    $os = $res_os_by_template;
                }
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
                    if (in_array($lang['iso_code'], array('en', 'au', 'ca', 'ie', 'gb', 'uk', 'us'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [PayPlug]';
                    } elseif (in_array($lang['iso_code'], array('fr', 'be', 'lu', 'ch'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['fr'].' [PayPlug]';
                    } elseif (in_array($lang['iso_code'], array('es', 'ar', 'cl', 'co', 'mx', 'py', 'uy', 've'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['es'].' [PayPlug]';
                    } elseif (in_array($lang['iso_code'], array('it', 'sm', 'va'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['it'].' [PayPlug]';
                    } else {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [PayPlug]';
                    }
                }
                $order_state->add();
                $os = (int)$order_state->id;
                $log->info('ID: '.$os);
            }
            PayplugBackward::updateConfiguration($key_config, (int)$os);

            //TEST
            $log->info('Test context.');
            if ((int)PayplugBackward::getConfiguration($key_config_test) != 0) {
                $os_test = (int)PayplugBackward::getConfiguration($key_config_test);
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
                    if (in_array($lang['iso_code'], array('en', 'au', 'ca', 'ie', 'gb', 'uk', 'us'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [TEST]';
                    } elseif (in_array($lang['iso_code'], array('fr', 'be', 'lu', 'ch'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['fr'].' [TEST]';
                    } elseif (in_array($lang['iso_code'], array('es', 'ar', 'cl', 'co', 'mx', 'py', 'uy', 've'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['es'].' [TEST]';
                    } elseif (in_array($lang['iso_code'], array('it', 'sm', 'va'))) {
                        $order_state->name[$lang['id_lang']] = $values['name']['it'].' [TEST]';
                    } else {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'].' [TEST]';
                    }
                }
                $order_state->add();
                $os_test = (int)$order_state->id;
                $log->info('ID: '.$os);
            }
            PayplugBackward::updateConfiguration($key_config_test, (int)$os_test);
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
    public function findOrderState($name, $test_mode = false)
    {
        if (!is_array($name) || empty($name)) {
            return false;
        } else {
            $req_order_state = '
				SELECT DISTINCT osl.id_order_state 
				FROM '._DB_PREFIX_.'order_state_lang osl 
				WHERE osl.name LIKE \''.pSQL($name['en'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\' 
				OR osl.name LIKE \''.pSQL($name['fr'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\' 
				OR osl.name LIKE \''.pSQL($name['es'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\' 
				OR osl.name LIKE \''.pSQL($name['it'].($test_mode ? ' [TEST]' : ' [PayPlug]')).'\' 
			';
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
    public function installSQL()
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
            `id_cart` INT(11) UNSIGNED NOT NULL
            ) ENGINE='._MYSQL_ENGINE_;
        $res_payplug_payment_cart = DB::getInstance()->Execute($req_payplug_payment_cart);

        if (!$res_payplug_payment_cart) {
            $log->error('Installation SQL failed: PAYPLUG_PAYMENT_CART.');
            return false;
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $req_payplug_order_payment = '
            CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'payplug_order_payment` (
            `id_payplug_order_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_payment` VARCHAR(255) NOT NULL
            ) ENGINE='._MYSQL_ENGINE_;
            $res_payplug_order_payment = DB::getInstance()->Execute($req_payplug_order_payment);

            if (!$res_payplug_order_payment) {
                $log->error('Installation SQL failed: PAYPLUG_ORDER_PAYMENT.');
                return false;
            }
        }
        $log->info('Installation SQL ended.');
        return true;
    }

    /**
     * Remove SQL tables used by module
     *
     * @return bool
     */
    public function uninstallSQL($keep_cards = false)
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

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $req_payplug_order_payment = '
            DROP TABLE IF EXISTS `'._DB_PREFIX_.'payplug_order_payment`';
            $res_payplug_order_payment = DB::getInstance()->Execute($req_payplug_order_payment);

            if (!$res_payplug_order_payment) {
                $log->error('Uninstallation SQL failed: PAYPLUG_ORDER_PAYMENT.');
                return false;
            }
        }
        $log->info('Uninstallation SQL ended.');
        return true;
    }

    /**
     * get id_order_state
     *
     * @param string $state_name
     * @return int
     */
    public static function getOsConfiguration($state_name)
    {
        $key = 'PAYPLUG_ORDER_STATE_'.Tools::strtoupper($state_name);

        if (PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE')) {
            $key .= '_TEST';
        }

        return PayplugBackward::getConfiguration($key, false);
    }

    /**
     * Module is active
     *
     * @return bool
     */
    public static function moduleIsActive()
    {
        if (self::$is_active == -1) {
            // This override is part of the cloudcache module, so the cloudcache.php file exists
            require_once(dirname(__FILE__).'/../../modules/cloudcache/cloudcache.php');
            $module = new CloudCache();
            self::$is_active = $module->active;
        }
        return self::$is_active;
    }

    /**
     * assign variable for smarty
     *
     * @param string $variable
     * @param mixed $content
     * @return void
     */
    public function assignForVersion($variable, $content = null)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            if (version_compare(_PS_VERSION_, '1.4', '<')) {
                //global $smarty;
                //$smarty->assign($variable, $content);
            } else {
                $this->context->smarty->assign($variable, $content);
            }
        } else {
            $this->smarty->assign($variable, $content);
        }
    }

    /**
     * Redirection
     *
     * @param string $link
     * @return void
     */
    public static function redirectForVersion($link)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            Tools::redirectLink($link);
        } else {
            Tools::redirect($link);
        }
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
            if (
                (
                    !Validate::isEmail(Tools::getValue('PAYPLUG_EMAIL'))
                    || !Validate::isPasswd(Tools::getValue('PAYPLUG_PASSWORD'))
                )
                && (Tools::getValue('PAYPLUG_EMAIL') != false)
            ) {
                $this->validationErrors['username_password'] = $this->l('The email and/or password was not correct.');
            } elseif ($curl_exists && $openssl_exists) {
                if ($this->login(Tools::getValue('PAYPLUG_EMAIL'), Tools::getValue('PAYPLUG_PASSWORD'))) {
                    PayplugBackward::updateConfiguration('PAYPLUG_EMAIL', Tools::getValue('PAYPLUG_EMAIL'));
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
            PayplugBackward::updateConfiguration('PAYPLUG_SANDBOX_MODE', Tools::getValue('PAYPLUG_SANDBOX_MODE'));
            PayplugBackward::updateConfiguration('PAYPLUG_EMBEDDED_MODE', Tools::getValue('PAYPLUG_EMBEDDED_MODE'));
            PayplugBackward::updateConfiguration('PAYPLUG_ONE_CLICK', Tools::getValue('PAYPLUG_ONE_CLICK'));
            PayplugBackward::updateConfiguration('PAYPLUG_SHOW', Tools::getValue('PAYPLUG_SHOW'));
        }

        if (Tools::isSubmit('submitUninstallSettings')) {
            PayplugBackward::updateConfiguration('PAYPLUG_KEEP_CARDS', Tools::getValue('PAYPLUG_KEEP_CARDS'));
        }
    }

    /**
     * login to Payplug API
     *
     * @param string $email
     * @param string $password
     * @return bool
     */
    public function login($email, $password)
    {
        $data = array
        (
            'email' => $email,
            'password' => $password
        );
        $data_string = PayplugBackward::jsonEncode($data);

        $url = $this->api_url.$this->routes['login'];
        $curl_version = curl_version();
        $process = curl_init($url);
        curl_setopt(
            $process,
            CURLOPT_HTTPHEADER,
            array('Content-Type:application/json',
            'Content-Length: '.PayplugBackward::strlen($data_string))
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
        //d(PayplugBackward::jsonDecode($answer));
        if ($error_curl == 0) {
            $json_answer = PayplugBackward::jsonDecode($answer);

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
            $json_answer = PayplugBackward::jsonDecode($answer);

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
    public function treatAccountResponse($json_answer)
    {
        if (
            (isset($json_answer->object) && $json_answer->object == 'error')
            || empty($json_answer)
        ) {
            return false;
        }

        $id = $json_answer->id;

        $configuration = array(
            'currencies' => PayplugBackward::getConfiguration('PAYPLUG_CURRENCIES'),
            'min_amounts' => PayplugBackward::getConfiguration('PAYPLUG_MIN_AMOUNTS'),
            'max_amounts' => PayplugBackward::getConfiguration('PAYPLUG_MAX_AMOUNTS'),
        );
        if (isset($json_answer->configuration)) {
            if (
                isset($json_answer->configuration->currencies)
                && !empty($json_answer->configuration->currencies)
                && sizeof($json_answer->configuration->currencies)
            ) {
                $configuration['currencies'] = array();
                foreach ($json_answer->configuration->currencies as $value) {
                    $configuration['currencies'][] = $value;
                }
            }
            if (
                isset($json_answer->configuration->min_amounts)
                && !empty($json_answer->configuration->min_amounts)
                && sizeof($json_answer->configuration->min_amounts)
            ) {
                $configuration['min_amounts'] = '';
                foreach ($json_answer->configuration->min_amounts as $key => $value) {
                    $configuration['min_amounts'] .= $key.':'.$value.';';
                }
                $configuration['min_amounts'] = PayplugBackward::substr($configuration['min_amounts'], 0, -1);
            }
            if (
                isset($json_answer->configuration->max_amounts)
                && !empty($json_answer->configuration->max_amounts)
                && sizeof($json_answer->configuration->max_amounts)
            ) {
                $configuration['max_amounts'] = '';
                foreach ($json_answer->configuration->max_amounts as $key => $value) {
                    $configuration['max_amounts'] .= $key.':'.$value.';';
                }
                $configuration['max_amounts'] = PayplugBackward::substr($configuration['max_amounts'], 0, -1);
            }
        }

        $permissions = array(
            'use_live_mode' => $json_answer->permissions->use_live_mode,
            'can_save_cards' => $json_answer->permissions->can_save_cards,
        );

        $currencies = implode(';', $configuration['currencies']);
        PayplugBackward::updateConfiguration('PAYPLUG_CURRENCIES', $currencies);
        PayplugBackward::updateConfiguration('PAYPLUG_MIN_AMOUNTS', $configuration['min_amounts']);
        PayplugBackward::updateConfiguration('PAYPLUG_MAX_AMOUNTS', $configuration['max_amounts']);
        PayplugBackward::updateConfiguration('PAYPLUG_COMPANY_ID', $id);

        return $permissions;
    }

    /**
     * Register API Keys
     *
     * @param string $json_answer
     * @return bool
     */
    public function setApiKeysbyJsonResponse($json_answer)
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

        PayplugBackward::updateConfiguration('PAYPLUG_TEST_API_KEY', $api_keys['test_key']);
        PayplugBackward::updateConfiguration('PAYPLUG_LIVE_API_KEY', $api_keys['live_key']);

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

        include_once(_PS_MODULE_DIR_.'payplug/classes/PayplugAdmin.php');
        $this->payplugAdmin = new PayplugAdmin();
        $this->checkConfiguration();

        $PAYPLUG_SHOW = PayplugBackward::getConfiguration('PAYPLUG_SHOW');
        $PAYPLUG_EMAIL = PayplugBackward::getConfiguration('PAYPLUG_EMAIL');
        $PAYPLUG_SANDBOX_MODE = PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE');
        $PAYPLUG_EMBEDDED_MODE = PayplugBackward::getConfiguration('PAYPLUG_EMBEDDED_MODE');
        $PAYPLUG_ONE_CLICK = PayplugBackward::getConfiguration('PAYPLUG_ONE_CLICK');
        $PAYPLUG_TEST_API_KEY = PayplugBackward::getConfiguration('PAYPLUG_TEST_API_KEY');
        $PAYPLUG_LIVE_API_KEY = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');
        $PAYPLUG_DEBUG_MODE = PayplugBackward::getConfiguration('PAYPLUG_DEBUG_MODE');

        if (!empty($PAYPLUG_EMAIL) && (!empty($PAYPLUG_TEST_API_KEY) || !empty($PAYPLUG_LIVE_API_KEY))) {
            $connected = true;
        } else {
            $connected = false;
        }

        if (count($this->validationErrors && !$connected)) {
            $this->context->smarty->assign(array(
                'validationErrors'	=> $this->validationErrors,
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

        $this->payplug_url;

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
                'p_error'	=> $p_error,
            ));
        } else {
            $this->context->smarty->assign(array(
                'PAYPLUG_EMAIL'	=> $PAYPLUG_EMAIL,
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
            'connected'	=> $connected,
            'verified'	=> $verified,
            'premium'	=> $premium,
            'is_active'	=> $is_active,
            'payplug_url' => $this->payplug_url,
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

    public function getUninstallContent()
    {
        $this->postProcess();
        $this->html = '';

        $PAYPLUG_KEEP_CARDS = (int)PayplugBackward::getConfiguration('PAYPLUG_KEEP_CARDS');

        $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin.css');

        $this->context->smarty->assign(array(
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'payplug_url' => $this->payplug_url,
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
        include_once(_PS_MODULE_DIR_.'payplug/classes/PayplugAdmin.php');
        $this->payplugAdmin = new PayplugAdmin();
        $this->checkConfiguration();
        $this->html = '';

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $this->context->smarty->assign(array(
            'admin_ajax_url' => $admin_ajax_url,
            'check_configuration' => $this->check_configuration,
        ));
        $this->html = $this->fetchTemplateRC('/views/templates/admin/fieldset.tpl');

        return $this->html;
    }

    public function getLogin()
    {
        $this->postProcess();

        $this->html = '';

        $this->checkConfiguration();

        $PAYPLUG_SHOW = PayplugBackward::getConfiguration('PAYPLUG_SHOW');
        $PAYPLUG_EMAIL = PayplugBackward::getConfiguration('PAYPLUG_EMAIL');
        $PAYPLUG_SANDBOX_MODE = PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE');
        $PAYPLUG_EMBEDDED_MODE = PayplugBackward::getConfiguration('PAYPLUG_EMBEDDED_MODE');
        $PAYPLUG_ONE_CLICK = PayplugBackward::getConfiguration('PAYPLUG_ONE_CLICK');
        $PAYPLUG_TEST_API_KEY = PayplugBackward::getConfiguration('PAYPLUG_TEST_API_KEY');
        $PAYPLUG_LIVE_API_KEY = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');
        $PAYPLUG_DEBUG_MODE = PayplugBackward::getConfiguration('PAYPLUG_DEBUG_MODE');

        if (!empty($PAYPLUG_EMAIL) && (!empty($PAYPLUG_TEST_API_KEY) || !empty($PAYPLUG_LIVE_API_KEY))) {
            $connected = true;
        } else {
            $connected = false;
        }

        if (count($this->validationErrors && !$connected)) {
            $this->context->smarty->assign(array(
                'validationErrors'	=> $this->validationErrors,
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

        $this->payplug_url;

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
                'p_error'	=> $p_error,
            ));
        } else {
            $this->context->smarty->assign(array(
                'PAYPLUG_EMAIL'	=> $PAYPLUG_EMAIL,
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
            'connected'	=> $connected,
            'verified'	=> $verified,
            'premium'	=> $premium,
            'is_active'	=> $is_active,
            'payplug_url' => $this->payplug_url,
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

    public function checkRequirements()
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
        $payplug_email = PayplugBackward::getConfiguration('PAYPLUG_EMAIL');
        $payplug_test_api_key = PayplugBackward::getConfiguration('PAYPLUG_TEST_API_KEY');
        $payplug_live_api_key = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');

        $report = $this->checkRequirements();

        if (empty($payplug_email) || (empty($payplug_test_api_key) && empty($payplug_live_api_key))) {
            $is_payplug_connected = false;
        } else {
            $is_payplug_connected = true;
        }

        if (
            $report['curl']['installed'] &&
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

        //$configuration_warning =
        $check_warning = $this->l('Unfortunately at least one issue is preventing you from using Payplug.').' '
            .$this->l('Refresh the page or click "Check" once they are fixed');
        if ($is_payplug_configured) {
            //$this->check_configuration['success'][] .= $configuration_warning;
        } else {
            PayplugBackward::updateConfiguration('PAYPLUG_SHOW', 0);
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
            'payplug_url' => $this->payplug_url,
        ));
        $this->html = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

        die(PayplugBackward::jsonEncode(array('content' => $this->html)));
    }

    /**
     * Include js script in template
     *
     * @param string $js_uri
     * @return void
     */
    public function addJsRC($js_uri)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $js_files = array();
            $this->html .= '<script type="text/javascript" src="'.$js_uri.'"></script>';
            $js_files[] = $js_uri;
        } else {
            $this->context->controller->addJS($js_uri);
        }
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
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $this->html .= '<link type="text/css" rel="stylesheet" href="'.$css_uri.'" />';
        } else {
            $this->context->controller->addCSS($css_uri, $css_media_type);
        }
    }

    /**
     * Register payment for later use
     *
     * @param string $pay_id
     * @param int $id_cart
     * @return bool
     */
    public function storePayment($pay_id, $id_cart)
    {
        $req_payment_cart_exists = '
            SELECT * 
            FROM '._DB_PREFIX_.'payplug_payment_cart ppc  
            WHERE ppc.id_cart = '.(int)$id_cart;
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
                SET ppc.id_payment = \''.pSQL($pay_id).'\'';
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
        $req_payment_cart = '
            SELECT ppc.id_payment 
            FROM '._DB_PREFIX_.'payplug_payment_cart ppc  
            WHERE ppc.id_cart = '.(int)$cart_id;
        $res_payment_cart = Db::getInstance()->getValue($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        }

        return $res_payment_cart;
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
    public function checkCurrency($cart)
    {
        $currency_order = new Currency((int)($cart->id_currency));
        $currencies_module = $this->getCurrency((int)$cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
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
    public function checkAmount($cart)
    {
        $currency = new Currency($cart->id_currency);
        $amounts_by_currency = Payplug::getAmountsByCurrency($currency->iso_code);
        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            $amount = $cart->getOrderTotal(true, 3) * 100;
        } else {
            $amount = $cart->getOrderTotal(true, Cart::BOTH) * 100;
        }
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
    public static function getAmountsByCurrency($iso_code)
    {
        $min_amounts = array();
        $max_amounts = array();
        foreach (explode(';', PayplugBackward::getConfiguration('PAYPLUG_MIN_AMOUNTS')) as $amount_cur) {
            $cur = array();
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $min_amounts[$cur[1]] = (int)$cur[2];
        }
        foreach (explode(';', PayplugBackward::getConfiguration('PAYPLUG_MAX_AMOUNTS')) as $amount_cur) {
            $cur = array();
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $max_amounts[$cur[1]] = (int)$cur[2];
        }
        $current_min_amount = $min_amounts[$iso_code];
        $current_max_amount = $max_amounts[$iso_code];

        return array('min_amount' => $current_min_amount, 'max_amount' => $current_max_amount);
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
        $one_click = (int)PayplugBackward::getConfiguration('PAYPLUG_ONE_CLICK');
        $embedded_mode = (int)PayplugBackward::getConfiguration('PAYPLUG_EMBEDDED_MODE');

        $current_card = null;
        if ($id_card != null && $id_card != 'new_card') {
            $current_card = $this->getCardId(
                (int)$cart->id_customer,
                $id_card,
                (int)PayplugBackward::getConfiguration('PAYPLUG_COMPANY_ID')
            );
        }
        //currency
        $result_currency = array();
        if (version_compare(_PS_VERSION_, '1.3', '<')) {
            $result_currency['iso_code'] = Tools::setCurrency()->iso_code;
        } elseif (version_compare(_PS_VERSION_, '1.5', '<')) {
            $result_currency['iso_code'] = Currency::getCurrent()->iso_code;
        } else {
            $currency = $cart->id_currency;
            $result_currency = Currency::getCurrency($currency);
        }
        $supported_currencies = explode(';', PayplugBackward::getConfiguration('PAYPLUG_CURRENCIES'));

        if (!in_array($result_currency['iso_code'], $supported_currencies)) {
            return false;
        }

        $currency = $result_currency['iso_code'];

        //amount
        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            $amount = $cart->getOrderTotal(true, 3);
        } else {
            $amount = $cart->getOrderTotal(true, Cart::BOTH);
        }

        $amount = round($amount, 2) * 100;
        $current_amounts = Payplug::getAmountsByCurrency($currency);
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
        $payment_customer = array(
            'first_name'        => !empty($customer->firstname) ? $customer->firstname : null,
            'last_name'         => !empty($customer->lastname) ? $customer->lastname : null,
            'email'             => $customer->email,
            'address1'          => !empty($address_delivery->address1) ? $address_delivery->address1 : null,
            'address2'          => !empty($address_delivery->address2) ? $address_delivery->address2 : null,
            'postcode'          => !empty($address_delivery->postcode) ? $address_delivery->postcode : null,
            'city'              => !empty($address_delivery->city) ? $address_delivery->city : null,
            'country'           => !empty($country->iso_code) ? $country->iso_code : null,
        );

        //hosted payment
        if (version_compare(_PS_VERSION_, '1.4', '<')) {
            if (PayplugBackward::getConfiguration('PS_SSL_ENABLED')) {
                $baseurl = _PS_BASE_URL_SSL_;
            } else {
                $baseurl = PayplugBackward::getHttpHost(true);
            }
        } elseif (version_compare(_PS_VERSION_, '1.5', '<')) {
            if (Tools::getProtocol() == 'https://') {
                $baseurl = _PS_BASE_URL_SSL_;
            } else {
                $baseurl = PayplugBackward::getHttpHost(true);
            }
        } else {
            if (Tools::getShopProtocol() == 'https://') {
                $baseurl = _PS_BASE_URL_SSL_;
            } else {
                $baseurl = PayplugBackward::getHttpHost(true);
            }
        }
        $base_return_url = $baseurl.__PS_BASE_URI__.'modules/payplug/controllers/front/validation.php';
        if ($one_click != 1 || ($one_click == 1 && ($id_card == null || $id_card == 'new_card'))) {
            $hosted_payment = array(
                'return_url'        => $base_return_url.'?ps=1&cartid='.(int)$cart->id,
                'cancel_url'        => $base_return_url.'?ps=2&cartid='.(int)$cart->id
            );
        }

        //notification
        $notification_url = $baseurl.__PS_BASE_URI__.'modules/payplug/controllers/front/ipn.php';

        //payment method
        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $payment_method = $current_card;
        }

        //force 3ds
        $force_3ds = false;

        //save card
        $allow_save_card = false;
        if ($one_click == 1) {
            $allow_save_card = true;
        }

        //meta data
        $metadata = array(
            'customer_id'       => (int)$customer->id,
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
            $payment_tab['allow_save_card'] = false;
        } else {
            $payment_tab['hosted_payment'] = array(
                'return_url'        => $hosted_payment['return_url'],
                'cancel_url'        => $hosted_payment['cancel_url'],
            );
            $payment_tab['allow_save_card'] = $allow_save_card;
        }

        try {
            if (PayplugBackward::getConfiguration('PAYPLUG_DEBUG_MODE')) {
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
        } catch (ConfigurationNotSetException $e) {
            $data = array(
                'result' => false,
                'response' => $e->__toString(),
            );
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                die(PayplugBackward::jsonEncode($data));
            } else {
                return($data);
            }
        }

        $this->storePayment($payment->id, (int)$cart->id);
        if ($one_click == 1 && $current_card != null && $id_card != 'new_card') {
            $data = array(
                'result' => true,
                'validation_url' => $base_return_url.'?ps=1&cartid='.(int)$cart->id
            );
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                die(PayplugBackward::jsonEncode($data));
            } else {
                return($data);
            }
        } elseif (($one_click == 1 && $id_card == 'new_card') || ($one_click != 1 && $id_card == 'new_card')) {
            $data = array(
                'result' => 'new_card',
                'embedded_mode' => (int)$embedded_mode,
                'payment_url' => $payment->hosted_payment->payment_url,
            );
            die(PayplugBackward::jsonEncode($data));
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
    public function getCardId($id_customer, $id_payplug_card, $id_company)
    {
        $is_sandbox = (int)PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE');
        $req_card_id = '
            SELECT pc.id_card 
            FROM '._DB_PREFIX_.'payplug_card pc 
            WHERE pc.id_customer = '.(int)$id_customer.' 
            AND pc.id_payplug_card = '.(int)$id_payplug_card.' 
            AND pc.id_company = '.(int)$id_company.' 
            AND pc.is_sandbox = '.(int)$is_sandbox;
        $res_card_id = Db::getInstance()->getValue($req_card_id);
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
        if (PayplugBackward::strtolower($brand) != 'mastercard' && PayplugBackward::strtolower($brand) != 'visa') {
            $brand = 'none';
        }

        $customer_id = (int)$payment->metadata['ID Client'];
        $company_id = (int)PayplugBackward::getConfiguration('PAYPLUG_COMPANY_ID');
        $is_sandbox = (int)PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE');

        $req_last_id_payplug_card = '
            SELECT MAX(pc.id_payplug_card) 
            FROM '._DB_PREFIX_.'payplug_card pc 
            WHERE pc.id_customer = '.(int)$customer_id.' 
            AND pc.id_company = '.(int)$company_id.' 
            AND pc.is_sandbox = '.(int)$is_sandbox;
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
        $id_company = (int)PayplugBackward::getConfiguration('PAYPLUG_COMPANY_ID');
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
     * Get collection of cards
     *
     * @param int $id_customer
     * @return array OR bool
     */
    public function getCardsByCustomer($id_customer)
    {
        $is_sandbox = (int)PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE');
        $req_payplug_card = '
            SELECT pc.id_customer, pc.id_payplug_card, pc.id_company, pc.last4, 
              pc. exp_month, pc.exp_year, pc.brand, pc.country, pc.metadata  
            FROM '._DB_PREFIX_.'payplug_card pc
            WHERE pc.id_customer = '.(int)$id_customer.' 
            AND pc.id_company = '.(int)PayplugBackward::getConfiguration('PAYPLUG_COMPANY_ID').'
            AND pc.is_sandbox = '.(int)$is_sandbox;
        $res_payplug_card = Db::getInstance()->ExecuteS($req_payplug_card);
        if (!$res_payplug_card) {
            return false;
        } else {
            foreach ($res_payplug_card as &$card) {
                $card['expiry_date'] = date(
                    'm / y',
                    mktime(0, 0, 0, (int)$card['exp_month'], 1, (int)$card['exp_year'])
                );
            }
            return $res_payplug_card;
        }
    }

    /**
     * @see Module::hookPayment()
     *
     * @param array $params
     * @return string
     */
    public function hookPayment($params)
    {
        $embedded_mode = (int)PayplugBackward::getConfiguration('PAYPLUG_EMBEDDED_MODE');
        $one_click = (int)PayplugBackward::getConfiguration('PAYPLUG_ONE_CLICK');

        if (!$this->active) {
            return;
        }
        if (PayplugBackward::getConfiguration('PAYPLUG_SHOW') == 0) {
            return;
        }
        if (!$this->checkCurrency($params['cart'])) {
            return;
        }
        if (!$this->checkAmount($params['cart'])) {
            return;
        }

        if (version_compare(_PS_VERSION_, 1.3, '<')) {
            $path_ssl = (PayplugBackward::getConfiguration('PS_SSL_ENABLED') ? 'https://' : 'http://')
                .htmlspecialchars($_SERVER['HTTP_HOST'], ENT_COMPAT, 'UTF-8')
                .__PS_BASE_URI__.'modules/'.$this->name.'/';
        } elseif (version_compare(_PS_VERSION_, 1.4, '<')) {
            $path_ssl = Tools::getHttpHost(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/';
        } else {
            $path_ssl = Tools::getShopDomainSsl(true, true).__PS_BASE_URI__.'modules/'.$this->name.'/';
        }

        $payplug_cards = $this->getCardsByCustomer($params['cart']->id_customer);

        $use_taxes = (int)Configuration::get('PS_TAX');
        $base_total_tax_inc = $params['cart']->getOrderTotal(true);
        $base_total_tax_exc = $params['cart']->getOrderTotal(false);

        $price2display = 0;
        if ($use_taxes == 1) {
            $price2display = $base_total_tax_inc;
        } else {
            $price2display = $base_total_tax_exc;
        }

        $this->smarty->assign(array(
            'this_path' => $this->_path,
            'this_path_ssl' => $path_ssl,
            'iso_lang' => $this->context->language->iso_code,
            'price2display' => $price2display,
        ));

        if ($embedded_mode || $one_click == 1) {
            $front_ajax_url = PayplugBackward::getHttpHost(true).__PS_BASE_URI__
                .'modules/payplug/controllers/front/FrontAjaxPayplug.php?_ajax=1';
            $this->smarty->assign(array(
                'front_ajax_url' => $front_ajax_url,
                'api_url' => $this->api_url
            ));
        }

        if (!empty($payplug_cards) && $one_click == 1) {
            $payplug_cards = $this->getCardsByCustomer($params['cart']->id_customer);
            $this->smarty->assign(array(
                'payplug_cards' => $payplug_cards,
                'payplug_one_click' => 1,
            ));
        }
        $payment_url = '';

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $payment_url = 'order.php?step=3';
        } else {
            $payment_url = 'index.php?controller=order&step=3';
        }

        $this->smarty->assign(array(
            'spinner_url' => PayplugBackward::getHttpHost(true).__PS_BASE_URI__
                .'modules/payplug/views/img/admin/spinner.gif',
            'payment_url' => $payment_url,
        ));
        // Different tpl depending version
        if (version_compare(_PS_VERSION_, '1.6', '<')) {
            $this->smarty->assign(array(
                'version' => _PS_VERSION_,
            ));
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                return $this->display(__FILE__, './views/templates/hook/payment_1_5.tpl');
            } else {
                return $this->display(__FILE__, 'payment_1_5.tpl');
            }
        } else {
            return $this->display(__FILE__, 'payment_1_6.tpl');
        }
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
        if (PayplugBackward::getConfiguration('PAYPLUG_SHOW') == 0) {
            return null;
        }
        $order_id = Tools::getValue('id_order');
        $order = new Order($order_id);
        // Check order state to display appropriate message
        $state = null;
        if (
            isset($order->current_state)
            && $order->current_state == PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_PENDING')
        ) {
            $state = 'pending';
        } elseif (
            isset($order->current_state)
            && $order->current_state == PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_PAID')
        ) {
            $state = 'paid';
        } elseif (
            isset($order->current_state)
            && $order->current_state == PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_PENDING_TEST')
        ) {
            $state = 'pending_test';
        } elseif (
            isset($order->current_state)
            && $order->current_state == PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_PAID_TEST')
        ) {
            $state = 'paid_test';
        }

        $this->assignForVersion('state', $state);
        // Get order information for display
        $total_paid = number_format($order->total_paid, 2, ',', '');
        $context = array('totalPaid' => $total_paid);
        if (isset($order->reference)) {
            $context['reference'] = $order->reference;
        }
        $this->assignForVersion($context);
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            return $this->display(__FILE__, './views/templates/hook/confirmation.tpl');
        } else {
            return $this->display(__FILE__, 'confirmation.tpl');
        }
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
        if (PayplugBackward::getConfiguration('PAYPLUG_SHOW') == 0) {
            return;
        }

        if (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/front_1_6.css');
            $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/front.js');
        } elseif (version_compare(_PS_VERSION_, '1.5', '>=')) {
            $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/front_1_5.css');
            $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/front.js');
        } elseif (version_compare(_PS_VERSION_, '1.4', '>=')) {
            Tools::addCSS(__PS_BASE_URI__.'modules/payplug/views/css/front_1_4.css');
            Tools::addJS(__PS_BASE_URI__.'modules/payplug/views/js/front.js');
        }
    }

    /**
     * Make a refund
     *
     * @param string $pay_id
     * @param int $amount
     * @param string $metadata
     * @return string
     */
    public function makeRefund($pay_id, $amount, $metadata)
    {
        $data = array(
            'amount' => $amount,
            'metadata' => $metadata
        );
        $refund = '';
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
        if (version_compare(_PS_VERSION_, '1.4', '>=')) {
            return parent::enable($force_all);
        }

        $req_enable = '
            UPDATE `'._DB_PREFIX_.'module`
            SET `active`= 1
            WHERE `name` = \''.pSQL($this->name).'\'';

        $res_enable = Db::getInstance()->Execute($req_enable);
        if (!$res_enable) {
            return false;
        }

        return true;
    }

    /**
     * @see Module::disable()
     *
     * @param bool $force_all
     * @return bool
     */
    public function disable($force_all = false)
    {
        PayplugBackward::updateConfiguration('PAYPLUG_SHOW', 0);
        if (version_compare(_PS_VERSION_, '1.4', '>=')) {
            parent::disable($force_all);
        }

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
        $email = PayplugBackward::getConfiguration('PAYPLUG_EMAIL');
        $connected = $this->login($email, $pwd);
        $use_live_mode = false;

        if ($connected) {
            if (PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY') != '') {
                $use_live_mode = true;

                $valid_key = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');
                $permissions = $this->getAccount($valid_key);
                $can_save_cards = $permissions['can_save_cards'];
            }
        } else {
            die(PayplugBackward::jsonEncode(array('content' => 'wrong_pwd')));
        }
        if (!$use_live_mode) {
            die(PayplugBackward::jsonEncode(array('content' => 'activate')));
        } elseif ($can_save_cards) {
            die(PayplugBackward::jsonEncode(array('content' => 'live_ok')));
        } else {
            die(PayplugBackward::jsonEncode(array('content' => 'live_ok_not_premium')));
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
        $sandbox_mode = (int)PayplugBackward::getConfiguration('PAYPLUG_SANDBOX_MODE');
        $valid_key = null;
        if ($sandbox_mode) {
            $valid_key = PayplugBackward::getConfiguration('PAYPLUG_TEST_API_KEY');
        } else {
            $valid_key = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');
        }

        return $valid_key;
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
        if (PayplugBackward::getConfiguration('PAYPLUG_SHOW') == 0) {
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

        $pay_id = '';
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $pay_id = $this->getPayplugOrderPayment($order->id);
        } else {
            $payments = $order->getOrderPaymentCollection();
            if (count($payments) > 1 || !isset($payments[0])) {
                return false;
            } else {
                $pay_id = $payments[0]->transaction_id;
            }
        }
        if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
            return false;
        }

        $amount_refunded_presta = $this->getTotalRefunded($order->id);
        $amount_refunded_payplug = ($payment->amount_refunded) / 100;
        $amount_available = ($payment->amount - $payment->amount_refunded) / 100;
        $amount_suggested = (min($amount_refunded_presta, $amount_available) - $amount_refunded_payplug);
        $amount_suggested = number_format((float)$amount_suggested, 2);
        if ($amount_suggested < 0) {
            $amount_suggested = 0;
        }

        $admin_ajax_url = $this->getAdminAjaxUrl('AdminOrders', (int)$params['id_order']);

        $id_currency = (int)Currency::getIdByIsoCode($payment->currency);

        $sandbox = ((int)$payment->is_live == 1 ? false : true);
        $id_new_order_state = 0;

        if ($sandbox) {
            $id_new_order_state = (int)PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_REFUND_TEST');
        } else {
            $id_new_order_state = (int)PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_REFUND');
        }

        $show_popin = false;
        $show_menu = true;
        $show_menu_refunded = false;

        if ((((int)$payment->amount_refunded > 0) || $amount_refunded_presta > 0) && (int)$payment->is_refunded != 1) {
            $show_menu = true;
        } elseif ((int)$payment->is_refunded == 1) {
            $show_menu_refunded = true;
            $show_menu = false;
        }

        $conf = (int)Tools::getValue('conf');
        if (
            ($conf == 1 && version_compare(_PS_VERSION_, '1.3', '<'))
            || ($conf == 21 && version_compare(_PS_VERSION_, '1.4', '<'))
            || ($conf == 24 && version_compare(_PS_VERSION_, '1.5', '<'))
            || (($conf == 30 || $conf == 31 ) && version_compare(_PS_VERSION_, '1.5', '>='))
        ) {
            $show_popin = true;

            $admin_ajax_url = $this->getAdminAjaxUrl('AdminOrders', (int)$params['id_order']);

            $this->html .= '
                <a class="pp_admin_ajax_url" href="'.$admin_ajax_url.'"></a>
            ';
        }

        if ($show_popin && $show_menu) {
            if (version_compare(_PS_VERSION_, '1.5', '<')) {
                $this->html .= '<script type="text/javascript" src="'.__PS_BASE_URI__
                    .'modules/payplug/views/js/admin_order_popin.js"></script>';
            } else {
                $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin_order_popin.js');
            }
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $this->html .= '<script type="text/javascript" src="'.__PS_BASE_URI__
                .'modules/payplug/views/js/admin_order.js"></script>';
            $this->html .= '<link type="text/css" rel="stylesheet" href="'.__PS_BASE_URI__
                .'modules/payplug/views/css/admin_order.css" />';
        } else {
            $this->addJsRC(__PS_BASE_URI__.'modules/payplug/views/js/admin_order.js');
            $this->addCSSRC(__PS_BASE_URI__.'modules/payplug/views/css/admin_order.css');
        }

        $currency = new Currency($id_currency);
        if (!Validate::isLoadedObject($currency)) {
            return false;
        }

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $version = 1.4;
        } elseif (version_compare(_PS_VERSION_, '1.6', '<')) {
            $version = 1.5;
        } elseif (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $version = 1.6;
        }

        $pay_status = (int)$payment->is_paid == 1 ? $this->l('PAID') : $this->l('NOT PAID');
        $pay_amount = (int)$payment->amount / 100;
        $pay_date = date('d/m/Y H:i', (int)$payment->created_at);
        $pay_brand = ($payment->card->brand != '' ? $payment->card->brand : $this->l('Card'))
            .' ('.$payment->card->country.')';
        $pay_card_mask = '**** **** **** '.$payment->card->last4;
        $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
        //$pay_fees = '';
        $pay_mode = $payment->is_live ? $this->l('LIVE') : $this->l('TEST');
        $pay_card_date = $payment->card->exp_month.'/'.$payment->card->exp_year;

        $this->context->smarty->assign(array(
            'logo_url' => __PS_BASE_URI__.'modules/payplug/views/img/logo_payplug.png',
            'pay_id' => $pay_id,
            'pay_status' => $pay_status,
            'pay_amount' => $pay_amount,
            'pay_date' => $pay_date,
            'pay_brand' => $pay_brand,
            'pay_card_mask' => $pay_card_mask,
            'pay_tds' => $pay_tds,
            //'pay_fees' => $pay_fees,
            'pay_mode' => $pay_mode,
            'version' => $version,
            'show_menu' => $show_menu,
            'show_menu_refunded' => $show_menu_refunded,
            'pay_card_date' => $pay_card_date,
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
                'version' => $version
            ));
        } elseif ($show_menu_refunded) {
            $this->context->smarty->assign(array(
                'amount_refunded_payplug' => $amount_refunded_payplug,
                'currency' => $currency,
                'version' => $version
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
    public function getTotalRefunded($id_order)
    {
        $order = new Order((int)$id_order);
        if (!Validate::isLoadedObject($order)) {
            return false;
        } else {
            $amount_refunded_presta = 0;
            $flag_shipping_refunded = false;
            if (version_compare(_PS_VERSION_, '1.6', '<')) {
                $products = $order->getProducts(false, false, false);
                if (isset($products) && !empty($products) && sizeof($products)) {
                    foreach ($products as $product) {
                        $amount_refunded_presta += (
                            $product['product_quantity_refunded'] * $product['product_price_wt']
                        );
                    }
                }
                if ($amount_refunded_presta > 0) {
                    $amount_refunded_presta += $order->total_shipping;
                }
            } else {
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
            }
            return $amount_refunded_presta;
        }
    }

    /**
     * Add Order Payment
     *
     * @param int $id_order
     * @param string $id_payment
     * @return bool
     */
    public function addPayplugOrderPayment($id_order, $id_payment)
    {
        $req_order_payment = '
            INSERT INTO '._DB_PREFIX_.'payplug_order_payment (id_order, id_payment) 
            VALUE ('.(int)$id_order.',\''.pSQL($id_payment).'\')';
        $res_order_payment = Db::getInstance()->execute($req_order_payment);

        return $res_order_payment;
    }

    /**
     * get order payment
     *
     * @param int $id_order
     * @return bool
     */
    public function getPayplugOrderPayment($id_order)
    {
        $req_order_payment = '
            SELECT pop.id_payment 
            FROM '._DB_PREFIX_.'payplug_order_payment pop  
            WHERE pop.id_order = '.(int)$id_order;
        $res_order_payment = Db::getInstance()->getValue($req_order_payment);

        return $res_order_payment;
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
            if ((int)Tools::getValue('en') == 1 && (int)PayplugBackward::getConfiguration('PAYPLUG_SHOW') == 0) {
                PayplugBackward::updateConfiguration('PAYPLUG_SHOW', 1);
                $this->enable();
                die(true);
            }
            if (
                Tools::getIsset('en')
                && (int)Tools::getValue('en') == 0
                && (int)PayplugBackward::getConfiguration('PAYPLUG_SHOW') == 1
            ) {
                PayplugBackward::updateConfiguration('PAYPLUG_SHOW', 0);
                die(true);
            }
            if (Tools::getIsset('db')) {
                if (Tools::getValue('db') == 'on') {
                    PayplugBackward::updateConfiguration('PAYPLUG_DEBUG_MODE', 1);
                } elseif (Tools::getValue('db') == 'off') {
                    PayplugBackward::updateConfiguration('PAYPLUG_DEBUG_MODE', 0);
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
                die(PayplugBackward::jsonEncode(array('content' => 'confirm_ok')));
            }
            if (Tools::getValue('submit') == 'submitPopin_confirm_a') {
                die(PayplugBackward::jsonEncode(array('content' => 'confirm_ok_activate')));
            }
            if (Tools::getValue('submit') == 'submitPopin_desactivate') {
                die(PayplugBackward::jsonEncode(array('content' => 'confirm_ok_desactivate')));
            }
            if ((int)Tools::getValue('check') == 1) {
                $content = $this->getCheckFieldset();
                die(PayplugBackward::jsonEncode(array('content' => $content)));
            }
            if ((int)Tools::getValue('log') == 1) {
                $content = $this->getLogin();
                die(PayplugBackward::jsonEncode(array('content' => $content)));
            }
            if ((int)Tools::getValue('checkPremium') == 1) {
                $api_key = PayplugBackward::getConfiguration('PAYPLUG_LIVE_API_KEY');
                if ($this->checkPremium($api_key)) {
                    die(true);
                } else {
                    die(false);
                }
            }
            if ((int)Tools::getValue('refund') == 1) {
                if (!$this->checkAmountToRefund(Tools::getValue('amount'))) {
                    die(PayplugBackward::jsonEncode(array(
                        'status' => 'error',
                        'data' => $this->l('Incorrect amount to refund')
                    )));
                } else {
                    $amount = Tools::getValue('amount');
                    $amount = str_replace(',', '.', $amount);
                    $amount = $amount * 100;
                }

                $id_order = Tools::getValue('id_order');
                $pay_id = Tools::getValue('pay_id');
                $metadata = array(
                    'ID Client' => (int)Tools::getValue('id_customer'),
                    'reason' => 'Refunded with Prestashop'
                );
                $refund = $this->makeRefund($pay_id, $amount, $metadata);
                if ($refund == 'error') {
                    die(PayplugBackward::jsonEncode(array(
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
                            $new_state = (int)PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_REFUND');
                        } else {
                            $new_state = (int)PayplugBackward::getConfiguration('PAYPLUG_ORDER_STATE_REFUND_TEST');
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
                    die(PayplugBackward::jsonEncode(array(
                        'status' => 'ok',
                        'data' => $data,
                        'message' => $this->l('Amount successfully refunded.'),
                        'reload' => $reload
                    )));
                }
            }
            if ((int)Tools::getValue('popinRefund') == 1) {
                $popin = $this->displayPopin('refund');
                die(PayplugBackward::jsonEncode(array('content' => $popin)));
            }
        } else {
            exit;
        }
    }

    public function getAdminAjaxUrl($controller_name = 'AdminModules', $id_order = 0)
    {
        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $admin_ajax_url = PayplugBackward::getHttpHost(true).__PS_BASE_URI__
            .'modules/payplug/controllers/admin/AdminAjaxController.php';
        } else {
            if ($controller_name = 'AdminModules') {
                $admin_ajax_url = 'index.php?controller='.$controller_name.'&configure='.$this->name
                    .'&tab_module=payments_gateways&module_name=payplug&token='
                    .Tools::getAdminTokenLite($controller_name);
            } elseif ($controller_name = 'AdminOrders') {
                $admin_ajax_url = 'index.php?controller='.$controller_name.'&id_order='.$id_order
                    .'&vieworder&token='.Tools::getAdminTokenLite($controller_name);
            }
        }
        return $admin_ajax_url;
    }

    public function hookCustomerAccount($params)
    {
        if (!$this->active) {
            return;
        }
        if (PayplugBackward::getConfiguration('PAYPLUG_SHOW') == 0) {
            return;
        }

        $link = new Link();

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            $payplug_cards_url = PayplugBackward::getHttpHost(true).__PS_BASE_URI__
                .'modules/'.$this->name.'/controllers/front/cards.php';
            $payplug_icon_url = PayplugBackward::getHttpHost(true).__PS_BASE_URI__
                .'modules/'.$this->name.'/views/img/logo16.gif';
            $version = 1.4;
        } elseif (version_compare(_PS_VERSION_, '1.6', '<')) {
            $payplug_cards_url = $link->getModuleLink($this->name, 'savedCards', array('process' => 'cardlist'));
            $payplug_icon_url = PayplugBackward::getHttpHost(true).__PS_BASE_URI__
                .'modules/'.$this->name.'/views/img/logo26.png';
            $version = 1.5;
        } elseif (version_compare(_PS_VERSION_, '1.6', '>=')) {
            $payplug_cards_url = $link->getModuleLink($this->name, 'savedCards', array('process' => 'cardlist'));
            $payplug_icon_url = PayplugBackward::getHttpHost(true).__PS_BASE_URI__
                .'modules/'.$this->name.'/views/img/logo26.png';
            $version = 1.6;
        }

        $this->smarty->assign(array(
            'payplug_cards_url' => $payplug_cards_url,
            'payplug_icon_url' => $payplug_icon_url,
            'version' => $version
        ));

        if (version_compare(_PS_VERSION_, '1.5', '<')) {
            return $this->display(__FILE__, './views/templates/hook/my_account.tpl');
        } else {
            return $this->display(__FILE__, 'my_account.tpl');
        }
    }
}
