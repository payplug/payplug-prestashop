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

class PayPlugClass extends PaymentModule
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
        $this->version = '3.5.0';

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

    public function abortPayment()
    {
        $inst_id = Tools::getValue('inst_id');
        $id_order = Tools::getValue('id_order');

        try {
            $abort = InstallmentPlan::abort($inst_id);
        } catch (Exception $e) {
            if (Configuration::get('PAYPLUG_SANDBOX_MODE') == 1) {
                ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                $abort = InstallmentPlan::abort($inst_id);
                ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
            } elseif (Configuration::get('PAYPLUG_SANDBOX_MODE') == 0) {
                ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                $abort = InstallmentPlan::abort($inst_id);
                ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
            }
        }

        if ($abort == 'error') {
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('payplug.abortPayment.cannotAbort', 'payplugclass')
            ]));
        } else {
            $installment = InstallmentClass::retrieveInstallment($inst_id);

            if ($installment->is_live == 1) {
                $new_state = (int)Configuration::get('PS_OS_CANCELED');
            } else {
                $new_state = (int)Configuration::get('PS_OS_CANCELED');
            }

            $order = new Order((int)$id_order);

            if (Validate::isLoadedObject($order)) {
                $current_state = (int)$order->getCurrentState();
                if ($current_state != 0 && $current_state !== $new_state) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$order->id;
                    $history->changeIdOrderState($new_state, (int)$order->id);
                    $history->addWithemail();
                }
            }
            InstallmentClass::updatePayplugInstallment($installment);
            $reload = true;

            die(json_encode(['reload' => $reload]));
        }
    }

//    /**
//     * Retrieve payment informations
//     *
//     * @param $inst_id
//     * @return bool|InstallmentPlan|null
//     */
//    public function retrieveInstallment($inst_id)
//    {
//        try {
//            $installment = InstallmentPlan::retrieve($inst_id);
//        } catch (Exception $e) {
//            return false;
//        }
//        return $installment;
//    }
//
//    /**
//     * @param $installment
//     * @return bool
//     */
//    public function updatePayplugInstallment($installment)
//    {
//        if (!is_object($installment)) {
//            $installment = InstallmentPlan::retrieve($installment);
//        }
//        if (isset($installment->schedule)) {
//            $step_count = count($installment->schedule);
//            $index = 0;
//            foreach ($installment->schedule as $schedule) {
//                $index++;
//                $pay_id = '';
//                if (count($schedule->payment_ids) > 0) {
//                    $pay_id = $schedule->payment_ids[0];
//                    $payment = Payment::retrieve($pay_id);
//                    $status = $this->getPaymentStatusByPayment($payment);
//                } else {
//                    if ((int)$installment->is_active == 1) {
//                        $status = 6; //ongoing
//                    } else {
//                        $status = 7; //cancelled
//                    }
//                }
//                $step = $index . '/' . $step_count;
//
//                if ($step2update = $this->getStoredInstallmentTransaction($installment, $step)) {
//                    $req_insert_installment = '
//                        UPDATE `' . _DB_PREFIX_ . 'payplug_installment`
//                        SET `id_payment` = \'' . pSQL($pay_id) . '\',
//                        `status` = \'' . (int)$status . '\'
//                        WHERE `id_payplug_installment` = ' . (int)$step2update['id_payplug_installment'];
//                    $res_insert_installment = DB::getInstance()->Execute($req_insert_installment);
//
//                    if (!$res_insert_installment) {
//                        return false;
//                    }
//                } else {
//                    return false;
//                }
//            }
//        }
//    }

    /**
     * @param $payment
     * @return int
     */
    public static function getPaymentStatusByPayment($payment)
    {

        /*
            1 => 'not paid',
            2 => 'paid',
            3 => 'failed',
            4 => 'partially refunded',
            5 => 'refunded',
            6 => 'on going',
            7 => 'cancelled',
            8 => 'authorized',
            9 => 'authorization expired',
            10 => 'oney pending',
            11 => 'abandoned',
        */
        if (!is_object($payment)) {
            $payment = Payment::retrieve($payment);
        }

        if ($payment->installment_plan_id !== null) {
            $installment = InstallmentPlan::retrieve($payment->installment_plan_id);
        } else {
            $installment = null;
        }

        $pay_status = 1; //not paid
        if ((int)$payment->is_paid == 1) {
            $pay_status = 2; //paid
        } elseif (isset($payment->payment_method)
            && isset($payment->payment_method['is_pending'])
            && (int)$payment->payment_method['is_pending'] == 1
        ) {
            $pay_status = 10; //oney pending
        } elseif (isset($payment->failure) && $payment->failure && $pay_status != 9) {
            if ($payment->failure->code == 'aborted') {
                $pay_status = 7; //cancelled
            } elseif ($payment->failure->code == 'timeout') {
                $pay_status = 11; //abandoned
            } else {
                $pay_status = 3; //failed
            }
        } elseif ($payment->authorization !== null && ($payment->authorization->expires_at - time()) > 0) {
            $pay_status = 8; //authorized
        } elseif ($payment->authorization !== null && ($payment->authorization->expires_at - time()) <= 0) {
            $pay_status = 9; //authorization expired
        } elseif ($payment->installment_plan_id !== null && (int)$installment->is_active == 1) {
            $pay_status = 6; //ongoing
        }
        if ((int)$payment->is_refunded == 1) {
            $pay_status = 5; //refunded
        } elseif ((int)$payment->amount_refunded > 0) {
            $pay_status = 4; //partially refunded
        }

        return $pay_status;
    }

//    /**
//     * @param $installment
//     * @param $step
//     * @return array|bool|object|null
//     */
//    public function getStoredInstallmentTransaction($installment, $step)
//    {
//        if (!is_object($installment)) {
//            $installment = InstallmentPlan::retrieve($installment);
//        }
//        $req_installment = '
//            SELECT pi.*
//            FROM `' . _DB_PREFIX_ . 'payplug_installment` pi
//            WHERE pi.id_installment = \'' . $installment->id . '\'
//            AND pi.step = ' . (int)$step;
//        $res_installment = DB::getInstance()->getRow($req_installment);
//
//        if (!$res_installment) {
//            return false;
//        } else {
//            return $res_installment;
//        }
//    }
//
//    /**
//     * @param $installment
//     * @param $order
//     * @return bool
//     * @throws ConfigurationNotSetException
//     */
//    public function addPayplugInstallment($installment, $order)
//    {
//        if (!is_object($installment)) {
//            $installment = InstallmentPlan::retrieve($installment);
//        }
//
//        if ($this->getStoredInstallment($installment)) {
//            $this->updatePayplugInstallment($installment);
//        } else {
//            if (isset($installment->schedule)) {
//                $step_count = count($installment->schedule);
//                $index = 0;
//                foreach ($installment->schedule as $schedule) {
//                    $index++;
//                    $pay_id = '';
//                    if (is_array($schedule->payment_ids) && count($schedule->payment_ids) > 0) {
//                        $pay_id = $schedule->payment_ids[0];
//                        $status = $this->getPaymentStatusByPayment($pay_id);
//                    } else {
//                        $status = 6;
//                    }
//                    $amount = (int)$schedule->amount;
//                    $step = $index . '/' . $step_count;
//                    $date = $schedule->date;
//                    $req_insert_installment = '
//                INSERT INTO `' . _DB_PREFIX_ . 'payplug_installment` (
//                    `id_installment`,
//                    `id_payment`,
//                    `id_order`,
//                    `id_customer`,
//                    `order_total`,
//                    `step`,
//                    `amount`,
//                    `status`,
//                    `scheduled_date`
//                ) VALUES (
//                    \'' . $installment->id . '\',
//                    \'' . $pay_id . '\',
//                    \'' . $order->id . '\',
//                    \'' . $order->id_customer . '\',
//                    \'' . (int)(($order->total_paid * 1000) / 10) . '\',
//                    \'' . $step . '\',
//                    \'' . $amount . '\',
//                    \'' . $status . '\',
//                    \'' . $date . '\'
//                )';
//
//                    $res_insert_installment = DB::getInstance()->Execute($req_insert_installment);
//
//                    if (!$res_insert_installment) {
//                        return false;
//                    }
//                }
//            }
//        }
//    }
//
//    /**
//     * @param $installment
//     * @return array|bool|false|mysqli_result|PDOStatement|resource|null
//     * @throws PrestaShopDatabaseException
//     */
//    public function getStoredInstallment($installment)
//    {
//        if (!is_object($installment)) {
//            $installment = InstallmentPlan::retrieve($installment);
//        }
//        $req_installment = '
//            SELECT pi.*
//            FROM `' . _DB_PREFIX_ . 'payplug_installment` pi
//            WHERE pi.id_payment = \'' . $installment->id . '\'';
//        $res_installment = DB::getInstance()->executeS($req_installment);
//
//        if (!$res_installment) {
//            return false;
//        } else {
//            return $res_installment;
//        }
//    }

    /**
     * @param $cart
     * @return bool
     */
    public function assignPaymentOptions($cart)
    {
        $standard = Configuration::get('PAYPLUG_STANDARD');
        $one_click = $standard && Configuration::get('PAYPLUG_ONE_CLICK');
        $bancontact = Configuration::get('PAYPLUG_BANCONTACT');
        $installment = Configuration::get('PAYPLUG_INST');
        $installment_mode = Configuration::get('PAYPLUG_INST_MODE');
        $installment_min_amount = Configuration::get('PAYPLUG_INST_MIN_AMOUNT');

        if (!$this->amountCurrencyClass->checkCurrency($cart) ||
            !$this->amountCurrencyClass->checkAmount($cart)) {
            return false;
        }

        $path_ssl = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/';

        $payplug_card = $this->card;

        $payplug_cards = $payplug_card->getByCustomer((int)$cart->id_customer, true);

        $use_taxes = Configuration::get('PS_TAX');
        $base_total_tax_inc = $cart->getOrderTotal(true);
        $base_total_tax_exc = $cart->getOrderTotal(false);

        if ($base_total_tax_inc < $installment_min_amount) {
            $installment = 0;
        }

        if ($use_taxes) {
            $price2display = $base_total_tax_inc;
        } else {
            $price2display = $base_total_tax_exc;
        }

        $this->smarty->assign([
            'this_path' => $this->_path,
            'this_path_ssl' => $path_ssl,
            'iso_lang' => $this->context->language->iso_code,
            'price2display' => $price2display,
        ]);

        $front_ajax_url = $this->context->link->getModuleLink($this->name, 'ajax', [], true);

        $this->smarty->assign([
            'front_ajax_url' => $front_ajax_url,
            'api_url' => $this->apiClass->getApiUrl(),
        ]);

        if (!empty($payplug_cards) && $one_click == 1) {
            $this->smarty->assign([
                'payplug_cards' => $payplug_cards,
                'payplug_one_click' => 1,
            ]);
        }

        $payment_url = 'index.php?controller=order&step=3';

        $payment_controller_url = $this->context->link->getModuleLink($this->name, 'payment', [], true);
        $installment_controller_url = $this->context->link->getModuleLink($this->name, 'payment', ['i' => 1], true);
        $current_lang = explode('-', $this->context->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, ['it', 'en'], true)) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        $this->smarty->assign([
            'spinner_url' => Tools::getHttpHost(true)
                . __PS_BASE_URI__ . 'modules/payplug/views/img/admin/spinner.gif',
            'payment_url' => $payment_url,
            'payment_controller_url' => $payment_controller_url,
            'installment_controller_url' => $installment_controller_url,
            'img_lang' => $img_lang,
            'payplug_installment' => $installment,
            'installment_mode' => $installment_mode,
        ]);
    }

    public function capturePayment()
    {
        $this->logger->addLog('[Payplug] Start capture', 'notice');
        $pay_id = Tools::getValue('pay_id');
        $id_order = Tools::getValue('id_order');
        $payment = new PPPayment($pay_id);
        $capture = $payment->capture();
        $payment->refresh();
        if ($payment->resource->card->id !== null) {
            $this->logger->addLog('Save the payment card', 'notice');
            $this->card->saveCard($payment->resource);
        }
        if ($capture['code'] >= 300) {
            $this->logger->addLog('Cannot capture this payment', 'notice');
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('payplug.capturePayment.cannotCapture', 'payplugclass'),
                'message' => $capture['message'],
            ]));
        } else {
            $state_addons = ($payment->resource->is_live ? '' : '_TEST');
            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);

            $order = new Order((int)$id_order);
            if (Validate::isLoadedObject($order)) {
                if (!$this->createLockFromCartId($order->id_cart)) {
                    $this->logger->addLog('An error occured on lock creation', 'notice');
                    die(json_encode([
                        'status' => 'error',
                        'data' => $this->l('payplug.capturePayment.errorOccurred', 'payplugclass')
                    ]));
                }

                $order->setInvoice(true);
                $current_state = (int)$order->getCurrentState();
                $this->logger->addLog('Current order state: ' . $current_state, 'notice');
                if ($current_state != 0 && $current_state != $new_state) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$order->id;
                    $this->logger->addLog('New order state: ' . $new_state, 'notice');
                    $history->changeIdOrderState($new_state, (int)$order->id);
                    $history->addWithemail();
                }

                if (!$this->deleteLockFromCartId($order->id_cart)) {
                    $this->logger->addLog('Lock cannot be deleted.', 'error');
                } else {
                    $this->logger->addLog('Lock deleted.', 'notice');
                }
            }

            die(json_encode([
                'status' => 'ok',
                'data' => '',
                'message' => $this->l('payplug.capturePayment.captured.', 'payplugclass'),
                'reload' => true,
            ]));
        }
    }

    /**
     * @description Create a lock from a Cart ID
     * @param bool $id_cart
     * @return bool
     */
    public function createLockFromCartId($id_cart = false)
    {
        if (!$id_cart) {
            return false;
        }

        $this->logger->addLog('Lock creation', 'notice');

        $creation_date = new DateTime('now');
        $duration = '10S';
        $lifetime = new DateInterval('PT' . $duration);
        $end_of_life = $creation_date->add($lifetime);

        do {
            $cart_lock = PayplugLock::createLockG2($id_cart, 'payplug');

            if (!$cart_lock) {
                $time = new DateTime('now');
                if ($time > $end_of_life) {
                    $this->logger->addLog(
                        'Try to create lock during ' . $duration . ' sec, but can\'t proceed',
                        'error'
                    );
                    return false;
                }
            } else {
                $this->logger->addLog('Lock created', 'notice');
            }
        } while (!$cart_lock);

        return true;
    }

    /**
     * @description Delete payplug lock for given id cart
     * @param bool $id_cart
     * @return bool
     */
    public function deleteLockFromCartId($id_cart = false)
    {
        if (!$id_cart) {
            return false;
        }
        return PayplugLock::deleteLockG2($id_cart);
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
            $errors[$error_key] = $this->l('payplug.catchErrorsFromApi.transactionNotCompleted', 'payplugclass');
            return $errors;
        }

        $keys = array_keys($response['details']);
        foreach ($keys as $key) {
            // add specific error message
            switch ($key) {
                default:
                    $error_key = md5('The transaction was not completed and your card was not charged.');
                    // push error only if not catched before
                    if (!array_key_exists($error_key, $errors)) {
                        $errors[$error_key] =
                            $this->l('payplug.catchErrorsFromApi.transactionNotCompleted', 'payplugclass');
                    }
            }
        }

        return $errors;
    }

//    /**
//     * Delete stored installment
//     *
//     * @param string $inst_id
//     * @param array $cart_id
//     * @return bool
//     */
//    public function deleteInstallment($inst_id, $cart_id)
//    {
//        $req_installment_cart = '
//            DELETE FROM ' . _DB_PREFIX_ . 'payplug_payment
//            WHERE id_cart = ' . (int)$cart_id . '
//            AND id_payment = \'' . pSQL($inst_id) . '\'';
//        $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
//        if (!$res_installment_cart) {
//            return false;
//        }
//
//        return true;
//    }

    /**
     * Display payment errors messages template
     *
     * @param array $errors
     * @return mixed
     */
    public function displayPaymentErrors($errors = [])
    {
        if (empty($errors)) {
            return false;
        }

        $formated = [];
        $with_msg_button = false;

        foreach ($errors as $error) {
            if (strpos($error, 'oney_required_field') !== false) {
                $this->smarty->assign(['is_popin_tpl' => true]);
                $fields = $this->oney->getOneyRequiredFields();
                $this->smarty->assign([
                    'oney_type' => str_replace('oney_required_field_', '', $error),
                    'oney_required_fields' => $fields,
                ]);
                $formated[] = [
                    'type' => 'template',
                    'value' => 'oney/required.tpl'
                ];
            } else {
                $with_msg_button = true;
                $formated[] = [
                    'type' => 'string',
                    'value' => $error
                ];
            }
        }

        $this->smarty->assign([
            'is_error_message' => true,
            'messages' => $formated,
            'with_msg_button' => $with_msg_button
        ]);

        return $this->fetchTemplate('_partials/messages.tpl');
    }

    public function fetchTemplate($file)
    {
        if ($this->context->smarty->tpl_vars) {
            foreach ($this->context->smarty->tpl_vars as $key => $value) {
                if (strpos($key, 'feature_') !== false && !$this->isValidFeature($key)) {
                    unset($this->context->smarty->tpl_vars[$key]);
                }
            }
        }

        $output = $this->display(_PS_MODULE_DIR_ . 'payplug/payplug.php', $file);
        return $output;
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

    public function getAllowedPaymentOptions($cart)
    {
        $options = [
            'standard' => false,
            'oneclick' => false,
            'installment' => false,
            'oney' => false,
        ];

        if (!$this->active ||
            !Configuration::get('PAYPLUG_SHOW') ||
            !$this->amountCurrencyClass->checkCurrency($cart) ||
            !$this->amountCurrencyClass->checkAmount($cart)) {
            return $options;
        }

        // check if installment allowed
        $installment = Configuration::get('PAYPLUG_INST');
        $installment_min_amount = Configuration::get('PAYPLUG_INST_MIN_AMOUNT');
        $order_total = $cart->getOrderTotal(true);
        $installment = $installment && $order_total >= $installment_min_amount;

        // check if one click allowed
        $one_click = Configuration::get('PAYPLUG_ONE_CLICK');
        $payplug_card = $this->card;
        $payplug_cards = $payplug_card->getByCustomer((int)$cart->id_customer, true);
        $one_click = (bool)($one_click && !empty($payplug_cards));

        // check if oney is allowed
        $oney = Configuration::get('PAYPLUG_ONEY');

        $options = [
            'standard' => true,
            'oneclick' => $one_click,
            'installment' => $installment,
            'oney' => $oney,
        ];

        return $options;
    }

    /**
     * get the payment method for a given payment card
     *
     * @param string $card
     * @return object PayPlugPaymentStandard|PayPlugPaymentInstallment|PayPlugPaymentOneClick|PayPlugPaymentOney
     */
    public function getCurrentPaymentMethod($card = null)
    {
        $card = $card != null ? $card : Tools::getValue('pc', null);

        // check if is Installment
        if (Tools::getValue('io') || Tools::getValue('type') == 'oney') {
            $payment_method = 'PayPlugPaymentOney';
        } elseif (Tools::getValue('i') || Tools::getValue('type') == 'installment') {
            $payment_method = 'PayPlugPaymentInstallment';
        } elseif (($card != null && $card != 'new_card') || Tools::getValue('type') == 'oneclick') {
            $payment_method = 'PayPlugPaymentOneClick';
        } elseif (Tools::getValue('type') == 'standard') {
            $payment_method = 'PayPlugPaymentStandard';
        } else {
            $payment_method = 'PayPlugPaymentStandard';
        }
        return $payment_method;
    }

    /**
     * Get payment errors from cookie
     *
     * @return mixed
     */
    public function getPaymentErrorsCookie()
    {
        // get payplug errors
        $cookie_errors = $this->context->cookie->__get('payplug_errors');
        $payplug_errors = !empty($cookie_errors) ? $cookie_errors : false;

        // then flush to avoid repetition
        $this->context->cookie->__set('payplug_errors', '');

        // if no error all good then return true
        return json_decode($payplug_errors, true);
    }

    /**
     * @param $id_status
     * @param null $id_lang
     * @return mixed
     */
    public function getPaymentStatusById($id_status, $id_lang = null)
    {
        if ($id_lang == null) {
            $id_lang = (int)$this->context->language->id;
        }

        return $this->payment_status[$id_status];
    }

    /**
     * @return string
     */
    public function getUninstallContent()
    {
        $this->configClass->postProcess();
        $this->html = '';

        $PAYPLUG_KEEP_CARDS = (int)Configuration::get('PAYPLUG_KEEP_CARDS');

        $this->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin.js');
        $this->mediaClass->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin.css');

        $this->context->smarty->assign([
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'site_url' => $this->apiClass->getSiteUrl(),
            'PAYPLUG_KEEP_CARDS' => $PAYPLUG_KEEP_CARDS,
        ]);

        $this->html .= $this->fetchTemplate('/views/templates/admin/admin_uninstall_configuration.tpl');

        return $this->html;
    }

//    /**
//     * @description To load admin and admin_order (js and css) in order details in PS 1.7.7.0
//     */
//    public function hookActionAdminControllerSetMedia()
//    {
//        if ($this->context->controller->controller_name == 'AdminOrders') {
//            $this->mediaClass->setMedia([
//                __PS_BASE_URI__ . 'modules/payplug/views/css/admin_order.css',
//                __PS_BASE_URI__ . 'modules/payplug/views/js/admin_order.js',
//            ]);
//        } else {
//            $this->mediaClass->setMedia([
//                __PS_BASE_URI__ . 'modules/payplug/views/js/admin.js',
//                __PS_BASE_URI__ . 'modules/payplug/views/css/admin.css',
//            ]);
//        }
//    }
//
//    /**
//     * @description Flush PayPlugCache (PS 1.6), when PrestaShop cache cleared
//     *
//     * @param array $params
//     * @return boolean
//     */
//    public function hookActionAdminPerformanceControllerAfter($params)
//    {
//        if ($this->sql->checkExistingTable('payplug_cache', 1)) {
//            return $this
//                ->getPlugin()
//                ->getCache()
//                ->flushCache();
//        }
//    }
//
//    /**
//     * @description Flush PayPlugCache (PS 1.7), when PrestaShop cache cleared
//     *
//     * @param array $params
//     * @return boolean
//     */
//    public function hookActionClearCompileCache($params)
//    {
//        if ($this->sql->checkExistingTable('payplug_cache', 1)) {
//            return $this
//                ->getPlugin()
//                ->getCache()
//                ->flushCache();
//        }
//    }
//
//    /**
//     * @param $customer
//     * @return false|string
//     */
//    public function hookActionDeleteGDPRCustomer($customer)
//    {
//        if (!$this->card->deleteCards((int)$customer['id'])) {
//            return json_encode($this->oldTranslate('payplug.hookActionDeleteGDPRCustomer.unableDelete'));
//        }
//        return json_encode(true);
//    }
//
//    /**
//     * @param $customer
//     * @return false|string
//     * @throws PrestaShopDatabaseException
//     */
//    public function hookActionExportGDPRData($customer)
//    {
//        if (!$cards = $this->configClass->gdprCardExport((int)$customer['id'])) {
//            return json_encode($this->oldTranslate('payplug.hookActionExportGDPRData.unableToExport'));
//        } else {
//            return json_encode($cards);
//        }
//    }
//
//    /**
//     * @param $params
//     * @throws PrestaShopDatabaseException
//     * @throws PrestaShopException
//     */
//    public function hookActionOrderStatusUpdate($params)
//    {
//        $active = false;
//        $order = new Order((int)$params['id_order']);
//        $active = Module::isEnabled($this->name);
//        if (!$active
//            || $order->payment != $this->displayName
//            || !$this->isReferredPaymentsActive()
//            || !$this->isReferredAutoActive()
//            || $params['newOrderStatus']->id != Configuration::get('PAYPLUG_DEFERRED_STATE')
//        ) {
//            return;
//        } else {
//            $cart = new Cart((int)$order->id_cart);
//            $payment_method = $this->getPaymentMethodByCart($cart);
//            if ($payment_method['type'] == 'installment') {
//                $installment = new PPPaymentInstallment($payment_method['id']);
//                $payment = $installment->getFirstPayment();
//            } else {
//                $payment = new PPPayment($payment_method['id']);
//            }
//            if (!$payment->isPaid()) {
//                $payment->capture();
//                $payment->refresh();
//                if ($payment->resource->card->id !== null) {
//                    $this->card->saveCard($payment->resource);
//                }
//            }
//        }
//    }
//
//    public function hookActionUpdateLangAfter($params)
//    {
//        $payplug_order_states = explode(',', $this->orderClass->getPayPlugOrderStates($this->name));
//
//        if (empty($payplug_order_states) || !in_array($params['lang']->iso_code, $this->payplug_languages)) {
//            return true;
//        }
//
//        $all_order_states = array_merge($this->order_states, $this->oney_order_state);
//
//        foreach ($all_order_states as $order_state) {
//            foreach ($order_state['payplug_cfg'] as $payplug_conf) {
//                if (in_array(Configuration::get($payplug_conf), $payplug_order_states)) {
//                    $ps_order_state_name = $order_state['name'][$params['lang']->iso_code];
//                    if (strpos($payplug_conf, '_TEST')) {
//                        $ps_order_state_name .= ' [TEST]';
//                    } else {
//                        $ps_order_state_name .= ' [PayPlug]';
//                    }
//
//                    $ps_order_state = new OrderState(Configuration::get($payplug_conf));
//                    $ps_order_state->name[$params['lang']->id] = $ps_order_state_name;
//                    $ps_order_state->save();
//                }
//            }
//        }
//
//        return true;
//    }

    /**
     * @return bool
     */
    public function isDeferredPaymentsActive()
    {
        return (int)Configuration::get('PAYPLUG_DEFERRED') == 1;
    }

    /**
     * @return bool
     */
    public function isDeferredAutoActive()
    {
        return (int)Configuration::get('PAYPLUG_DEFERRED_AUTO') == 1;
    }

    /**
     * Check payment method for given cart object
     *
     * @param object Cart
     * @return array|bool pay_id or inst_id or False
     */
    public function getPaymentMethodByCart($cart)
    {
        if (!is_object($cart)) {
            $cart = new Cart((int)$cart);
        }

        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $inst_id = InstallmentClass::getInstallmentByCart($cart->id);
        if ($inst_id) {
            return ['id' => $inst_id, 'type' => 'installment'];
        }

        $pay_id = self::getPaymentByCart($cart->id);
        if ($pay_id) {
            return ['id' => $pay_id, 'type' => 'payment'];
        }

        return false;
    }

//    /**
//     * @description ONLY FOR VALIDATION
//     * Retrieve installment stored
//     *
//     * @param int $id_cart
//     * @return int OR bool
//     */
//    public function getInstallmentByCart($id_cart)
//    {
//        $req_installment_cart = '
//            SELECT pic.id_payment
//            FROM ' . _DB_PREFIX_ . 'payplug_payment pic
//            WHERE pic.id_cart = ' . (int)$id_cart . ' AND pic.payment_method = \'installment\'';
//        $res_installment_cart = Db::getInstance()->getValue($req_installment_cart);
//        if (!$res_installment_cart) {
//            return false;
//        }
//
//        return $res_installment_cart;
//    }

    /**
     * @description ONLY FOR VALIDATION
     * Retrieve payment stored
     *
     * @param int $cart_id
     * @return int|bool
     */
    public static function getPaymentByCart($cart_id)
    {
        $req_payment_cart = new DbQuery();
        $req_payment_cart->select('ppc.id_payment');
        $req_payment_cart->from('payplug_payment', 'ppc');
        $req_payment_cart->where('ppc.payment_method != \'installment\' AND ppc.id_cart = ' . (int)$cart_id);
        $res_payment_cart = Db::getInstance()->getValue($req_payment_cart);

        if (!$res_payment_cart) {
            return false;
        }

        return $res_payment_cart;
    }

//    /**
//     * @description retrocompatibility of hookDisplayAdminOrderMain for version before 1.7.7.0
//     *
//     * @param $params
//     * @return string
//     * @throws PrestaShopDatabaseException
//     * @throws PrestaShopException
//     * @throws ConfigurationException
//     */
//    public function hookAdminOrder($params)
//    {
//        if (version_compare(_PS_VERSION_, '1.7.7.0', '<')) {
//            return $this->hookDisplayAdminOrderMain($params);
//        }
//    }
//
//    /**
//     * @param array $params
//     * @return string
//     * @throws PrestaShopDatabaseException
//     * @throws PrestaShopException
//     * @throws ConfigurationException
//     * @see Module::hookAdminOrder()
//     */
//    public function hookDisplayAdminOrderMain($params)
//    {
//        if (!$this->active) {
//            return;
//        }
//
//        $this->html = '';
//        $order = new Order((int)$params['id_order']);
//        if (!Validate::isLoadedObject($order)) {
//            return false;
//        }
//
//        if ($order->module != $this->name) {
//            return false;
//        }
//
//        $show_popin = false;
//        $display_refund = false;
//        $refund_delay_oney = false;
//        $show_menu_refunded = false;
//        $show_menu_update = false;
//        $show_menu_installment = false;
//        $show_menu_payment = false;
//        $pay_error = '';
//        $amount_refunded_payplug = 0;
//        $amount_available = 0;
//
//        $admin_ajax_url = AdminClass::getAdminAjaxUrl('AdminModules', (int)$params['id_order']);
//        $amount_refunded_presta = RefundClass::getTotalRefunded($order->id);
//
//        $inst_id = null;
//        $payment_id = $this->getPayplugInstallmentCart($order->id_cart);
//        // Backward if order validated before
//        if (!$payment_id) {
//            $payment_id = $this->getPayplugInstallmentCartBackward($order->id_cart);
//        }
//
//        if ($payment_id && strpos($payment_id, 'inst') !== false) {
//            $inst_id = $payment_id;
//        }
//        if ($inst_id) {
//            $payment_list = [];
//            if (!$inst_id || empty($inst_id) || !$installment = InstallmentClass::retrieveInstallment($inst_id)) {
//                if (Configuration::get('PAYPLUG_SANDBOX_MODE') == 1) {
//                    ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
//                    if (empty($inst_id) || !$installment = InstallmentClass::retrieveInstallment($inst_id)) {
//                        ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
//                        return false;
//                    }
//                } elseif (Configuration::get('PAYPLUG_SANDBOX_MODE') == 0) {
//                    ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
//                    if (empty($inst_id) || !$installment = InstallmentClass::retrieveInstallment($inst_id)) {
//                        ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
//                        return false;
//                    }
//                }
//            }
//
//            $pay_mode = $installment->is_live
//                ? $this->oldTranslate('payplug.hookDisplayAdminOrderMain.live')
//                : $this->oldTranslate('payplug.hookDisplayAdminOrderMain.test');
//            $payments = $order->getOrderPaymentCollection();
//            $pps = [];
//            if (count($payments) > 0) {
//                foreach ($payments as $payment) {
//                    $pps[] = $payment->transaction_id;
//                }
//            }
//
//            $payment_list_new = [];
//            foreach ($installment->schedule as $schedule) {
//                if ($schedule->payment_ids != null) {
//                    foreach ($schedule->payment_ids as $pay_id) {
//                        $p = $this->retrievePayment($pay_id);
//                        $payment_list_new[] = $this->buildPaymentDetails($p);
//                        if ((int)$p->is_paid == 0) {
//                            $amount_refunded_payplug += 0;
//                            $amount_available += 0;
//                        } elseif ((int)$p->is_refunded == 1) {
//                            $amount_refunded_payplug += ($p->amount_refunded) / 100;
//                            $amount_available += ($p->amount - $p->amount_refunded) / 100;
//                        } elseif ((int)$p->amount_refunded > 0) {
//                            $amount_refunded_payplug += ($p->amount_refunded) / 100;
//                            $amount_refundable_payment = ($p->amount - $p->amount_refunded);
//                            if ($amount_refundable_payment >= 10) {
//                                $amount_available += $amount_refundable_payment / 100;
//                            }
//                        } else {
//                            $amount_available += ($p->amount >= 10 ? $p->amount / 100 : 0);
//                        }
//
//                        if ($amount_available > 0) {
//                            $display_refund = true;
//                        }
//
//                        if ($p->amount_refunded > 0) {
//                            $show_menu_refunded = true;
//                        }
//                    }
//                } else {
//                    $payment_list_new[] = [
//                        'id' => null,
//                        'status' => $installment->is_active ? $this->payment_status[6] : $this->payment_status[7],
//                        'status_class' => $installment->is_active ? 'pp_success' : 'pp_error',
//                        'status_code' => 'incoming',
//                        'amount' => (int)$schedule->amount / 100,
//                        'card_brand' => null,
//                        'card_mask' => null,
//                        'tds' => null,
//                        'card_date' => null,
//                        'mode' => null,
//                        'authorization' => null,
//                        'date' => date('d/m/Y', strtotime($schedule->date)),
//                    ];
//                }
//            }
//
//            $id_currency = (int)Currency::getIdByIsoCode($installment->currency);
//            $show_menu_installment = true;
//            $inst_status = $installment->is_active ?
//                $this->oldTranslate('payplug.hookDisplayAdminOrderMain.ongoing') :
//                (
//                    $installment->is_fully_paid ?
//                    $this->oldTranslate('payplug.hookDisplayAdminOrderMain.paid') :
//                    $this->oldTranslate('payplug.hookDisplayAdminOrderMain.suspended')
//                );
//            $inst_status_code = $installment->is_active ?
//                'ongoing' :
//                ($installment->is_fully_paid ? 'paid' : 'suspended');
//            $inst_aborted = !$installment->is_active;
//            $ppInstallment = new PPPaymentInstallment($installment->id);
//            $instPaymentOne = $ppInstallment->getFirstPayment();
//            $inst_can_be_aborted = !($inst_aborted || ($instPaymentOne->isDeferred() && !$instPaymentOne->isPaid()));
//            $inst_paid = $installment->is_fully_paid;
//            $this->context->smarty->assign([
//                'inst_id' => $inst_id,
//                'inst_status' => $inst_status,
//                'inst_status_code' => $inst_status_code,
//                'inst_aborted' => $inst_aborted,
//                'inst_paid' => $inst_paid,
//                'payment_list' => $payment_list,
//                'payment_list_new' => $payment_list_new,
//                'inst_can_be_aborted' => $inst_can_be_aborted,
//            ]);
//
//            $sandbox = ((int)$installment->is_live == 1 ? false : true);
//            $state_addons = ($sandbox ? '_TEST' : '');
//            $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
//
//            InstallmentClass::updatePayplugInstallment($installment);
//        } else {
//            if (!$pay_id = $this->isTransactionPending($order->id_cart)) {
//                $pay_id = $this->orderClass->getPayplugOrderPayment($order->id);
//
//                if (!$pay_id) {
//                    $payments = $order->getOrderPaymentCollection();
//                    if (count($payments->getResults()) > 1 || !$payments->getFirst()) {
//                        return false;
//                    } else {
//                        $pay_id = $payments->getFirst()->transaction_id;
//                    }
//                }
//            }
//
//            $sandbox = (bool)Configuration::get('PAYPLUG_SANDBOX_MODE');
//
//            if (!$pay_id || empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
//                if ($sandbox) {
//                    ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
//                    if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
//                        ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
//                        return false;
//                    }
//                } else {
//                    ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
//                    if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
//                        ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
//                        return false;
//                    }
//                }
//            }
//
//            // check if order is from oney payment
//            $oney_payment_method = [
//                'oney_x3_with_fees',
//                'oney_x4_with_fees',
//                'oney_x3_without_fees',
//                'oney_x4_without_fees',
//            ];
//
//            $is_oney = isset($payment->payment_method)
//                && isset($payment->payment_method['type'])
//                && in_array($payment->payment_method['type'], $oney_payment_method);
//
//            // Update order state if is pending
//            $state_addons = $payment->is_live ? '' : '_TEST';
//            $paid_state = Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
//            $oney_state = Configuration::get('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons);
//            $cancelled_state = Configuration::get('PS_OS_CANCELED');
//
//            if ($is_oney) {
//                // update order state from payment status
//                if ($order->getCurrentState() == $oney_state) {
//                    $new_order_state = false;
//                    if ($payment->is_paid) {
//                        $new_order_state = $paid_state;
//                    } elseif (isset($payment->failure) && $payment->failure !== null) {
//                        $new_order_state = $cancelled_state;
//                    }
//
//                    if ($new_order_state) {
//                        $order_history = new OrderHistory();
//                        $order_history->id_order = $order->id;
//                        $order_history->changeIdOrderState($new_order_state, $order->id, true);
//                        $order_history->save();
//                    }
//                }
//            }
//
//            $single_payment = $this->buildPaymentDetails($payment);
//            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
//            $amount_available_payment = ($payment->amount - $payment->amount_refunded);
//            $amount_available = ($amount_available_payment >= 10 ? $amount_available_payment / 100 : 0);
//            $id_currency = (int)Currency::getIdByIsoCode($payment->currency);
//            $state_addons = (!$payment->is_live ? '_TEST' : '');
//
//            $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
//            $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);
//
//            $current_state = (int)$order->getCurrentState();
//
//            if ((int)$payment->is_paid == 0) {
//                if (isset($payment->failure) && isset($payment->failure->message)) {
//                    $pay_error = '(' . $payment->failure->message . ')';
//                } else {
//                    $pay_error = '';
//                }
//                $display_refund = false;
//                if ($current_state != 0 && $current_state == $id_pending_order_state) {
//                    $show_menu_update = true;
//                }
//            } elseif ((((int)$payment->amount_refunded > 0)
//                    || $amount_refunded_presta > 0)
//                && (int)$payment->is_refunded != 1) {
//                $display_refund = true;
//            } elseif ((int)$payment->is_refunded == 1) {
//                $show_menu_refunded = true;
//                $display_refund = false;
//            } elseif (time() >= $payment->refundable_until) {
//                $display_refund = false;
//            } else {
//                $display_refund = true;
//                if ($is_oney) {
//                    $refund_delay_oney = time() <= $payment->refundable_after;
//                }
//            }
//
//            $conf = (int)Tools::getValue('conf');
//            if ($conf == 30 || $conf == 31) {
//                $show_popin = true;
//
//                $admin_ajax_url = AdminClass::getAdminAjaxUrl('AdminModules', (int)$params['id_order']);
//
//                $this->html .= '<a class="pp_admin_ajax_url" href="' . $admin_ajax_url . '"></a>';
//            }
//
//            $pay_status = ((int)$payment->is_paid == 1)
//                ? $this->oldTranslate('payplug.hookDisplayAdminOrderMain.paid')
//                : $this->oldTranslate('payplug.hookDisplayAdminOrderMain.notPaid');
//            if ((int)$payment->is_refunded == 1) {
//                $pay_status = $this->oldTranslate('payplug.hookDisplayAdminOrderMain.refunded');
//            } elseif ((int)$payment->amount_refunded > 0) {
//                $pay_status = $this->oldTranslate('payplug.hookDisplayAdminOrderMain.partiallyRefunded');
//            }
//            $pay_amount = (int)$payment->amount / 100;
//            $pay_date = date('d/m/Y H:i', (int)$payment->created_at);
//            if ($payment->card->brand != '') {
//                $pay_brand = $payment->card->brand;
//            } else {
//                $pay_brand = $this->oldTranslate('payplug.hookDisplayAdminOrderMain.unavailable');
//            }
//            if ($payment->card->country != '') {
//                $pay_brand .= ' ' . $this->oldTranslate('payplug.hookDisplayAdminOrderMain.card') .
//                    ' (' . $payment->card->country . ')';
//            }
//            if ($payment->card->last4 != '') {
//                $pay_card_mask = '**** **** **** ' . $payment->card->last4;
//            } else {
//                $pay_card_mask = $this->oldTranslate('payplug.hookDisplayAdminOrderMain.unavailable');
//            }
//
//            // Deferred payment does'nt display 3DS option before capture so we have to consider it null
//            if ($payment->is_3ds !== null) {
//                $pay_tds = $payment->is_3ds
//                    ? $this->oldTranslate('payplug.hookDisplayAdminOrderMain.yes')
//                    : $this->oldTranslate('payplug.hookDisplayAdminOrderMain.no');
//                $this->context->smarty->assign(['pay_tds' => $pay_tds]);
//            }
//
//            $pay_mode = $payment->is_live
//                ? $this->oldTranslate('payplug.hookDisplayAdminOrderMain.live')
//                : $this->oldTranslate('payplug.hookDisplayAdminOrderMain.test');
//
//            if ($payment->card->exp_month === null) {
//                $pay_card_date = $this->oldTranslate('payplug.hookDisplayAdminOrderMain.unavailable');
//            } else {
//                $pay_card_date = date(
//                    'm/y',
//                    strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year)
//                );
//            }
//
//            $show_menu_payment = true;
//
//            $this->context->smarty->assign([
//                'pay_id' => $pay_id,
//                'pay_status' => $pay_status,
//                'pay_amount' => $pay_amount,
//                'pay_date' => $pay_date,
//                'pay_brand' => $pay_brand,
//                'pay_card_mask' => $pay_card_mask,
//                'pay_card_date' => $pay_card_date,
//                'pay_error' => $pay_error,
//            ]);
//
//            //Deferred payment does'nt display 3DS option before capture so we have to consider it null
//            if ($payment->is_3ds !== null) {
//                $pay_tds = $payment->is_3ds
//                    ? $this->oldTranslate('payplug.hookDisplayAdminOrderMain.yes')
//                    : $this->oldTranslate('payplug.hookDisplayAdminOrderMain.no');
//                $this->context->smarty->assign(['pay_tds' => $pay_tds]);
//            }
//        }
//
//        $currency = new Currency($id_currency);
//        if (!Validate::isLoadedObject($currency)) {
//            return false;
//        }
//
//        $amount_suggested = (min($amount_refunded_presta, $amount_available) - $amount_refunded_payplug);
//        $amount_suggested = number_format((float)$amount_suggested, 2);
//        if ($amount_suggested < 0) {
//            $amount_suggested = 0;
//        }
//
//        if ($display_refund) {
//            $this->context->smarty->assign([
//                'order' => $order,
//                'amount_refunded_payplug' => $amount_refunded_payplug,
//                'amount_available' => $amount_available,
//                'amount_refunded_presta' => $amount_refunded_presta,
//                'currency' => $currency,
//                'amount_suggested' => $amount_suggested,
//                'id_new_order_state' => $id_new_order_state,
//            ]);
//        } elseif ($show_menu_refunded) {
//            $this->context->smarty->assign([
//                'amount_refunded_payplug' => $amount_refunded_payplug,
//                'currency' => $currency,
//            ]);
//        } elseif ($show_menu_update) {
//            $this->context->smarty->assign([
//                'admin_ajax_url' => $admin_ajax_url,
//                'order' => $order,
//            ]);
//        }
//
//        $display_single_payment = $show_menu_payment;
//        $this->context->smarty->assign([
//            'logo_url' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
//            'admin_ajax_url' => $admin_ajax_url,
//            'display_single_payment' => $display_single_payment,
//            'display_refund' => $display_refund,
//            'refund_delay_oney' => $refund_delay_oney,
//            'show_menu_payment' => $show_menu_payment,
//            'show_menu_refunded' => $show_menu_refunded,
//            'show_menu_update' => $show_menu_update,
//            'show_menu_installment' => $show_menu_installment,
//            'pay_mode' => $pay_mode,
//            'order' => $order,
//        ]);
//
//        if ($display_single_payment) {
//            $this->context->smarty->assign([
//                'single_payment' => $single_payment,
//            ]);
//        }
//
//        if ($show_popin && $display_refund) {
//            $this->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin_order_popin.js');
//        }
//
//        // check order state history
//        $undefined_history_states = $this->getUndefinedOrderHistory($order->id);
//        if (!empty($undefined_history_states)) {
//            $payplug_order_state_url = 'https://support.payplug.com/hc/'
//                . $this->context->language->iso_code
//                . '/articles/4406805105298';
//            $this->context->smarty->assign([
//                'payplug_order_state_url' => $payplug_order_state_url,
//                'undefined_history_states' => $undefined_history_states,
//            ]);
//        }
//
//        $this->html .= $this->fetchTemplate('/views/templates/admin/order/order.tpl');
//        return $this->html;
//    }

    /**
     * @description get the undefined order state on an history
     * @param int $orderId
     * @return array
     */
    public function getUndefinedOrderHistory($orderId = false)
    {
        if (!$orderId || !is_int($orderId)) {
            return [];
        }

        $order_history_states = $this->query
            ->select()
            ->fields('oh.id_order_state, osl.name')
            ->from(_DB_PREFIX_ . 'order_history', 'oh')
            ->leftJoin(_DB_PREFIX_ . 'order_state_lang', 'osl', 'osl.`id_order_state` = oh.`id_order_state`')
            ->where('oh.id_order = ' . $orderId)
            ->where('osl.id_lang = ' . $this->context->language->id)
            ->build();

        if (empty($order_history_states)) {
            return [];
        }

        foreach ($order_history_states as $key => &$state) {
            $type = $this->plugin->getOrderState()->getType((int)$state['id_order_state']);
            $state['type'] = $type;
            if (!$type || 'undefined' != $type) {
                unset($order_history_states[$key]);
                continue;
            }
            $update_link_params = [
                'updateorder_state' => '',
                'id_order_state' => $state['id_order_state']
            ];
            $state['updateLink'] = AdminClass::getAdminUrl('AdminStatuses', $update_link_params);
        }

        return $order_history_states;
    }

//    /**
//     * Get total amount already refunded
//     *
//     * @param $id_order
//     * @return bool|int
//     * @throws PrestaShopDatabaseException
//     * @throws PrestaShopException
//     */
//    private function getTotalRefunded($id_order)
//    {
//        $order = new Order((int)$id_order);
//        if (!Validate::isLoadedObject($order)) {
//            return false;
//        } else {
//            $amount_refunded_presta = 0;
//            $flag_shipping_refunded = false;
//
//            $order_slips = OrderSlip::getOrdersSlip($order->id_customer, $order->id);
//            if (isset($order_slips) && !empty($order_slips) && sizeof($order_slips)) {
//                foreach ($order_slips as $order_slip) {
//                    $amount_refunded_presta += $order_slip['amount'];
//                    if (!$flag_shipping_refunded && $order_slip['shipping_cost'] == 1) {
//                        $amount_refunded_presta += $order_slip['shipping_cost_amount'];
//                        $flag_shipping_refunded = true;
//                    }
//                }
//            }
//
//            return $amount_refunded_presta;
//        }
//    }

    /**
     * get cart installment
     *
     * @param $id_cart
     * @return bool
     */
    public function getPayplugInstallmentCart($id_cart)
    {
        $req_cart_installment = '
            SELECT pic.id_payment
            FROM ' . _DB_PREFIX_ . 'payplug_payment pic
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_cart_installment = Db::getInstance()->getValue($req_cart_installment);

        return $res_cart_installment;
    }

    /**
     * @description get cart installment backward
     * @param $id_cart
     * @return mixed
     * @deprecated use for installment from PayPlug 3.1.3 or further
     */
    public function getPayplugInstallmentCartBackward($id_cart)
    {
        $req_cart_installment = '
            SELECT pic.id_installment
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_cart_installment = Db::getInstance()->getValue($req_cart_installment);

        return $res_cart_installment;
    }

    /**
     * Retrieve payment informations
     *
     * @param string $pay_id
     * @return bool|Payment|null
     */
    public function retrievePayment($pay_id)
    {
        try {
            $payment = Payment::retrieve($pay_id);
        } catch (Exception $e) {
            return false;
        }

        return $payment;
    }

    /**
     * @param $payment
     * @return array|Exception
     */
    public function buildPaymentDetails($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = Payment::retrieve($payment);
            } catch (Exception $exception) {
                return $exception;
            }
        }
        $pay_status = self::getPaymentStatusByPayment($payment);
        $status_class = null;
        switch ($pay_status) {
            case 1: // not paid
            case 5: // refunded
            case 8: // authorized
            case 11: // abandoned
                $status_class = 'pp_warning';
                break;
            case 2: // paid
                $status_class = 'pp_success';
                break;
            case 3: // failed
            case 7: // cancelled
            case 9: // authorization expired
                $status_class = 'pp_error';
                break;
            case 4: // partially refunded
            case 6: // on going
                $status_class = 'pp_neutral';
                break;
            default:
                $status_class = 'pp_other';
                break;
        }

        switch ($pay_status) {
            case 1:
                $status_code = 'not_paid';
                break;
            case 2:
                $status_code = 'paid';
                break;
            case 3:
                $status_code = 'failed';
                break;
            case 4:
                $status_code = 'partially_refunded';
                break;
            case 5:
                $status_code = 'refunded';
                break;
            case 6:
                $status_code = 'on_going';
                break;
            case 7:
                $status_code = 'cancelled';
                break;
            case 8:
                $status_code = 'authorized';
                break;
            case 9:
                $status_code = 'authorization_expired';
                break;
            case 10:
                $status_code = 'oney_pending';
                break;
            case 11:
                $status_code = 'abandoned';
                break;
            default: // none
                $status_code = 'none';
                break;
        }

        $pay_status = $this->payment_status[$pay_status];

        /*
         * Get card details to order details (views/templates/admin/order/details.tpl)
         * Mask (last4), exp date...
         */
        $card_details = false;
        if (isset($payment->card->last4) && (!empty($payment->card->last4))) {
            $card_details = $this->card->getCardDetailFromPayment($payment);
        }

        // Card brand
        $card_brand = null;
        if ($card_details
            && isset($card_details['brand'])
            && !empty($card_details['brand'])
            && ($card_details['brand'] !== 'none')) {
            $card_brand = $this->l('payplug.adminAjaxController.card', 'payplugclass') . ' ' . $card_details['brand'];
        }

        // Card Country
        $card_country = null;
        if ($card_details
            && isset($card_details['country'])
            && ($card_details['country'] !== 'none')) {
            $card_country = $card_details['country'];
            $card_brand .= ' (' . $card_details['country'] . ')';
        }

        // Card mask
        $card_mask = null;
        if ($card_details && isset($card_details['last4']) && !empty($card_details['last4'])) {
            $card_mask = '**** **** **** ' . $card_details['last4'];
        }

        // Card exp. date
        $card_date = null;
        if ($card_details && (isset($card_details['exp_month']) && !empty($card_details['exp_month']))
            && (isset($card_details['exp_year']) && !empty($card_details['exp_year']))) {
            $card_date = $card_details['exp_month'] . '/' . $card_details['exp_year'];
        }

        $payment_details = [
            'id' => $payment->id,
            'status' => $pay_status,
            'status_code' => $status_code,
            'status_class' => $status_class,
            'amount' => (int)$payment->amount / 100,
            'refunded' => (int)$payment->amount_refunded / 100,
            'card_brand' => $card_brand,
            'card_mask' => $card_mask,
            'card_date' => $card_date,
            'card_country' => $card_country,
            'mode' => ($payment->is_live)
                ? $this->l('payplug.buildPaymentDetails.live', 'payplugclass')
                : $this->l('payplug.buildPaymentDetails.test', 'payplugclass'),
            'paid' => (bool)$payment->is_paid,
        ];

        //Deferred payment does'nt display 3DS option before capture so we have to consider it null
        if ($payment->is_3ds !== null) {
            $payment_details['tds'] = ($payment->is_3ds)
                ? $this->l('payplug.buildPaymentDetails.yes', 'payplugclass')
                : $this->l('payplug.buildPaymentDetails.no', 'payplugclass');
        }

        $is_oney = false;
        $is_bancontact = false;
        if (isset($payment->payment_method) && isset($payment->payment_method['type'])) {
            switch ($payment->payment_method['type']) {
                case 'oney_x3_with_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->l('payplug.buildPaymentDetails.oneyX3WithFees', 'payplugclass');
                    break;
                case 'oney_x4_with_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->l('payplug.buildPaymentDetails.oneyX4WithFees', 'payplugclass');
                    break;
                case 'oney_x3_without_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->l('payplug.buildPaymentDetails.oneyX3WithoutFees', 'payplugclass');
                    break;
                case 'oney_x4_without_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->l('payplug.buildPaymentDetails.oneyX4WithoutFees', 'payplugclass');
                    break;
                case 'bancontact':
                    $is_bancontact = true;
                    $payment_details['type'] = $this->l('payplug.buildPaymentDetails.bancontact', 'payplugclass');
                    break;
                default:
                    $payment_details['type'] = $payment->payment_method['type'];
            }
            $payment_details['type_code'] = $payment->payment_method['type'];
        }



        if ($payment->authorization !== null && !$is_oney) {
            $payment_details['authorization'] = true;
            if ($payment->is_paid) {
                $payment_details['date'] = date('d/m/Y', $payment->paid_at);
                $payment_details['can_be_cancelled'] = false;
                $payment_details['can_be_captured'] = false;
                if (!isset($payment_details['type'])) {
                    $payment_details['status_message'] = '(' . $this->l('payplug.buildPaymentDetails.deferred', 'payplugclass') . ')';
                }
            } else {
                $expiration = date('d/m/Y', $payment->authorization->expires_at);
                if (isset($payment->authorization->expires_at) && $payment->authorization->expires_at - time() > 0) {
                    if (isset($payment->failure) && $payment->failure) {
                        $payment_details['can_be_cancelled'] = false;
                        $payment_details['can_be_captured'] = false;
                    } else {
                        $payment_details['can_be_captured'] = true;
                        $payment_details['can_be_cancelled'] = true;
                        $payment_details['status_message'] = sprintf(
                            '(' . $this->l('payplug.buildPaymentDetails.captureAuthorizedBefore', 'payplugclass') . ')',
                            $expiration
                        );
                    }
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                    $payment_details['date_expiration'] = $expiration;
                    $payment_details['expiration_display'] = sprintf(
                        $this->l('payplug.buildPaymentDetails.captureAuthorizedBeforeWarning', 'payplugclass'),
                        $expiration
                    );
                } elseif (isset($payment->authorization->authorized_at)
                    && $payment->authorization->authorized_at != null
                ) {
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                    $payment_details['can_be_cancelled'] = false;
                    $payment_details['can_be_captured'] = false;
                } else {
                    $payment_details['can_be_cancelled'] = false;
                    $payment_details['can_be_captured'] = false;
                }
            }
        } else {
            $payment_details['authorization'] = false;
            $payment_details['date'] = date('d/m/Y', $payment->created_at);
            $payment_details['can_be_cancelled'] = false;
            $payment_details['can_be_captured'] = false;
        }

        if (isset($payment->failure) && isset($payment->failure->message)) {
            $payment_details['error'] = '(' . $payment->failure->message . ')';
        }

        if ($is_oney) {
            unset($payment_details['card_brand']);
            unset($payment_details['card_mask']);
            unset($payment_details['card_date']);
        }
        if ($is_bancontact) {
            unset($payment_details['tds']);
            unset($payment_details['card_brand']);
        }

        return $payment_details;
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
            FROM ' . _DB_PREFIX_ . 'payplug_payment ppc  
            WHERE ppc.id_cart = ' . (int)$id_cart . '
            AND ppc.is_pending = 1';
        $res_payment_cart = Db::getInstance()->getValue($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        } else {
            return $res_payment_cart;
        }
    }

//    /**
//     * @param $params
//     * @return string|void
//     */
//    public function hookCustomerAccount($params)
//    {
//        if (!ConfigClass::isAllowed()) {
//            return false;
//        }
//
//        $payplug_cards_url = $this->context->link->getModuleLink(
//            $this->name,
//            'cards',
//            ['process' => 'cardlist'],
//            true
//        );
//
//        if ((class_exists($this->PrestashopSpecificClass))
//            && (method_exists($this->PrestashopSpecificObject, 'hookCustomerAccount'))) {
//            $this->PrestashopSpecificObject->hookCustomerAccount();
//        }
//
//        $this->smarty->assign([
//            'version' => _PS_VERSION_[0] . '.' . _PS_VERSION_[2],
//            'payplug_cards_url' => $payplug_cards_url
//        ]);
//
//        return $this->fetchTemplate('customer/my_account.tpl');
//    }
//
//    /**
//     * @param $params
//     * @return string
//     */
//    public function hookDisplayBackOfficeFooter($params)
//    {
//        if (version_compare(_PS_VERSION_, '1.6.1.0', '<')) {
//            $this->assignContentVar();
//            $this->context->smarty->assign([
//                'js_def' => Media::getJsDef(),
//            ]);
//            return $this->fetchTemplate('/views/templates/hook/_partials/javascript.tpl');
//        }
//    }
//
//    /**
//     * Display Oney CTA on Shopping cart page
//     *
//     * @param array $params
//     * @return bool|mixedf
//     */
//    public function hookDisplayBeforeShoppingCartBlock($params)
//    {
//        if (!$this->oney->isOneyAllowed()) {
//            return false;
//        }
//        $amount = $params['cart']->getOrderTotal(true, Cart::BOTH);
//        $is_valid_amount = $this->oney->isValidOneyAmount($amount, $params['cart']->id_currency);
//
//        $this->smarty->assign([
//            'payplug_oney_amount' => $amount,
//            'payplug_oney_allowed' => $is_valid_amount['result'],
//            'payplug_oney_error' => $is_valid_amount['error'],
//            'use_fees' => (bool)Configuration::get('PAYPLUG_ONEY_FEES'),
//            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
//        ]);
//
//        return $this->oney->getOneyCTA('checkout');
//    }
//
//    /**
//     * @param $params
//     * @return string|void
//     */
//    public function hookDisplayExpressCheckout($param)
//    {
//        if (!$this->oney->isOneyAllowed()) {
//            return false;
//        }
//
//        $use_taxes = (bool)Configuration::get('PS_TAX');
//        $amount = $this->context->cart->getOrderTotal($use_taxes);
//        $is_elligible = $this->oney->isValidOneyAmount($amount);
//        $is_elligible = $is_elligible['result'];
//
//        $this->smarty->assign([
//            'env' => 'checkout',
//            'payplug_is_oney_elligible' => $is_elligible,
//            'use_fees' => (bool)Configuration::get('PAYPLUG_ONEY_FEES'),
//            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
//        ]);
//        return $this->fetchTemplate('oney/cta.tpl');
//    }
//
//    public function hookDisplayProductPriceBlock($param)
//    {
//        $current_controller = Dispatcher::getInstance()->getController();
//        if (!$this->oney->isOneyAllowed() || $current_controller != 'product') {
//            return false;
//        }
//
//        $action = Tools::getValue('action');
//        if ($action == 'quickview') {
//            return false;
//        }
//        if (!isset($param['product'])
//            || !isset($param['type'])
//            || !in_array($param['type'], ['after_price'])
//        ) {
//            return false;
//        }
//
//        if ($action == 'refresh') {
//            $use_taxes = (bool)Configuration::get('PS_TAX');
//
//            $id_product = (int)Tools::getValue('id_product');
//            $group = Tools::getValue('group');
//            // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
//            if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
//                $id_product_attribute = $group ? (int)Product::getIdProductAttributesByIdAttributes(
//                    $id_product,
//                    $group
//                ) : 0;
//            } else {
//                $id_product_attribute = $group ? (int)Product::getIdProductAttributeByIdAttributes(
//                    $id_product,
//                    $group
//                ) : 0;
//            }
//            $quantity = (int)Tools::getValue('qty', (int)Tools::getValue('quantity_wanted', 1));
//
//            $product_price = Product::getPriceStatic(
//                (int)$id_product,
//                $use_taxes,
//                $id_product_attribute,
//                6,
//                null,
//                false,
//                true,
//                $quantity
//            );
//            $amount = $product_price * $quantity;
//            $is_elligible = $this->oney->isValidOneyAmount($amount, $this->context->currency->id);
//            $is_elligible = $is_elligible['result'];
//            $this->smarty->assign([
//                'payplug_is_oney_elligible' => $is_elligible,
//            ]);
//            $this->smarty->assign(['popin' => true]);
//        }
//
//        $this->smarty->assign([
//            'env' => 'product',
//            'use_fees' => (bool)Configuration::get('PAYPLUG_ONEY_FEES'),
//            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
//        ]);
//        return $this->fetchTemplate('oney/cta.tpl');
//    }
//
//    /**
//     * @param array $params
//     * @return string
//     * @throws Exception
//     * @see Module::hookHeader()
//     */
//    public function hookHeader($params)
//    {
//        if (!ConfigClass::isAllowed()) {
//            return false;
//        }
//
//        if (Tools::getValue('error')) {
//            Media::addJsDef(['payment_errors' => true]);
//        }
//        if ((class_exists($this->PrestashopSpecificClass))
//            && (method_exists($this->PrestashopSpecificObject, 'hookHeader'))) {
//            $this->PrestashopSpecificObject->hookHeader();
//        }
//
//        if ((int)Tools::getValue('lightbox') == 1) {
//            $cart = $params['cart'];
//            if (!Validate::isLoadedObject($cart)) {
//                return;
//            }
//
//            $this->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/embedded.js');
//
//            $payment_options = [
//                'id_card' => Tools::getValue('pc', 'new_card'),
//                'is_installment' => (bool)Tools::getValue('inst'),
//                'is_deferred' => (bool)Tools::getValue('def'),
//            ];
//
//            $payment = $this->preparePayment($payment_options);
//
//            if ($payment['result']) {
//                // If payment is paid then redirect
//                if ($payment['redirect']) {
//                    Tools::redirect($payment['return_url']);
//                } else {
//                    // else show the popin
//                    $this->context->smarty->assign([
//                        'payment_url' => $payment['return_url'],
//                        'api_url' => $this->apiClass->getApiUrl(),
//                    ]);
//                    return $this->fetchTemplate('checkout/embedded.tpl');
//                }
//            } else {
//                $this->setPaymentErrorsCookie([
//                    $this->oldTranslate('payplug.hookHeader.transactionNotCompleted')
//                ]);
//                $error_url = 'index.php?controller=order&step=3&error=1';
//                Tools::redirect($error_url);
//            }
//        }
//
//        if (Configuration::get('PAYPLUG_ONEY')) {
//            Media::addJsDef([
//                'payplug_oney' => true,
//                'payplug_oney_loading_msg' => $this->oldTranslate('payplug.hookHeader.loading')
//            ]);
//        }
//
//        $payplug_ajax_url = $this->context->link->getModuleLink($this->name, 'ajax', [], true);
//        Media::addJsDef([
//            'payplug_ajax_url' => $payplug_ajax_url,
//        ]);
//    }

    /**
     * @description
     * prepare payment
     *
     * @param $options
     * @return mixed
     * @throws Exception
     */
    public function preparePayment($options)
    {
        if (!Validate::isLoadedObject($this->context->cart)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->l('payplug.preparePayment.transactionNotCompleted', 'payplugclass')
            ];
        }

        $cart = $this->context->cart;

        $default_options = [
            'id_card' => 'new_card',
            'is_installment' => false,
            'is_deferred' => false,
            'is_oney' => false,
            'is_integrated' =>false,
            'is_bancontact' => false
        ];

        foreach ($default_options as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }

        $customer = new Customer((int)$cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->l('payplug.preparePayment.transactionNotCompleted', 'payplugclass')
            ];
        }

        $is_sandbox = (int)Configuration::get('PAYPLUG_SANDBOX_MODE');

        // get the config
        $config = [
            'one_click' => (int)Configuration::get('PAYPLUG_ONE_CLICK'),
            'installment' => (int)Configuration::get('PAYPLUG_INST'),
            'company' => (int)Configuration::get('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : '')),
            'inst_mode' => (int)Configuration::get('PAYPLUG_INST_MODE'),
            'deferred' => (int)Configuration::get('PAYPLUG_DEFERRED'),
            'oney' => (int)Configuration::get('PAYPLUG_ONEY'),
            'standard' => (int)Configuration::get('PAYPLUG_STANDARD'),
            'bancontact' => (int)Configuration::get('PAYPLUG_BANCONTACT')
        ];

        $is_one_click = $options['id_card'] != 'new_card' && $config['one_click'];
        $options['is_installment'] = $options['is_installment'] && $config['installment'];
        $options['is_bancontact'] = $options['is_bancontact'] && $config['bancontact'];

        // defined which is current payment method
        if ($is_one_click) {
            $payment_method = 'oneclick';
        } elseif ($options['is_oney']) {
            $payment_method = 'oney';
        } elseif ($options['is_installment']) {
            $payment_method = 'installment';
        } elseif ($options['is_bancontact']) {
            $payment_method = 'bancontact';
        } elseif ($options['is_integrated']) {
            $payment_method = 'integrated';
        } else {
            $payment_method = 'standard';
        }

        // Build payment Tab

        // Currency
        $currency = $cart->id_currency;
        $result_currency = Currency::getCurrency($currency);
        $supported_currencies = explode(';', Configuration::get('PAYPLUG_CURRENCIES'));
        $currency = $result_currency['iso_code'];

        // if unvalid iso code, return false
        if (!in_array($currency, $supported_currencies, true)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->l('payplug.preparePayment.transactionNotCompleted', 'payplugclass')
            ];
        }

        // Amount
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $amount = $this->amountCurrencyClass->convertAmount($amount);
        $current_amounts = $this->amountCurrencyClass->getAmountsByCurrency($currency);
        if ($amount < $current_amounts['min_amount'] || $amount > $current_amounts['max_amount']) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->l('payplug.preparePayment.transactionNotCompleted', 'payplugclass')
            ];
        }

        // Hosted url
        $hosted_url = [
            'return' => $this->context->link->getModuleLink(
                $this->name,
                'validation',
                ['ps' => 1, 'cartid' => (int)$cart->id],
                true
            ),
            'cancel' => $this->context->link->getModuleLink(
                $this->name,
                'validation',
                ['ps' => 2, 'cartid' => (int)$cart->id],
                true
            ),
            'notification' => $this->context->link->getModuleLink($this->name, 'ipn', [], true)
        ];

        // Meta data
        $metadata = [
            'ID Client' => (int)$customer->id,
            'ID Cart' => (int)$cart->id,
            'Website' => Tools::getShopDomainSsl(true, false)
        ];

        // Addresses
        $billing_address = new Address((int)$cart->id_address_invoice);
        $shipping_address = new Address((int)$cart->id_address_delivery);

        // ISO
        $billing_iso = ConfigClass::getIsoCodeByCountryId((int)$billing_address->id_country);
        $shipping_iso = ConfigClass::getIsoCodeByCountryId((int)$shipping_address->id_country);
        if (!$shipping_iso || !$billing_iso) {
            $default_language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
            $iso_code_list = ConfigClass::getIsoCodeList();
            if (in_array(Tools::strtoupper($default_language->iso_code), $iso_code_list, true)) {
                $iso_code = Tools::strtoupper($default_language->iso_code);
            } else {
                $iso_code = 'FR';
            }
            if (!$shipping_iso) {
                $shipping_country = new Country($shipping_address->id_country);
                $metadata['cms_shipping_country'] = $shipping_country->iso_code;
                $shipping_iso = $iso_code;
            }
            if (!$billing_iso) {
                $billing_country = new Country($billing_address->id_country);
                $metadata['cms_billing_country'] = $billing_country->iso_code;
                $billing_iso = $iso_code;
            }
        }

        // Billing
        $billing = [
            'title' => null,
            'first_name' => !empty($billing_address->firstname) ? $billing_address->firstname : null,
            'last_name' => !empty($billing_address->lastname) ? $billing_address->lastname : null,
            'company_name' => !empty($billing_address->company) ? trim($billing_address->company) : null,
            'email' => $customer->email,
            'landline_phone_number' => ConfigClass::formatPhoneNumber(
                $billing_address->phone,
                $billing_address->id_country
            ),
            'mobile_phone_number' => ConfigClass::formatPhoneNumber(
                $billing_address->phone_mobile,
                $billing_address->id_country
            ),
            'address1' => !empty($billing_address->address1) ? $billing_address->address1 : null,
            'address2' => !empty($billing_address->address2) ? $billing_address->address2 : null,
            'postcode' => !empty($billing_address->postcode) ? $billing_address->postcode : null,
            'city' => !empty($billing_address->city) ? $billing_address->city : null,
            'country' => $billing_iso,
            'language' => ConfigClass::getIsoFromLanguageCode($this->context->language),
        ];
        $billing['company_name'] = empty($billing['company_name']) || ! $billing['company_name']
            ? $billing['first_name'] . ' ' . $billing['last_name']
            : $billing['company_name'];
        $billing['mobile_phone_number'] = $billing['mobile_phone_number']
            ? $billing['mobile_phone_number']
            : $billing['landline_phone_number'];

        // Shipping
        $delivery_type = 'NEW';
        if ($cart->id_address_delivery == $cart->id_address_invoice) {
            $delivery_type = 'BILLING';
        } elseif ($shipping_address->isUsed()) {
            $delivery_type = 'VERIFIED';
        }
        $shipping = [
            'title' => null,
            'first_name' => !empty($shipping_address->firstname) ? $shipping_address->firstname : null,
            'last_name' => !empty($shipping_address->lastname) ? $shipping_address->lastname : null,
            'company_name' => !empty($shipping_address->company) ? trim($shipping_address->company) : null,
            'email' => $customer->email,
            'landline_phone_number' => ConfigClass::formatPhoneNumber(
                $shipping_address->phone,
                $shipping_address->id_country
            ),
            'mobile_phone_number' => ConfigClass::formatPhoneNumber(
                $shipping_address->phone_mobile,
                $shipping_address->id_country
            ),
            'address1' => !empty($shipping_address->address1) ? $shipping_address->address1 : null,
            'address2' => !empty($shipping_address->address2) ? $shipping_address->address2 : null,
            'postcode' => !empty($shipping_address->postcode) ? $shipping_address->postcode : null,
            'city' => !empty($shipping_address->city) ? $shipping_address->city : null,
            'country' => $shipping_iso,
            'language' => ConfigClass::getIsoFromLanguageCode($this->context->language),
            'delivery_type' => $delivery_type,
        ];
        $shipping['company_name'] = empty($shipping['company_name']) || ! $shipping['company_name']
            ? $shipping['first_name'] . ' ' . $shipping['last_name']
            : $shipping['company_name'];
        $shipping['mobile_phone_number'] = $shipping['mobile_phone_number']
            ? $shipping['mobile_phone_number']
            : $shipping['landline_phone_number'];

        // 3ds
        $force_3ds = false;

        //save card
        $allow_save_card =
            $config['one_click']
            && Cart::isGuestCartByCartId($cart->id) != 1
            && $options['id_card'] == 'new_card';

        //
        $payment_tab = [
            'currency' => $currency,
            'shipping' => $shipping,
            'billing' => $billing,
            'notification_url' => $hosted_url['notification'],
            'force_3ds' => $force_3ds,
            'hosted_payment' => [
                'return_url' => $hosted_url['return'],
                'cancel_url' => $hosted_url['cancel'],
            ],
            'metadata' => $metadata,
            'allow_save_card' => $allow_save_card
        ];

        if (!$options['is_deferred'] && !$options['is_oney']) {
            $payment_tab['amount'] = $amount;
        } else {
            $payment_tab['authorized_amount'] = $amount;
        }

        // check payment tab from current payment method
        if ($options['is_installment']) {
            // remove useless field from payment table
            unset($payment_tab['force_3ds']);
            unset($payment_tab['allow_save_card']);
            unset($payment_tab['amount']);
            unset($payment_tab['authorized_amount']);

            // then add schedule
            $schedule = [];
            for ($i = 0; $i < $config['inst_mode']; $i++) {
                if ($i == 0) {
                    $schedule[$i]['date'] = 'TODAY';
                    $int_part = (int)($amount / $config['inst_mode']);
                    if ($options['is_deferred']) {
                        $schedule[$i]['authorized_amount'] = (int)($int_part +
                            ($amount - ($int_part * $config['inst_mode'])));
                    } else {
                        $schedule[$i]['amount'] = (int)($int_part + ($amount - ($int_part * $config['inst_mode'])));
                    }
                } else {
                    $delay = $i * 30;
                    $schedule[$i]['date'] = date('Y-m-d', strtotime("+ $delay days"));
                    $schedule[$i]['amount'] = (int)($amount / $config['inst_mode']);
                }
            }
            $payment_tab['schedule'] = $schedule;
        } elseif ($is_one_click) {
            $payment_tab['initiator'] = 'PAYER';
            $payment_tab['payment_method'] = null;
            if ($options['id_card'] && $options['id_card'] != 'new_card') {
                $card = $this->card->getCard((int)$options['id_card']);
                if ($card["id_customer"] != $customer->id) {
                    return [
                        'result' => false,
                        'response' => 'Card customer differs from cart customer'
                    ];
                }
                $payment_tab['payment_method'] = $card['id_card'];
            }
        }

        // check payment tab from current payment method
        if ($options['is_oney']) {
            // check if oney was elligible then return if not
            $is_elligible = $this->oney->isOneyElligible($this->context->cart, false, true);

            if (!$is_elligible['result']) {
                $this->setPaymentErrorsCookie([$is_elligible['error']]);
                return ['result' => false, 'response' => $is_elligible['error']];
            }

            // check billing phonenumber
            if (!$payment_tab['billing']['mobile_phone_number'] || !ConfigClass::isValidMobilePhoneNumber(
                $payment_tab['billing']['country'],
                $payment_tab['billing']['mobile_phone_number']
            )) {
                if (ConfigClass::isValidMobilePhoneNumber(
                    $payment_tab['billing']['country'],
                    $payment_tab['billing']['landline_phone_number']
                )) {
                    $payment_tab['billing']['mobile_phone_number'] = $payment_tab['billing']['landline_phone_number'];
                }
            }

            // check shipping phonenumber
            if (!$payment_tab['shipping']['mobile_phone_number'] || !ConfigClass::isValidMobilePhoneNumber(
                $payment_tab['shipping']['country'],
                $payment_tab['shipping']['mobile_phone_number']
            )) {
                if (ConfigClass::isValidMobilePhoneNumber(
                    $payment_tab['shipping']['country'],
                    $payment_tab['shipping']['landline_phone_number']
                )) {
                    $payment_tab['shipping']['mobile_phone_number'] = $payment_tab['shipping']['landline_phone_number'];
                }
            }

            if ($this->oney->hasOneyRequiredFields($payment_tab)) {
                // check oney required fields

                $payment_data = $this->getPaymentDataCookie();

                if (!$payment_data) {
                    $payment_data = Tools::getValue('oney_form');
                }

                if ($payment_data) {
                    // hydrate with payment data
                    $payment_tab = $this->hydratePaymentTabFromPaymentData($payment_tab, $payment_data);


                    // then recheck
                    if ($this->oney->hasOneyRequiredFields($payment_tab)) {
                        $this->setPaymentErrorsCookie(['oney_required_field_' . $options['is_oney']]);
                        return [
                            'result' => false,
                            'response' => $this->l('payplug.preparePayment.fieldsNotCompleted', 'payplugclass')
                        ];
                    }
                } else {
                    $this->setPaymentErrorsCookie(['oney_required_field_' . $options['is_oney']]);
                    return ['result' => false, 'response' => false];
                }
            }

            unset($payment_tab['allow_save_card']);

            $payment_tab['force_3ds'] = false;
            $payment_tab['auto_capture'] = true;
            $payment_tab['payment_method'] = 'oney_' . $options['is_oney'];
            $payment_tab['payment_context'] = $this->oney->getOneyPaymentContext();

            $return_url_params = ['ps' => 1, 'cartid' => (int)$cart->id, 'isoney' => $options['is_oney']];
            $return_url = $this->context->link->getModuleLink(
                $this->name,
                'validation',
                $return_url_params,
                true
            );
            $payment_tab['hosted_payment']['return_url'] = $return_url;
        }
        if ($options['is_integrated']) {
            $payment_tab['integration'] = 'INTEGRATED_PAYMENT';
            unset($payment_tab['hosted_payment']['cancel_url']);
        }

        if ($options['is_bancontact']) {
            $payment_tab['payment_method'] = "bancontact";
            unset($payment_tab['force_3ds']);
            unset($payment_tab['allow_save_card']);
        }

        // Prepare details to create / retrieve payment
        $this->paymentDetails = [
            'paymentMethod' => $payment_method,
            'paymentTab' => $payment_tab,
            'paymentId' => null,
            'paymentReturnUrl' => null,
            'paymentUrl' => null,
            'paymentDate' => null,
            'authorizedAt' => null,
            'isPaid' => null,
            'isDeferred' => $options['is_deferred'],
            'isEmbedded' => Configuration::get('PAYPLUG_EMBEDDED_MODE'),
            'isIntegrated' => $options['is_integrated'],
            'isMobileDevice' => ConfigClass::isMobiledevice(),
            'cart' => $cart,
            'cartId' => $payment_tab['metadata']['ID Cart'],
            'cartHash' => null,
            'oneyDetails' => isset($options['is_oney']) ? $options['is_oney'] : null
        ];

        /*
         * Create payment if inexistent
         */
        if (!$this->payment->checkPaymentTable($cart->id)) {
            // Create payment or installment
            $createPayment = $this->payment->createPayment($this->paymentDetails);

            if ($createPayment['result'] && $createPayment['paymentDetails']) {
                $this->paymentDetails = $createPayment['paymentDetails'];
            } elseif (!$createPayment['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $createPayment['paymentDetails'],
                    'response' => $createPayment['response']
                ];
            }

            // Insert payment to paymentTable
            $insertPaymentTable = $this->payment->insertPaymentTable($this->paymentDetails);
            if ($insertPaymentTable['result'] && $insertPaymentTable['paymentDetails']) {
                $this->paymentDetails = $insertPaymentTable['paymentDetails'];
            } elseif (!$insertPaymentTable['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $insertPaymentTable['paymentDetails'],
                    'response' => $insertPaymentTable['response']
                ];
            }

            // Generate the return URL
            $getpaymentReturnUrl = $this->payment->getPaymentReturnUrl($this->paymentDetails);
            if ($getpaymentReturnUrl['result'] && $getpaymentReturnUrl['url']) {
                return $getpaymentReturnUrl['url'];
            } elseif (!$getpaymentReturnUrl['result']) {
                return [
                    'result' => false,
                    'url' => $getpaymentReturnUrl['url'],
                    'response' => $getpaymentReturnUrl['response']
                ];
            }
        } elseif (!$this->payment->checkTimeoutPayment($cart->id)) {
            /*
             * If payment already exists, and timeout > 3 min : Create a new payment
             */

            // Create payment or installment
            $createPayment = $this->payment->createPayment($this->paymentDetails);
            if ($createPayment['result'] && $createPayment['paymentDetails']) {
                $this->paymentDetails = $createPayment['paymentDetails'];
            } elseif (!$createPayment['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $createPayment['paymentDetails'],
                    'response' => $createPayment['response']
                ];
            }

            // Update payment table
            $updatePaymentTable = $this->payment->updatePaymentTable($this->paymentDetails);
            if ($updatePaymentTable['result'] && $updatePaymentTable['paymentDetails']) {
                $this->paymentDetails = $updatePaymentTable['paymentDetails'];
            } elseif (!$updatePaymentTable['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $updatePaymentTable['paymentDetails'],
                    'response' => $updatePaymentTable['response']
                ];
            }

            // Check hash
            $checkHash = $this->payment->checkHash($this->paymentDetails);
            if ($checkHash['result'] && $checkHash['paymentDetails']) {
                $this->paymentDetails = $checkHash['paymentDetails'];
            } elseif (!$checkHash['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $checkHash['paymentDetails'],
                    'response' => $checkHash['response']
                ];
            }

            $getpaymentReturnUrl = $this->payment->getPaymentReturnUrl($this->paymentDetails);
            if ($getpaymentReturnUrl['result'] && $getpaymentReturnUrl['url']) {
                return $getpaymentReturnUrl['url'];
            } elseif (!$getpaymentReturnUrl['result']) {
                return [
                    'result' => false,
                    'url' => $getpaymentReturnUrl['url'],
                    'response' => $getpaymentReturnUrl['response']
                ];
            }
        } elseif ($this->payment->checkTimeoutPayment($cart->id)
            && $this->payment->checkHash($this->paymentDetails)
            && $this->payment->isValidApiPayment($this->paymentDetails)) {
            /*
             * If timeout < 3 min and hash OK
             */
            $store_payment = $this->payment->checkPaymentTable($cart->id);
            $this->paymentDetails['paymentId'] = $store_payment['id_payment'];

            $getpaymentReturnUrl = $this->payment->getPaymentReturnUrl($this->paymentDetails);

            if ($getpaymentReturnUrl['result'] && isset($getpaymentReturnUrl['url']) && $getpaymentReturnUrl['url']) {
                return $getpaymentReturnUrl['url'];
            } elseif (!$getpaymentReturnUrl['result']) {
                return [
                    'result' => false,
                    'url' => $getpaymentReturnUrl['url'],
                    'response' => $getpaymentReturnUrl['response']
                ];
            }
        }
    }

    /**
     * @description Set payment errors in cookie
     *{
        "php": "5.6.*"
    }
     * @param array $payplug_errors
     * @return mixed
     * @throws Exception
     */
    public function setPaymentErrorsCookie($payplug_errors = [])
    {
        if (empty($payplug_errors)) {
            return false;
        }

        $value = json_encode($payplug_errors);

        $this->context->cookie->__set('payplug_errors', $value);
        return (bool)$this->context->cookie->__get('payplug_errors');
    }

    /**
     * Get payment data from cookie
     *
     * @return mixed
     */
    public function getPaymentDataCookie()
    {
        // get payplug data
        $cookie_data = $this->context->cookie->__get('payplug_data');
        $payplug_data = !empty($cookie_data) ? $cookie_data : false;

        // then flush to avoid repetition
        $this->context->cookie->__set('payplug_data', '');

        // if no error all good then return true
        return json_decode($payplug_data, true);
    }

    /**
     * Hydrate Oney Payment Tab from Cookie Payment Data
     * @param array $payment_tab
     * @param array $payment_data
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    private function hydratePaymentTabFromPaymentData($payment_tab, $payment_data)
    {
        if (empty($payment_data) || !is_array($payment_data) || !is_array($payment_tab)) {
            return $payment_tab;
        }

        foreach ($payment_data as $k => $field) {
            $keys = explode('-', $k);
            $type = $keys[0];
            $field_name = $keys[1];

            if (strpos($field_name, 'phone') != false) {
                switch ($type) {
                    case 'billing':
                        $id_country = Country::getByIso($payment_tab['billing']['country']);
                        $country = new Country($id_country);
                        $field = ConfigClass::formatPhoneNumber($field, $country);
                        break;
                    case 'same':
                    case 'shipping':
                    default:
                        $id_country = Country::getByIso($payment_tab['shipping']['country']);
                        $country = new Country($id_country);
                        $field = ConfigClass::formatPhoneNumber($field, $country);
                        break;
                }
            }

            if ($field_name == 'email') {
                $payment_tab['billing']['email'] = $field;
                $payment_tab['shipping']['email'] = $field;
            } elseif ($type == 'same') {
                $payment_tab['billing'][$field_name] = $field;
                $payment_tab['shipping'][$field_name] = $field;
            } else {
                $payment_tab[$type][$field_name] = $field;
            }
        }

        return $payment_tab;
    }

//    /**
//     * @param array $params
//     * @return string
//     * @throws Exception
//     * @see Module::hookPayment()
//     *
//     * This hook is not used anymore in PS 1.7 but we have to keep it for retro-compatibility
//     */
//    public function hookPayment($params)
//    {
//        if (!ConfigClass::isAllowed()) {
//            return false;
//        }
//
//        $use_taxes = Configuration::get('PS_TAX');
//        $base_total_tax_inc = $params['cart']->getOrderTotal(true);
//        $base_total_tax_exc = $params['cart']->getOrderTotal(false);
//
//        if ($use_taxes) {
//            $price2display = $base_total_tax_inc;
//        } else {
//            $price2display = $base_total_tax_exc;
//        }
//
//        $cart = $params['cart'];
//
//        $currency = $cart->id_currency;
//        $result_currency = Currency::getCurrency($currency);
//        $supported_currencies = explode(';', Configuration::get('PAYPLUG_CURRENCIES'));
//        if (!in_array($result_currency['iso_code'], $supported_currencies, true)) {
//            return false;
//        }
//
//        if (Configuration::get('PAYPLUG_ONEY_OPTIMIZED')) {
//            $this->oney->assignOneyPaymentOptions($cart);
//        }
//
//        $payment_options = $this->getPaymentOptions($cart);
//
//        // Transforme tableau en TPL
//        $paymentOptions = $this->PrestashopSpecificObject->displayPaymentOption(
//            $payment_options,
//            $cart
//        );
//
//        foreach ($paymentOptions as $paymentOption) {
//            $find = 'oney';
//            if (strstr($paymentOption['tpl'], $find)) {
//                $this->oneyLogoUrl = $paymentOption['logo_url'];
//            }
//        }
//
//        $this->smarty->assign([
//            'use_fees' => (bool)Configuration::get('PAYPLUG_ONEY_FEES'),
//            'iso_code' => $this->tools->tool('strtoupper', $this->context->language->iso_code),
//            'payplug_payment_options' => $paymentOptions,
//            'spinner_url' => Tools::getHttpHost(true) .
//                __PS_BASE_URI__ . 'modules/payplug/views/img/admin/spinner.gif',
//            'front_ajax_url' => $this->context->link->getModuleLink($this->name, 'ajax', [], true),
//            'api_url' => $this->apiClass->getApiUrl(),
//            'price2display' => $price2display,
//            'this_path' => $this->_path,
//        ]);
//
//        return $this->fetchTemplate('checkout/payment/display.tpl');
//    }

    /**
     * Get the valid payment options from payplug configuration
     *
     * @param $cart
     * @return array
     * @throws Exception
     */
    public function getPaymentOptions($cart)
    {
        $options = ConfigClass::getAvailableOptions($cart);

        $id_customer = (isset($cart->id_customer)) ? $cart->id_customer : $cart['cart']->id_customer;

        $payplug_cards = $options['one_click'] ? $this->card->getByCustomer((int)$id_customer, true) : [];

        $paymentOption = [];

        // OneClick Payment
        if (Configuration::get('PAYPLUG_STANDARD')) {
            if ($options['one_click'] && !empty($payplug_cards)) {
                foreach ($payplug_cards as $card) {
                    $brand = ($card['brand'] != 'none')
                        ? Tools::ucfirst($card['brand'])
                        : $this->l('payplug.getPaymentOptions.card', 'payplugclass');
                    $payment_key = 'one_click_' . $card['id_payplug_card'];
                    $paymentOption[$payment_key]['name'] = 'one_click';
                    $paymentOption[$payment_key]['inputs'] = [
                        'pc' => [
                            'name' => 'pc',
                            'type' => 'hidden',
                            'value' => (int)$card['id_payplug_card'],
                        ],
                        'pay' => [
                            'name' => 'pay',
                            'type' => 'hidden',
                            'value' => '1',
                        ],
                        'id_cart' => [
                            'name' => 'id_cart',
                            'type' => 'hidden',
                            'value' => (int)$this->context->cart->id,
                        ],
                        'method' => [
                            'name' => 'method',
                            'type' => 'hidden',
                            'value' => 'one_click',
                        ],
                    ];
                    $paymentOption[$payment_key]['tpl'] = 'one_click.tpl';
                    $paymentOption[$payment_key]['payment_controller_url'] =
                        $this->context->link->getModuleLink(
                            $this->name,
                            'payment',
                            [],
                            true
                        );
                    $paymentOption[$payment_key]['logo'] = $card['brand'] != 'none' ? Media::getMediaPath(
                        _PS_MODULE_DIR_ . $this->name . '/views/img/' . Tools::strtolower($card['brand']) . '.svg'
                    ) : '';
                    $paymentOption[$payment_key]['callToActionText'] = $brand .
                        ' **** **** **** ' . $card['last4'];
                    $paymentOption[$payment_key]['expiry_date_card'] =
                        $this->l('payplug.getPaymentOptions.expiryDate', 'payplugclass') . ': ' . $card['expiry_date'];
                    $paymentOption[$payment_key]['action'] = $this->context->link->getModuleLink(
                        $this->name,
                        'dispatcher',
                        ['def' => (int)$options['deferred']],
                        true
                    );
                    $paymentOption[$payment_key]['moduleName'] = 'payplug';
                }
            }

            // Standard Payment or new card from one-click
            $paymentOption['standard']['name'] = 'standard';
            $paymentOption['standard']['inputs'] = [
                'pc' => [
                    'name' => 'pc',
                    'type' => 'hidden',
                    'value' => 'new_card',
                ],
                'pay' => [
                    'name' => 'pay',
                    'type' => 'hidden',
                    'value' => '1',
                ],
                'id_cart' => [
                    'name' => 'id_cart',
                    'type' => 'hidden',
                    'value' => (int)$this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'standard',
                ],
            ];
            $paymentOption['standard']['tpl'] = 'standard.tpl';
            $paymentOption['standard']['extra_classes'] = 'payplug default';
            $paymentOption['standard']['payment_controller_url'] = $this->context->link->getModuleLink(
                $this->name,
                'payment',
                ['type' => 'standard']
            );

            $paymentOption['standard']['logo'] = Media::getMediaPath(
                _PS_MODULE_DIR_ . $this->name . '/views/img/logos_schemes_' . $this->configClass->getImgLang() . '.svg'
            );
            if (count($payplug_cards) > 0) {
                $paymentOption['standard']['callToActionText'] = $this->l('payplug.getPaymentOptions.payDifferentCard', 'payplugclass');
            } else {
                $paymentOption['standard']['callToActionText'] = $this->l('payplug.getPaymentOptions.payCreditCard', 'payplugclass');
            }
            $paymentOption['standard']['action'] = $this->context->link->getModuleLink(
                $this->name,
                'dispatcher',
                ['def' => (int)$options['deferred']],
                true
            );
            $paymentOption['standard']['moduleName'] = 'payplug';
        }

        // Installment Payment
        if ($options['installment']) {
            $use_taxes = (bool)Configuration::get('PS_TAX');
            $cart_amount = $this->context->cart->getOrderTotal($use_taxes);
            if ($cart_amount >= Configuration::get('PAYPLUG_INST_MIN_AMOUNT')) {
                $installment_mode = Configuration::get('PAYPLUG_INST_MODE');
                $paymentOption['installment']['name'] = 'installment';
                $paymentOption['installment']['inputs'] = [
                    'pc' => [
                        'name' => 'pc',
                        'type' => 'hidden',
                        'value' => 'new_card',
                    ],
                    'pay' => [
                        'name' => 'pay',
                        'type' => 'hidden',
                        'value' => '1',
                    ],
                    'id_cart' => [
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => (int)$this->context->cart->id,
                    ],
                    'method' => [
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'installment',
                    ],
                ];
                $paymentOption['installment']['tpl'] = 'installment.tpl';
                $paymentOption['installment']['payment_controller_url'] = $this->context->link->getModuleLink(
                    $this->name,
                    'payment',
                    ['type' => 'installment', 'i' => 1],
                    true
                );
                $paymentOption['installment']['logo'] = Media::getMediaPath(
                    _PS_MODULE_DIR_ . $this->name . '/views/img/logos_schemes_installment_' .
                    Configuration::get('PAYPLUG_INST_MODE') . '_' . $this->configClass->getImgLang() . '.png'
                );
                $paymentOption['installment']['callToActionText'] = sprintf(
                    $this->l('payplug.getPaymentOptions.payByCardInstallment', 'payplugclass'),
                    Configuration::get('PAYPLUG_INST_MODE')
                );
                $paymentOption['installment']['action'] = $this->context->link->getModuleLink(
                    $this->name,
                    'dispatcher',
                    ['def' => (int)$options['deferred']],
                    true
                );
                $paymentOption['installment']['moduleName'] = 'payplug';

                $this->smarty->assign([
                    'installment_controller_url' => $this->context->link->getModuleLink(
                        $this->name,
                        'payment',
                        ['i' => 1],
                        true
                    ),
                    'installment_mode' => $installment_mode,
                ]);
            }
        }

        if ($options['oney']) {
            $use_taxes = (bool)Configuration::get('PS_TAX');
            $cart_amount = $this->context->cart->getOrderTotal($use_taxes);

            $is_elligible = $this->oney->isOneyElligible($this->context->cart, $cart_amount, true);
            $error = $is_elligible['result'] ? false : $is_elligible['error_type'];

            $optimized = Configuration::get('PAYPLUG_ONEY_OPTIMIZED')
                && !$error;

            $available_oney_payments = $this->oney->oneyEntity->getOperations();
            $use_fees = (bool)Configuration::get('PAYPLUG_ONEY_FEES');

            foreach ($available_oney_payments as $oney_payment) {
                $with_fees = (bool)strpos($oney_payment, 'with_fees') !== false;
                if (($use_fees && !$with_fees) || (!$use_fees && $with_fees)) {
                    continue;
                }

                $payment_key = 'oney_' . $oney_payment;
                $paymentOption[$payment_key]['name'] = 'oney';
                $paymentOption[$payment_key]['is_optimized'] = $optimized;
                $paymentOption[$payment_key]['type'] = $oney_payment;
                $paymentOption[$payment_key]['amount'] = $cart_amount;
                $delivery_address = new Address($this->context->cart->id_address_delivery);
                $delivery_country = new Country($delivery_address->id_country);
                $paymentOption[$payment_key]['iso_code'] = $delivery_country->iso_code;

                $paymentOption[$payment_key]['inputs'] = [
                    'pc' => [
                        'name' => 'pc',
                        'type' => 'hidden',
                        'value' => 'new_card',
                    ],
                    'pay' => [
                        'name' => 'pay',
                        'type' => 'hidden',
                        'value' => '1',
                    ],
                    'id_cart' => [
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => (int)$this->context->cart->id,
                    ],
                    'method' => [
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'oney',
                    ],
                    'oney_type' => [
                        'name' => 'oney_type',
                        'type' => 'hidden',
                        'value' => $oney_payment,
                    ],
                ];

                switch ($error) {
                    case 'invalid_addresses':
                        $err_label = $this->l('payplug.getPaymentOptions.invalidAddresses', 'payplugclass');
                        break;
                    case 'invalid_amount_bottom':
                    case 'invalid_amount_top':
                        $limits = $this->oney->getOneyPriceLimit(true);
                        $err_label = sprintf(
                            $this->l('payplug.getPaymentOptions.invalidAmount', 'payplugclass'),
                            $limits['min'],
                            $limits['max']
                        );
                        break;
                    case 'invalid_carrier':
                        $err_label = $this->l('payplug.getPaymentOptions.invalidCarrier', 'payplugclass');
                        break;
                    case 'invalid_cart':
                        $err_label = $this->l('payplug.getPaymentOptions.invalidCart', 'payplugclass');
                        break;
                    default:
                        $err_label = $this->l('payplug.getPaymentOptions.errorOccurred', 'payplugclass');
                        break;
                }

                $type = explode('_', $oney_payment);
                $split = (int)str_replace('x', '', $type[0]);
                $iso = $this->tools->tool('strtoupper', $this->context->language->iso_code);
                $oneyTpl = 'unified.tpl';

                if ($iso != 'IT' && $iso != 'FR') {
                    $iso = Configuration::get('PAYPLUG_COMPANY_ISO');
                }

                $oneyLogo = $oney_payment . (!$use_fees ? '_side_' . $iso : '') . ($error ? '_alt' : '') . '.svg';
                $text = $use_fees
                    ? $this->l('payplug.getPaymentOptions.payWithOney', 'payplugclass')
                    : $this->l('payplug.getPaymentOptions.payWithOneyWithout', 'payplugclass');

                $oneyLabel = $error ? $err_label : sprintf($text, $split);

                if ($optimized) {
                    $oneyTpl = 'oney.tpl';

                    if ((class_exists($this->PrestashopSpecificClass))
                        && (method_exists($this->PrestashopSpecificObject, 'getPaymentOption'))) {
                        $oneyData = $this->PrestashopSpecificObject->getPaymentOption();
                        $oneyLogo = $oneyData['oneyLogo'];
                        $oneyLabel = $oneyData['oneyCallToActionText'];
                    }
                }

                $paymentOption[$payment_key]['tpl'] = $oneyTpl;
                $paymentOption[$payment_key]['extra_classes'] = sprintf('oney%sx', $split);
                $paymentOption[$payment_key]['payment_controller_url'] = $this->context->link->getModuleLink(
                    $this->name,
                    'payment',
                    ['type' => 'oney', 'io' => sprintf('%s', $split)],
                    true
                );
                $paymentOption[$payment_key]['logo'] = Media::getMediaPath(_PS_MODULE_DIR_ .
                    $this->name . '/views/img/oney/' . $oneyLogo);
                $paymentOption[$payment_key]['callToActionText'] = $oneyLabel;
                $paymentOption[$payment_key]['action'] = $this->context->link->getModuleLink(
                    $this->name,
                    'dispatcher',
                    [],
                    true
                );
                $paymentOption[$payment_key]['moduleName'] = 'payplug';
                $paymentOption[$payment_key]['err_label'] = $err_label;
            }
        }
        // Bancontact Payment
        if ($options['bancontact'] && $this->isValidFeature('feature_bancontact')) {
            $paymentOption['bancontact']['name'] = 'bancontact';
            $paymentOption['bancontact']['tpl'] = 'bancontact.tpl';
            $paymentOption['bancontact']['logo'] = Media::getMediaPath(_PS_MODULE_DIR_ .
                $this->name . '/views/img/bancontact/bancontact.svg');
            $paymentOption['bancontact']['callToActionText'] = $this->l(
                'payplug.getPaymentOptions.payWithBancontact',
                'payplugclass'
            );
            $paymentOption['bancontact']['extra_classes'] = 'bancontact';
            $paymentOption['bancontact']['action'] = $this->context->link->getModuleLink(
                $this->name,
                'dispatcher',
                [],
                true
            );
            $paymentOption['bancontact']['payment_controller_url'] = $this->context->link->getModuleLink(
                $this->name,
                'payment',
                ['type' => 'bancontact'],
                true
            );
            $paymentOption['bancontact']['moduleName'] = 'payplug';
            $paymentOption['bancontact']['inputs'] = [
                'pc' => [
                    'name' => 'pc',
                    'type' => 'hidden',
                    'value' => 'new_card',
                ],
                'pay' => [
                    'name' => 'pay',
                    'type' => 'hidden',
                    'value' => '1',
                ],
                'id_cart' => [
                    'name' => 'id_cart',
                    'type' => 'hidden',
                    'value' => (int)$this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'bancontact',
                ],
            ];
        }
        return $paymentOption;
    }

//    /**
//     * @param array $params
//     * @return array
//     * @throws Exception
//     * @see Module::hookPaymentOptions()
//     *
//     */
//    public function hookPaymentOptions($params)
//    {
//        if (!ConfigClass::isAllowed()) {
//            return false;
//        }
//
//        $cart = $params['cart'];
//        if (!Validate::isLoadedObject($cart)) {
//            return false;
//        }
//
//        $this->context->smarty->assign([
//            'api_url' => $this->apiClass->getApiUrl(),
//        ]);
//
//        $payment_options = $this->getPaymentOptions($cart); // Données sous forme de tableau (pour 1.6 et 1.7)
//
//        return $this->PrestashopSpecificObject->displayPaymentOption($payment_options); // Transforme tableau en object
//    }

//    /**
//     * @param array $params
//     * @return string
//     * @throws PrestaShopDatabaseException
//     * @throws PrestaShopException
//     * @see Module::hookPaymentReturn()
//     */
//    public function hookPaymentReturn($params)
//    {
//        if (!ConfigClass::isAllowed()) {
//            return false;
//        }
//
//        $order_id = Tools::getValue('id_order');
//        $order = new Order($order_id);
//        // Check order state to display appropriate message
//        $state = null;
//        if (isset($order->current_state)
//            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PENDING')
//        ) {
//            $state = 'pending';
//        } elseif (isset($order->current_state)
//            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PAID')
//        ) {
//            $state = 'paid';
//        } elseif (isset($order->current_state)
//            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST')
//        ) {
//            $state = 'pending_test';
//        } elseif (isset($order->current_state)
//            && $order->current_state == Configuration::get('PAYPLUG_ORDER_STATE_PAID_TEST')
//        ) {
//            $state = 'paid_test';
//        }
//
//        $this->smarty->assign('state', $state);
//        // Get order information for display
//        $total_paid = number_format($order->total_paid, 2, ',', '');
//        $context = ['totalPaid' => $total_paid];
//        if (isset($order->reference)) {
//            $context['reference'] = $order->reference;
//        }
//        $this->smarty->assign($context);
//        return $this->fetchTemplate('checkout/order-confirmation.tpl');
//    }
//
//    public function hookRegisterGDPRConsent($params)
//    {
//    }

    /**
     * Check if payment method is valid for given id
     *
     * @param string $payment_id
     * @param string $type default payment
     * @return bool
     * @throws ConfigurationNotSetException
     */
    public function isPaidPaymentMethod($payment_id, $type = 'payment')
    {
        switch ($type) {
            case 'installment':
                $installment = InstallmentPlan::retrieve($payment_id);
                if ($installment && $installment->is_active) {
                    $schedules = $installment->schedule;
                    foreach ($schedules as $schedule) {
                        foreach ($schedule->payment_ids as $pay_id) {
                            $inst_payment = Payment::retrieve($pay_id);
                            if ($inst_payment && $inst_payment->is_paid) {
                                return true;
                            }
                        }
                    }
                }
                break;
            case 'payment':
            default:
                $payment = Payment::retrieve($payment_id);
                return $payment && $payment->is_paid;
        }
        return false;
    }

    /**
     * check if a payment for the same id cart is pending
     *
     * @param int $id_cart
     * @return bool
     */
    public function isPaymentPending($id_cart)
    {
        $current_time = strtotime(date('Y-m-d H:i:s'));
        $timeout_delay = 9;
        $req_payment_cart_exists = '
            SELECT *
            FROM ' . _DB_PREFIX_ . 'payplug_payment ppc
            WHERE ppc.id_cart = ' . (int)$id_cart . '
            AND ppc.id_payment LIKE \'pending\'';
        $res_payment_cart_exists = Db::getInstance()->getRow($req_payment_cart_exists);
        if (!$res_payment_cart_exists) {
            return false;
        } elseif (($current_time - strtotime($res_payment_cart_exists['date_upd'])) >= $timeout_delay) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Send cURL request to PayPlug to patch a given payment
     *
     * @param String $pay_id
     * @param Array $data
     * @return Array
     */
    public function patchPayment($pay_id, $data)
    {
        $result = [
            'status' => true,
            'message' => null,
        ];

        try {
            $payment = \Payplug\Resource\Payment::fromAttributes(['id' => $pay_id]);
            $payment->update($data);
        } catch (Exception $e) {
            $result = [
                'status' => false,
                'message' => $e['message']
            ];
        }

        return $result;
    }

//    public function refundPayment()
//    {
//        $this->logger->addLog('[Payplug] Start refund', 'notice');
//        $amount = Tools::getValue('amount');
//
//        if (!$this->amountCurrencyClass->checkAmountToRefund($amount)) {
//            $this->logger->addLog('Incorrect amount to refund', 'notice');
//            die(json_encode([
//                'status' => 'error',
//                'data' => $this->oldTranslate('payplug.refundPayment.incorrectAmount')
//            ]));
//        } elseif ($this->amountCurrencyClass->checkAmountToRefund($amount) && ($amount < 0.10)) {
//            $this->logger->addLog('The amount to be refunded must be at least 0.10 €', 'notice');
//            die(json_encode([
//                'status' => 'error',
//                'data' => $this->oldTranslate('payplug.refundPayment.amountAtLeast')
//            ]));
//        } else {
//            $amount = str_replace(',', '.', Tools::getValue('amount'));
//            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
//            $amount = (float)($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
//            $amount = (int)$amount;
//        }
//
//        $id_order = Tools::getValue('id_order');
//        $pay_id = Tools::getValue('pay_id');
//        $inst_id = Tools::getValue('inst_id');
//        $metadata = [
//            'ID Client' => (int)Tools::getValue('id_customer'),
//            'reason' => 'Refunded with Prestashop'
//        ];
//        $pay_mode = Tools::getValue('pay_mode');
//        $refund = $this->makeRefund($pay_id, $amount, $metadata, $pay_mode, $inst_id);
//
//        if ($refund == 'error') {
//            $this->logger->addLog('Cannot refund that amount.', 'notice');
//            $this->logger->addLog(
//                '$pay_id : ' . $pay_id .
//                ' - $amount : ' . $amount .
//                ' - $metadata : ' . json_encode($metadata) . /* or implode() ? */
//                ' - $pay_mode : ' . $pay_mode .
//                ' - $inst_id : ' . $inst_id,
//                'debug'
//            );
//
//            die(json_encode([
//                'status' => 'error',
//                'data' => $this->oldTranslate('payplug.refundPayment.cannotRefund')
//            ]));
//        } else {
//            $new_state = 7;
//            $reload = false;
//
//            if ($inst_id != null) {
//                $installment = $this->retrieveInstallment($inst_id);
//                $amount_available = 0;
//                $amount_refunded_payplug = 0;
//                if (isset($installment->schedule)) {
//                    foreach ($installment->schedule as $schedule) {
//                        if (!empty($schedule->payment_ids)) {
//                            foreach ($schedule->payment_ids as $p_id) {
//                                $p = Payment::retrieve($p_id);
//                                if ($p->is_paid && !$p->is_refunded) {
//                                    $amount_available += (int)($p->amount - $p->amount_refunded);
//                                }
//                                $amount_refunded_payplug += $p->amount_refunded;
//                            }
//                        }
//                    }
//                }
//                $amount_available = (float)($amount_available / 100);
//                $amount_refunded_payplug = (float)($amount_refunded_payplug / 100);
//                if ((int)Tools::getValue('id_state') != 0 || $amount_available == 0) {
//                    $new_state = (int)Tools::getValue('id_state');
//                    if ($new_state == 0) {
//                        if ($installment->is_live == 1) {
//                            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
//                        } else {
//                            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
//                        }
//                    }
//                    $order = new Order((int)$id_order);
//                    if (Validate::isLoadedObject($order)) {
//                        if (!$this->createLockFromCartId($order->id_cart)) {
//                            die(json_encode([
//                                'status' => 'error',
//                                'data' => $this->oldTranslate('payplug.refundPayment.errorOccurred')
//                            ]));
//                        }
//
//                        $current_state = (int)$this->orderClass->getCurrentOrderState($order->id);
//                        $this->logger->addLog('Current order state: ' . $current_state, 'notice');
//                        if ($current_state != 0 && $current_state != $new_state) {
//                            $history = new OrderHistory();
//                            $history->id_order = (int)$order->id;
//                            $history->changeIdOrderState($new_state, (int)$order->id);
//                            $history->addWithemail();
//                            $this->logger->addLog('Change order state to ' . $new_state, 'notice');
//                        }
//
//                        if (!$this->deleteLockFromCartId($order->id_cart)) {
//                            $this->logger->addLog('Lock cannot be deleted.', 'error');
//                        } else {
//                            $this->logger->addLog('Lock deleted.', 'notice');
//                        }
//                    }
//                    $reload = true;
//                }
//            } else {
//                $payment = $this->retrievePayment($refund->payment_id);
//
//                if ((int)Tools::getValue('id_state') != 0) {
//                    $new_state = (int)Tools::getValue('id_state');
//                } elseif ($payment->is_refunded == 1) {
//                    if ($payment->is_live == 1) {
//                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
//                    } else {
//                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
//                    }
//                }
//                if ((int)Tools::getValue('id_state') != 0 || ($payment->is_refunded == 1 && empty($inst_id))) {
//                    $order = new Order((int)$id_order);
//                    if (Validate::isLoadedObject($order)) {
//                        if (!$this->createLockFromCartId($order->id_cart)) {
//                            die(json_encode([
//                                'status' => 'error',
//                                'data' => $this->oldTranslate('payplug.refundPayment.errorOccurred')
//                            ]));
//                        }
//
//                        $current_state = (int)$this->orderClass->getCurrentOrderState($order->id);
//                        $this->logger->addLog('Current order state: ' . $current_state, 'notice');
//                        if ($current_state != 0 && $current_state != $new_state) {
//                            $history = new OrderHistory();
//                            $history->id_order = (int)$order->id;
//                            $history->changeIdOrderState($new_state, (int)$order->id);
//                            $history->addWithemail();
//                            $this->logger->addLog('Change order state to ' . $new_state, 'notice');
//                        } else {
//                            $this->logger->addLog('Order status is already \'refunded\'', 'notice');
//                        }
//
//                        if (!$this->deleteLockFromCartId($order->id_cart)) {
//                            $this->logger->addLog('Lock cannot be deleted.', 'error');
//                        } else {
//                            $this->logger->addLog('Lock deleted.', 'notice');
//                        }
//                    }
//                    $reload = true;
//                }
//
//                $amount_refunded_payplug = ($payment->amount_refunded) / 100;
//                $amount_available = ($payment->amount - $payment->amount_refunded) / 100;
//            }
//
//
//            $data = $this->getRefundData(
//                $amount_refunded_payplug,
//                $amount_available
//            );
//            die(json_encode([
//                'status' => 'ok',
//                'data' => $data,
//                'template' => $this->hookDisplayAdminOrderMain(['id_order' => $id_order]),
//                'message' => $this->oldTranslate('payplug.refundPayment.success'),
//                'reload' => $reload
//            ]));
//        }
//    }
//
//    /**
//     * Make a refund
//     *
//     * @param string $pay_id
//     * @param int $amount
//     * @param string $metadata
//     * @param string $pay_mode
//     * @param null $inst_id
//     * @return string
//     * @throws ConfigurationException
//     */
//    public function makeRefund($pay_id, $amount, $metadata, $pay_mode = 'LIVE', $inst_id = null)
//    {
//        if (Tools::strtoupper($pay_mode) == 'TEST') {
//            ApiClass::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
//        } else {
//            ApiClass::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
//        }
//        if ($pay_id == null) {
//            if ($inst_id != null) {
//                try {
//                    $installment = InstallmentPlan::retrieve($inst_id);
//                    if (isset($installment->schedule)) {
//                        $total_amount = $amount;
//                        $refund_to_go = [];
//                        $truly_refundable_amount = 0;
//                        foreach ($installment->schedule as $schedule) {
//                            if (!empty($schedule->payment_ids)) {
//                                foreach ($schedule->payment_ids as $p_id) {
//                                    $p = Payment::retrieve($p_id);
//                                    if ($p->is_paid && !$p->is_refunded && $amount > 0) {
//                                        $amount_refundable = (int)($p->amount - $p->amount_refunded);
//                                        $truly_refundable_amount += $amount_refundable;
//                                        if ($truly_refundable_amount < 10) {
//                                            continue;
//                                        } elseif ($amount >= $amount_refundable) {
//                                            $data = [
//                                                'amount' => $amount_refundable,
//                                                'metadata' => $metadata
//                                            ];
//                                            $amount -= $amount_refundable;
//                                        } else {
//                                            $data = [
//                                                'amount' => $amount,
//                                                'metadata' => $metadata
//                                            ];
//                                            $amount = 0;
//                                        }
//                                        $refund_to_go[] = ['id' => $p_id, 'data' => $data];
//                                    }
//                                }
//                            }
//                        }
//                        if ($truly_refundable_amount < $total_amount) {
//                            return ('error');
//                        }
//                        if (!empty($refund_to_go)) {
//                            foreach ($refund_to_go as $refnd) {
//                                try {
//                                    $refund = Refund::create($refnd['id'], $refnd['data']);
//                                } catch (Exception $e) {
//                                    return ('error');
//                                }
//                            }
//                        }
//                    }
//                } catch (Exception $e) {
//                    return ('error');
//                }
//                $this->updatePayplugInstallment($installment);
//            } else {
//                return ('error');
//            }
//        } else {
//            $data = [
//                'amount' => (int)$amount,
//                'metadata' => $metadata
//            ];
//
//            try {
//                $refund = Refund::create($pay_id, $data);
//            } catch (Exception $e) {
//                $error = 'error [PayPlugClass - makeRefund()]: ' . $e->getMessage();
//                $this->logger->addLog($error, 'error');
//                return 'error';
//            }
//        }
//
//        return $refund;
//    }
//
//    /**
//     * Generate refund form
//     *
//     * @param int $amount_refunded_payplug
//     * @param int $amount_available
//     * @return string
//     */
//    public function getRefundData($amount_refunded_payplug, $amount_available)
//    {
//        $this->context->smarty->assign([
//            'amount_refunded_payplug' => $amount_refunded_payplug,
//            'amount_available' => $amount_available,
//        ]);
//
//        $this->html = $this->fetchTemplate('/views/templates/admin//order/refund_data.tpl');
//
//        return $this->html;
//    }

    /**
     * Register transaction as pending to etablish link with order in case of error
     *
     * @param int $id_cart
     * @return bool
     */
    public function registerPendingTransaction($id_cart)
    {
        $req_payment_cart = '
            UPDATE ' . _DB_PREFIX_ . 'payplug_payment ppc  
            SET ppc.is_pending = 1
            WHERE ppc.id_cart = ' . (int)$id_cart;
        $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        } else {
            return true;
        }
    }

    public function setNotification()
    {
        return new PayPlugNotifications();
    }

    /**
     * Set payment data in cookie
     *
     * @return mixed
     * @throws Exception
     */
    public function setPaymentDataCookie($payplug_data = [])
    {
        if (empty($payplug_data)) {
            return false;
        }

        $value = json_encode($payplug_data);

        $this->context->cookie->__set('payplug_data', $value);
        return (bool)$this->context->cookie->__get('payplug_data');
    }

//    /**
//     * @description Register installment for later use
//     *
//     * @param string $installment_id
//     * @param int $id_cart
//     * @return bool
//     */
//    public function storeInstallment($installment_id, $id_cart)
//    {
//        if ($pay_id = $this->getPaymentByCart($id_cart)) {
//            $this->deletePayment($pay_id, $id_cart);
//        }
//
//        $req_installment_cart_exists = '
//            SELECT *
//            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic
//            WHERE pic.id_cart = ' . (int)$id_cart;
//        $res_installment_cart_exists = Db::getInstance()->getRow($req_installment_cart_exists);
//        $date_upd = date('Y-m-d H:i:s');
//        $is_pending = 0;
//        if (!$res_installment_cart_exists) {
//            //insert
//            $req_installment_cart = '
//                INSERT INTO ' . _DB_PREFIX_ . 'payplug_payment (id_payment, id_cart, is_pending, date_upd)
//                VALUES (\'' . pSQL($installment_id) . '\',
//                ' . (int)$id_cart . ',
//                ' . (int)$is_pending . ',
//                \'' . pSQL($date_upd) . '\')';
//            $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
//            if (!$res_installment_cart) {
//                return false;
//            }
//        } else {
//            //update
//            $req_installment_cart = '
//                UPDATE ' . _DB_PREFIX_ . 'payplug_payment pic
//                SET pic.id_payment = \'' . pSQL($installment_id) . '\', pic.date_upd = \'' . pSQL($date_upd) . '\'
//                WHERE pic.id_cart = ' . (int)$id_cart;
//            $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
//            if (!$res_installment_cart) {
//                return false;
//            }
//        }
//
//        return true;
//    }

    /**
     * Delete stored payment
     *
     * @param string $pay_id
     * @param array $cart_id
     * @return bool
     */
    public static function deletePayment($pay_id, $cart_id)
    {
        $req_payment_cart = '
            DELETE FROM ' . _DB_PREFIX_ . 'payplug_payment  
            WHERE id_cart = ' . (int)$cart_id . ' 
            AND id_payment = \'' . pSQL($pay_id) . '\'';
        $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        }

        return true;
    }

    /**
     * @description Delete saved cards when uninstalling module
     * todo: move this method in CardRepository
     * @return bool
     * @throws Exception
     */
    public function uninstallCards()
    {
        if ($this->sql->checkExistingTable('payplug_card', 1)) {
            $cards = $this->query
                ->select()
                ->fields('*')
                ->from(_DB_PREFIX_ . 'payplug_card')
                ->build();

            if ($cards) {
                foreach ($cards as $card) {
                    $id_customer = $card['id_customer'];
                    $id_payplug_card = $card['id_payplug_card'];
                    if (!$this->card->deleteCard((int)$id_customer, (int)$id_payplug_card)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function getPath()
    {
        return $this->_path;
    }
}
