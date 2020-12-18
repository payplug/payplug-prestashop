<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

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
    private $card; // 3.0

    private $tools; // 3.0

    private $logger;

    public $oney;

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

    /** @var string */
    private $api_live;

    /** @var string */
    private $api_test;

    /** @var array */
    public $check_configuration = [];

    /** @var string */
    public $current_api_key;

    /** @var string */
    private $email;

    /** @var array */
    public $errors = [];

    /** @var string */
    private $html = '';

    /** @var string */
    private $img_lang;

    /** @var bool */
    private $is_active = 1;

    /** @var MyLogPHP */
    private $log_general;

    /** @var MyLogPHP */
    private $log_install;

    /** @var array */
    public $payment_status = [];

    /** @var array */
    private $routes = [
        'login' => '/v1/keys',
        'account' => '/v1/account',
        'patch' => '/v1/payments'
    ];

    /** @var string */
    public $site_url;

    /** @var PayPlugConfiguration */
    public $configuration;

    /** @var bool */
    private $ssl_enable;

    /** @var array */
    public $validationErrors = [];

    public $available_oney_payments = [
        'x3_with_fees',
        'x4_with_fees',
    ];

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
                'en' => 'Payment successful',
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

    public $oney_order_state = [
        'oney_pg' => [
            'cfg' => null,
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

    private function initializeAccessors()
    {
        $this->setPlugin((new PayPlug\src\repositories\PluginRepository($this))->getEntity());

        $this->card = $this->getPlugin()->getCard();
        $this->logger = $this->getPlugin()->getLogger();
        $this->oney = $this->getPlugin()->getOney();
        $this->query = $this->getPlugin()->getQuery();
        $this->tools = $this->getPlugin()->getTools();
    }

    public function loadSpecificPrestaClasses()
    {
        $this->PrestashopSpecificClass = '\PayPlug\src\specific\PrestashopSpecific' . _PS_VERSION_[0] . _PS_VERSION_[2];
        if (class_exists($this->PrestashopSpecificClass)) {
            $this->PrestashopSpecificObject = new $this->PrestashopSpecificClass($this);
        }
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

    public function abortPayment()
    {
        $inst_id = Tools::getValue('inst_id');
        $id_order = Tools::getValue('id_order');

        try {
            $abort = \Payplug\InstallmentPlan::abort($inst_id);
        } catch (Exception $e) {
            if (Configuration::get('PAYPLUG_SANDBOX_MODE') == 1) {
                $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                $abort = \Payplug\InstallmentPlan::abort($inst_id);
                $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
            } elseif (Configuration::get('PAYPLUG_SANDBOX_MODE') == 0) {
                $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                $abort = \Payplug\InstallmentPlan::abort($inst_id);
                $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
            }
        }

        if ($abort == 'error') {
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('Cannot abort installment.')
            ]));
        } else {
            $installment = $this->retrieveInstallment($inst_id);
            if ($installment->is_live == 1) {
                $new_state = (int)Configuration::get('PS_OS_CANCELED');
            } else {
                $new_state = (int)Configuration::get('PS_OS_CANCELED');
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
            $this->updatePayplugInstallment($installment);
            $reload = true;

            die(json_encode(['reload' => $reload]));
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
        $this->context->controller->addCSS($css_uri, $css_media_type);
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
     * @param $installment
     * @param $order
     * @return bool
     * @throws ConfigurationNotSetException
     */
    public function addPayplugInstallment($installment, $order)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }

        if ($this->getStoredInstallment($installment)) {
            $this->updatePayplugInstallment($installment);
        } else {
            if (isset($installment->schedule)) {
                $step_count = count($installment->schedule);
                $index = 0;
                foreach ($installment->schedule as $schedule) {
                    $index++;
                    $pay_id = '';
                    if (is_array($schedule->payment_ids) && count($schedule->payment_ids) > 0) {
                        $pay_id = $schedule->payment_ids[0];
                        $status = $this->getPaymentStatusByPayment($pay_id);
                    } else {
                        $status = 6;
                    }
                    $amount = (int)$schedule->amount;
                    $step = $index . '/' . $step_count;
                    $date = $schedule->date;
                    $req_insert_installment = '
                INSERT INTO `' . _DB_PREFIX_ . 'payplug_installment` (
                    `id_installment`, 
                    `id_payment`, 
                    `id_order`, 
                    `id_customer`, 
                    `order_total`, 
                    `step`, 
                    `amount`, 
                    `status`, 
                    `scheduled_date`
                ) VALUES (
                    \'' . $installment->id . '\', 
                    \'' . $pay_id . '\', 
                    \'' . $order->id . '\', 
                    \'' . $order->id_customer . '\', 
                    \'' . (int)(($order->total_paid * 1000) / 10) . '\', 
                    \'' . $step . '\', 
                    \'' . $amount . '\', 
                    \'' . $status . '\', 
                    \'' . $date . '\'
                )';
                    $res_insert_installment = DB::getInstance()->Execute($req_insert_installment);

                    if (!$res_insert_installment) {
                        return false;
                    }
                }
            }
        }
    }

    /**
     * @description Add Order Payment
     *
     * @param int $id_order
     * @param string $id_payment
     * @return bool
     */
    public function addPayplugOrderPayment($id_order, $id_payment)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'payplug_order_payment (id_order, id_payment) 
                VALUE (' . (int)$id_order . ',\'' . pSQL($id_payment) . '\')';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function adminAjaxController()
    {
        if (!Tools::getValue('_ajax', false)) {
            return;
        }

        if (Tools::getValue('popin')) {
            $args = null;
            if (Tools::getValue('type') == 'confirm') {
                $keys = [
                    'sandbox',
                    'embedded',
                    'one_click',
                    'oney',
                    'installment',
                    'activate',
                    'deferred',
                ];
                $args = [];
                foreach ($keys as $key) {
                    $args[$key] = Tools::getValue($key);
                }
            }
            $this->displayPopin(Tools::getValue('type'), $args);
        }

        if (Tools::getValue('submitSettings')) {
            if (Tools::getValue('PAYPLUG_INST_MIN_AMOUNT') < 4) {
                $this->displayError($this->l('Settings not updated'));

                die(json_encode(['error' => $this->l('Settings not updated')]));
            } else {
                $this->saveConfiguration();

                $this->assignContentVar();
                $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

                $this->context->smarty->assign([
                    'title' => '',
                    'type' => 'save',
                ]);
                $popin = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

                die(json_encode(['popin' => $popin, 'content' => $content]));
            }
        }

        if (Tools::isSubmit('submitAccount')) {
            /*
             * We can't use $password = Tools::getValue('PAYPLUG_PASSWORD');
             * Because pwd with special chars don't work
             */
            $password = $_POST['PAYPLUG_PASSWORD'];
            $email = Tools::getValue('PAYPLUG_EMAIL');
            if (!Validate::isEmail($email) || !PayPlug\backward\PayPlugBackward::isPlaintextPassword($password)) {
                die(json_encode([
                    'content' => null,
                    'error' => $this->l('The email and/or password was not correct.')
                ]));
            }

            if ($this->login($email, $password)) {
                Configuration::updateValue('PAYPLUG_EMAIL', Tools::getValue('PAYPLUG_EMAIL'));
                Configuration::updateValue('PAYPLUG_SHOW', 1);

                $this->assignContentVar();
                $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

                die(json_encode(['content' => $content]));
            } else {
                die(json_encode([
                    'content' => null,
                    'error' => $this->l('The email and/or password was not correct.')
                ]));
            }
        }

        if (Tools::getValue('submitPwd')) {
            $password = Tools::getValue('password');
            if (!$password || !PayPlug\backward\PayPlugBackward::isPlaintextPassword($password)) {
                die(json_encode(['content' => null, 'error' => $this->l('The password you entered is invalid')]));
            }

            $email = Configuration::get('PAYPLUG_EMAIL');

            if ($this->login($email, $password)) {
                $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
                if ((bool)$api_key) {
                    Configuration::updateValue('PAYPLUG_SANDBOX_MODE', 0);
                    $this->assignContentVar();
                    $content = $this->fetchTemplateRC('/views/templates/admin/admin.tpl');
                    die(json_encode(['content' => $content]));
                } else {
                    $this->context->smarty->assign([
                        'title' => '',
                        'type' => 'activate',
                    ]);
                    $popin = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');
                    die(json_encode(['popin' => $popin]));
                }
            } else {
                die(json_encode([
                    'content' => null,
                    'error' => $this->l('The email and/or password was not correct.')
                ]));
            }


            $this->submitPopinPwd($password);
        }

        if (Tools::getValue('submit') == 'submitPopin_abort') {
            $this->abortPayment();
        }
        if ((int)Tools::getValue('check') == 1) {
            $content = $this->getCheckFieldset();
            die(json_encode(['content' => $content]));
        }
        if ((int)Tools::getValue('log') == 1) {
            $content = $this->getLogin();
            die(json_encode(['content' => $content]));
        }
        if ((int)Tools::getValue('checkPremium') == 1) {
            $api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
            $permissions = $this->getAccountPermissions($api_key);
            $return = [
                'payplug_sandbox' => $permissions['use_live_mode'],
                'payplug_one_click' => $permissions['can_save_cards'],
                'payplug_oney' => $permissions['can_use_oney'],
                'payplug_inst' => $permissions['can_create_installment_plan'],
                'payplug_deferred' => $permissions['can_create_deferred_payment'],
            ];
            die(json_encode($return));
        }
        if (Tools::getValue('has_live_key')) {
            die(json_encode(['result' => $this->has_live_key()]));
        }
        if ((int)Tools::getValue('refund') == 1) {
            $this->refundPayment();
        }
        if ((int)Tools::getValue('capture') == 1) {
            $this->capturePayment();
        }
        if ((int)Tools::getValue('popinRefund') == 1) {
            $popin = $this->displayPopin('refund');
            die(json_encode(['content' => $popin]));
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

            die(json_encode([
                'message' => $this->l('Order successfully updated.'),
                'reload' => true
            ]));
        }
    }

    /**
     * @param $payment
     * @return array|Exception
     * @throws ConfigurationNotSetException
     */
    public function buildPaymentDetails($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                return $exception;
            }
        }
        $pay_status = $this->getPaymentStatusByPayment($payment);
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

        $pay_brand = $this->card->getCardBrandByPayment($payment);
        if ($payment->card->country != '') {
            $pay_brand .= ' ' . $this->l('Card') . ' (' . $payment->card->country . ')';
        }

        $payment_details = [
            'id' => $payment->id,
            'status' => $pay_status,
            'status_code' => $status_code,
            'status_class' => $status_class,
            'amount' => (int)$payment->amount / 100,
            'refunded' => (int)$payment->amount_refunded / 100,
            'card_brand' => $pay_brand,
            'card_mask' => $this->card->getCardMaskByPayment($payment),
            'card_date' => $this->card->getCardExpiryDateByPayment($payment),
            'mode' => $payment->is_live ? $this->l('LIVE') : $this->l('TEST'),
            'paid' => (bool)$payment->is_paid,
        ];

        //Deferred payment does'nt display 3DS option before capture so we have to consider it null
        if ($payment->is_3ds !== null) {
            $payment_details['tds'] = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
        }

        if (isset($payment->payment_method) && isset($payment->payment_method['type'])) {
            switch ($payment->payment_method['type']) {
                case 'oney_x3_with_fees':
                    $payment_details['type'] = 'Oney 3x';
                    break;
                case 'oney_x4_with_fees':
                    $payment_details['type'] = 'Oney 4x';
                    break;
                default:
                    $payment_details['type'] = $payment->payment_method['type'];
            }
            $payment_details['type_code'] = $payment->payment_method['type'];
        }
        if ($payment->authorization !== null) {
            $payment_details['authorization'] = true;
            if ($payment->is_paid) {
                $payment_details['date'] = date('d/m/Y', $payment->paid_at);
                $payment_details['can_be_cancelled'] = false;
                $payment_details['can_be_captured'] = false;
                if (!isset($payment_details['type'])) {
                    $payment_details['status_message'] = $this->l('(deferred)');
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
                            $this->l('(capture authorized before %s)'),
                            $expiration
                        );
                    }
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                    $payment_details['date_expiration'] = $expiration;
                    $payment_details['expiration_display'] = sprintf(
                        $this->l('Capture of this payment is authorized before %s. 
                        After this date, you will not be able to get paid.'),
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

        if (isset($payment_details['type']) && in_array($payment_details['type'], ['Oney 3x', 'Oney 4x'], true)) {
            unset($payment_details['card_brand']);
            unset($payment_details['card_mask']);
            unset($payment_details['card_date']);
        }

        return $payment_details;
    }

    public function capturePayment()
    {
        $pay_id = Tools::getValue('pay_id');
        $id_order = Tools::getValue('id_order');
        $payment = new PPPayment($pay_id);
        $capture = $payment->capture();
        $payment->refresh();
        if ($payment->resource->card->id !== null) {
            $this->card->saveCard($payment->resource);
        }
        if ($capture['code'] >= 300) {
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('Cannot capture this payment.'),
                'message' => $capture['message'],
            ]));
        } else {
            $state_addons = ($payment->resource->is_live ? '' : '_TEST');
            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID' . $state_addons);

            $order = new Order((int)$id_order);
            if (Validate::isLoadedObject($order)) {
                $order->setInvoice(true);
                $current_state = (int)$order->getCurrentState();
                if ($current_state != 0 && $current_state != $new_state) {
                    $history = new OrderHistory();
                    $history->id_order = (int)$order->id;
                    $history->changeIdOrderState($new_state, (int)$order->id);
                    $history->addWithemail();
                }
            }

            die(json_encode([
                'status' => 'ok',
                'data' => '',
                'message' => $this->l('Payment successfully captured.'),
                'reload' => true,
            ]));
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
                        $errors[$error_key] = $this->l('The transaction was not completed 
                        and your card was not charged.');
                    }
            }
        }

        return $errors;
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
     * Check amount to refund
     *
     * @param int $amount
     * @return string
     */
    public function checkAmountToRefund($amount)
    {
        $amount = str_replace(',', '.', $amount);
        return is_numeric($amount) && ($amount >= 0.1);
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
                    if (in_array(Tools::strtoupper($currency_module['iso_code']), $supported_currencies, true)) {
                        return true;
                    }
                }
            }
        }
        return false;
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
     * @description Check if payplug order state are well installed
     */
    public function checkOrderStates()
    {
        $order_states = array_merge($this->order_states, $this->oney_order_state);

        foreach ($order_states as $key => $state) {
            // Check live OrderState
            $key_config_live = 'PAYPLUG_ORDER_STATE_' . Tools::strtoupper($key);
            $id_order_state_live = Configuration::get($key_config_live);
            $order_state_live = new OrderState((int)$id_order_state_live);
            if (!Validate::isLoadedObject($order_state_live)) {
                $this->createOrderState($key, $state, false, true);
            }

            // Check sandbox OrderState
            $key_config_sandbox = $key_config_live . '_TEST';
            $id_order_state_sandbox = Configuration::get($key_config_sandbox);
            $order_state_sandbox = new OrderState((int)$id_order_state_sandbox);

            if (!Validate::isLoadedObject($order_state_sandbox)) {
                $this->createOrderState($key, $state, true, true);
            }
        }
    }

    /**
     * Format amount float to int or int to float
     *
     * @param $amount
     * @param bool $to_cents
     * @return float|int
     */
    public function convertAmount($amount, $to_cents = false)
    {
        if ($to_cents) {
            return (float)($amount / 100);
        } else {
            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float)($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
            return (int)Tools::ps_round($amount);
        }
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

    public function createOrderState($name, $state, $sandbox = true, $force = false)
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $key_config = 'PAYPLUG_ORDER_STATE_' . Tools::strtoupper($name) . ($sandbox ? '_TEST' : '');

        $log->info('Order state: ' . $name . ($sandbox ? ' - test' : ''));
        $os = Configuration::get($key_config);

        // if we can't find order state with payplug key, check with configuration key
        if (!$os && !$sandbox && $state['cfg']) {
            $os = Configuration::get($state['cfg']);

            // if we don't find order state either, try with template name
            if (!$sandbox && $state['template'] != null) {
                $sql = 'SELECT DISTINCT `id_order_state`
                        FROM `' . _DB_PREFIX_ . 'order_state_lang` 
                        WHERE `template` = \'' . pSQL($state['template']) . '\'';
                $os = Db::getInstance()->getValue($sql);
            }
        }

        if (!$os || $force) {
            $log->info('Creating new order state.');
            $order_state = new OrderState();
            $order_state->logable = $state['logable'];
            $order_state->send_email = $state['send_email'];
            $order_state->paid = $state['paid'];
            $order_state->module_name = $state['module_name'];
            $order_state->hidden = $state['hidden'];
            $order_state->delivery = $state['delivery'];
            $order_state->invoice = $state['invoice'];
            $order_state->color = $state['color'];

            $tag = $sandbox ? ' [TEST]' : ' [PayPlug]';
            foreach (Language::getLanguages(false) as $lang) {
                $order_state->template[$lang['id_lang']] = $state['template'];
                if (in_array($lang['iso_code'], ['en', 'au', 'ca', 'ie', 'gb', 'uk', 'us'], true)) {
                    $order_state->name[$lang['id_lang']] = $state['name']['en'] . $tag;
                } elseif (in_array($lang['iso_code'], ['fr', 'be', 'lu', 'ch'], true)) {
                    $order_state->name[$lang['id_lang']] = $state['name']['fr'] . $tag;
                } elseif (in_array($lang['iso_code'], ['es', 'ar', 'cl', 'co', 'mx', 'py', 'uy', 've'], true)) {
                    $order_state->name[$lang['id_lang']] = $state['name']['es'] . $tag;
                } elseif (in_array($lang['iso_code'], ['it', 'sm', 'va'], true)) {
                    $order_state->name[$lang['id_lang']] = $state['name']['it'] . $tag;
                } else {
                    $order_state->name[$lang['id_lang']] = $state['name']['en'] . $tag;
                }
            }
            if ($order_state->add()) {
                $source = _PS_MODULE_DIR_ . $this->name . '/views/img/os/' . $name . '.gif';
                $destination = _PS_ROOT_DIR_ . '/img/os/' . $order_state->id . '.gif';
                @copy($source, $destination);
                $log->info('State created');
            }
            $os = $order_state->id;
            $log->info('ID: ' . $os);
        }
        return Configuration::updateValue($key_config, $os);
    }

    /**
     * Create usual status
     *
     * @return bool
     * @throws Exception
     */
    public function createOrderStates()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $this->log_install->info('Order state creation starting.');

        foreach ($this->order_states as $key => $state) {
            $this->createOrderState($key, $state, true);
            $this->createOrderState($key, $state, false);
        }

        $log->info('Order state creation ended.');
        return true;
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
     * Delete stored installment
     *
     * @param string $inst_id
     * @param array $cart_id
     * @return bool
     */
    public function deleteInstallment($inst_id, $cart_id)
    {
        $req_installment_cart = '
            DELETE FROM ' . _DB_PREFIX_ . 'payplug_installment_cart  
            WHERE id_cart = ' . (int)$cart_id . ' 
            AND id_installment = \'' . pSQL($inst_id) . '\'';
        $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
        if (!$res_installment_cart) {
            return false;
        }

        return true;
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
            DELETE FROM ' . _DB_PREFIX_ . 'payplug_payment_cart  
            WHERE id_cart = ' . (int)$cart_id . ' 
            AND id_payment = \'' . pSQL($pay_id) . '\'';
        $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        }

        return true;
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
     * @return string
     */
    public function displayGDPRConsent()
    {
        $this->context->smarty->assign(['id_module' => $this->id]);
        return $this->display(__FILE__, 'customer/gdpr_consent.tpl');
    }

    /**
     * Display payment errors template
     *
     * @param array $errors
     * @return mixed
     */
    public function displayPaymentErrors($errors = [])
    {
        if (empty($errors)) {
            return false;
        }

        $payment_messages = [];
        $with_msg_button = false;
        foreach ($errors as $error) {
            if (strpos($error, 'oney_required_field') !== false) {
                $this->smarty->assign(['is_popin_tpl' => true]);
                $fields = $this->oney->getOneyRequiredFields();
                $this->smarty->assign([
                    'oney_type' => str_replace('oney_required_field_', '', $error),
                    'oney_required_fields' => $fields,
                ]);
                $payment_messages[] = [
                    'type' => 'template',
                    'value' => 'oney/required.tpl'
                ];
            } else {
                $with_msg_button = true;
                $payment_messages[] = [
                    'type' => 'string',
                    'value' => $error
                ];
            }
        }

        $this->smarty->assign([
            'payment_messages' => $payment_messages,
            'with_msg_button' => $with_msg_button
        ]);

        return $this->display(__FILE__, '_partials/messages.tpl');
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
            $this->context->smarty->assign([
                'sandbox' => $args['sandbox'],
                'embedded' => $args['embedded'],
                'one_click' => $args['one_click'],
                'oney' => $args['oney'],
                'installment' => $args['installment'],
                'deferred' => $args['deferred'],
                'activate' => $args['activate'],
            ]);
        }

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $inst_id = isset($args['inst_id']) ? $args['inst_id'] : null;

        switch ($type) {
            case 'pwd':
                $title = $this->l('LIVE mode');
                break;
            case 'activate':
                $title = $this->l('LIVE mode');
                break;
            case 'premium':
                $title = $this->l('Enable advanced feature');
                break;
            case 'confirm':
                $title = $this->l('Save settings');
                break;
            case 'deactivate':
                $title = $this->l('Deactivate');
                break;
            case 'refund':
                $title = $this->l('Refund');
                break;
            case 'abort':
                $title = $this->l('Suspend installment');
                break;
            default:
                $title = '';
        }

        $this->context->smarty->assign([
            'title' => $title,
            'type' => $type,
            'admin_ajax_url' => $admin_ajax_url,
            'site_url' => $this->site_url,
            'inst_id' => $inst_id,
        ]);
        $this->html = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

        die(json_encode(['content' => $this->html]));
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
            $req_order_state->where(
                'osl.name LIKE \'' . pSQL($name['en'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                OR osl.name LIKE \'' . pSQL($name['fr'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                OR osl.name LIKE \'' . pSQL($name['es'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                OR osl.name LIKE \'' . pSQL($name['it'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\''
            );
            $res_order_state = Db::getInstance()->getValue($req_order_state);

            if (!$res_order_state) {
                return false;
            } else {
                return (int)$res_order_state;
            }
        }
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

    /**
     * @description
     * Get account permission from Payplug API
     *
     * @param string $api_key
     * @param boolean $sandbox
     * @return array OR bool
     */
    public function getAccount($api_key, $sandbox = true)
    {
        $response = \Payplug\Authentication::getAccount();
        $json_answer = $response['httpResponse'];
        if ($permissions = $this->treatAccountResponse($json_answer, $sandbox)) {
            return $permissions;

        } else {
            return false;
        }
    }

    /**
     * @description
     * Check if account is premium
     *
     * @param string $api_key
     * @return bool
     */
    public function getAccountPermissions($api_key = null)
    {
        if ($api_key == null) {
            $api_key = self::setAPIKey();
        }
        $permissions = $this->getAccount($api_key, false);
        return $permissions;
    }

    /**
     * @param string $controller_name
     * @param int $id_order
     * @return string
     */
    public function getAdminAjaxUrl($controller_name = 'AdminModules', $id_order = 0)
    {
        if ($controller_name == 'AdminModules') {
            $admin_ajax_url = 'index.php?controller=' . $controller_name . '&configure=' . $this->name
                . '&tab_module=payments_gateways&module_name=payplug&token=' .
                Tools::getAdminTokenLite($controller_name);
        } elseif ($controller_name == 'AdminOrders') {
            $admin_ajax_url = 'index.php?controller=' . $controller_name . '&id_order=' . $id_order
                . '&vieworder&token=' . Tools::getAdminTokenLite($controller_name);
        }
        return $admin_ajax_url;
    }

    /**
     * Get amounts with the right currency
     *
     * @param string $iso_code
     * @return array
     */
    private function getAmountsByCurrency($iso_code)
    {
        $min_amounts = [];
        $max_amounts = [];
        foreach (explode(';', Configuration::get('PAYPLUG_MIN_AMOUNTS')) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $min_amounts[$cur[1]] = (int)$cur[2];
        }
        foreach (explode(';', Configuration::get('PAYPLUG_MAX_AMOUNTS')) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $max_amounts[$cur[1]] = (int)$cur[2];
        }
        $current_min_amount = $min_amounts[$iso_code];
        $current_max_amount = $max_amounts[$iso_code];

        return ['min_amount' => $current_min_amount, 'max_amount' => $current_max_amount];
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
     * @see Module::getContent()
     *
     */
    public function getContent()
    {
        if (Tools::getValue('_ajax')) {
            $this->adminAjaxController();
        }

        $this->postProcess();

        $this->assignContentVar();

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin.tpl');

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
     * Retrieve installment stored
     *
     * @param int $id_cart
     * @return int OR bool
     */
    public function getInstallmentByCart($id_cart)
    {
        $req_installment_cart = '
            SELECT pic.id_installment 
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic 
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_installment_cart = Db::getInstance()->getValue($req_installment_cart);
        if (!$res_installment_cart) {
            return false;
        }

        return $res_installment_cart;
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

    public function getLogin()
    {
        $this->postProcess();

        $this->assignContentVar();

        $this->html = $this->fetchTemplateRC('/views/templates/admin/login.tpl');

        return $this->html;
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
     * @param null $id_lang
     * @return array
     */
    private function getOrderStates($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = $this->context->language->id;
        }
        $order_states = OrderState::getOrderStates($id_lang);
        return $order_states;
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
        $req_payment_cart->where('ppc.id_cart = ' . (int)$cart_id);
        $res_payment_cart = Db::getInstance()->getValue($req_payment_cart);

        if (!$res_payment_cart) {
            return false;
        }

        return $res_payment_cart;
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

        $inst_id = $this->getInstallmentByCart($cart->id);
        if ($inst_id) {
            return ['id' => $inst_id, 'type' => 'installment'];
        }

        $pay_id = $this->getPaymentByCart($cart->id);
        if ($pay_id) {
            return ['id' => $pay_id, 'type' => 'payment'];
        }

        return false;
    }

    /**
     * Get the valid payment options from payplug configuration
     *
     * @param $cart
     * @return array
     * @throws Exception
     */
    private function getPaymentOptions($cart)
    {
        $options = $this->getAvailableOptions($cart);

        $id_customer = (isset($cart->id_customer)) ? $cart->id_customer : $cart['cart']->id_customer;

        $payplug_cards = $options['one_click'] ? $this->card->getCardsByCustomer((int)$id_customer, true) : [];

        $paymentOption = [];

        // OneClick Payment
        if ($options['one_click'] && !empty($payplug_cards)) {
            foreach ($payplug_cards as $card) {
                $brand = $card['brand'] != 'none' ? Tools::ucfirst($card['brand']) : $this->l('Card');
                $paymentOption['one_click_' . $card['id_payplug_card']]['name'] = 'one_click';
                $paymentOption['one_click_' . $card['id_payplug_card']]['inputs'] = [
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
                $paymentOption['one_click_' . $card['id_payplug_card']]['tpl'] = 'one_click.tpl';
                $paymentOption['one_click_' . $card['id_payplug_card']]['payment_controller_url'] =
                    PayplugBackward::getModuleLink(
                        $this->name,
                        'payment',
                        [],
                        true
                    );
                $paymentOption['one_click_' . $card['id_payplug_card']]['logo'] = Media::getMediaPath(
                    _PS_MODULE_DIR_ . $this->name . '/views/img/' . Tools::strtolower($card['brand']) . '.png'
                );
                $paymentOption['one_click_' . $card['id_payplug_card']]['callToActionText'] = $brand .
                    ' **** **** **** ' . $card['last4'];
                $paymentOption['one_click_' . $card['id_payplug_card']]['expiry_date_card'] =
                    $this->l('Expiry date') . ': ' . $card['expiry_date'];
                $paymentOption['one_click_' . $card['id_payplug_card']]['action'] = $this->context->link->getModuleLink(
                    $this->name,
                    'dispatcher',
                    ['def' => (int)$options['deferred']],
                    true
                );
                $paymentOption['one_click_' . $card['id_payplug_card']]['moduleName'] = 'payplug';
            }
        }

        // Standart Payment or new card from one-click
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
        $paymentOption['standard']['payment_controller_url'] = PayplugBackward::getModuleLink(
            $this->name,
            'payment',
            ['type' => 'standard']
        );
        $paymentOption['standard']['logo'] = Media::getMediaPath(
            _PS_MODULE_DIR_ . $this->name . '/views/img/' . (count($payplug_cards) > 0 ?
                'none' : 'logos_schemes_' . $this->img_lang) . '.png'
        );
        if (count($payplug_cards) > 0) {
            $paymentOption['standard']['callToActionText'] = $this->l('Pay with a different card');
        } else {
            $paymentOption['standard']['callToActionText'] = $this->l('Pay with a credit card');
        }
        $paymentOption['standard']['action'] = $this->context->link->getModuleLink(
            $this->name,
            'dispatcher',
            ['def' => (int)$options['deferred']],
            true
        );
        $paymentOption['standard']['moduleName'] = 'payplug';

        // Installment Payment
        if ($options['installment']) {
            $use_taxes = (bool)Configuration::get('PS_TAX');
            $cart_amount = $this->context->cart->getOrderTotal($use_taxes);
            if ($cart_amount >= $this->getConfiguration('PAYPLUG_INST_MIN_AMOUNT')) {
                $installment_mode = $this->getConfiguration('PAYPLUG_INST_MODE');
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
                $paymentOption['installment']['payment_controller_url'] = PayplugBackward::getModuleLink(
                    $this->name,
                    'payment',
                    ['type' => 'installment', 'i' => 1],
                    true
                );
                $paymentOption['installment']['logo'] = Media::getMediaPath(
                    _PS_MODULE_DIR_ . $this->name . '/views/img/logos_schemes_installment_' .
                    Configuration::get('PAYPLUG_INST_MODE') . '_' . $this->img_lang . '.png'
                );
                $paymentOption['installment']['callToActionText'] = $this->l('Pay by card in') . ' ' .
                    Configuration::get('PAYPLUG_INST_MODE') . ' ' . $this->l('installments');
                $paymentOption['installment']['action'] = $this->context->link->getModuleLink(
                    $this->name,
                    'dispatcher',
                    ['def' => (int)$options['deferred']],
                    true
                );
                $paymentOption['installment']['moduleName'] = 'payplug';

                $this->smarty->assign([
                    'installment_controller_url' => PayplugBackward::getModuleLink(
                        $this->name,
                        'payment',
                        ['i' => 1],
                        true
                    ),
                    'installment_mode' => $installment_mode,
                ]);
            }
        }

        if ($options['oney'] && isset($this->available_oney_payments) && $this->available_oney_payments) {
            $use_taxes = (bool)Configuration::get('PS_TAX');
            $cart_amount = $this->context->cart->getOrderTotal($use_taxes);

            $is_elligible = $this->oney->isOneyElligible($this->context->cart, $cart_amount, true);
            $error = $is_elligible['result'] ? false : $is_elligible['error_type'];

            $optimized = Configuration::get('PAYPLUG_ONEY_OPTIMIZED') && !$error;

            foreach ($this->available_oney_payments as $oney_payment) {
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
                        $err_label = $this->l('Unavailable for the specified country');
                        break;
                    case 'invalid_amount_bottom':
                    case 'invalid_amount_top':
                        $err_label = $this->l('Between 100€ and 3000€ only');
                        break;
                    case 'invalid_carrier':
                        $err_label = $this->l('Unavailable for this shipping method');
                        break;
                    case 'invalid_cart':
                        $err_label = $this->l('Your cart is unavailable');
                        break;
                    default:
                        $err_label = $this->l('An error has occurred');
                        break;
                }

                $type = explode('_', $oney_payment);
                $split = (int)str_replace('x', '', $type[0]);

                $oneyTpl = 'unified.tpl';
                $oneyLogo = $oney_payment . ($error ? '-alt' : '') . '.svg';
                $oneyLabel = $error ? $err_label : sprintf($this->l('Pay by card in %sx with Oney'), $split);

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
                $paymentOption[$payment_key]['payment_controller_url'] = PayplugBackward::getModuleLink(
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
        return $paymentOption;
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
     * @param $payment
     * @return int
     * @throws ConfigurationNotSetException
     */
    private function getPaymentStatusByPayment($payment)
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
            $payment = \Payplug\Payment::retrieve($payment);
        }

        if ($payment->installment_plan_id !== null) {
            $installment = \Payplug\InstallmentPlan::retrieve($payment->installment_plan_id);
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

    /**
     * get cart installment
     *
     * @param $id_cart
     * @return bool
     */
    public function getPayplugInstallmentCart($id_cart)
    {
        $req_cart_installment = '
            SELECT pic.id_installment
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_cart_installment = Db::getInstance()->getValue($req_cart_installment);

        return $res_cart_installment;
    }

    /**
     * @description
     * get order payment
     *
     * @param int $id_order
     * @return integer
     */
    public function getPayplugOrderPayment($id_order)
    {
        $sql = 'SELECT id_payment 
                FROM ' . _DB_PREFIX_ . 'payplug_order_payment   
                WHERE id_order = ' . (int)$id_order;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @description
     * get all order payment for given id order
     *
     * @param int $id_order
     * @return array
     */
    public function getPayplugOrderPayments($id_order)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'payplug_order_payment 
                WHERE id_order = ' . (int)$id_order;

        return Db::getInstance()->executeS($sql);
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
        $this->context->smarty->assign([
            'amount_refunded_payplug' => $amount_refunded_payplug,
            'amount_available' => $amount_available,
        ]);

        $this->html = $this->fetchTemplateRC('/views/templates/admin//order/refund_data.tpl');

        return $this->html;
    }

    /**
     * @param $installment
     * @return array|bool|false|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     * @throws ConfigurationNotSetException
     */
    public function getStoredInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }
        $req_installment = '
            SELECT pi.*
            FROM `' . _DB_PREFIX_ . 'payplug_installment` pi
            WHERE pi.id_installment = \'' . $installment->id . '\'';
        $res_installment = DB::getInstance()->executeS($req_installment);

        if (!$res_installment) {
            return false;
        } else {
            return $res_installment;
        }
    }

    /**
     * @param $installment
     * @param $step
     * @return array|bool|object|null
     * @throws ConfigurationNotSetException
     */
    public function getStoredInstallmentTransaction($installment, $step)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }
        $req_installment = '
            SELECT pi.*
            FROM `' . _DB_PREFIX_ . 'payplug_installment` pi 
            WHERE pi.id_installment = \'' . $installment->id . '\' 
            AND pi.step = ' . (int)$step;
        $res_installment = DB::getInstance()->getRow($req_installment);

        if (!$res_installment) {
            return false;
        } else {
            return $res_installment;
        }
    }

    /**
     * Get supported currencies
     *
     * @return array
     */
    private function getSupportedCurrencies()
    {
        $currencies = [];
        foreach (explode(';', Configuration::get('PAYPLUG_MIN_AMOUNTS')) as $amount_cur) {
            $cur = [];
            preg_match('/^([A-Z]{3}):([0-9]*)$/', $amount_cur, $cur);
            $currencies[] = Tools::strtoupper($cur[1]);
        }

        return $currencies;
    }

    /**
     * Get total amount already refunded
     *
     * @param $id_order
     * @return bool|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
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
     * @return string
     */
    private function getUninstallContent()
    {
        $this->postProcess();
        $this->html = '';

        $PAYPLUG_KEEP_CARDS = (int)Configuration::get('PAYPLUG_KEEP_CARDS');

        $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin.css');

        $this->context->smarty->assign([
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'site_url' => $this->site_url,
            'PAYPLUG_KEEP_CARDS' => $PAYPLUG_KEEP_CARDS,
        ]);

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin_uninstall_configuration.tpl');

        return $this->html;
    }

    /**
     * @return bool
     */
    public function has_live_key()
    {
        return (bool)Configuration::get('PAYPLUG_LIVE_API_KEY');
    }

    /**
     * Automatically update PayPlugCarrier after someone update a Prestashop Carrier
     *
     * @param array $params List of parameters used when the hook was triggered
     * @return void
     */
    public function hookActionCarrierUpdate($params)
    {
        $updated_carrier = $params['carrier'];
        $payplug_carrier = PayPlugCarrier::getPayPlugCarrierByIdCarrier((int)$params['id_carrier']);

        // if the payplug carrier don't exists, set default value
        if (!Validate::isLoadedObject($payplug_carrier)) {
            $payplug_carrier->delay = '1';
            $payplug_carrier->delivery_type = 'carrier';
        }

        $payplug_carrier->id_carrier = (int)$updated_carrier->id;
        $payplug_carrier->save();
    }

    /**
     * @param $customer
     * @return false|string
     */
    public function hookActionDeleteGDPRCustomer($customer)
    {
        if (!$this->card->deleteCards((int)$customer['id'])) {
            return json_encode($this->l('PayPlug : Unable to delete customer saved cards.'));
        }
        return json_encode(true);
    }

    /**
     * @param $customer
     * @return false|string
     * @throws PrestaShopDatabaseException
     */
    public function hookActionExportGDPRData($customer)
    {
        if (!$cards = $this->gdprCardExport((int)$customer['id'])) {
            return json_encode($this->l('PayPlug : Unable to export customer saved cards.'));
        } else {
            return json_encode($cards);
        }
    }

    /**
     * Automatically add and populate a PayPlugCarrier after someone add a Prestashop Carrier
     *
     * @param array $params List of parameters used when the hook was triggered
     * @return void
     */
    public function hookActionObjectCarrierAddAfter($params)
    {
        $new_carrier = $params['object'];
        $new_pp_carrier = new PayPlugCarrier();
        $new_pp_carrier->populateFromCarrier($new_carrier);
        $new_pp_carrier->save();
    }

    /**
     * @param $params
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionOrderStatusUpdate($params)
    {
        $active = false;
        $order = new Order((int)$params['id_order']);
        $active = Module::isEnabled($this->name);
        if (!$active
            || !$order->payment == $this->displayName
            || !$this->isReferredPaymentsActive()
            || !$this->isReferredAutoActive()
            || $params['newOrderStatus']->id != Configuration::get('PAYPLUG_DEFERRED_STATE')
        ) {
            return;
        } else {
            $cart = new Cart((int)$order->id_cart);
            $payment_method = $this->getPaymentMethodByCart($cart);
            if ($payment_method['type'] == 'installment') {
                $installment = new PPPaymentInstallment($payment_method['id']);
                $payment = $installment->getFirstPayment();
            } else {
                $payment = new PPPayment($payment_method['id']);
            }
            if (!$payment->isPaid()) {
                $payment->capture();
                $payment->refresh();
                if ($payment->resource->card->id !== null) {
                    $this->card->saveCard($payment->resource);
                }
            }
        }
    }

    /**
     * @param array $params
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \Payplug\Exception\ConfigurationException
     * @throws ConfigurationNotSetException
     * @see Module::hookAdminOrder()
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

        $show_popin = false;
        $display_refund = false;
        $refund_delay_oney = false;
        $show_menu_refunded = false;
        $show_menu_update = false;
        $show_menu_installment = false;
        $show_menu_payment = false;
        $pay_error = '';
        $amount_refunded_payplug = 0;
        $amount_available = 0;

        $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);
        $amount_refunded_presta = $this->getTotalRefunded($order->id);

        if ($inst_id = $this->getPayplugInstallmentCart($order->id_cart)) {
            $payment_list = [];
            if (!$inst_id || empty($inst_id) || !$installment = $this->retrieveInstallment($inst_id)) {
                if (Configuration::get('PAYPLUG_SANDBOX_MODE') == 1) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                    if (empty($inst_id) || !$installment = $this->retrieveInstallment($inst_id)) {
                        $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                        return false;
                    }
                } elseif (Configuration::get('PAYPLUG_SANDBOX_MODE') == 0) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                    if (empty($inst_id) || !$installment = $this->retrieveInstallment($inst_id)) {
                        $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                        return false;
                    }
                }
            }

            $pay_mode = $installment->is_live ? $this->l('LIVE') : $this->l('TEST');
            $payments = $order->getOrderPaymentCollection();
            $pps = [];
            if (count($payments) > 0) {
                foreach ($payments as $payment) {
                    $pps[] = $payment->transaction_id;
                }
            }

            $payment_list_new = [];
            foreach ($installment->schedule as $schedule) {
                if ($schedule->payment_ids != null) {
                    foreach ($schedule->payment_ids as $pay_id) {
                        $p = $this->retrievePayment($pay_id);
                        $payment_list_new[] = $this->buildPaymentDetails($p);
                        if ((int)$p->is_paid == 0) {
                            $amount_refunded_payplug += 0;
                            $amount_available += 0;
                        } elseif ((int)$p->is_refunded == 1) {
                            $amount_refunded_payplug += ($p->amount_refunded) / 100;
                            $amount_available += ($p->amount - $p->amount_refunded) / 100;
                        } elseif ((int)$p->amount_refunded > 0) {
                            $amount_refunded_payplug += ($p->amount_refunded) / 100;
                            $amount_refundable_payment = ($p->amount - $p->amount_refunded);
                            if ($amount_refundable_payment >= 10) {
                                $amount_available += $amount_refundable_payment / 100;
                            }
                        } else {
                            $amount_available += ($p->amount >= 10 ? $p->amount / 100 : 0);
                        }

                        if ($amount_available > 0) {
                            $display_refund = true;
                        }

                        if ($p->amount_refunded > 0) {
                            $show_menu_refunded = true;
                        }
                    }
                } else {
                    $payment_list_new[] = [
                        'id' => null,
                        'status' => $installment->is_active ? $this->payment_status[6] : $this->payment_status[7],
                        'status_class' => $installment->is_active ? 'pp_success' : 'pp_error',
                        'status_code' => 'incoming',
                        'amount' => (int)$schedule->amount / 100,
                        'card_brand' => null,
                        'card_mask' => null,
                        'tds' => null,
                        'card_date' => null,
                        'mode' => null,
                        'authorization' => null,
                        'date' => date('d/m/Y', strtotime($schedule->date)),
                    ];
                }
            }

            $id_currency = (int)Currency::getIdByIsoCode($installment->currency);
            $show_menu_installment = true;
            $inst_status = $installment->is_active ?
                $this->l('ongoing') :
                ($installment->is_fully_paid ?
                    $this->l('paid') :
                    $this->l('suspended')
                );
            $inst_status_code = $installment->is_active ?
                'ongoing' :
                ($installment->is_fully_paid ? 'paid' : 'suspended');
            $inst_aborted = !$installment->is_active;
            $ppInstallment = new PPPaymentInstallment($installment->id);
            $instPaymentOne = $ppInstallment->getFirstPayment();
            $inst_can_be_aborted = !($inst_aborted || ($instPaymentOne->isDeferred() && !$instPaymentOne->isPaid()));
            $inst_paid = $installment->is_fully_paid;
            $this->context->smarty->assign([
                'inst_id' => $inst_id,
                'inst_status' => $inst_status,
                'inst_status_code' => $inst_status_code,
                'inst_aborted' => $inst_aborted,
                'inst_paid' => $inst_paid,
                'payment_list' => $payment_list,
                'payment_list_new' => $payment_list_new,
                'inst_can_be_aborted' => $inst_can_be_aborted,
            ]);

            $sandbox = ((int)$installment->is_live == 1 ? false : true);
            $state_addons = ($sandbox ? '_TEST' : '');
            $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);

            $this->updatePayplugInstallment($installment);
        } else {
            if (!$pay_id = $this->isTransactionPending($order->id_cart)) {
                $pay_id = $this->getPayplugOrderPayment($order->id);

                if (!$pay_id) {
                    $payments = $order->getOrderPaymentCollection();
                    if (count($payments->getResults()) > 1 || !$payments->getFirst()) {
                        return false;
                    } else {
                        $pay_id = $payments->getFirst()->transaction_id;
                    }
                }
            }

            $sandbox = (bool)Configuration::get('PAYPLUG_SANDBOX_MODE');

            if (!$pay_id || empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                if ($sandbox) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                    if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                        $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                        return false;
                    }
                } else {
                    $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                    if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                        $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                        return false;
                    }
                }
            }

            // check if order is from oney payment
            $oney_payment_method = ['oney_x3_with_fees', 'oney_x4_with_fees'];
            $is_oney = isset($payment->payment_method)
                && isset($payment->payment_method['type'])
                && in_array($payment->payment_method['type'], $oney_payment_method);

            // Update order state if is pending
            $state_addons = $payment->is_live ? '' : '_TEST';
            $paid_state = $this->getConfiguration('PAYPLUG_ORDER_STATE_PAID' . $state_addons);
            $oney_state = $this->getConfiguration('PAYPLUG_ORDER_STATE_ONEY_PG' . $state_addons);
            $cancelled_state = $this->getConfiguration('PS_OS_CANCELED');

            if ($is_oney) {
                // update order state from payment status
                if ($order->getCurrentState() == $oney_state) {
                    $new_order_state = false;
                    if ($payment->is_paid) {
                        $new_order_state = $paid_state;
                    } elseif (isset($payment->failure) && $payment->failure !== null) {
                        $new_order_state = $cancelled_state;
                    }

                    if ($new_order_state) {
                        $order_history = new OrderHistory();
                        $order_history->id_order = $order->id;
                        $order_history->changeIdOrderState($new_order_state, $order->id, true);
                        $order_history->save();
                    }
                }
            }

            $single_payment = $this->buildPaymentDetails($payment);
            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
            $amount_available_payment = ($payment->amount - $payment->amount_refunded);
            $amount_available = ($amount_available_payment >= 10 ? $amount_available_payment / 100 : 0);
            $id_currency = (int)Currency::getIdByIsoCode($payment->currency);
            $state_addons = (!$payment->is_live ? '_TEST' : '');

            $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND' . $state_addons);
            $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING' . $state_addons);

            $current_state = (int)$order->getCurrentState();

            if ((int)$payment->is_paid == 0) {
                if (isset($payment->failure) && isset($payment->failure->message)) {
                    $pay_error = '(' . $payment->failure->message . ')';
                } else {
                    $pay_error = '';
                }
                $display_refund = false;
                if ($current_state != 0 && $current_state == $id_pending_order_state) {
                    $show_menu_update = true;
                }
            } elseif ((((int)$payment->amount_refunded > 0)
                    || $amount_refunded_presta > 0)
                && (int)$payment->is_refunded != 1) {
                $display_refund = true;
            } elseif ((int)$payment->is_refunded == 1) {
                $show_menu_refunded = true;
                $display_refund = false;
            }  elseif(time() >= $payment->refundable_until) {
                $display_refund = false;
            } else {
                $display_refund = true;
                if ($is_oney) {
                    $refund_delay_oney = time() <= $payment->refundable_after;
                }
            }

            $conf = (int)Tools::getValue('conf');
            if ($conf == 30 || $conf == 31) {
                $show_popin = true;

                $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);

                $this->html .= '<a class="pp_admin_ajax_url" href="' . $admin_ajax_url . '"></a>';
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
                $pay_brand .= ' ' . $this->l('Card') . ' (' . $payment->card->country . ')';
            }
            if ($payment->card->last4 != '') {
                $pay_card_mask = '**** **** **** ' . $payment->card->last4;
            } else {
                $pay_card_mask = $this->l('Unavailable in test mode');
            }

            // Deferred payment does'nt display 3DS option before capture so we have to consider it null
            if ($payment->is_3ds !== null) {
                $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
                $this->context->smarty->assign(['pay_tds' => $pay_tds]);
            }

            $pay_mode = $payment->is_live ? $this->l('LIVE') : $this->l('TEST');

            if ($payment->card->exp_month === null) {
                $pay_card_date = $this->l('Unavailable in test mode');
            } else {
                $pay_card_date = date(
                    'm/y',
                    strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year)
                );
            }

            $show_menu_payment = true;

            $this->context->smarty->assign([
                'pay_id' => $pay_id,
                'pay_status' => $pay_status,
                'pay_amount' => $pay_amount,
                'pay_date' => $pay_date,
                'pay_brand' => $pay_brand,
                'pay_card_mask' => $pay_card_mask,
                'pay_card_date' => $pay_card_date,
                'pay_error' => $pay_error,
            ]);

            //Deferred payment does'nt display 3DS option before capture so we have to consider it null
            if ($payment->is_3ds !== null) {
                $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
                $this->context->smarty->assign(['pay_tds' => $pay_tds]);
            }
        }

        $currency = new Currency($id_currency);
        if (!Validate::isLoadedObject($currency)) {
            return false;
        }

        $amount_suggested = (min($amount_refunded_presta, $amount_available) - $amount_refunded_payplug);
        $amount_suggested = number_format((float)$amount_suggested, 2);
        if ($amount_suggested < 0) {
            $amount_suggested = 0;
        }

        if ($display_refund) {
            $this->context->smarty->assign([
                'order' => $order,
                'amount_refunded_payplug' => $amount_refunded_payplug,
                'amount_available' => $amount_available,
                'amount_refunded_presta' => $amount_refunded_presta,
                'currency' => $currency,
                'amount_suggested' => $amount_suggested,
                'id_new_order_state' => $id_new_order_state,
            ]);
        } elseif ($show_menu_refunded) {
            $this->context->smarty->assign([
                'amount_refunded_payplug' => $amount_refunded_payplug,
                'currency' => $currency,
            ]);
        } elseif ($show_menu_update) {
            $this->context->smarty->assign([
                'admin_ajax_url' => $admin_ajax_url,
                'order' => $order,
            ]);
        }

        $display_single_payment = $show_menu_payment;
        $this->context->smarty->assign([
            'logo_url' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'display_single_payment' => $display_single_payment,
            'display_refund' => $display_refund,
            'refund_delay_oney' => $refund_delay_oney,
            'show_menu_payment' => $show_menu_payment,
            'show_menu_refunded' => $show_menu_refunded,
            'show_menu_update' => $show_menu_update,
            'show_menu_installment' => $show_menu_installment,
            'pay_mode' => $pay_mode,
            'order' => $order,
        ]);

        if ($display_single_payment) {
            $this->context->smarty->assign([
                'single_payment' => $single_payment,
            ]);
        }

        if ($show_popin && $display_refund) {
            $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin_order_popin.js');
        }

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/order/order.tpl');
        return $this->html;
    }

    /**
     * @param $params
     * @return string|void
     */
    public function hookCustomerAccount($params)
    {
        if (!$this->isAllowed()) {
            return false;
        }

        $payplug_cards_url = $this->context->link->getModuleLink(
            $this->name,
            'cards',
            ['process' => 'cardlist'],
            true
        );

        if ((class_exists($this->PrestashopSpecificClass))
            && (method_exists($this->PrestashopSpecificObject, 'hookCustomerAccount'))) {
            $this->PrestashopSpecificObject->hookCustomerAccount();
        }

        $this->smarty->assign([
            'version' => _PS_VERSION_[0] . '.' . _PS_VERSION_[2],
            'payplug_cards_url' => $payplug_cards_url
        ]);

        return $this->display(__FILE__, 'customer/my_account.tpl');
    }

    /**
     * @param $params
     * @return string|void
     */
    public function hookDisplayExpressCheckout($param)
    {
        if (!$this->oney->isOneyAllowed()) {
            return false;
        }

        $is_elligible = $this->oney->isOneyElligible($this->context->cart, false, true);
        $is_elligible = $is_elligible['result'];

        $this->smarty->assign([
            'env' => 'checkout',
            'payplug_is_oney_elligible' => $is_elligible,
        ]);
        return $this->display(__FILE__, 'oney/cta.tpl');
    }

    public function hookDisplayProductPriceBlock($param)
    {
        if ((!$this->getConfiguration('PAYPLUG_ONEY'))
            || (!$this->oney->isOneyAllowed())
            || (Dispatcher::getInstance()->getController() == 'category')
            || (Dispatcher::getInstance()->getController() == 'index')
        ) {
            return false;
        }

        $action = Tools::getValue('action');
        if ($action == 'quickview') {
            return false;
        }
        if (!isset($param['product']) || !isset($param['type']) || $param['type'] != 'after_price') {
            return;
        }

        if ($action == 'refresh') {
            $use_taxes = (bool)Configuration::get('PS_TAX');

            $id_product = (int)Tools::getValue('id_product');
            $group = Tools::getValue('group');
            // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
            if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
                $id_product_attribute = $group ? (int)Product::getIdProductAttributesByIdAttributes(
                    $id_product,
                    $group
                ) : 0;
            } else {
                $id_product_attribute = $group ? (int)Product::getIdProductAttributeByIdAttributes(
                    $id_product,
                    $group
                ) : 0;
            }
            $quantity = (int)Tools::getValue('qty', (int)Tools::getValue('quantity_wanted', 1));

            $product_price = Product::getPriceStatic(
                (int)$id_product,
                $use_taxes,
                $id_product_attribute,
                6,
                null,
                false,
                true,
                $quantity
            );
            $amount = $product_price * $quantity;
            $is_elligible = $this->oney->isValidOneyAmount($amount, $this->context->currency->id);
            $is_elligible = $is_elligible['result'];

            $this->smarty->assign([
                'payplug_is_oney_elligible' => $is_elligible,
            ]);
            $this->smarty->assign(['popin' => true]);
        }
        $this->smarty->assign(['env' => 'product']);
        return $this->display(__FILE__, 'oney/cta.tpl');
    }

    /**
     * @description To load JS and CSS medias
     *
     * @param array|string $medias
     * @return bool
     */
    public function setMedia($medias)
    {
        if (!$medias) {
            return false;
        }

        if (!is_array($medias)) {
            $medias = [$medias];
        }

        foreach ($medias as $media) {
            if (strpos($media, 'css') === false) {
                $this->context->controller->addJS($media);
            } else {
                $this->context->controller->addCSS($media);
            }
        }
        return true;
    }

    /**
     * @description To load admin and admin_order (js and css) in order details in PS 1.7.7.0
     */
    public function hookActionAdminControllerSetMedia()
    {
        if ($this->context->controller->controller_name == 'AdminOrders') {
            $this->setMedia([
                __PS_BASE_URI__ . 'modules/payplug/views/css/admin_order.css',
                __PS_BASE_URI__ . 'modules/payplug/views/css/admin.css',
                __PS_BASE_URI__ . 'modules/payplug/views/js/admin_order.js',
            ]);
        } else {
            $this->setMedia([
                __PS_BASE_URI__ . 'modules/payplug/views/js/admin.js',
                __PS_BASE_URI__ . 'modules/payplug/views/css/admin.css',
            ]);
        }
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception
     * @see Module::hookHeader()
     */
    public function hookHeader($params)
    {
        if (!$this->isAllowed()) {
            return false;
        }

        if (Tools::getValue('error')) {
            Media::addJsDef(['payment_errors' => true]);
        }
        if ((class_exists($this->PrestashopSpecificClass))
            && (method_exists($this->PrestashopSpecificObject, 'hookHeader'))) {
            $this->PrestashopSpecificObject->hookHeader();
        }

        if ((int)Tools::getValue('lightbox') == 1) {
            $cart = $params['cart'];
            if (!Validate::isLoadedObject($cart)) {
                return;
            }

            $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/embedded.js');

            $payment_options = [
                'id_card' => Tools::getValue('pc', 'new_card'),
                'is_installment' => (bool)Tools::getValue('inst'),
                'is_deferred' => (bool)Tools::getValue('def'),
            ];

            $payment = $this->preparePayment($payment_options, 'new_card');

            if ($payment['result']) {
                // If payment is paid then redirect
                if ($payment['redirect']) {
                    Tools::redirect($payment['return_url']);
                } else {
                    // else show the popin
                    $this->context->smarty->assign([
                        'payment_url' => $payment['return_url'],
                        'api_url' => $this->plugin->getApiUrl(),
                    ]);
                    return $this->display(__FILE__, 'checkout/embedded.tpl');
                }
            } else {
                $this->setPaymentErrorsCookie([
                    $this->l('The transaction was not completed and your card was not charged.')
                ]);
                $error_url = 'index.php?controller=order&step=3&error=1';
                Tools::redirect($error_url);
            }
        }

        if (Configuration::get('PAYPLUG_ONEY')) {
            Media::addJsDef([
                'payplug_oney' => true,
                'payplug_oney_loading_msg' => $this->l('Loading')
            ]);
        }

        $payplug_ajax_url = $this->context->link->getModuleLink($this->name, 'ajax', [], true);
        Media::addJsDef([
            'payplug_ajax_url' => $payplug_ajax_url,
        ]);
    }

    /**
     * @param array $params
     * @return array
     * @throws Exception
     * @see Module::hookPaymentOptions()
     *
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->isAllowed()) {
            return false;
        }

        $cart = $params['cart'];
        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $this->context->smarty->assign([
            'api_url' => $this->plugin->getApiUrl(),
        ]);

        $payment_options = $this->getPaymentOptions($cart); // Données sous forme de tableau (pour 1.6 et 1.7)

        return $this->PrestashopSpecificObject->displayPaymentOption($payment_options); // Transforme tableau en object
    }

    /**
     * @param array $params
     * @return string
     * @see Module::hookPaymentReturn()
     *
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->isAllowed()) {
            return false;
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
        $context = ['totalPaid' => $total_paid];
        if (isset($order->reference)) {
            $context['reference'] = $order->reference;
        }
        $this->smarty->assign($context);
        return $this->display(__FILE__, 'checkout/order-confirmation.tpl');
    }

    public function hookRegisterGDPRConsent()
    {
    }

    /**
     * @description Flush PayPlugCache (PS 1.6), when PrestaShop cache cleared
     *
     * @param array $params
     * @return boolean
     */
    public function hookActionAdminPerformanceControllerAfter($params)
    {
        return $this
            ->getPlugin()
            ->getCache()
            ->flushCache();
    }

    /**
     * @description Flush PayPlugCache (PS 1.7), when PrestaShop cache cleared
     *
     * @param array $params
     * @return boolean
     */
    public function hookActionClearCompileCache($params)
    {
        return $this
            ->getPlugin()
            ->getCache()
            ->flushCache();
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
                        $field = $this->formatPhoneNumber($field, $country);
                        break;
                    case 'same':
                    case 'shipping':
                    default:
                        $id_country = Country::getByIso($payment_tab['shipping']['country']);
                        $country = new Country($id_country);
                        $field = $this->formatPhoneNumber($field, $country);
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

    /**
     * @return bool
     * @throws Exception
     * @see Module::install()
     *
     */
    public function install()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Starting to install.');
        $install = [
            'flag' => true,
            'error' => false
        ];

        $report = $this->checkRequirements();
        if (!$report['php']['up2date'] && $install['flag']) {
            $this->_errors[] = Tools::displayError($this->l('Your server must run PHP 5.3 or greater'));
            $log->error('Install failed: PHP Requirement.');
            $install['flag'] = false;
            $install['error'] = 'Configuration PHP inf. version 5.3';
        } else {
            $log->info('Install success: PHP Requirement.');
        }

        if (!$report['curl']['up2date'] && $install['flag']) {
            $this->_errors[] = Tools::displayError($this->l('PHP cURL extension must be enabled on your server'));
            $log->error('Install failed: cURL Requirement.');
            $install['flag'] = false;
            $install['error'] = 'cURL Requirement';
        } else {
            $log->info('Install success: cURL Requirement.');
        }

        if (!$report['openssl']['up2date'] && $install['flag']) {
            $this->_errors[] = Tools::displayError($this->l('OpenSSL 1.0.1 or later'));
            $log->error('Install failed: OpenSSL Requirement.');
            $install['flag'] = false;
            $install['error'] = 'OpenSSL Requirement';
        } else {
            $log->info('Install success: OpenSSL Requirement.');
        }

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        $log->info('Starting to install parent::install().');
        if (!parent::install() && $install['flag']) {
            $log->error('Install failed: parent::install().');
            $install['flag'] = false;
            $install['error'] = 'parent::install()';
        } else {
            $log->info('Install success: parent::install().');
        }

        $log->info('----------------> Install hooks. <----------------');
        $hooksToRegister = [
            'paymentReturn',
            'Header',
            'adminOrder',
            'actionOrderStatusUpdate',
            'customerAccount',
            'paymentOptions',
            'Payment',
            'moduleRoutes',
            'registerGDPRConsent',
            'actionDeleteGDPRCustomer',
            'actionExportGDPRData',
            'actionObjectCarrierAddAfter',
            'actionCarrierUpdate',
            'displayProductPriceBlock',
            'displayExpressCheckout',
            'actionClearCompileCache',
            'displayBeforeShoppingCartBlock',
            'actionAdminControllerSetMedia',
        ];

        foreach ($hooksToRegister as $hookToRegister) {
            $log->info('Try to install Hook ' . $hookToRegister . '.');
            if (!$this->registerHook($hookToRegister) && $install['flag']) {
                $log->error('Install failed: Hook ' . $hookToRegister . '.');
                $install['flag'] = false;
                $install['error'] = 'Hook ' . $hookToRegister . ' non greffé';
                break;
            } else {
                $log->info('Install success: Hook ' . $hookToRegister . '.');
            }
        }

        //install hook 1.6
        $log->info('----------------> Install hooks 1.6. <----------------');
        if ($install['flag']) {
            $installHook16 = $this->installHook();
            $install['flag'] = $installHook16['flag'];
            $install['error'] = $installHook16['error'];
        }
        $log->info('----------------> Install hooks: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install configuration. <----------------');
        if (!$this->createConfig() && $install['flag']) {
            $log->error('Install failed: configuration.');
            $install['flag'] = false;
            $install['error'] = 'Création des éléments de configuration  ($this->createConfig)';
        }
        $log->info('----------------> Install configuration: ' .
            ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install order states. <----------------');
        if (!$this->createOrderStates() && $install['flag']) {
            $log->error('Install failed: order states.');
            $install['flag'] = false;
        }
        $log->info('----------------> Install order states: ' .
            ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install SQL. <----------------');
        if (!(new PayPlug\src\repositories\SQLtableRepository())->installSQL() /*&& $install['flag']*/) {
            $log->error('Install failed: SQL.');
            $install['flag'] = false;
            $install['error'] = 'Création des tables SQL';
        }
        $log->info('----------------> Install SQL: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install tab. <----------------');
        if (!$this->installTab() && $install['flag']) {
            $log->error('Install failed: tab.');
            $install['flag'] = false;
            $install['error'] = 'Onglet comprenant les détails des échéances des Paiements Fractionnés (back office)';
        }
        $log->info('----------------> Install tab: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        $log->info('----------------> Install Oney. <----------------');
        if (!$this->oney->installOney() && $install['flag']) {
            $log->error('Install failed: Oney.');
            $install['flag'] = false;
            $install['error'] = 'Oney ($this->installOney)';
        }
        $log->info('----------------> Install Oney: ' . ($install['flag'] ? 'ok' : 'nok') . ' <----------------');

        if ($install['flag']) {
            $log->info('Install succeeded.');
            return true;
        }

        $log->info('Install failed.');
        $log->info('Install error: ' . $install['error']);

        // revert installation
        $this->uninstall();
        $install['error'] = (isset($install['error'])) ? 'Élément en cause : ' . $install['error'] : '';
        $this->context->controller->errors[] = $this->l('Le module PayPlug n\'a pas été installé 
        en raison d\'une erreur. Les modifications apportées ont bien été annulées. ' . $install['error']);
        return false;
    }

    /**
     * @param $tabClass
     * @param $translations
     * @param $idTabParent
     * @param null $moduleName
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installModuleTab($tabClass, $translations, $idTabParent, $moduleName = null)
    {
        $tab = new Tab();

        foreach (Language::getLanguages(false) as $language) {
            if (isset($translations[Tools::strtolower($language['iso_code'])])) {
                $tab->name[(int)$language['id_lang']] = $translations[Tools::strtolower($language['iso_code'])];
            } else {
                $tab->name[(int)$language['id_lang']] = $translations['en'];
            }
        }

        $tab->class_name = $tabClass;
        if (is_null($moduleName)) {
            $moduleName = $this->name;
        }

        $tab->module = $moduleName;
        $tab->id_parent = $idTabParent;

        if (!$tab->save()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab()
    {
        if ((class_exists($this->PrestashopSpecificClass))
            && (method_exists($this->PrestashopSpecificObject, 'installTab'))) {
            return $this->PrestashopSpecificObject->installTab();
        }

        $install = [];

        $translationsAdminPayPlug = [
            'en' => 'PayPlug',
            'gb' => 'PayPlug',
            'it' => 'PayPlug',
            'fr' => 'PayPlug'
        ];
        $install['flag'] = $this->installModuleTab('AdminPayPlug', $translationsAdminPayPlug, 0);

        $translationsAdminPayPlugInstallment = [
            'en' => 'Installment Plans',
            'gb' => 'Installment Plans',
            'it' => 'Pagamenti frazionati',
            'fr' => 'Paiements en plusieurs fois'
        ];

        $adminPayPlugId = Tab::getIdFromClassName('AdminPayPlug');
        $install['flag'] = $install['flag']
            && $this->installModuleTab(
                'AdminPayPlugInstallment',
                $translationsAdminPayPlugInstallment,
                $adminPayPlugId,
                $this->name
            );

        return $install['flag'];
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
                $installment = \Payplug\InstallmentPlan::retrieve($payment_id);
                if ($installment && $installment->is_active) {
                    $schedules = $installment->schedule;
                    foreach ($schedules as $schedule) {
                        foreach ($schedule->payment_ids as $pay_id) {
                            $inst_payment = \Payplug\Payment::retrieve($pay_id);
                            if ($inst_payment && $inst_payment->is_paid) {
                                return true;
                            }
                        }
                    }
                }
                break;
            case 'payment':
            default:
                $payment = \Payplug\Payment::retrieve($payment_id);
                return $payment && $payment->is_paid;
        }
        return false;
    }

    /**
     * @return bool
     */
    private function isReferredAutoActive()
    {
        return (int)Configuration::get('PAYPLUG_DEFERRED_AUTO') == 1;
    }

    /**
     * @return bool
     */
    private function isReferredPaymentsActive()
    {
        return (int)Configuration::get('PAYPLUG_DEFERRED') == 1;
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
            FROM ' . _DB_PREFIX_ . 'payplug_payment_cart ppc  
            WHERE ppc.id_cart = ' . (int)$id_cart . '
            AND ppc.is_pending = 1';
        $res_payment_cart = Db::getInstance()->getValue($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        } else {
            return $res_payment_cart;
        }
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

    public function installOneyHook($hook)
    {
        $this->registerHook($hook);
        $this->context->controller->warnings[] = $this->l($hook);
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
        $response = \Payplug\Authentication::getKeysByLogin($email, $password);
        $json_answer = $response['httpResponse'];
        if ($this->setApiKeysbyJsonResponse($json_answer)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Make a refund
     *
     * @param string $pay_id
     * @param int $amount
     * @param string $metadata
     * @param string $pay_mode
     * @param null $inst_id
     * @return string
     * @throws \Payplug\Exception\ConfigurationException
     * @throws ConfigurationNotSetException
     */
    public function makeRefund($pay_id, $amount, $metadata, $pay_mode = 'LIVE', $inst_id = null)
    {
        if ($pay_mode == 'TEST') {
            $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
        } else {
            $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
        }
        if ($pay_id == null) {
            if ($inst_id != null) {
                try {
                    $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
                    if (isset($installment->schedule)) {
                        $total_amount = $amount;
                        $refund_to_go = [];
                        $truly_refundable_amount = 0;
                        foreach ($installment->schedule as $schedule) {
                            if (!empty($schedule->payment_ids)) {
                                foreach ($schedule->payment_ids as $p_id) {
                                    $p = \Payplug\Payment::retrieve($p_id);
                                    if ($p->is_paid && !$p->is_refunded && $amount > 0) {
                                        $amount_refundable = (int)($p->amount - $p->amount_refunded);
                                        $truly_refundable_amount += $amount_refundable;
                                        if ($truly_refundable_amount < 10) {
                                            continue;
                                        } elseif ($amount >= $amount_refundable) {
                                            $data = [
                                                'amount' => $amount_refundable,
                                                'metadata' => $metadata
                                            ];
                                            $amount -= $amount_refundable;
                                        } else {
                                            $data = [
                                                'amount' => $amount,
                                                'metadata' => $metadata
                                            ];
                                            $amount = 0;
                                        }
                                        $refund_to_go[] = ['id' => $p_id, 'data' => $data];
                                    }
                                }
                            }
                        }
                        if ($truly_refundable_amount < $total_amount) {
                            return ('error');
                        }
                        if (!empty($refund_to_go)) {
                            foreach ($refund_to_go as $refnd) {
                                try {
                                    $refund = \Payplug\Refund::create($refnd['id'], $refnd['data']);
                                } catch (Exception $e) {
                                    return ('error');
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    return ('error');
                }
                $this->updatePayplugInstallment($installment);
            } else {
                return ('error');
            }
        } else {
            $data = [
                'amount' => (int)$amount,
                'metadata' => $metadata
            ];

            try {
                $refund = \Payplug\Refund::create($pay_id, $data);
            } catch (Exception $e) {
                return ('error');
            }
        }

        return $refund;
    }

    /**
     * Send cURL request to PayPlug to patch a given payment
     *
     * @param String $api_key
     * @param String $pay_id
     * @param Array $data
     * @return Array
     * @throws ConfigurationNotSetException
     */
    public function patchPayment($api_key, $pay_id, $data)
    {
        $payment = \Payplug\Resource\Payment::fromAttributes(array('id' => $pay_id));
        $response = $payment->update($data);
        $json_answer = $response['httpResponse'];

        $result = [
            'status' => false,
            'message' => null,
        ];

        if (isset($json_answer['object']) && $json_answer['object'] == 'error') {
            $result['status'] = false;
            $result['message'] = $json_answer['message'];
        } else {
            $result['status'] = true;
        }
        return $result;
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
     * @description
     * prepare payment
     *
     * @param $options
     * @param string $id_card
     * @return mixed
     * @throws Exception
     */
    public function preparePayment($options, $id_card = null)
    {
        if (!Validate::isLoadedObject($this->context->cart)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->l('The transaction was not completed and your card was not charged.')
            ];
        }

        $cart = $this->context->cart;

        $default_options = [
            'id_card' => 'new_card',
            'is_installment' => false,
            'is_deferred' => false,
            'is_oney' => false
        ];

        foreach($default_options as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }

        $customer = new Customer((int)$cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->l('The transaction was not completed and your card was not charged.')
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
            'oney' => (int)Configuration::get('PAYPLUG_ONEY')
        ];

        $is_one_click = $options['id_card'] != 'new_card' && $config['one_click'];
        $options['is_installment'] = $options['is_installment'] && $config['installment'];

        // defined which is current payment method
        if ($is_one_click) {
            $payment_method = 'oneclick';
        } elseif ($options['is_oney']) {
            $payment_method = 'oney';
        } elseif ($options['is_installment']) {
            $payment_method = 'installment';
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
                'response' => $this->l('The transaction was not completed and your card was not charged.')
            ];
        }

        // Amount
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $amount = (int)(round(($amount * 100), PHP_ROUND_HALF_UP));
        $current_amounts = $this->getAmountsByCurrency($currency);
        if ($amount < $current_amounts['min_amount'] || $amount > $current_amounts['max_amount']) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->l('The transaction was not completed and your card was not charged.')
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
            'Website' => Tools::getShopDomainSsl(true, false),
        ];

        // Addresses
        $billing_address = new Address((int)$cart->id_address_invoice);
        $shipping_address = new Address((int)$cart->id_address_delivery);

        // ISO
        $billing_iso = $this->getIsoCodeByCountryId((int)$billing_address->id_country);
        $shipping_iso = $this->getIsoCodeByCountryId((int)$shipping_address->id_country);
        if (!$shipping_iso || !$billing_iso) {
            $default_language = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
            $iso_code_list = $this->getIsoCodeList();
            if (in_array(Tools::strtoupper($default_language->iso_code), $iso_code_list, true)) {
                $iso_code = $default_language->iso_code;
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
            'company_name' => !empty($billing_address->company) ?
                $billing_address->company :
                $billing_address->firstname . ' ' . $billing_address->lastname,
            'email' => $customer->email,
            'landline_phone_number' => $this->formatPhoneNumber($billing_address->phone, $billing_address->id_country),
            'mobile_phone_number' => $this->formatPhoneNumber(
                $billing_address->phone_mobile,
                $billing_address->id_country
            ),
            'address1' => !empty($billing_address->address1) ? $billing_address->address1 : null,
            'address2' => !empty($billing_address->address2) ? $billing_address->address2 : null,
            'postcode' => !empty($billing_address->postcode) ? $billing_address->postcode : null,
            'city' => !empty($billing_address->city) ? $billing_address->city : null,
            'country' => $billing_iso,
            'language' => $this->getIsoFromLanguageCode($this->context->language),
        ];

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
            'company_name' => !empty($shipping_address->company) ?
                $shipping_address->company :
                $shipping_address->firstname . ' ' . $shipping_address->lastname,
            'email' => $customer->email,
            'landline_phone_number' => $this->formatPhoneNumber(
                $shipping_address->phone,
                $shipping_address->id_country
            ),
            'mobile_phone_number' => $this->formatPhoneNumber(
                $shipping_address->phone_mobile,
                $shipping_address->id_country
            ),
            'address1' => !empty($shipping_address->address1) ? $shipping_address->address1 : null,
            'address2' => !empty($shipping_address->address2) ? $shipping_address->address2 : null,
            'postcode' => !empty($shipping_address->postcode) ? $shipping_address->postcode : null,
            'city' => !empty($shipping_address->city) ? $shipping_address->city : null,
            'country' => $shipping_iso,
            'language' => $this->getIsoFromLanguageCode($this->context->language),
            'delivery_type' => $delivery_type,
        ];

        // 3ds
        $force_3ds = false;

        //save card
        $allow_save_card = $config['one_click'] && Cart::isGuestCartByCartId($cart->id) != 1 && $options['id_card'] == 'new_card';

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
            $payment_tab['payment_method'] = $options['id_card'] && $options['id_card'] != 'new_card' ?
                $this->card->getCardId((int)$cart->id_customer, $options['id_card'], $config['company'])
                : null;
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
            if (!$this->isValidMobilePhoneNumber(
                $payment_tab['billing']['mobile_phone_number'],
                $payment_tab['billing']['country']
            )) {
                if ($this->isValidMobilePhoneNumber(
                    $payment_tab['billing']['landline_phone_number'],
                    $payment_tab['billing']['country']
                )) {
                    $payment_tab['billing']['mobile_phone_number'] = $payment_tab['billing']['landline_phone_number'];
                }
            }

            // check shipping phonenumber
            if (!$this->isValidMobilePhoneNumber(
                $payment_tab['shipping']['mobile_phone_number'],
                $payment_tab['shipping']['country']
            )) {
                if ($this->isValidMobilePhoneNumber(
                    $payment_tab['shipping']['landline_phone_number'],
                    $payment_tab['shipping']['country']
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

                //
                if ($payment_data) {
                    // hydrate with payment data
                    $payment_tab = $this->hydratePaymentTabFromPaymentData($payment_tab, $payment_data);


                    // then recheck
                    if ($this->oney->hasOneyRequiredFields($payment_tab)) {
                        $this->setPaymentErrorsCookie(['oney_required_field_' . $options['is_oney']]);
                        return ['result' => false, 'response' => false];
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

        // Create payment
        try {
            if ($options['is_installment']) {
                $payment = \Payplug\InstallmentPlan::create($payment_tab);
                if ($payment->failure != null && !empty($payment->failure->message)) {
                    return [
                        'result' => false,
                        'response' => $payment->failure->message,
                    ];
                }
                $this->storeInstallment($payment->id, (int)$cart->id);
            } else {
                $payment = \Payplug\Payment::create($payment_tab);
                if ($payment->failure == true && !empty($payment->failure->message)) {
                    return [
                        'result' => false,
                        'response' => $payment->failure->message,
                    ];
                }
                $this->storePayment($payment->id, (int)$cart->id);
            }
        } catch (Exception $e) {
            $messages = $this->catchErrorsFromApi($e->__toString());
            return [
                'result' => false,
                'payment_tab' => $payment_tab,
                'response' => count($messages) > 1 ? $messages : reset($messages),
            ];
        }

        switch ($payment_method) {
            case 'oneclick':
                $redirect = $payment->is_paid;
                if (!$redirect && $options['is_deferred']) {
                    $redirect = (bool)$payment->authorization->authorized_at;
                }
                $payment_return = [
                    'result' => true,
                    'embedded' => true,
                    'redirect' => $redirect, // force `true` we are in 3DS 1
                    'return_url' => $redirect ?
                        $payment->hosted_payment->return_url : $payment->hosted_payment->payment_url,
                ];
                break;
            case 'oney':
                $payment_return = [
                    'result' => 'new_card',
                    'embedded' => false,
                    'redirect' => true,
                    'return_url' => $payment->hosted_payment->payment_url,
                ];
                break;
            case 'standard':
            case 'installment':
            default:
                $payment_return = [
                    'result' => 'new_card',
                    'embedded' => $this->getConfiguration('PAYPLUG_EMBEDDED_MODE') && !$this->isMobiledevice(),
                    'redirect' => $this->isMobiledevice(),
                    'return_url' => $payment->hosted_payment->payment_url,
                ];
                break;
        }

        return $payment_return;
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
            FROM ' . _DB_PREFIX_ . 'payplug_payment_cart ppc
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

    public function refundPayment()
    {
        if (!$this->checkAmountToRefund(Tools::getValue('amount'))) {
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('Incorrect amount to refund')
            ]));
        } else {
            $amount = str_replace(',', '.', Tools::getValue('amount'));
            $amount = (float)($amount * 1000); // we use this trick to avoid rounding while converting to int
            $amount = (float)($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
            $amount = (int)$amount;
        }

        $id_order = Tools::getValue('id_order');
        $pay_id = Tools::getValue('pay_id');
        $inst_id = Tools::getValue('inst_id');
        $metadata = [
            'ID Client' => (int)Tools::getValue('id_customer'),
            'reason' => 'Refunded with Prestashop'
        ];
        $pay_mode = Tools::getValue('pay_mode');
        $refund = $this->makeRefund($pay_id, $amount, $metadata, $pay_mode, $inst_id);
        if ($refund == 'error') {
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('Cannot refund that amount.')
            ]));
        } else {
            $new_state = 7;
            $reload = false;

            if ($inst_id != null) {
                $installment = $this->retrieveInstallment($inst_id);
                $amount_available = 0;
                $amount_refunded_payplug = 0;
                if (isset($installment->schedule)) {
                    foreach ($installment->schedule as $schedule) {
                        if (!empty($schedule->payment_ids)) {
                            foreach ($schedule->payment_ids as $p_id) {
                                $p = \Payplug\Payment::retrieve($p_id);
                                if ($p->is_paid && !$p->is_refunded) {
                                    $amount_available += (int)($p->amount - $p->amount_refunded);
                                }
                                $amount_refunded_payplug += $p->amount_refunded;
                            }
                        }
                    }
                }
                $amount_available = (float)($amount_available / 100);
                $amount_refunded_payplug = (float)($amount_refunded_payplug / 100);
                if ((int)Tools::getValue('id_state') != 0 || $amount_available == 0) {
                    $new_state = (int)Tools::getValue('id_state');
                    if ($new_state == 0) {
                        if ($installment->is_live == 1) {
                            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                        } else {
                            $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
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
                    $reload = true;
                }
            } else {
                $payment = $this->retrievePayment($refund->payment_id);
                if ((int)Tools::getValue('id_state') != 0) {
                    $new_state = (int)Tools::getValue('id_state');
                } elseif ($payment->is_refunded == 1) {
                    if ($payment->is_live == 1) {
                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                    } else {
                        $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
                    }
                }
                if ((int)Tools::getValue('id_state') != 0 || ($payment->is_refunded == 1 && empty($inst_id))) {
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
            }


            $data = $this->getRefundData(
                $amount_refunded_payplug,
                $amount_available
            );
            die(json_encode([
                'status' => 'ok',
                'data' => $data,
                'message' => $this->l('Amount successfully refunded.'),
                'reload' => $reload
            ]));
        }
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
            UPDATE ' . _DB_PREFIX_ . 'payplug_payment_cart ppc  
            SET ppc.is_pending = 1
            WHERE ppc.id_cart = ' . (int)$id_cart;
        $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
        if (!$res_payment_cart) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Retrieve payment informations
     *
     * @param $inst_id
     * @return bool|\Payplug\Resource\InstallmentPlan|null
     */
    public function retrieveInstallment($inst_id)
    {
        try {
            $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
        } catch (Exception $e) {
            return false;
        }
        return $installment;
    }

    /**
     * Retrieve payment informations
     *
     * @param string $pay_id
     * @return bool|\Payplug\Resource\Payment|null
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
     * Run update module
     */
    public function runUpgradeModule()
    {
        $upgrade = parent::runUpgradeModule();

        $this->checkOrderStates();

        return $upgrade;
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
            $this->warning = $this->l('In order to accept payments you need to configure your module 
            by connecting your PayPlug account.');
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

    public function setNotification()
    {
        return new PayPlugNotifications();
    }

    public function setValidation()
    {
        return new PayPlugValidation();
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
     * Set payment data in cookie
     *
     * @return mixed
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

    /**
     * @description Set payment errors in cookie
     *
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
     * @description Register installment for later use
     *
     * @param string $installment_id
     * @param int $id_cart
     * @return bool
     */
    public function storeInstallment($installment_id, $id_cart)
    {
        if ($pay_id = $this->getPaymentByCart($id_cart)) {
            $this->deletePayment($pay_id, $id_cart);
        }

        $req_installment_cart_exists = '
            SELECT * 
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic  
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_installment_cart_exists = Db::getInstance()->getRow($req_installment_cart_exists);
        $date_upd = date('Y-m-d H:i:s');
        $is_pending = 0;
        if (!$res_installment_cart_exists) {
            //insert
            $req_installment_cart = '
                INSERT INTO ' . _DB_PREFIX_ . 'payplug_installment_cart (id_installment, id_cart, is_pending, date_upd)
                VALUES (\'' . pSQL($installment_id) . '\', 
                ' . (int)$id_cart . ', 
                ' . (int)$is_pending . ', 
                \'' . pSQL($date_upd) . '\')';
            $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
            if (!$res_installment_cart) {
                return false;
            }
        } else {
            //update
            $req_installment_cart = '
                UPDATE ' . _DB_PREFIX_ . 'payplug_installment_cart pic  
                SET pic.id_installment = \'' . pSQL($installment_id) . '\', pic.date_upd = \'' . pSQL($date_upd) . '\'
                WHERE pic.id_cart = ' . (int)$id_cart;
            $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
            if (!$res_installment_cart) {
                return false;
            }
        }

        return true;
    }

    /**
     * @description Register payment for later use
     *
     * @param string $pay_id
     * @param int $id_cart
     * @return bool
     */
    private function storePayment($pay_id, $id_cart)
    {
        if ($inst_id = $this->getInstallmentByCart($id_cart)) {
            $this->deleteInstallment($inst_id, $id_cart);
        }

        $req_payment_cart_exists = new DbQuery();
        $req_payment_cart_exists->select('*');
        $req_payment_cart_exists->from('payplug_payment_cart', 'ppc');
        $req_payment_cart_exists->where('ppc.id_cart = ' . (int)$id_cart);
        $res_payment_cart_exists = Db::getInstance()->getRow($req_payment_cart_exists);

        if (!$res_payment_cart_exists) {
            //insert
            $req_payment_cart = '
                INSERT INTO ' . _DB_PREFIX_ . 'payplug_payment_cart (id_payment, id_cart) 
                VALUES (\'' . pSQL($pay_id) . '\', ' . (int)$id_cart . ')';
            $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
            if (!$res_payment_cart) {
                return false;
            }
        } else {
            //update
            $req_payment_cart = '
                UPDATE ' . _DB_PREFIX_ . 'payplug_payment_cart ppc  
                SET ppc.id_payment = \'' . pSQL($pay_id) . '\'
                WHERE ppc.id_cart = ' . (int)$id_cart;
            $res_payment_cart = Db::getInstance()->execute($req_payment_cart);
            if (!$res_payment_cart) {
                return false;
            }
        }

        return true;
    }

    /**
     * @description submit password
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
                $can_create_installment_plan = $permissions['can_create_installment_plan'];
            }
        } else {
            die(json_encode(['content' => 'wrong_pwd']));
        }
        if (!$use_live_mode) {
            die(json_encode(['content' => 'activate']));
        } elseif ($can_save_cards && $can_create_installment_plan) {
            die(json_encode(['content' => 'live_ok']));
        } elseif ($can_save_cards && !$can_create_installment_plan) {
            die(json_encode(['content' => 'live_ok_no_inst']));
        } elseif (!$can_save_cards && $can_create_installment_plan) {
            die(json_encode(['content' => 'live_ok_no_oneclick']));
        } else {
            die(json_encode(['content' => 'live_ok_not_premium']));
        }
    }

    /**
     * @description Read API response and return permissions
     *
     * @param string $json_answer
     * @return array OR bool
     */
    private function treatAccountResponse($json_answer, $is_sandbox = true)
    {
        if ((isset($json_answer['object']) && $json_answer['object'] == 'error')
            || empty($json_answer)
        ) {
            return false;
        }

        $id = $json_answer['id'];

        $configuration = [
            'currencies' => Configuration::get('PAYPLUG_CURRENCIES'),
            'min_amounts' => Configuration::get('PAYPLUG_MIN_AMOUNTS'),
            'max_amounts' => Configuration::get('PAYPLUG_MAX_AMOUNTS'),
            'oney_allowed_countries' => Configuration::get('PAYPLUG_ONEY_ALLOWED_COUNTRIES'),
            'oney_max_amounts' => Configuration::get('PAYPLUG_ONEY_MAX_AMOUNTS'),
            'oney_min_amounts' => Configuration::get('PAYPLUG_ONEY_MIN_AMOUNTS'),
        ];

        if (isset($json_answer['configuration'])) {

            if (isset($json_answer['configuration']['currencies'])
                && !empty($json_answer['configuration']['currencies'])) {
                $configuration['currencies'] = [];
                foreach ($json_answer['configuration']['currencies'] as $value) {
                    $configuration['currencies'][] = $value;
                }
            }

            if (isset($json_answer['configuration']['min_amounts'])
                && !empty($json_answer['configuration']['min_amounts'])) {
                $configuration['min_amounts'] = '';
                foreach ($json_answer['configuration']['min_amounts'] as $key => $value) {
                    $configuration['min_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['min_amounts'] = Tools::substr($configuration['min_amounts'], 0, -1);
            }

            if (isset($json_answer['configuration']['max_amounts'])
                && !empty($json_answer['configuration']['max_amounts'])) {
                $configuration['max_amounts'] = '';
                foreach ($json_answer['configuration']['max_amounts'] as $key => $value) {
                    $configuration['max_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['max_amounts'] = Tools::substr($configuration['max_amounts'], 0, -1);
            }

            if (isset($json_answer['configuration']['oney'])) {
                if (isset($json_answer['configuration']['oney']['allowed_countries'])
                    && !empty($json_answer['configuration']['oney']['allowed_countries'])
                    && sizeof($json_answer['configuration']['oney']['allowed_countries'])
                ) {
                    $allowed = '';
                    foreach ($json_answer['configuration']['oney']['allowed_countries'] as $country) {
                        $allowed .= $country . ',';
                    }
                    $configuration['oney_allowed_countries'] = Tools::substr($allowed, 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['min_amounts'])
                    && !empty($json_answer['configuration']['oney']['min_amounts'])
                ) {
                    $configuration['oney_min_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['min_amounts'] as $key => $value) {
                        $configuration['oney_min_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_min_amounts'] = Tools::substr($configuration['oney_min_amounts'], 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['max_amounts'])
                    && !empty($json_answer['configuration']['oney']['max_amounts'])
                ) {
                    $configuration['oney_max_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['max_amounts'] as $key => $value) {
                        $configuration['oney_max_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_max_amounts'] = Tools::substr($configuration['oney_max_amounts'], 0, -1);
                }
            }
        }

        $permissions = [
            'use_live_mode' => $json_answer['permissions']['use_live_mode'],
            'can_save_cards' => $json_answer['permissions']['can_save_cards'],
            'can_create_installment_plan' => $json_answer['permissions']['can_create_installment_plan'],
            'can_create_deferred_payment' => $json_answer['permissions']['can_create_deferred_payment'],
            'can_use_oney' => $json_answer['permissions']['can_use_oney'],
        ];

        // If sandbox mode active, no allowed countries sent
        // Then set default as `FR,MQ,YT,RE,GF,GP,IT`
        if (isset($json_answer['is_live']) && !$json_answer['is_live']) {
            $configuration['oney_allowed_countries'] = 'FR,MQ,YT,RE,GF,GP,IT';
        }

        Configuration::updateValue('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : ''), $id);
        Configuration::updateValue('PAYPLUG_CURRENCIES', implode(';', $configuration['currencies']));
        Configuration::updateValue('PAYPLUG_MIN_AMOUNTS', $configuration['min_amounts']);
        Configuration::updateValue('PAYPLUG_MAX_AMOUNTS', $configuration['max_amounts']);
        Configuration::updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', $configuration['oney_allowed_countries']);
        Configuration::updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', $configuration['oney_max_amounts']);
        Configuration::updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', $configuration['oney_min_amounts']);

        return $permissions;
    }

    /**
     * @description Uninstall plugin
     *
     * @return bool
     * @throws Exception
     * @see Module::uninstall()
     */
    public function uninstall()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Starting to uninstall.');

        $keep_cards = (bool)Configuration::get('PAYPLUG_KEEP_CARDS');
        if (!$keep_cards) {
            $log->info('Saved cards will be deleted.');
            if (!$this->uninstallCards()) {
                $log->error('Unable to delete saved cards.');
            } else {
                $log->info('Saved cards successfully deleted.');
            }
        } else {
            $log->info('Cards will be kept.');
        }

        if (!parent::uninstall()) {
            $log->error('Uninstall failed: parent.');
        } elseif (!$this->deleteConfig()) {
            $log->error('Uninstall failed: configuration.');
        } elseif (!(new PayPlug\src\repositories\SQLtableRepository())->uninstallSQL($keep_cards)) {
            $log->error('Uninstall failed: sql.');
        } elseif (!$this->uninstallTab()) {
            $log->error('Uninstall failed: tab.');
        } elseif (!$this->oney->uninstallOney()) {
            $log->error('Uninstall failed: Oney.');
        } else {
            $log->info('Uninstall succeeded.');
            return true;
        }
        return false;
    }

    /**
     * @description Delete saved cards when uninstalling module
     *
     * @return bool
     * @throws Exception
     */
    private function uninstallCards()
    {
        $test_api_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        $live_api_key = Configuration::get('PAYPLUG_LIVE_API_KEY');

        $req_all_cards = new DbQuery();
        $req_all_cards->select('pc.*');
        $req_all_cards->from('payplug_card', 'pc');
        $res_all_cards = Db::getInstance()->executeS($req_all_cards);

        if (!empty($res_all_cards)) {
            foreach ($res_all_cards as $card) {
                $id_customer = $card['id_customer'];
                $id_payplug_card = $card['id_payplug_card'];
                $api_key = $card['is_sandbox'] == 1 ? $test_api_key : $live_api_key;
                if (!$this->card->deleteCard($id_customer, $id_payplug_card, $api_key)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * @description Uninstall module installment tab
     *
     * @param $tabClass
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallModuleTab($tabClass)
    {
        //$tabRepository = $this->get('prestashop.core.admin.tab.repository');
        //$idTab = $tabRepository->findOneIdByClassName($tabClass);
        //deprecated but without any retro-compatibility solution... thx Prestashop
        $idTab = Tab::getIdFromClassName($tabClass);
        if ($idTab != 0) {
            $tab = new Tab($idTab);
            $tab->delete();
            return true;
        }
        return false;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallTab()
    {
        if ((class_exists($this->PrestashopSpecificClass))
            && (method_exists($this->PrestashopSpecificObject, 'uninstallTab'))) {
            return $this->PrestashopSpecificObject->uninstallTab();
        }
        return ($this->uninstallModuleTab('AdminPayPlug')
            && $this->uninstallModuleTab('AdminPayPlugInstallment'));
    }

    /**
     * @param $installment
     * @return bool
     * @throws ConfigurationNotSetException
     *
     */
    public function updatePayplugInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }
        if (isset($installment->schedule)) {
            $step_count = count($installment->schedule);
            $index = 0;
            foreach ($installment->schedule as $schedule) {
                $index++;
                $pay_id = '';
                if (count($schedule->payment_ids) > 0) {
                    $pay_id = $schedule->payment_ids[0];
                    $payment = \Payplug\Payment::retrieve($pay_id);
                    $status = $this->getPaymentStatusByPayment($payment);
                } else {
                    if ((int)$installment->is_active == 1) {
                        $status = 6; //ongoing
                    } else {
                        $status = 7; //cancelled
                    }
                }
                $step = $index . '/' . $step_count;
                if ($step2update = $this->getStoredInstallmentTransaction($installment, $step)) {
                    $req_insert_installment = '
                        UPDATE `' . _DB_PREFIX_ . 'payplug_installment` 
                        SET `id_payment` = \'' . pSQL($pay_id) . '\', 
                        `status` = \'' . (int)$status . '\' 
                        WHERE `id_payplug_installment` = ' . (int)$step2update['id_payplug_installment'];
                    $res_insert_installment = DB::getInstance()->Execute($req_insert_installment);

                    if (!$res_insert_installment) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }
    }

    //____________________________________________
    // PS 1.6
    //____________________________________________

    /**
     * @param array $params
     * @return string
     * @throws Exception
     * @see Module::hookPayment()
     *
     */
    public function hookPayment($params)
    {
        if (!$this->isAllowed()) {
            return false;
        }

        $use_taxes = $this->getConfiguration('PS_TAX');
        $base_total_tax_inc = $params['cart']->getOrderTotal(true);
        $base_total_tax_exc = $params['cart']->getOrderTotal(false);

        if ($use_taxes) {
            $price2display = $base_total_tax_inc;
        } else {
            $price2display = $base_total_tax_exc;
        }

        $cart = $params['cart'];

        if ($this->getConfiguration('PAYPLUG_ONEY_OPTIMIZED')) {
            $this->oney->assignOneyPaymentOptions($cart);
        }

        $payment_options = $this->getPaymentOptions($cart);

        // Transforme tableau en TPL
        $paymentOptions = $this->PrestashopSpecificObject->displayPaymentOption(
            $payment_options,
            $cart
        );

        foreach ($paymentOptions as $paymentOption) {
            $find = 'oney';
            if (strstr($paymentOption['tpl'], $find)) {
                $this->oneyLogoUrl = $paymentOption['logo_url'];
            }
        }

        $this->smarty->assign([
            'payplug_payment_options' => $paymentOptions,
            'spinner_url' => PayplugBackward::getHttpHost(true) .
                __PS_BASE_URI__ . 'modules/payplug/views/img/admin/spinner.gif',
            'front_ajax_url' => PayplugBackward::getModuleLink($this->name, 'ajax', [], true),
            'api_url' => $this->plugin->getApiUrl(),
            'price2display' => $price2display,
            'this_path' => $this->_path,
        ]);

        return $this->display(__FILE__, 'checkout/payment/display.tpl');
    }

    /**
     * @param $cart
     * @return bool
     */
    public function assignPaymentOptions($cart)
    {
        $one_click = $this->getConfiguration('PAYPLUG_ONE_CLICK');
        $installment = $this->getConfiguration('PAYPLUG_INST');
        $installment_mode = $this->getConfiguration('PAYPLUG_INST_MODE');
        $installment_min_amount = $this->getConfiguration('PAYPLUG_INST_MIN_AMOUNT');

        if (!$this->checkCurrency($cart) ||
            !$this->checkAmount($cart)) {
            return false;
        }

        $path_ssl = Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/';

        $payplug_card = $this->card;

        $payplug_cards = $payplug_card->getByCustomer($cart->id_customer, true);

        $use_taxes = $this->getConfiguration('PS_TAX');
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

        $front_ajax_url = PayplugBackward::getModuleLink($this->name, 'ajax', [], true);

        $this->smarty->assign([
            'front_ajax_url' => $front_ajax_url,
            'api_url' => $this->plugin->getApiUrl(),
        ]);

        if (!empty($payplug_cards) && $one_click == 1) {
            $this->smarty->assign([
                'payplug_cards' => $payplug_cards,
                'payplug_one_click' => 1,
            ]);
        }

        $payment_url = 'index.php?controller=order&step=3';

        $payment_controller_url = PayplugBackward::getModuleLink($this->name, 'payment', [], true);
        $installment_controller_url = PayplugBackward::getModuleLink($this->name, 'payment', ['i' => 1], true);
        $current_lang = explode('-', $this->context->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, ['it', 'en'], true)) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        $this->smarty->assign([
            'spinner_url' => PayplugBackward::getHttpHost(true)
                . __PS_BASE_URI__ . 'modules/payplug/views/img/admin/spinner.gif',
            'payment_url' => $payment_url,
            'payment_controller_url' => $payment_controller_url,
            'installment_controller_url' => $installment_controller_url,
            'img_lang' => $img_lang,
            'payplug_installment' => $installment,
            'installment_mode' => $installment_mode,
        ]);
    }

    /**
     * Install the required hooks
     * @return array
     */
    protected function installHook()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');

        $hooksToRegister = [
            'adminOrder',
            'customerAccount',
            'header',
            'paymentReturn',
            'actionAdminPerformanceControllerAfter',
            'moduleRoutes'
        ];

        $install = [
            'flag' => true,
            'error' => false
        ];

        foreach ($hooksToRegister as $hookToRegister) {
            $install['flag'] = true;
            $log->info('Try to install Hook ' . $hookToRegister . '.');
            if (!$this->registerHook($hookToRegister)) {
                $log->error('Install failed: Hook ' . $hookToRegister . '.');
                $install['flag'] = false;
                $install['error'] = $hookToRegister;
                return $install;
            } else {
                $log->info('Install success: Hook ' . $hookToRegister . '.');
                $install['flag'] = true;
            }
        }
        return $install;
    }

    public function getConfiguration($key)
    {
        if (isset($this->_conf[$key])) {
            return $this->_conf[$key]['value'];
        } else {
            return Configuration::get($key);
        }
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
            !$this->getConfiguration('PAYPLUG_SHOW') ||
            !$this->checkCurrency($cart) ||
            !$this->checkAmount($cart)) {
            return $options;
        }

        // check if installment allowed
        $installment = $this->getConfiguration('PAYPLUG_INST');
        $installment_min_amount = $this->getConfiguration('PAYPLUG_INST_MIN_AMOUNT');
        $order_total = $cart->getOrderTotal(true);
        $installment = $installment && $order_total >= $installment_min_amount;

        // check if one click allowed
        $one_click = $this->getConfiguration('PAYPLUG_ONE_CLICK');
        $payplug_card = $this->card;
        $payplug_cards = $payplug_card->getByCustomer($cart->id_customer, true);
        $one_click = (bool)($one_click && !empty($payplug_cards));

        // check if oney is allowed
        $oney = $this->getConfiguration('PAYPLUG_ONEY') && $this->checkVersion();

        $options = [
            'standard' => true,
            'oneclick' => $one_click,
            'installment' => $installment,
            'oney' => $oney,
        ];

        return $options;
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
     * Check Prestashop version for new feature
     * @param string $min
     * @return bool
     */
    public function checkVersion($min = '1.6')
    {
        return (bool)version_compare(_PS_VERSION_, $min, '>=');
    }

    /**
     * Display Oney CTA on Shopping cart page
     *
     * @param array $params
     * @return bool|mixedf
     */
    public function hookDisplayBeforeShoppingCartBlock($params)
    {
        if (!$this->oney->isOneyAllowed()) {
            return false;
        }

        $amount = $params['cart']->getOrderTotal(true, Cart::BOTH);
        $is_valid_amount = $this->oney->isValidOneyAmount($amount, $params['cart']->id_currency);

        $this->smarty->assign([
            'payplug_oney_amount' => $amount,
            'payplug_oney_allowed' => $is_valid_amount['result'],
            'payplug_oney_error' => $is_valid_amount['error'],
        ]);

        return $this->oney->getOneyCTA('checkout');
    }
}
