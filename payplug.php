<?php
/**
 * 2013 - 2019 PayPlug SAS
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
 * @copyright 2013 - 2019 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

/**
 * Core file of PayPlug module
 */

use libphonenumber\PhoneNumberUtil;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use Symfony\Component\VarDumper\VarDumper;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once(_PS_MODULE_DIR_ . 'payplug/classes/MyLogPHP.class.php');
require_once(_PS_MODULE_DIR_ . 'payplug/lib/init.php');
require_once(_PS_MODULE_DIR_ . 'payplug/classes/PPPayment.php');
require_once(_PS_MODULE_DIR_ . 'payplug/classes/PPPaymentInstallment.php');

class Payplug extends PaymentModule
{
    const PAYPLUG_PROD_API_URL = 'https://api.payplug.com';
    const PAYPLUG_PROD_SITE_URL = 'https://www.payplug.com';
    const INST_MIN_AMOUNT = 4;

    /** @var string */
    private $api_live;

    /** @var string */
    private $api_test;

    /** @var string */
    private $api_url;

    /** @var string */
    public $api_version;

    /** @var array */
    public $check_configuration = array();

    /** @var string */
    public $current_api_key;

    /** @var string */
    private $email;

    /** @var array */
    public $errors = array();

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
    public $payment_status = array();

    /** @var array */
    private $routes = array(
        'login' => '/v1/keys',
        'account' => '/v1/account',
        'patch' => '/v1/payments'
    );

    /** @var string */
    public $site_url;

    /** @var bool */
    private $ssl_enable;

    /** @var array */
    public $validationErrors = array();

    /**
     * Constructor
     *
     * @return void
     * @throws Exception
     */
    public function __construct()
    {
        $this->setPrimaryModuleProperties();
        $this->setLoggers();
        parent::__construct();
        $this->setEnvironment();
        $this->setConfigurationProperties();
        $this->setSecretKey();
        $this->setUserAgent();
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
     * @throws \Payplug\Exception\ConfigurationNotSetException
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
                    `id_installment`, `id_payment`, `id_order`, `id_customer`, 
                    `order_total`, `step`, `amount`, `status`, `scheduled_date`
                ) VALUES (
                    \'' . $installment->id . '\', \'' . $pay_id . '\', \'' . $order->id . '\', \'' . $order->id_customer . '\', 
                    \'' . (int)(($order->total_paid * 1000) / 10) . '\', \'' . $step . '\', \'' . $amount . '\', \'' . $status . '\', \'' . $date . '\'
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
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws \Payplug\Exception\ConfigurationNotSetException
     */
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
                die(true);
            }
            if ((int)Tools::getValue('popin') == 1) {
                $args = null;
                if (Tools::getValue('type') == 'confirm') {
                    $sandbox = (int)Tools::getValue('sandbox');
                    $embedded = (int)Tools::getValue('embedded');
                    $one_click = (int)Tools::getValue('one_click');
                    $installment = (int)Tools::getValue('installment');
                    $deferred = (int)Tools::getValue('deferred');
                    $activate = (int)Tools::getValue('activate');
                    $args = array(
                        'sandbox' => $sandbox,
                        'embedded' => $embedded,
                        'one_click' => $one_click,
                        'installment' => $installment,
                        'deferred' => $deferred,
                        'activate' => $activate,
                    );
                }
                $this->displayPopin(Tools::getValue('type'), $args);
            }
            if (Tools::getValue('submit') == 'submitPopin_pwd') {
                $this->submitPopinPwd($_POST['pwd']);
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
            if (Tools::getValue('submit') == 'submitPopin_abort') {
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
                    die(json_encode(array(
                        'status' => 'error',
                        'data' => $this->l('Cannot abort installment.')
                    )));
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

                    die(json_encode(array('reload' => $reload)));
                }
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
                die(json_encode($this->getAccountPermissions($api_key)));
            }
            if (Tools::getValue('has_live_key')) {
                die(Tools::jsonEncode(['result' => $this->has_live_key()]));
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
                    $amount = (float)($amount / 10); // otherwise, sometimes 17.90 become 17.89 \o/
                    $amount = (int)$amount;
                }

                $id_order = Tools::getValue('id_order');
                $pay_id = Tools::getValue('pay_id');
                $inst_id = Tools::getValue('inst_id');
                $metadata = array(
                    'ID Client' => (int)Tools::getValue('id_customer'),
                    'reason' => 'Refunded with Prestashop'
                );
                $pay_mode = Tools::getValue('pay_mode');
                $refund = $this->makeRefund($pay_id, $amount, $metadata, $pay_mode, $inst_id);
                if ($refund == 'error') {
                    die(json_encode(array(
                        'status' => 'error',
                        'data' => $this->l('Cannot refund that amount.')
                    )));
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
                    die(json_encode(array(
                        'status' => 'ok',
                        'data' => $data,
                        'message' => $this->l('Amount successfully refunded.'),
                        'reload' => $reload
                    )));
                }
            }
            if ((int)Tools::getValue('capture') == 1) {
                $pay_id = Tools::getValue('pay_id');
                $id_order = Tools::getValue('id_order');
                $payment = new PPPayment($pay_id);
                $capture = $payment->capture();
                $payment->refresh();
                if ($payment->resource->card->id !== null) {
                    $this->saveCard($payment->resource);
                }
                if ($capture['code'] >= 300) {
                    die(Tools::jsonEncode(array(
                        'status' => 'error',
                        'data' => $this->l('Cannot capture this payment.'),
                        'message' => $capture['message'],
                    )));
                } else {
                    $state_addons = ($payment->resource->is_live ? '' : '_TEST');
                    $new_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PAID'.$state_addons);

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

                    die(Tools::jsonEncode(array(
                        'status' => 'ok',
                        'data' => '',
                        'message' => $this->l('Payment successfully captured.'),
                        'reload' => true,
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

                die(json_encode(array(
                    'message' => $this->l('Order successfully updated.'),
                    'reload' => true
                )));
            }
        } else {
            exit;
        }
    }

    /**
     * @param $payment
     * @return array|Exception
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
            case 4: // partially refunded
            case 5: // refunded
            case 8: // authorized
                $status_class = 'pp_warning';
                break;
            case 2: // paid
            case 6: // on going
                $status_class = 'pp_success';
                break;
            case 3: // failed
            case 7: // cancelled
            case 9: // authorization expired
                $status_class = 'pp_error';
                break;
            default:
                $status_class = 'pp_neutral';
                break;
        }
        $pay_status = $this->payment_status[(int)$pay_status];

        $pay_brand = $this->getCardBrandByPayment($payment);
        if ($payment->card->country != '') {
            $pay_brand .= ' '.$this->l('Card').' ('.$payment->card->country.')';
        }

        $payment_details = array(
            'id' => $payment->id,
            'status' => $pay_status,
            'status_class' => $status_class,
            'amount' => (int)$payment->amount / 100,
            'card_brand' => $pay_brand,
            'card_mask' => $this->getCardMaskByPayment($payment),
            'card_date' => $this->getCardExpiryDateByPayment($payment),
            'mode' => $payment->is_live ? $this->l('LIVE') : $this->l('TEST'),
        );

        //Deferred payment does'nt display 3DS option before capture so we have to consider it null
        if ($payment->is_3ds !== null) {
            $payment_details['tds'] = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
        }

        $is_expired = false;
        if ($payment->authorization !== null) {
            $payment_details['authorization'] = true;
            if ($payment->is_paid) {
                $payment_details['date'] = date('d/m/Y', (int)$payment->paid_at);
                $payment_details['can_be_captured'] = false;
                $payment_details['status_message'] = $this->l('(deferred)');
            } else {
                $expiration = date('d/m/Y', (int)$payment->authorization->expires_at);
                if ($payment->authorization->expires_at - time() > 0) {
                    $payment_details['can_be_captured'] = true;
                    $payment_details['status_message'] = sprintf($this->l('(capture authorized before %s)'), $expiration);
                    $payment_details['date'] = date('d/m/Y', (int)$payment->authorization->authorized_at);
                    $payment_details['date_expiration'] = $expiration;
                } else {
                    $is_expired = true;
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                    $payment_details['can_be_captured'] = false;
                }
            }
        } else {
            $payment_details['authorization'] = false;
            $payment_details['date'] = date('d/m/Y', (int)$payment->created_at);
            $payment_details['can_be_captured'] = false;
        }

        if (isset($payment->failure) && isset($payment->failure->message) && !$is_expired) {
            $payment_details['error'] = $this->l('('.$payment->failure->message.')');
        }
        return $payment_details;
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

        $errors = array();
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
                        $errors[$error_key] = $this->l('The transaction was not completed and your card was not charged.');
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

        $check_warning = $this->l('Unfortunately at least one issue is preventing you from using Payplug.') . ' '
            . $this->l('Refresh the page or click "Check" once they are fixed');
        if ($is_payplug_configured) {
        } else {
            Configuration::get('PAYPLUG_SHOW', 0);
            $this->check_configuration['warning'][] .= $check_warning;
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
     * @return array
     */
    private function checkRequirements()
    {
        $php_min_version = 50300;
        $curl_min_version = '7.21';
        $openssl_min_version = 0x1000100f;
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
            $report['curl']['up2date'] = version_compare($curl_version['version'], $curl_min_version,
                '>=') ? true : false;
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
     * Create basic configuration
     *
     * @return bool
     */
    private function createConfig()
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
     * Create an order state according to properties
     *
     * @param array $properties
     * @return bool
     */
    private function createOrderState($properties)
    {
        return true;
    }

    /**
     * Create usual status
     *
     * @return bool
     * @throws Exception
     */
    public function createOrderStates()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $this->log_install->info('Order state creation starting.');
        $state_key = array(
            'paid' => array(
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
            'refund' => array(
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
            'pending' => array(
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
            'error' => array(
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
            'auth' => array(
                'cfg' => null,
                'template' => null,
                'logable' => true,
                'send_email' => false,
                'paid' => true,
                'module_name' => 'payplug',
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#04b404',
                'name' => array(
                    'en' => 'Payment authorised',
                    'fr' => 'Paiement autorisé',
                    'es' => 'Pago',
                    'it' => 'Pagamento',
                ),
            ),
            'exp' => array(
                'cfg' => null,
                'template' => null,
                'logable' => true,
                'send_email' => false,
                'paid' => false,
                'module_name' => 'payplug',
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#8f0621',
                'name' => array(
                    'en' => 'Authorization expired',
                    'es' => 'Autorización vencida',
                    'fr' => 'Autorisation expirée',
                    'it' => 'Autorizzazione scaduta',
                ),
            ),
        );

        foreach ($state_key as $key => $values) {
            $key_config = 'PAYPLUG_ORDER_STATE_' . Tools::strtoupper($key);
            $key_config_test = 'PAYPLUG_ORDER_STATE_' . Tools::strtoupper($key . '_test');
            $os = 0;
            $os_test = 0;

            $log->info('Order state: ' . $key);

            //LIVE
            $log->info('Live context.');
            if ((int)Configuration::get($key_config) != 0) {
                $os = (int)Configuration::get($key_config);
            } elseif ($val = $this->findOrderState($values['name'], false)) {
                $os = $val;
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
                        $order_state->name[$lang['id_lang']] = $values['name']['en'] . ' [PayPlug]';
                    } elseif ($lang['iso_code'] == 'fr') {
                        $order_state->name[$lang['id_lang']] = $values['name']['fr'] . ' [PayPlug]';
                    } elseif ($lang['iso_code'] == 'es') {
                        $order_state->name[$lang['id_lang']] = $values['name']['es'] . ' [PayPlug]';
                    } elseif ($lang['iso_code'] == 'it') {
                        $order_state->name[$lang['id_lang']] = $values['name']['it'] . ' [PayPlug]';
                    } else {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'] . ' [PayPlug]';
                    }
                }
                if ($order_state->add()) {
                    $source = _PS_MODULE_DIR_ . $this->name . '/views/img/os/' . $key . '.gif';
                    $destination = _PS_ROOT_DIR_ . '/img/os/' . (int)$order_state->id . '.gif';
                    @copy($source, $destination);
                }
                $os = (int)$order_state->id;
                $log->info('ID: ' . $os);
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
                        $order_state->name[$lang['id_lang']] = $values['name']['en'] . ' [TEST]';
                    } elseif ($lang['iso_code'] == 'fr') {
                        $order_state->name[$lang['id_lang']] = $values['name']['fr'] . ' [TEST]';
                    } elseif ($lang['iso_code'] == 'es') {
                        $order_state->name[$lang['id_lang']] = $values['name']['es'] . ' [TEST]';
                    } elseif ($lang['iso_code'] == 'it') {
                        $order_state->name[$lang['id_lang']] = $values['name']['it'] . ' [TEST]';
                    } else {
                        $order_state->name[$lang['id_lang']] = $values['name']['en'] . ' [TEST]';
                    }
                }
                if ($order_state->add()) {
                    $source = _PS_MODULE_DIR_ . $this->name . '/views/img/os/' . $key . '.gif';
                    $destination = _PS_ROOT_DIR_ . '/img/os/' . (int)$order_state->id . '.gif';
                    @copy($source, $destination);
                }
                $os_test = (int)$order_state->id;
                $log->info('ID: ' . $os);
            }
            Configuration::updateValue($key_config_test, (int)$os_test);
        }
        $log->info('Order state creation ended.');
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
        $url = $this->api_url . '/v1/cards/' . $id_card;
        $curl_version = curl_version();

        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
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
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__) . '/cacert.pem')); //work only wiht cURL 7.10+
        $error_curl = curl_errno($process);

        curl_close($process);

        // if no error
        if ($error_curl == 0) {
            $req_payplug_card = '
            DELETE FROM ' . _DB_PREFIX_ . 'payplug_card
            WHERE ' . _DB_PREFIX_ . 'payplug_card.id_card = \'' . pSQL($id_card) . '\'';
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
        if (isset($cardsToDelete) && !empty($cardsToDelete) && sizeof($cardsToDelete)) {
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
     * Delete basic configuration
     *
     * @return bool
     */
    private function deleteConfig()
    {
        return (Configuration::deleteByName('PAYPLUG_ALLOW_SAVE_CARD')
            && Configuration::deleteByName('PAYPLUG_COMPANY_ID')
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
        $this->context->smarty->assign(array('id_module' => $this->id));
        return $this->display(__FILE__, 'gdpr_consent.tpl');
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
                'installment' => $args['installment'],
                'deferred' => $args['deferred'],
                'activate' => $args['activate'],
            ));
        }

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $inst_id = $args['inst_id'];

        $this->context->smarty->assign(array(
            'type' => $type,
            'admin_ajax_url' => $admin_ajax_url,
            'site_url' => $this->site_url,
            'inst_id' => $inst_id,
        ));
        $this->html = $this->fetchTemplateRC('/views/templates/admin/popin.tpl');

        die(json_encode(array('content' => $this->html)));
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
            $req_order_state->where('osl.name LIKE \'' . pSQL($name['en'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
				OR osl.name LIKE \'' . pSQL($name['fr'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
				OR osl.name LIKE \'' . pSQL($name['es'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
				OR osl.name LIKE \'' . pSQL($name['it'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\'');
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
            $phone_util = libphonenumber\PhoneNumberUtil::getInstance();
            $parsed = $phone_util->parse($phone_number, $iso_code);

            if (!$phone_util->isValidNumber($parsed)) {
                // todo: add log
                return null;
            }

            $formated = $phone_util->format($parsed, \libphonenumber\PhoneNumberFormat::E164);
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
                    $this->l('Card') => '**** **** **** ' . $card['last4'],
                    $this->l('Expiry date') => $card['expiry_date']
                );
                $i++;
            }
        }
        return $cards;
    }

    /**
     * Get account permission from Payplug API
     *
     * @param string $api_key
     * @return array OR bool
     */
    public function getAccount($api_key)
    {
        $url = $this->api_url . $this->routes['account'];
        $curl_version = curl_version();
        $process = curl_init($url);
        curl_setopt($process, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $api_key));
        curl_setopt($process, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($process, CURLINFO_HEADER_OUT, true);
        curl_setopt($process, CURLOPT_SSL_VERIFYPEER, true);
        # >= 7.26 to 7.28.1 add a notice message for value 1 will be remove
        curl_setopt(
            $process,
            CURLOPT_SSL_VERIFYHOST,
            (version_compare($curl_version['version'], '7.21', '<') ? true : 2)
        );
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__) . '/cacert.pem')); //work only wiht cURL 7.10+
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
        $permissions = $this->getAccount($api_key);
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
                . '&tab_module=payments_gateways&module_name=payplug&token=' . Tools::getAdminTokenLite($controller_name);
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
     * @param $cart
     * @return array
     */
    public function getAvailableOptions($cart)
    {
        $permissions = $this->getAccountPermissions();
        $inst_min_amount = (float)str_replace(',', '.', Configuration::get('PAYPLUG_INST_MIN_AMOUNT'));

        $available_options = array(
            'standard' => true,
            'live' => (int)Configuration::get('PAYPLUG_SANDBOX_MODE') === 0 ? true : false,
            'embedded' => (int)Configuration::get('PAYPLUG_EMBEDDED_MODE') === 1 ? true : false,
            'one_click' => (int)Configuration::get('PAYPLUG_ONE_CLICK') === 1 ? true : false,
            'installment' => (int)Configuration::get('PAYPLUG_INST') === 1 ? true : false,
            'deferred' => (int)Configuration::get('PAYPLUG_DEFERRED') === 1 ? true : false,
        );

        if (!$this->active
            || Configuration::get('PAYPLUG_EMAIL') === null
            || (int)Configuration::get('PAYPLUG_SHOW') === 0
            || !$this->checkCurrency($cart)
            || !$this->checkAmount($cart)
        ) {
            $available_options['standard'] = false;
            $available_options['sandbox'] = false;
            $available_options['embedded'] = false;
            $available_options['one_click'] = false;
            $available_options['installment'] = false;
            $available_options['deferred'] = false;
        } else {
            if (!$permissions['use_live_mode']
                || Configuration::get('PAYPLUG_LIVE_API_KEY') === null
            ) {
                $available_options['live'] = false;
            }
            if (!$permissions['can_save_cards']) {
                $available_options['one_click'] = false;
            }
            if (!$permissions['can_create_installment_plan']
                || $cart->getOrderTotal(true, Cart::BOTH) < $inst_min_amount
            ) {
                $available_options['installment'] = false;
            }
            if (!$permissions['can_create_deferred_payment']) {
                $available_options['deferred'] = false;
            }
        }

        return $available_options;
    }


    /**
     * @param $payment
     * @return Exception|string
     */
    public function getCardBrandByPayment($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                return $exception;
            }
        }

        if ($payment->card->brand != '') {
            $brand = $payment->card->brand;
        } else {
            $brand = $this->l('Unavailable');
        }
        return $brand;
    }

    /**
     * @param $payment
     * @return Exception|false|string
     */
    public function getCardExpiryDateByPayment($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                return $exception;
            }
        }

        if ($payment->card->exp_month === null) {
            $card_expiry_date = $this->l('Unavailable');
        } else {
            $card_expiry_date = date('m/y', strtotime('01.'.$payment->card->exp_month.'.'.$payment->card->exp_year));
        }
        return $card_expiry_date;
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
        $req_card_id->where('pc.id_customer = ' . (int)$id_customer);
        $req_card_id->where('pc.id_payplug_card = ' . (int)$id_payplug_card);
        $req_card_id->where('pc.id_company = ' . (int)$id_company);
        $req_card_id->where('pc.is_sandbox = ' . (int)$is_sandbox);
        $res_card_id = Db::getInstance()->getValue($req_card_id);

        if (!$res_card_id) {
            return false;
        } else {
            return $res_card_id;
        }
    }

    /**
     * @param $payment
     * @return Exception|string
     */
    public function getCardMaskByPayment($payment)
    {
        if (!is_object($payment)) {
            try {
                $payment = \Payplug\Payment::retrieve($payment);
            } catch (Exception $exception) {
                return $exception;
            }
        }

        if ($payment->card->last4 != '') {
            $card_mask = '**** **** **** '.$payment->card->last4;
        } else {
            $card_mask = $this->l('Unavailable');
        }
        return $card_mask;
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
        $req_payplug_card->where('pc.id_customer = ' . (int)$id_customer);
        $req_payplug_card->where('pc.id_company = ' . (int)Configuration::get('PAYPLUG_COMPANY_ID'));
        $req_payplug_card->where('pc.is_sandbox = ' . (int)$is_sandbox);
        $res_payplug_card = Db::getInstance()->executeS($req_payplug_card);

        if (!$res_payplug_card) {
            return [];
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

    /**
     * @return string
     * @see Module::getContent()
     *
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
        $PAYPLUG_INST = Configuration::get('PAYPLUG_INST');
        $PAYPLUG_INST_MODE = Configuration::get('PAYPLUG_INST_MODE');
        $PAYPLUG_INST_MIN_AMOUNT = Configuration::get('PAYPLUG_INST_MIN_AMOUNT');
        $PAYPLUG_DEFERRED = Configuration::get('PAYPLUG_DEFERRED');
        $PAYPLUG_DEFERRED_AUTO = Configuration::get('PAYPLUG_DEFERRED_AUTO');
        $PAYPLUG_DEFERRED_STATE = Configuration::get('PAYPLUG_DEFERRED_STATE');

        if (!empty($PAYPLUG_EMAIL) && (!empty($PAYPLUG_TEST_API_KEY) || !empty($PAYPLUG_LIVE_API_KEY))) {
            $connected = true;
        } else {
            $connected = false;
        }

        if (count($this->validationErrors) && !$connected) {
            $this->context->smarty->assign(array(
                'validationErrors' => $this->validationErrors,
            ));
        }

        $valid_key = self::setAPIKey();
        if (!empty($valid_key)) {
            $permissions = $this->getAccount($valid_key);
            $premium = $permissions['can_save_cards'] && $permissions['can_create_installment_plan'];
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

        $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin.js');
        $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin.css');

        $admin_ajax_url = $this->getAdminAjaxUrl();

        $login_infos = array();

        $installments_panel_url = 'index.php?controller=AdminPayPlugInstallment&token=' . Tools::getAdminTokenLite('AdminPayPlugInstallment');

        $switch_sandbox = array(
            'name' => 'payplug_sandbox',
            'label' => $this->l('Mode'),
            'active' => $connected,
            'checked' => $PAYPLUG_SANDBOX_MODE,
            'label_left' => $this->l('test'),
            'label_right' => $this->l('live'),
        );

        $switch_embedded = array(
            'name' => 'payplug_embedded',
            'label' => $this->l('Payment page'),
            'active' => $connected,
            'checked' => $PAYPLUG_EMBEDDED_MODE,
            'label_left' => $this->l('embedded'),
            'label_right' => $this->l('redirected'),
        );

        $switch_one_click = array(
            'name' => 'payplug_one_click',
            'label' => $this->l('Enable one-click payments'),
            'active' => $connected,
            'checked' => $PAYPLUG_ONE_CLICK,
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        );

        $switch_installment_plan = array(
            'name' => 'payplug_inst',
            'label' => $this->l('Enable payments by installments'),
            'active' => $connected,
            'checked' => $PAYPLUG_INST,
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        );

        $switch_deferred_payment = array(
            'name' => 'payplug_deferred',
            'label' => $this->l('Defer the payment capture'),
            'active' => $connected,
            'checked' => $PAYPLUG_DEFERRED,
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        );

        $this->context->smarty->assign(array(
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
            'PAYPLUG_SANDBOX_MODE' => $PAYPLUG_SANDBOX_MODE,
            'PAYPLUG_EMBEDDED_MODE' => $PAYPLUG_EMBEDDED_MODE,
            'PAYPLUG_ONE_CLICK' => $PAYPLUG_ONE_CLICK,
            'PAYPLUG_SHOW' => $PAYPLUG_SHOW,
            'PAYPLUG_DEBUG_MODE' => $PAYPLUG_DEBUG_MODE,
            'PAYPLUG_INST' => $PAYPLUG_INST,
            'PAYPLUG_INST_MODE' => $PAYPLUG_INST_MODE,
            'PAYPLUG_INST_MIN_AMOUNT' => $PAYPLUG_INST_MIN_AMOUNT,
            'PAYPLUG_DEFERRED' => $PAYPLUG_DEFERRED,
            'PAYPLUG_DEFERRED_AUTO' => $PAYPLUG_DEFERRED_AUTO,
            'PAYPLUG_DEFERRED_STATE' => $PAYPLUG_DEFERRED_STATE,
            'login_infos' => $login_infos,
            'iso' => $this->context->language->iso_code,
            'installments_panel_url' => $installments_panel_url,
            'switch_deferred_payment' => $switch_deferred_payment,
            'switch_installment_plan' => $switch_installment_plan,
            'switch_one_click' => $switch_one_click,
            'switch_embedded' => $switch_embedded,
            'switch_sandbox' => $switch_sandbox,
            'order_states' => $this->getOrderStates(),
        ));

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
     * @param $cart_id
     * @param bool $is_deferred
     * @return array
     */
    private function getInstPaymentOptions($cart_id, $is_deferred = false)
    {
        $is_installment = (int)Tools::getValue('inst');
        if ($is_installment == 1) {
            $payment = $this->preparePayment((int)$cart_id, null, true, $is_deferred);
        } else {
            $payment = $this->preparePayment((int)$cart_id, null, false, $is_deferred);
        }
        $payment_url = false;
        if (is_array($payment)) {
            if (!$payment['result']) {
                $this->setError($payment['response']);
            } else {
                $payment_url = $payment['payment_url'];
            }
        } else {
            $payment_data = json_decode($payment);
            if (is_object($payment_data)) {
                $payment_url = $payment_data->payment_url;
            } else {
                $payment_url = $payment;
            }
        }


        $payplug_errors = count($this->errors) ? implode('<br/>', $this->errors) : false;

        return array(
            'payplug_errors' => $payplug_errors,
            'lightbox' => 1,
            'is_installment' => $is_installment,
            'payment_url' => $payment_url,
            'api_url' => $this->api_url,
        );
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
        if (!in_array(Tools::strtoupper($country->iso_code), $iso_code_list)) {
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
        return strtolower($parse[0]);
    }

    /**
     * @return string
     */
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
        $PAYPLUG_INST = Configuration::get('PAYPLUG_INST');
        $PAYPLUG_INST_MODE = Configuration::get('PAYPLUG_INST_MODE');
        $PAYPLUG_INST_MIN_AMOUNT = Configuration::get('PAYPLUG_INST_MIN_AMOUNT');
        $PAYPLUG_DEFERRED = Configuration::get('PAYPLUG_DEFERRED');
        $PAYPLUG_DEFERRED_AUTO = Configuration::get('PAYPLUG_DEFERRED_AUTO');
        $PAYPLUG_DEFERRED_STATE = Configuration::get('PAYPLUG_DEFERRED_STATE');

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
            $premium = $permissions['can_save_cards'] && $permissions['can_create_installment_plan'];
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

        $login_infos = array();

        $installments_panel_url = 'index.php?controller=AdminPayPlugInstallment&token=' . Tools::getAdminTokenLite('AdminPayPlugInstallment');

        $switch_sandbox = array(
            'name' => 'payplug_sandbox',
            'label' => $this->l('Mode'),
            'active' => $connected,
            'checked' => $PAYPLUG_SANDBOX_MODE,
            'label_left' => $this->l('test'),
            'label_right' => $this->l('live'),
        );

        $switch_embedded = array(
            'name' => 'payplug_embedded',
            'label' => $this->l('Payment page'),
            'active' => $connected,
            'checked' => $PAYPLUG_EMBEDDED_MODE,
            'label_left' => $this->l('embedded'),
            'label_right' => $this->l('redirected'),
        );

        $switch_one_click = array(
            'name' => 'payplug_one_click',
            'label' => $this->l('Enable one-click payments'),
            'active' => $connected,
            'checked' => $PAYPLUG_ONE_CLICK,
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        );

        $switch_installment_plan = array(
            'name' => 'payplug_inst',
            'label' => $this->l('Enable payments by installments'),
            'active' => $connected,
            'checked' => $PAYPLUG_INST,
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        );

        $switch_deferred_payment = array(
            'name' => 'payplug_deferred',
            'label' => $this->l('Defer the payment capture'),
            'active' => $connected,
            'checked' => $PAYPLUG_DEFERRED,
            'label_left' => $this->l('yes'),
            'label_right' => $this->l('no'),
        );

        $this->context->smarty->assign(array(
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
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
            'PAYPLUG_INST' => $PAYPLUG_INST,
            'PAYPLUG_INST_MODE' => $PAYPLUG_INST_MODE,
            'PAYPLUG_INST_MIN_AMOUNT' => $PAYPLUG_INST_MIN_AMOUNT,
            'PAYPLUG_DEFERRED' => $PAYPLUG_DEFERRED,
            'PAYPLUG_DEFERRED_AUTO' => $PAYPLUG_DEFERRED_AUTO,
            'PAYPLUG_DEFERRED_STATE' => $PAYPLUG_DEFERRED_STATE,
            'login_infos' => $login_infos,
            'installments_panel_url' => $installments_panel_url,
            'switch_deferred_payment' => $switch_deferred_payment,
            'switch_installment_plan' => $switch_installment_plan,
            'switch_one_click' => $switch_one_click,
            'switch_embedded' => $switch_embedded,
            'switch_sandbox' => $switch_sandbox,
            'order_states' => $this->getOrderStates(),
        ));
        $this->html = $this->fetchTemplateRC('/views/templates/admin/login.tpl');

        return $this->html;
    }

    /**
     * @param null $id_lang
     * @return array
     */
    private function getOrderStates($id_lang = null) {
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
            return array('id' => $inst_id, 'type' => 'installment');
        }

        $pay_id = $this->getPaymentByCart($cart->id);
        if ($pay_id) {
            return array('id' => $pay_id, 'type' => 'payment');
        }

        return false;
    }

    /**
     * Get the valid payment options from payplug configuration
     *
     * @param $cart
     * @return array
     */
    private function getPaymentOptions($cart)
    {
        $options = $this->getAvailableOptions($cart);

        $payplug_cards = $options['one_click'] ? $this->getCardsByCustomer((int)$cart->id_customer, true) : [];
        $payment_list = [];

        // OneClick Payment
        if ($options['one_click'] && !empty($payplug_cards)) {
            foreach ($payplug_cards as $card) {
                $paymentOption = new PaymentOption();
                $brand = $card['brand'] != 'none' ? Tools::ucfirst($card['brand']) : $this->l('Card');
                $input_options = array(
                    'pc' => array(
                        'name' => 'pc',
                        'type' => 'hidden',
                        'value' => (int)$card['id_payplug_card'],
                    ),
                    'pay' => array(
                        'name' => 'pay',
                        'type' => 'hidden',
                        'value' => '1',
                    ),
                    'id_cart' => array(
                        'name' => 'id_cart',
                        'type' => 'hidden',
                        'value' => (int)$this->context->cart->id,
                    ),
                    'method' => array(
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'one_click',
                    ),
                );
                $paymentOption
                    ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/' . strtolower($card['brand']) . '.png'))
                    ->setCallToActionText($brand . ' **** **** **** ' . $card['last4'] . ' - ' . $this->l('Expiry date') . ': ' . $card['expiry_date'])
                    ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array('def' => (int)$options['deferred']), true))
                    ->setModuleName('payplug')
                    ->setInputs($input_options);
                $payment_list[] = $paymentOption;
            }
        }

        $is_error = Tools::getValue('error');
        $is_inst = Tools::getValue('inst');

        // Standart Payment or new card from one-click
        $paymentOption = new PaymentOption();
        $input_options = array(
            'pc' => array(
                'name' => 'pc',
                'type' => 'hidden',
                'value' => 'new_card',
            ),
            'pay' => array(
                'name' => 'pay',
                'type' => 'hidden',
                'value' => '1',
            ),
            'id_cart' => array(
                'name' => 'id_cart',
                'type' => 'hidden',
                'value' => (int)$this->context->cart->id,
            ),
            'method' => array(
                'name' => 'method',
                'type' => 'hidden',
                'value' => 'standard',
            ),
        );
        $paymentOption
            ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_.$this->name.'/views/img/'
                .(count($payplug_cards) > 0 ? 'none' : 'logos_schemes_'.$this->img_lang)
                .'.png'))
            ->setCallToActionText(count($payplug_cards) > 0 ? $this->l('Pay with a different card') : $this->l('Pay with a credit card'))
            ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array('def' => (int)$options['deferred']), true))
            ->setModuleName('payplug')
            ->setInputs($input_options);

        if ($is_error && !$is_inst) {
            $paymentOption->setAdditionalInformation(
                $this->context->smarty->fetch('module:payplug/views/templates/front/errors.tpl')
            );
        }

        $payment_list[] = $paymentOption;

        // Installment Payment
        if ($options['installment']) {
            $paymentOption = new PaymentOption();
            $input_options = array(
                'pc' => array(
                    'name' => 'pc',
                    'type' => 'hidden',
                    'value' => 'new_card',
                ),
                'pay' => array(
                    'name' => 'pay',
                    'type' => 'hidden',
                    'value' => '1',
                ),
                'id_cart' => array(
                    'name' => 'id_cart',
                    'type' => 'hidden',
                    'value' => (int)$this->context->cart->id,
                ),
                'method' => array(
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'installment',
                ),
            );
            $paymentOption
                ->setLogo(Media::getMediaPath(_PS_MODULE_DIR_ . $this->name . '/views/img/logos_schemes_installment_' . Configuration::get('PAYPLUG_INST_MODE') . '_' . $this->img_lang . '.png'))
                ->setCallToActionText($this->l('Pay by card in') . ' ' . Configuration::get('PAYPLUG_INST_MODE') . ' ' . $this->l('installments'))
                ->setAction($this->context->link->getModuleLink($this->name, 'dispatcher', array('def' => (int)$options['deferred']), true))
                ->setModuleName('payplug')
                ->setInputs($input_options);

            if ($is_error && $is_inst) {
                $paymentOption->setAdditionalInformation(
                    $this->context->smarty->fetch('module:payplug/views/templates/front/errors.tpl')
                );
            }
            $payment_list[] = $paymentOption;
        }

        return $payment_list;
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
     * @throws \Payplug\Exception\ConfigurationNotSetException
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
        } elseif ($payment->authorization !== null && ($payment->authorization->expires_at - time()) > 0) {
            $pay_status = 8; //authorized
        } elseif ($payment->authorization !== null && ($payment->authorization->expires_at - time()) <= 0) {
            $pay_status = 9; //authorization expired
        } elseif ($payment->installment_plan_id !== null && (int)$installment->is_active == 1) {
            $pay_status = 6; //ongoing
        } else {
            $pay_status = 7; //cancelled
        }
        if (is_array($payment->failure) && count($payment->failure) > 0) {
            $pay_status = 3; //failed
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
     * @param int $id_order
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
     * @param $installment
     * @return array|bool|false|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     * @throws \Payplug\Exception\ConfigurationNotSetException
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
     * @throws \Payplug\Exception\ConfigurationNotSetException
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
        $currencies = array();
        foreach (explode(';', Configuration::get('PAYPLUG_MIN_AMOUNTS')) as $amount_cur) {
            $cur = array();
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

        $this->context->smarty->assign(array(
            'form_action' => (string)($_SERVER['REQUEST_URI']),
            'url_logo' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'site_url' => $this->site_url,
            'PAYPLUG_KEEP_CARDS' => $PAYPLUG_KEEP_CARDS,
        ));

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
     * @param $customer
     * @return false|string
     */
    public function hookActionDeleteGDPRCustomer($customer)
    {
        if (!$this->deleteCards((int)$customer['id'])) {
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
                    $this->saveCard($payment->resource);
                }
            }
        }
    }

    /**
     * @param array $params
     * @return string
     * @see Module::hookAdminOrder()
     *
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
        $show_menu_refunded = false;
        $show_menu_update = false;
        $show_menu_installment = false;
        $show_menu_payment = false;
        $pay_error = '';
        $amount_refunded_payplug = 0;
        $amount_available = 0;
        $id_new_order_state = 0;
        $id_pending_order_state = 0;
        $pay_mode = '';

        $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);
        $amount_refunded_presta = $this->getTotalRefunded($order->id);

        if ($inst_id = $this->getPayplugInstallmentCart($order->id_cart)) {
            $payment_list = array();
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
            $pps = array();
            if (count($payments) > 0) {
                foreach ($payments as $payment) {
                    $pps[] = $payment->transaction_id;
                }
            }

            $payment_list_new = array();
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
                            $amount_available += ($amount_refundable_payment >= 10 ? $amount_refundable_payment / 100 : 0);
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
                    $payment_list_new[] = array(
                        'id' => null,
                        'status' => $inst_status = $installment->is_active ? $this->payment_status[6] : $this->payment_status[7],
                        'amount' => (int)$schedule->amount / 100,
                        'card_brand' => null,
                        'card_mask' => null,
                        'tds' => null,
                        'card_date' => null,
                        'mode' => null,
                        'authorization' => null,
                        'status_class' => $inst_status = $installment->is_active ? 'pp_success' : 'pp_error',
                        'date' => date('d/m/Y', strtotime($schedule->date)),
                    );
                }
            }

            $id_currency = (int)Currency::getIdByIsoCode($installment->currency);
            $show_menu_installment = true;
            $inst_status = $installment->is_active ? $this->l('ongoing') : ($installment->is_fully_paid ? $this->l('paid') : $this->l('suspended'));
            $inst_aborted = !$installment->is_active;
            $ppInstallment = new PPPaymentInstallment($installment->id);
            $instPaymentOne = $ppInstallment->getFirstPayment();
            $inst_can_be_aborted = !($inst_aborted || ($instPaymentOne->isDeferred() && !$instPaymentOne->isPaid()));
            $inst_paid = $installment->is_fully_paid;
            $this->context->smarty->assign(array(
                'inst_id' => $inst_id,
                'inst_status' => $inst_status,
                'inst_aborted' => $inst_aborted,
                'inst_paid' => $inst_paid,
                'payment_list' => $payment_list,
                'payment_list_new' => $payment_list_new,
                'inst_can_be_aborted' => $inst_can_be_aborted,
            ));

            $sandbox = ((int)$installment->is_live == 1 ? false : true);
            if ($sandbox) {
                $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
            } else {
                $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
            }

            $this->updatePayplugInstallment($installment);
        } else {
            if (!$pay_id = $this->isTransactionPending((int)$order->id_cart)) {
                $payments = $order->getOrderPaymentCollection();
                if (count($payments) > 1 || !isset($payments[0])) {
                    return false;
                } else {
                    $pay_id = $payments[0]->transaction_id;
                }
            }

            if (!$pay_id || empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                if (Configuration::get('PAYPLUG_SANDBOX_MODE') == 1) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                    if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                        $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                        return false;
                    }
                } elseif (Configuration::get('PAYPLUG_SANDBOX_MODE') == 0) {
                    $this->setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
                    if (empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
                        $this->setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
                        return false;
                    }
                }
            }

            $single_payment = $this->buildPaymentDetails($payment);
            $amount_refunded_payplug = ($payment->amount_refunded) / 100;
            $amount_available_payment = ($payment->amount - $payment->amount_refunded);
            $amount_available = ($amount_available_payment >= 10 ? $amount_available_payment / 100 : 0);
            $id_currency = (int)Currency::getIdByIsoCode($payment->currency);
            $sandbox = ((int)$payment->is_live == 1 ? false : true);

            if ($sandbox) {
                $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
                $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST');
            } else {
                $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING');
            }
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
            } elseif ((((int)$payment->amount_refunded > 0) || $amount_refunded_presta > 0) && (int)$payment->is_refunded != 1) {
                $display_refund = true;
            } elseif ((int)$payment->is_refunded == 1) {
                $show_menu_refunded = true;
                $display_refund = false;
            } else {
                $display_refund = true;
            }

            $conf = (int)Tools::getValue('conf');
            if (($conf == 30 || $conf == 31) && version_compare(_PS_VERSION_, '1.5', '>=')) {
                $show_popin = true;

                $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);

                $this->html .= '
                    <a class="pp_admin_ajax_url" href="' . $admin_ajax_url . '"></a>
                ';
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
            {
                if (!$pay_id = $this->isTransactionPending((int)$order->id_cart)) {
                    $payments = $order->getOrderPaymentCollection();
                    if (count($payments) > 1 || !isset($payments[0])) {
                        return false;
                    } else {
                        $pay_id = $payments[0]->transaction_id;
                    }
                }

                if (!$pay_id || empty($pay_id) || !$payment = $this->retrievePayment($pay_id)) {
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

                $single_payment = $this->buildPaymentDetails($payment);
                $amount_refunded_payplug = ($payment->amount_refunded) / 100;
                $amount_available_payment = ($payment->amount - $payment->amount_refunded);
                $amount_available = ($amount_available_payment >= 10 ? $amount_available_payment / 100 : 0);
                $id_currency = (int)Currency::getIdByIsoCode($payment->currency);
                $sandbox = ((int)$payment->is_live == 1 ? false : true);

                if ($sandbox) {
                    $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND_TEST');
                    $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING_TEST');
                } else {
                    $id_new_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_REFUND');
                    $id_pending_order_state = (int)Configuration::get('PAYPLUG_ORDER_STATE_PENDING');
                }
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
                } elseif ((((int)$payment->amount_refunded > 0) || $amount_refunded_presta > 0) && (int)$payment->is_refunded != 1) {
                    $display_refund = true;
                } elseif ((int)$payment->is_refunded == 1) {
                    $show_menu_refunded = true;
                    $display_refund = false;
                } else {
                    $display_refund = true;
                }

                $conf = (int)Tools::getValue('conf');
                if (($conf == 30 || $conf == 31) && version_compare(_PS_VERSION_, '1.5', '>=')) {
                    $show_popin = true;

                    $admin_ajax_url = $this->getAdminAjaxUrl('AdminModules', (int)$params['id_order']);

                    $this->html .= '
                    <a class="pp_admin_ajax_url" href="' . $admin_ajax_url . '"></a>
                ';
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

                //Deferred payment does'nt display 3DS option before capture so we have to consider it null
                if ($payment->is_3ds !== null) {
                    $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
                    $this->context->smarty->assign(array('pay_tds' => $pay_tds));
                }

                $pay_mode = $payment->is_live ? $this->l('LIVE') : $this->l('TEST');

                if ($payment->card->exp_month === null) {
                    $pay_card_date = $this->l('Unavailable in test mode');
                } else {
                    $pay_card_date = date('m/y',
                        strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year));
                }

                $show_menu_payment = true;

                $this->context->smarty->assign(array(
                    'pay_id' => $pay_id,
                    'pay_status' => $pay_status,
                    'pay_amount' => $pay_amount,
                    'pay_date' => $pay_date,
                    'pay_brand' => $pay_brand,
                    'pay_card_mask' => $pay_card_mask,
                    'pay_card_date' => $pay_card_date,
                    'pay_error' => $pay_error,
                ));
            }

            //Deferred payment does'nt display 3DS option before capture so we have to consider it null
            if ($payment->is_3ds !== null) {
                $pay_tds = $payment->is_3ds ? $this->l('YES') : $this->l('NO');
                $this->context->smarty->assign(array('pay_tds' => $pay_tds));
            }

            $pay_mode = $payment->is_live ? $this->l('LIVE') : $this->l('TEST');

            if ($payment->card->exp_month === null) {
                $pay_card_date = $this->l('Unavailable in test mode');
            } else {
                $pay_card_date = date('m/y',
                    strtotime('01.' . $payment->card->exp_month . '.' . $payment->card->exp_year));
            }

            $show_menu_payment = true;

            $this->context->smarty->assign(array(
                'pay_id' => $pay_id,
                'pay_status' => $pay_status,
                'pay_amount' => $pay_amount,
                'pay_date' => $pay_date,
                'pay_brand' => $pay_brand,
                'pay_card_mask' => $pay_card_mask,
                'pay_card_date' => $pay_card_date,
                'pay_error' => $pay_error,
            ));
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
            $this->context->smarty->assign(array(
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

        $display_single_payment = $show_menu_payment;
        $this->context->smarty->assign(array(
            'logo_url' => __PS_BASE_URI__ . 'modules/payplug/views/img/logo_payplug.png',
            'admin_ajax_url' => $admin_ajax_url,
            'display_single_payment' => $display_single_payment,
            'display_refund' => $display_refund,
            'show_menu_payment' => $show_menu_payment,
            'show_menu_refunded' => $show_menu_refunded,
            'show_menu_update' => $show_menu_update,
            'show_menu_installment' => $show_menu_installment,
            'pay_mode' => $pay_mode,
            'order' => $order,
        ));

        if ($display_single_payment) {
            $this->context->smarty->assign(array(
                'single_payment' => $single_payment,
            ));
        }

        if ($show_popin && $display_refund) {
            $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin_order_popin.js');
        }
        $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/admin_order.js');
        $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/admin_order.css');

        $this->html .= $this->fetchTemplateRC('/views/templates/admin/admin_order.tpl');
        return $this->html;
    }

    /**
     * @param $params
     * @return string|void
     */
    public function hookCustomerAccount($params)
    {
        if (!$this->active) {
            return;
        }
        if (Configuration::get('PAYPLUG_SHOW') == 0) {
            return;
        }

        $payplug_cards_url = $this->context->link->getModuleLink($this->name, 'cards', array('process' => 'cardlist'),
            true);

        $this->smarty->assign(array(
            'payplug_cards_url' => $payplug_cards_url
        ));

        return $this->display(__FILE__, 'my_account.tpl');
    }

    /**
     * @param array $params
     * @return string
     * @see Module::hookHeader()
     *
     */
    public function hookHeader($params)
    {
        if (!$this->active) {
            return;
        }
        if (Configuration::get('PAYPLUG_SHOW') == 0) {
            return;
        }

        $this->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/front.css');
        $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/front.js');

        if ((int)Tools::getValue('lightbox') == 1) {
            $cart = $params['cart'];
            if (!Validate::isLoadedObject($cart)) {
                return;
            }

            $this->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/embedded.js');

            $id_card = Tools::getValue('pc','new_card');
            $is_installment = (bool)Tools::getValue('inst') == 1;
            $is_deferred = (bool)Tools::getValue('def') == 1;
            $payment = $this->preparePayment($cart, $id_card, $is_installment, $is_deferred);

            if($payment['result']){
                // If payment is paid then redirect
                if($payment['redirect']) {
                    Tools::redirect($payment['return_url']);
                }
                // else show the popin
                else {
                    $this->context->smarty->assign(array(
                        'payment_url' => $payment['return_url'],
                        'api_url' => $this->api_url,
                    ));
                    return $this->display(__FILE__, 'embedded.tpl');
                }
            } else {
                $error_url = 'index.php?controller=order&step=3&error=1' . ($is_installment ? '&inst=1' : '');
                Tools::redirect($error_url);
            }
        }
    }

    /**
     * @param array $params
     * @return array
     * @see Module::hookPaymentOptions()
     *
     */
    public function hookPaymentOptions($params)
    {
        $cart = $params['cart'];
        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $this->context->smarty->assign(array(
            'api_url' => $this->api_url,
        ));

        $payment_options = $this->getPaymentOptions($cart);

        return $payment_options;
    }

    /**
     * @param array $params
     * @return string
     * @see Module::hookPaymentReturn()
     *
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

    public function hookRegisterGDPRConsent()
    {
    }

    /**
     * @return bool
     * @throws Exception
     * @see Module::install()
     *
     */
    public function install()
    {
        $this->log_install->info('Starting to install.');
        $report = $this->checkRequirements();
        if (!$report['php']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('Your server must run PHP 5.3 or greater'));
            $this->log_install->error('Install failed: PHP Requirement.');
        }
        if (!$report['curl']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('PHP cURL extension must be enabled on your server'));
            $this->log_install->error('Install failed: cURL Requirement.');
        }
        if (!$report['openssl']['up2date']) {
            $this->_errors[] = Tools::displayError($this->l('OpenSSL 1.0.1 or later'));
            $this->log_install->error('Install failed: OpenSSL Requirement.');
        }

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()) {
            $this->log_install->error('Install failed: parent.');
        } elseif (!$this->registerHook('paymentReturn') ||
            !$this->registerHook('header') ||
            !$this->registerHook('adminOrder') ||
            !$this->registerHook('actionOrderStatusUpdate') ||
            !$this->registerHook('customerAccount')
        ) {
            $this->log_install->error('Install failed: classics hooks.');
        } elseif (!$this->registerHook('paymentOptions')) {
            $this->log_install->error('Install failed: hook paymentOptions.');
        } elseif (!$this->registerHook('registerGDPRConsent') ||
            !$this->registerHook('actionDeleteGDPRCustomer') ||
            !$this->registerHook('actionExportGDPRData')
        ) {
            $this->log_install->error('Install failed: hooks GDPR.');
        } elseif (!$this->createConfig()) {
            $this->log_install->error('Install failed: configuration.');
        } elseif (!$this->createOrderStates()) {
            $this->log_install->error('Install failed: order states.');
        } elseif (!$this->installSQL()) {
            $this->log_install->error('Install failed: sql.');
        } elseif (!$this->installTab()) {
            $this->log_install->error('Install failed: tab.');
        } else {
            $this->log_install->info('Install succeeded.');
            return true;
        }
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
     * Install SQL tables used by module
     *
     * @return bool
     */
    private function installSQL()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Installation SQL Starting.');

        if (!defined('_MYSQL_ENGINE_')) {
            define('_MYSQL_ENGINE_', 'InnoDB');
        }

        $req_payplug_lock = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_lock` (
            `id_payplug_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `id_order` VARCHAR(100),
            `date_add` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\',
            `date_upd` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

        if (!$res_payplug_lock) {
            $log->error('Installation SQL failed: PAYPLUG_LOCK.');
            return false;
        }

        $req_payplug_card = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_card` (
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
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_card = DB::getInstance()->Execute($req_payplug_card);

        if (!$res_payplug_card) {
            $log->error('Installation SQL failed: PAYPLUG_CARD.');
            return false;
        }

        $req_payplug_payment_cart = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_payment_cart` (
            `id_payplug_payment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_payment` VARCHAR(255) NOT NULL,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `is_pending` TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_payment_cart = DB::getInstance()->Execute($req_payplug_payment_cart);

        if (!$res_payplug_payment_cart) {
            $log->error('Installation SQL failed: PAYPLUG_PAYMENT_CART.');
            return false;
        }

        $req_payplug_installment_cart = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_installment_cart` (
            `id_payplug_installment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_cart` INT(11) UNSIGNED NOT NULL,
            `is_pending` TINYINT(1) NOT NULL DEFAULT 0, 
            `date_upd` DATETIME NULL
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_installment_cart = DB::getInstance()->Execute($req_payplug_installment_cart);

        if (!$res_payplug_installment_cart) {
            $log->error('Installation SQL failed: PAYPLUG_INSTALLMENT_CART.');
            return false;
        }

        $req_payplug_installment = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'payplug_installment` (
            `id_payplug_installment` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `id_installment` VARCHAR(255) NOT NULL,
            `id_payment` VARCHAR(255) NULL,
            `id_order` INT(11) UNSIGNED NOT NULL,
            `id_customer` INT(11) UNSIGNED NOT NULL,
            `order_total` INT(11) UNSIGNED NOT NULL,
            `step` VARCHAR(11) NOT NULL,
            `amount` INT(11) UNSIGNED NOT NULL,
            `status` INT(11) UNSIGNED NOT NULL,
            `scheduled_date` DATETIME NOT NULL
            ) ENGINE=' . _MYSQL_ENGINE_;
        $res_payplug_installment = DB::getInstance()->Execute($req_payplug_installment);

        if (!$res_payplug_installment) {
            $log->error('Installation SQL failed: PAYPLUG_INSTALLMENTS.');
            return false;
        }


        $log->info('Installation SQL ended.');
        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab()
    {
        $translationsAdminPayPlug = array(
            'en' => 'PayPlug',
            'fr' => 'PayPlug'
        );
        $flag = $this->installModuleTab('AdminPayPlug', $translationsAdminPayPlug, 0);

        $translationsAdminPayPlugInstallment = array(
            'en' => 'Installment Plans',
            'fr' => 'Paiements en plusieurs fois'
        );

        $adminPayPlugId = Tab::getIdFromClassName('AdminPayPlug');
        $flag = ($flag && $this->installModuleTab('AdminPayPlugInstallment', $translationsAdminPayPlugInstallment,
                $adminPayPlugId, $this->name));

        return $flag;
    }

    /**
     * Check if payment method is valid for given id
     *
     * @param string $payment_id
     * @param string $type default payment
     * @return bool
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
                break;
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

        $url = $this->api_url . $this->routes['login'];
        $curl_version = curl_version();
        $process = curl_init($url);
        curl_setopt(
            $process,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type:application/json',
                'Content-Length: ' . Tools::strlen($data_string)
            )
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
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__) . '/cacert.pem')); //work only wiht cURL 7.10+
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
     * Make a refund
     *
     * @param string $pay_id
     * @param int $amount
     * @param string $metadata
     * @param string $pay_mode
     * @return string
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
                        $refund_to_go = array();
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
                                            $data = array(
                                                'amount' => $amount_refundable,
                                                'metadata' => $metadata
                                            );
                                            $amount -= $amount_refundable;
                                        } else {
                                            $data = array(
                                                'amount' => $amount,
                                                'metadata' => $metadata
                                            );
                                            $amount = 0;
                                        }
                                        $refund_to_go[] = array('id' => $p_id, 'data' => $data);
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
            $data = array(
                'amount' => (int)$amount,
                'metadata' => $metadata
            );

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
     */
    public function patchPayment($api_key, $pay_id, $data)
    {
        $data_string = json_encode($data);
        $url = $this->api_url . $this->routes['patch'] . '/' . $pay_id;
        $curl_version = curl_version();
        $process = curl_init($url);
        curl_setopt(
            $process,
            CURLOPT_HTTPHEADER,
            array(
                'Authorization: Bearer ' . $api_key,
                'Content-Type:application/json',
                'Content-Length: ' . Tools::strlen($data_string)
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
        curl_setopt($process, CURLOPT_CAINFO, realpath(dirname(__FILE__) . '/cacert.pem'));
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
     * @return void
     * @see Module::postProcess()
     *
     */
    private function postProcess()
    {
        $curl_exists = extension_loaded('curl');
        $openssl_exists = extension_loaded('openssl');
        if (Tools::isSubmit('submitAccount')) {
            $password = isset($_POST['PAYPLUG_PASSWORD']) && $_POST['PAYPLUG_PASSWORD'] ? $_POST['PAYPLUG_PASSWORD'] : false;
            if ((!Validate::isEmail(Tools::getValue('PAYPLUG_EMAIL'))
                    || !Validate::isPasswd($password)
                )
                && (Tools::getValue('PAYPLUG_EMAIL') != false)
            ) {
                $this->validationErrors['username_password'] = $this->l('The email and/or password was not correct.');
            } elseif ($curl_exists && $openssl_exists) {
                if ($this->login(Tools::getValue('PAYPLUG_EMAIL'), $password)) {
                    Configuration::updateValue('PAYPLUG_EMAIL', Tools::getValue('PAYPLUG_EMAIL'));
                    Configuration::updateValue('PAYPLUG_SHOW', 1);
                } else {
                    $this->validationErrors['username_password']
                        = $this->l('The email and/or password was not correct.');
                }
            }
        }

        if (Tools::isSubmit('submitDisconnect')) {
            $this->createConfig();
            Configuration::updateValue('PAYPLUG_SHOW', 0);
        }

        if (Tools::isSubmit('submitSettings')) {

            if (Tools::getValue('PAYPLUG_INST_MIN_AMOUNT') < 4) {
                $this->displayError($this->l('Settings not updated'));
            } else {
                Configuration::updateValue('PAYPLUG_SANDBOX_MODE', Tools::getValue('payplug_sandbox'));
                Configuration::updateValue('PAYPLUG_EMBEDDED_MODE', Tools::getValue('payplug_embedded'));
                Configuration::updateValue('PAYPLUG_ONE_CLICK', Tools::getValue('payplug_one_click'));
                Configuration::updateValue('PAYPLUG_INST', Tools::getValue('payplug_inst'));
                Configuration::updateValue('PAYPLUG_INST_MODE', Tools::getValue('PAYPLUG_INST_MODE'));
                Configuration::updateValue('PAYPLUG_INST_MIN_AMOUNT', Tools::getValue('PAYPLUG_INST_MIN_AMOUNT'));
                Configuration::updateValue('PAYPLUG_SHOW', Tools::getValue('PAYPLUG_SHOW'));
                Configuration::updateValue('PAYPLUG_DEFERRED', Tools::getValue('payplug_deferred'));
                Configuration::updateValue('PAYPLUG_DEFERRED_AUTO', Tools::getValue('PAYPLUG_DEFERRED_AUTO'));
                if ((int)Tools::getValue('PAYPLUG_DEFERRED_AUTO') == 1) {
                    Configuration::updateValue('PAYPLUG_DEFERRED_STATE', Tools::getValue('PAYPLUG_DEFERRED_STATE'));
                }
            }
        }

        if (Tools::isSubmit('submitUninstallSettings')) {
            Configuration::updateValue('PAYPLUG_KEEP_CARDS', Tools::getValue('PAYPLUG_KEEP_CARDS'));
        }
    }

    /**
     * prepare payment
     *
     * @param object $cart
     * @param string $id_card
     * @return mixed
     */
    public function preparePayment($cart, $id_card = 'new_card', $is_installment = false, $is_deferred = false)
    {
        if(!is_object($cart)){
            $cart = new Cart((int)$cart);
        }
        if (!Validate::isLoadedObject($cart)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => __LINE__ . $this->l('The transaction was not completed and your card was not charged.')
            ];
        }

        $customer = new Customer((int)$cart->id_customer);
        if (!Validate::isLoadedObject($customer)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => __LINE__ . $this->l('The transaction was not completed and your card was not charged.')
            ];
        }

        // get the config
        $config = [
            'one_click' => (int)Configuration::get('PAYPLUG_ONE_CLICK'),
            'installment' => (int)Configuration::get('PAYPLUG_INST'),
            'company' => (int)Configuration::get('PAYPLUG_COMPANY_ID'),
            'inst_mode' => (int)Configuration::get('PAYPLUG_INST_MODE'),
            'deferred' => (int)Configuration::get('PAYPLUG_DEFERRED')
        ];

        $is_one_click = $id_card != 'new_card' && $config['one_click'];
        $is_installment = $is_installment && $config['installment'];

        // Build payment Tab

        // Currency
        $currency = $cart->id_currency;
        $result_currency = Currency::getCurrency($currency);
        $supported_currencies = explode(';', Configuration::get('PAYPLUG_CURRENCIES'));
        $currency = $result_currency['iso_code'];
        // if unvalid iso code, return false
        if (!in_array($currency, $supported_currencies)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => __LINE__ . $this->l('The transaction was not completed and your card was not charged.')
            ];
        }

        // Amount
        $amount = $cart->getOrderTotal(true, Cart::BOTH);
        $amount = (int)(round(($amount * 100), PHP_ROUND_HALF_UP));
        $current_amounts = $this->getAmountsByCurrency($currency);
        if ($amount < $current_amounts['min_amount'] || $amount > $current_amounts['max_amount']) {
            // todo: add error log
            return array(
                'result' => false,
                'response' => $this->l('The transaction was not completed and your card was not charged.')
            );
        }

        // Hosted url
        $hosted_url = [
            'return' => $this->context->link->getModuleLink($this->name, 'validation',
                ['ps' => 1, 'cartid' => (int)$cart->id], true),
            'cancel' => $this->context->link->getModuleLink($this->name, 'validation',
                ['ps' => 2, 'cartid' => (int)$cart->id], true),
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
            if (in_array(Tools::strtoupper($default_language->iso_code), $iso_code_list)) {
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
            'company_name' => !empty($billing_address->company) ? $billing_address->company : $billing_address->firstname . ' ' . $billing_address->lastname,
            'email' => $customer->email,
            'landline_phone_number' => $this->formatPhoneNumber($billing_address->phone, $billing_address->id_country),
            'mobile_phone_number' => $this->formatPhoneNumber($billing_address->phone_mobile,
                $billing_address->id_country),
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
            'company_name' => !empty($shipping_address->company) ? $shipping_address->company : null,
            'email' => $customer->email,
            'landline_phone_number' => $this->formatPhoneNumber($shipping_address->phone,
                $shipping_address->id_country),
            'mobile_phone_number' => $this->formatPhoneNumber($shipping_address->phone_mobile,
                $shipping_address->id_country),
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
        $allow_save_card = $config['one_click'] && Cart::isGuestCartByCartId($cart->id) != 1 && $id_card == 'new_card';

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

        if (!$is_deferred) {
            $payment_tab['amount'] = $amount;
        } else {
            $payment_tab['authorized_amount'] = $amount;
        }

        // check payment tab from current payment method
        if ($is_installment) {
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
                    if ($is_deferred) {
                        $schedule[$i]['authorized_amount'] = (int)($int_part + ($amount - ($int_part * $config['inst_mode'])));
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
            $payment_tab['payment_method'] = $id_card != null && $id_card != 'new_card' ? $this->getCardId((int)$cart->id_customer, $id_card, $config['company']) : null;
        }


        // Create payment
        try {
            if ($is_installment) {
                $payment = \Payplug\InstallmentPlan::create($payment_tab);
                if ($payment->failure != null && !empty($payment->failure['message'])) {
                    return [
                        'result' => false,
                        'response' => __LINE__ . $payment->failure['message'],
                    ];
                }
                $this->storeInstallment($payment->id, (int)$cart->id);
            } else {
                $payment = \Payplug\Payment::create($payment_tab);
                if ($payment->failure == true && !empty($payment->failure['message'])) {
                    return [
                        'result' => false,
                        'response' => __LINE__ . $payment->failure['message'],
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

        if($is_one_click) {
            $is_paid = $payment->is_paid;
            if (!$is_paid && $is_deferred) {
                $is_paid = $payment->authorization->authorized_at;
            }
            return [
                'result' => true,
                'redirect' => $is_paid,
                'return_url' => $is_paid ? $payment_tab['hosted_payment']['return_url'] : $payment->hosted_payment->payment_url
            ];
        }

        return [
            'result' => true,
            'redirect' => false,
            'return_url' => $payment->hosted_payment->payment_url
        ];
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
     * @param string $pay_id
     * @return PayplugInstallment
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

        // if card exists then return false
        $db = new DbQuery();
        $db->select('id_card');
        $db->from('payplug_card');
        $db->where('id_card = "' . $payment->card->id .'"');
        $db->where('id_company = ' . (int)$company_id);
        $db->where('is_sandbox = ' . (int)$is_sandbox);
        if (Db::getInstance()->getValue($db)) {
            return false;
        }

        // else get next card position
        $db = new DbQuery();
        $db->select('COUNT(pc.id_payplug_card)');
        $db->from('payplug_card', 'pc');
        $db->where('pc.id_customer = ' . (int)$customer_id);
        $db->where('pc.id_company = ' . (int)$company_id);
        $db->where('pc.is_sandbox = ' . (int)$is_sandbox);
        $card_index = Db::getInstance()->getValue($db);

        $card_index = (int)$card_index + 1;

        // insert the new card in database
        $card = [
            'id_customer' => (int)$customer_id,
            'id_payplug_card' => (int)$card_index + 1,
            'id_company' => (int)$company_id,
            'is_sandbox' => (int)$is_sandbox,
            'id_card' => pSQL($payment->card->id),
            'last4' => pSQL($payment->card->last4),
            'exp_month' => pSQL($payment->card->exp_month),
            'exp_year' => pSQL($payment->card->exp_year),
            'brand' => pSQL($brand),
            'country' => pSQL($payment->card->country),
            'metadata' => serialize($payment->card->metadata),
        ];

        $return = Db::getInstance()->insert('payplug_card', $card);

        return (bool)$return;
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
        if (isset($json_answer->object) && $json_answer->object == 'error') {
            return false;
        }

        $api_keys = array();
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
     * Set very specific properties
     *
     * @return void
     */
    private function setConfigurationProperties()
    {
        $this->api_live = Configuration::get('PAYPLUG_LIVE_API_KEY');
        $this->api_test = Configuration::get('PAYPLUG_TEST_API_KEY');

        // Set the uninstall notice according to the "keep_cards" configuration
        $this->confirmUninstall = $this->l('Are you sure you wish to uninstall this module and delete your settings?') . ' ';
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
            $this->warning = $this->l('In order to accept payments you need to configure your module by connecting your PayPlug account.');
        }

        $this->payment_status = array(
            1 => $this->l('not paid'),
            2 => $this->l('paid'),
            3 => $this->l('failed'),
            4 => $this->l('partially refunded'),
            5 => $this->l('refunded'),
            6 => $this->l('on going'),
            7 => $this->l('cancelled'),
            8 => $this->l('authorized'),
            9 => $this->l('authorization expired'),
        );
    }

    /**
     * Determine witch environment is used
     *
     * @return void
     */
    private function setEnvironment()
    {
        if (isset($_SERVER['PAYPLUG_API_URL'])) {
            $this->api_url = $_SERVER['PAYPLUG_API_URL'];
        } else {
            $this->api_url = 'https://api.payplug.com';
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
        $this->log_general = new MyLogPHP(_PS_MODULE_DIR_ . $this->name . '/log/general-log.csv');
        $this->log_install = new MyLogPHP(_PS_MODULE_DIR_ . $this->name . '/log/install-log.csv');
    }

    /**
     * Set the essential properties of a Prestashop module
     *
     * @return void
     */
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
        $this->version = '2.25.1';
        $this->api_version = '2019-08-06';
    }

    /**
     * Set the current secret key used to interact with PayPlug API
     *
     * @return void
     * @throws Exception
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
            'apiVersion' => $this->api_version
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
     * Register installment for later use
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
                VALUES (\'' . pSQL($installment_id) . '\', ' . (int)$id_cart . ', ' . (int)$is_pending . ', \'' . pSQL($date_upd) . '\')';
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
     * Register payment for later use
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
                $can_create_installment_plan = $permissions['can_create_installment_plan'];
            }
        } else {
            die(json_encode(array('content' => 'wrong_pwd')));
        }
        if (!$use_live_mode) {
            die(json_encode(array('content' => 'activate')));
        } elseif ($can_save_cards && $can_create_installment_plan) {
            die(json_encode(array('content' => 'live_ok')));
        } elseif ($can_save_cards && !$can_create_installment_plan) {
            die(json_encode(array('content' => 'live_ok_no_inst')));
        } elseif (!$can_save_cards && $can_create_installment_plan) {
            die(json_encode(array('content' => 'live_ok_no_oneclick')));
        } else {
            die(json_encode(array('content' => 'live_ok_not_premium')));
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
            ) {
                $configuration['currencies'] = array();
                foreach ($json_answer->configuration->currencies as $value) {
                    $configuration['currencies'][] = $value;
                }
            }
            if (isset($json_answer->configuration->min_amounts)
                && !empty($json_answer->configuration->min_amounts)
            ) {
                $configuration['min_amounts'] = '';
                foreach ($json_answer->configuration->min_amounts as $key => $value) {
                    $configuration['min_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['min_amounts'] = Tools::substr($configuration['min_amounts'], 0, -1);
            }
            if (isset($json_answer->configuration->max_amounts)
                && !empty($json_answer->configuration->max_amounts)
            ) {
                $configuration['max_amounts'] = '';
                foreach ($json_answer->configuration->max_amounts as $key => $value) {
                    $configuration['max_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['max_amounts'] = Tools::substr($configuration['max_amounts'], 0, -1);
            }
        }

        $permissions = array(
            'use_live_mode' => $json_answer->permissions->use_live_mode,
            'can_save_cards' => $json_answer->permissions->can_save_cards,
            'can_create_installment_plan' => $json_answer->permissions->can_create_installment_plan,
            'can_create_deferred_payment' => $json_answer->permissions->can_create_deferred_payment,
        );

        $currencies = implode(';', $configuration['currencies']);
        Configuration::updateValue('PAYPLUG_CURRENCIES', $currencies);
        Configuration::updateValue('PAYPLUG_MIN_AMOUNTS', $configuration['min_amounts']);
        Configuration::updateValue('PAYPLUG_MAX_AMOUNTS', $configuration['max_amounts']);
        Configuration::updateValue('PAYPLUG_COMPANY_ID', $id);

        return $permissions;
    }

    /**
     * @return bool
     * @see Module::uninstall()
     *
     */
    public function uninstall()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $this->log_install->info('Starting to uninstall.');

        $keep_cards = (bool)Configuration::get('PAYPLUG_KEEP_CARDS');
        if (!$keep_cards) {
            $this->log_install->info('Saved cards will be deleted.');
            if (!$this->uninstallCards()) {
                $this->log_install->error('Unable to delete saved cards.');
            } else {
                $this->log_install->error('Saved cards successfully deleted.');
            }
        } else {
            $this->log_install->info('Cards will be kept.');
        }

        if (!parent::uninstall()) {
            $this->log_install->error('Uninstall failed: parent.');
        } elseif (!$this->deleteConfig()) {
            $this->log_install->error('Uninstall failed: configuration.');
        } elseif (!$this->uninstallSQL($keep_cards)) {
            $this->log_install->error('Uninstall failed: sql.');
        } elseif (!$this->uninstallTab()) {
            $this->log_install->error('Uninstall failed: tab.');
        } else {
            $log->info('Uninstall succeeded.');
            return true;
        }
        return false;
    }

    /**
     * Delete saved cards when uninstalling module
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
                if (!$this->deleteCard($id_customer, $id_payplug_card, $api_key)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
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
     * Remove SQL tables used by module
     *
     * @return bool
     */
    private function uninstallSQL($keep_cards = false)
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $log->info('Uninstallation SQL starting.');
        $req_payplug_lock = '
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'payplug_lock`';
        $res_payplug_lock = DB::getInstance()->Execute($req_payplug_lock);

        if (!$res_payplug_lock) {
            $log->error('Uninstallation SQL failed: PAYPLUG_LOCK.');
            return false;
        }

        if (!$keep_cards) {
            $req_payplug_card = '
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'payplug_card`';
            $res_payplug_card = DB::getInstance()->Execute($req_payplug_card);

            if (!$res_payplug_card) {
                $log->error('Uninstallation SQL failed: PAYPLUG_CARD.');
                return false;
            }
        } else {
            $log->info('Cards kept in database.');
        }

        $req_payplug_payment_cart = '
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'payplug_payment_cart`';
        $res_payplug_payment_cart = DB::getInstance()->Execute($req_payplug_payment_cart);

        if (!$res_payplug_payment_cart) {
            $log->error('Uninstallation SQL failed: PAYPLUG_PAYMENT_CART.');
            return false;
        }

        $req_payplug_installment_cart = '
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'payplug_installment_cart`';
        $res_payplug_installment_cart = DB::getInstance()->Execute($req_payplug_installment_cart);

        if (!$res_payplug_installment_cart) {
            $log->error('Uninstallation SQL failed: PAYPLUG_INSTALLMENT_CART.');
            return false;
        }

        $req_payplug_installment = '
            DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'payplug_installment`';
        $res_payplug_installment = DB::getInstance()->Execute($req_payplug_installment);

        if (!$res_payplug_installment) {
            $log->error('Uninstallation SQL failed: PAYPLUG_INSTALLMENTS.');
            return false;
        }

        $log->info('Uninstallation SQL ended.');
        return true;
    }

    /**
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     */
    public function uninstallTab()
    {
        return ($this->uninstallModuleTab('AdminPayPlug') && $this->uninstallModuleTab('AdminPayPlugInstallment'));
    }

    /**
     * @param $installment
     * @return bool
     * @throws \Payplug\Exception\ConfigurationNotSetException
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
}
