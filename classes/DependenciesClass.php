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

namespace PayPlug\classes;

// Global
use Address;
use Cart;
use Configuration;
use Context;
use Country;
use Currency;
use Customer;
use DateInterval;
use DateTime;
use Db;
use DbQuery;
use Dispatcher;
use Exception;
use Language;
use Media;
use Module;
use MyLogPHP;
use Order;
use OrderHistory;
use OrderSlip;
use OrderState;
use PaymentModule;
use Payplug\Exception\ConfigurationException;
use Payplug\Exception\ConfigurationNotSetException;
use Payplug\InstallmentPlan;
use Payplug\Resource\Payment;
use Payplug\Refund;
use PayPlug\src\repositories\PluginRepository;
use Product;
use Tab;
use Tools;
use Validate;

if (!defined('_PS_VERSION_')) {
    exit;
}

class DependenciesClass extends PaymentModule
{
    /** SPECIAL SPLIT */
    public $amountCurrencyClass;
    public $apiClass;
    public $configClass;
    public $mediaClass;
    public $orderClass;
    public $installmentClass;
    public $refundClass;
    /** @var PayPlugConfiguration */
    public $configuration;
    /** @var array */
    public $errors = [];
    public $logger;
    public $oney;
    public $payplug_languages = ['en', 'fr', 'es', 'it'];

    public $oney_order_state = [
        'oney_pg' => [
            'cfg' => null,
            'payplug_cfg' => [
                'PAYPLUG_ORDER_STATE_ONEY_PG',
                'PAYPLUG_ORDER_STATE_ONEY_PG_TEST'
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
    ]; // PaymentRepository
    /**
     * @var To inject logo_url in oney payment template
     */
    public $oneyLogoUrl;
    public $order_state;
    public $order_states = [
        'paid' => [
            'cfg' => 'PS_OS_PAYMENT',
            'payplug_cfg' => [
                'PAYPLUG_ORDER_STATE_PAID',
                'PAYPLUG_ORDER_STATE_PAID_TEST'
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
                'PAYPLUG_ORDER_STATE_REFUND',
                'PAYPLUG_ORDER_STATE_REFUND_TEST'
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
                'PAYPLUG_ORDER_STATE_PENDING',
                'PAYPLUG_ORDER_STATE_PENDING_TEST'
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
                'PAYPLUG_ORDER_STATE_ERROR',
                'PAYPLUG_ORDER_STATE_ERROR_TEST'
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
                'PAYPLUG_ORDER_STATE_CANCELLED',
                'PAYPLUG_ORDER_STATE_CANCELLED_TEST'
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
                'PAYPLUG_ORDER_STATE_AUTH',
                'PAYPLUG_ORDER_STATE_AUTH_TEST'
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
                'PAYPLUG_ORDER_STATE_EXP',
                'PAYPLUG_ORDER_STATE_EXP_TEST'
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

    /** @var array */
    public $payment_status = [];
    public $PrestashopSpecificClass;
    public $PrestashopSpecificObject;
    /** @var array */
    public $validationErrors = [];
    public $install;
    public $context;
    public $constantFile;
    /** @var PluginEntity */
    protected $plugin;
    protected $query;
    private $card;
    /** @var string */
    private $email;
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
    public $features_json;
    /** @var string */
    private $html = '';
    /** @var string */
    private $img_lang;
    /** @var bool */
    private $is_active = 1;
    private $payment;
    private $paymentDetails;
    /** @var object */
    private $sql;
    private $tools;

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
        $this->currencies = true;
        $this->currencies_mode = 'checkbox';
        $this->description = $this->l('payplug.construct.description', 'payplugclass');
        $this->displayName = 'PayPlug';
        $this->module_key = '1ee28a8fb5e555e274bd8c2e1c45e31a';
        $this->need_instance = true;
        $this->ps_versions_compliancy = ['min' => '1.6', 'max' => '1.8'];
        $this->tab = 'payments_gateways';
        $this->oneyLogoUrl = '';
        $this->version = PAYPLUG_VERSION;
        $this->initializeAccessors();

        $this->loadEntities();
        parent::__construct();
        $this->loadSpecificPrestaClasses();

        if (file_exists(__DIR__."/../features.json")) {
            $this->features_json = json_decode(file_get_contents(__DIR__."/../features.json"));
        } else {
            $this->features_json = [];
        }
    }

    private function initializeAccessors()
    {
        $this->setPlugin((new PluginRepository($this))->getEntity());

        $this->card = $this->getPlugin()->getCard();
        $this->logger = $this->getPlugin()->getLogger();
        $this->oney = $this->getPlugin()->getOney();
        $this->payment = $this->getPlugin()->getPayment();
        $this->query = $this->getPlugin()->getQuery();
        $this->sql = $this->getPlugin()->getSql();
        $this->tools = $this->getPlugin()->getTools();
        $this->order_state = $this->getPlugin()->getOrderState();
        $this->install = $this->getPlugin()->getInstall();
        $this->context = $this->getPlugin()->getContext();

        $this->amountCurrencyClass = new AmountCurrencyClass($this->tools);
        $this->apiClass = new ApiClass($this);
        $this->mediaClass = new MediaClass($this);
        $this->orderClass = new OrderClass();
        $this->configClass = new ConfigClass($this);
        $this->refundClass = new RefundClass($this);
        $this->hookClass = new HookClass($this);

        $this->payment_status = $this->configClass->getPaymentStatus();
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

    /**
     * Load Specific Prestashop Classes
     */
    public function loadSpecificPrestaClasses()
    {
        $this->PrestashopSpecificClass = '\PayPlug\src\specific\PrestashopSpecific' . _PS_VERSION_[0] . _PS_VERSION_[2];
        if (class_exists($this->PrestashopSpecificClass)) {
            $this->PrestashopSpecificObject = new $this->PrestashopSpecificClass($this);
        }
    }
}
