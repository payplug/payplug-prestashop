<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
// 13 JANV 2021 : 6081L
use Payplug\Exception\ConfigurationNotSetException;

/**
 * Core file of PayPlug module
 */

require_once(_PS_MODULE_DIR_ . 'payplug/vendor/autoload.php');
require_once(_PS_MODULE_DIR_ . 'payplug/src/repositories/PluginRepository.php');
require_once(_PS_MODULE_DIR_ . 'payplug/classes/MyLogPHP.class.php');
require_once(_PS_MODULE_DIR_ . 'payplug/backward/PayPlugBackward.php');
require_once(_PS_MODULE_DIR_ . 'payplug/src/specific/PrestashopLoaderSpecific.php');
require_once(_PS_MODULE_DIR_ . 'payplug/classes/PPPaymentInstallment.php');

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'payplug/classes/PPPayment.php');
require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayPlugCarrier.php');

class Payplug extends PaymentModule
{
    public $oney;

    public $order_state;

    public $constantFile; // 3.0

    /** @var PluginEntity */
    protected $plugin; // 3.0

    protected $query; // 3.0

    protected $PrestashopSpecificClass; // 3.0

    protected $PrestashopSpecificObject; // 3.0

    /**
     * @var To inject logo_url in oney payment template
     */
    public $oneyLogoUrl;

    /** @var array */
    public $check_configuration = [];

    /** @var string */
    public $current_api_key;

    /** @var array */
    public $errors = [];

    /** @var string */
    private $html = '';

    /** @var string */
    private $img_lang;

    /** @var array */
    public $payment_status = [];

    /** @var string */
    public $site_url;

    /** @var PayPlugConfiguration */
    public $configuration;

    /** @var array */
    public $validationErrors = [];

    public $order_states = [
        'paid' => [
            'cfg' => 'PS_OS_PAYMENT',
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
        'auth' => [
            'cfg' => null,
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
                'en' => 'Authoriation expired',
                'es' => 'Autorización vencida',
                'fr' => 'Autorisation expirée',
                'it' => 'Autorizzazione scaduta',
            ],
        ],
    ];

    /**
     * Constructor
     *
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        $this->name = 'payplug';
        $this->author = 'PayPlug';
        $this->bootstrap = true;
        $this->constantFile = _PS_MODULE_DIR_ . 'payplug/payplug.php';
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->description = $this->l('The online payment solution combining simplicity 
        and first-rate support to boost your sales.');
        $this->displayName = 'PayPlug';
        $this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';
        $this->need_instance = true;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.8'];
        $this->tab = 'payments_gateways';
        $this->version = '3.0.0';
        $this->oneyLogoUrl = '';

        $this->initializeAccessors();

        $this->setLoggers();
        $this->loadEntities();

        parent::__construct();
        $this->setEnvironment();
        $this->setConfigurationProperties();
        $this->setSecretKey();
        $this->setUserAgent();
        $this->loadSpecificPrestaClasses();
    }

    public function getPlugin()
    {
        return $this->plugin;
    }

    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
        return $this;
    }

    // hooks : tester avec un include ?
}
