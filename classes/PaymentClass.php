<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\classes;

class PaymentClass
{
    private $address;
    private $assign;
    private $card;
    private $cart;
    private $config;
    private $configuration;
    private $constant;
    private $context;
    private $country;
    private $currency;
    private $customer;
    private $dependencies;
    private $language;
    private $logger;
    private $module;
    private $oney;
    private $oney_allowed_iso_codes;
    private $order;
    private $orderHistory;
    private $payment;
    private $query;
    private $tools;
    private $validate;
    private $validators;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->address = $this->dependencies->getPlugin()->getAddress();
        $this->assign = $this->dependencies->getPlugin()->getAssign();
        $this->card = $this->dependencies->getPlugin()->getCard();
        $this->cart = $this->dependencies->getPlugin()->getCart();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->country = $this->dependencies->getPlugin()->getCountry();
        $this->currency = $this->dependencies->getPlugin()->getCurrency();
        $this->customer = $this->dependencies->getPlugin()->getCustomer();
        $this->language = $this->dependencies->getPlugin()->getLanguage();
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->module = $this->dependencies->getPlugin()->getModule();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->oney_allowed_iso_codes = ['FR', 'IT', 'ES', 'NL'];
        $this->order = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistory = $this->dependencies->getPlugin()->getOrderHistory();
        $this->payment = $this->dependencies->getPlugin()->getPayment();
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
        $this->validators = $this->dependencies->getValidators();
    }

    /**
     * @description Abort a payment
     */
    public function abortPayment()
    {
        $inst_id = $this->tools->tool('getValue', 'inst_id');
        $id_order = $this->tools->tool('getValue', 'id_order');

        $abort = $this->dependencies->apiClass->abortInstallment($inst_id);
        if (!$abort['result']) {
            $sandbox = (bool) $this->config->get($this->dependencies->getConfigurationKey('sandboxMode'));
            if ($sandbox) {
                $this->dependencies->apiClass->setSecretKey($this->config->get(
                    $this->dependencies->getConfigurationKey('liveApiKey')
                ));
                $abort = $this->dependencies->apiClass->abortInstallment($inst_id);
                $this->dependencies->apiClass->setSecretKey($this->config->get(
                    $this->dependencies->getConfigurationKey('testApiKey')
                ));
            } elseif (!$sandbox) {
                $this->dependencies->apiClass->setSecretKey($this->config->get(
                    $this->dependencies->getConfigurationKey('testApiKey')
                ));
                $abort = $this->dependencies->apiClass->abortInstallment($inst_id);
                $this->dependencies->apiClass->setSecretKey($this->config->get(
                    $this->dependencies->getConfigurationKey('liveApiKey')
                ));
            }
        }

        if (!$abort['result']) {
            exit(json_encode([
                'status' => 'error',
                'data' => $this->dependencies->l('payplug.abortPayment.cannotAbort', 'paymentclass'),
            ]));
        }
        $installment = $this->dependencies->apiClass->retrieveInstallment($inst_id);
        if (!$installment['result']) {
            exit(json_encode([
                'status' => 'error',
                'data' => $this->dependencies->l('payplug.abortPayment.cannotAbort', 'paymentclass'),
            ]));
        }

        $installment = $installment['resource'];

        if (1 == $installment->is_live) {
            $new_state = (int) $this->config->get('PS_OS_CANCELED');
        } else {
            $new_state = (int) $this->config->get('PS_OS_CANCELED');
        }

        $order = $this->order->get((int) $id_order);

        if ($this->validate->validate('isLoadedObject', $order)) {
            $current_state = (int) $order->getCurrentState();
            if (0 != $current_state && $current_state !== $new_state) {
                $order_history = $this->orderHistory->get();
                $order_history->id_order = (int) $order->id;
                $order_history->changeIdOrderState($new_state, (int) $order->id, true);
                $order_history->addWithemail();
            }
        }
        $this->dependencies->installmentClass->updatePayplugInstallment($installment);
        $reload = true;

        exit(json_encode(['reload' => $reload]));
    }

    /**
     * @description Assign payment option
     * @unused
     *
     * @param $cart
     *
     * @return bool
     */
    public function assignPaymentOptions($cart)
    {
        $standard = $this->config->get(
            $this->dependencies->getConfigurationKey('standard')
        );
        $one_click = $standard && $this->config->get(
            $this->dependencies->getConfigurationKey('oneClick')
        );
        $installment = $this->config->get(
            $this->dependencies->getConfigurationKey('inst')
        );
        $installment_mode = $this->config->get(
            $this->dependencies->getConfigurationKey('instMode')
        );
        $installment_min_amount = $this->config->get(
            $this->dependencies->getConfigurationKey('instMinAmount')
        );

        if (!$this->dependencies->amountCurrencyClass->checkCurrency($cart)
            || !$this->dependencies->amountCurrencyClass->checkAmount($cart)) {
            return false;
        }

        $payplug_cards = $this->card->getByCustomer((int) $cart->id_customer, true);

        $use_taxes = $this->config->get('PS_TAX');
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

        $this->assign->assign([
            'iso_lang' => $this->context->language->iso_code,
            'price2display' => $price2display,
        ]);

        $front_ajax_url = $this->context->link->getModuleLink($this->dependencies->name, 'ajax', [], true);

        $this->assign->assign([
            'front_ajax_url' => $front_ajax_url,
            'api_url' => $this->dependencies->apiClass->getApiUrl(),
        ]);

        if (!empty($payplug_cards) && 1 == $one_click) {
            $this->assign->assign([
                'payplug_cards' => $payplug_cards,
                'payplug_one_click' => 1,
            ]);
        }

        $payment_url = 'index.php?controller=order&step=3';

        $payment_controller_url = $this->context->link->getModuleLink(
            $this->dependencies->name,
            'payment',
            [],
            true
        );
        $installment_controller_url = $this->context->link->getModuleLink(
            $this->dependencies->name,
            'payment',
            ['i' => 1],
            true
        );
        $current_lang = explode('-', $this->context->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, ['it', 'en'], true)) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        $this->assign->assign([
            'spinner_url' => $this->tools->tool('getHttpHost', true)
                . $this->constant->get(__PS_BASE_URI__)
                . 'modules/' . $this->dependencies->name . '/views/img/admin/spinner.gif',
            'payment_url' => $payment_url,
            'payment_controller_url' => $payment_controller_url,
            'installment_controller_url' => $installment_controller_url,
            'img_lang' => $img_lang,
            'payplug_installment' => $installment,
            'installment_mode' => $installment_mode,
        ]);
    }

    /**
     * @description Build the payment details for order detail block
     *
     * @param $payment
     *
     * @return array|Exception
     */
    public function buildPaymentDetails($payment)
    {
        if (!is_object($payment)) {
            $payment = $this->dependencies->apiClass->retrievePayment($payment);
            if (!$payment['result']) {
                return $payment['message'];
            }
            $payment = $payment['resource'];
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

        $pay_status = $this->getPaymentStatusById($pay_status);

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
            && ('none' !== $card_details['brand'])) {
            $card_brand = $this
                ->dependencies
                ->l('payplug.adminAjaxController.card', 'paymentclass') . ' ' . $card_details['brand'];
        }

        // Card Country
        $card_country = null;
        if ($card_details
            && isset($card_details['country'])
            && ('none' !== $card_details['country'])) {
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
            'amount' => (int) $payment->amount / 100,
            'refunded' => (int) $payment->amount_refunded / 100,
            'card_brand' => $card_brand,
            'card_mask' => $card_mask,
            'card_date' => $card_date,
            'card_country' => $card_country,
            'mode' => ($payment->is_live)
                ? $this->dependencies->l('payplug.buildPaymentDetails.live', 'paymentclass')
                : $this->dependencies->l('payplug.buildPaymentDetails.test', 'paymentclass'),
            'paid' => (bool) $payment->is_paid,
        ];

        //Deferred payment does'nt display 3DS option before capture so we have to consider it null
        if (null !== $payment->is_3ds) {
            $payment_details['tds'] = ($payment->is_3ds)
                ? $this->dependencies->l('payplug.buildPaymentDetails.yes', 'paymentclass')
                : $this->dependencies->l('payplug.buildPaymentDetails.no', 'paymentclass');
        }

        $is_oney = false;
        $is_amex = false;
        $is_bancontact = false;
        if (isset($payment->payment_method, $payment->payment_method['type'])) {
            switch ($payment->payment_method['type']) {
                case 'oney_x3_with_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies->l('payplug.buildPaymentDetails.oneyX3WithFees', 'paymentclass');

                    break;

                case 'oney_x4_with_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies->l('payplug.buildPaymentDetails.oneyX4WithFees', 'paymentclass');

                    break;

                case 'oney_x3_without_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies->l('payplug.buildPaymentDetails.oneyX3WithoutFees', 'paymentclass');

                    break;

                case 'oney_x4_without_fees':
                    $is_oney = true;
                    $payment_details['type'] = $this->dependencies->l('payplug.buildPaymentDetails.oneyX4WithoutFees', 'paymentclass');

                    break;

                case 'bancontact':
                    $is_bancontact = true;
                    $payment_details['type'] = $this->dependencies->l('payplug.buildPaymentDetails.bancontact', 'paymentclass');

                    break;

                case 'apple_pay':
                    $payment_details['type'] = $this->dependencies->l('payplug.buildPaymentDetails.applepay', 'paymentclass');

                    break;

                case 'american_express':
                    $is_amex = true;
                    $payment_details['type'] = $this->dependencies->l('payplug.buildPaymentDetails.amex', 'paymentclass');

                    break;

                default:
                    $payment_details['type'] = $payment->payment_method['type'];
            }
            $payment_details['type_code'] = $payment->payment_method['type'];
        }

        $is_paid = $this->validators['payment']->isPaid($payment)['result'];
        $can_be_captured = $this->validators['payment']->isPayment($payment)['result']
                            && !$this->validators['payment']->isFailed($payment)['result']
                            && !$is_paid
                            && $this->validators['payment']->isDeferred($payment)['result']
                            && !$this->validators['payment']->isExpired($payment)['result'];
        $payment_details['can_be_captured'] = $can_be_captured;

        if (null !== $payment->authorization && !$is_oney) {
            $payment_details['authorization'] = true;
            if ($is_paid) {
                $payment_details['date'] = date('d/m/Y', $payment->paid_at);
                if (!isset($payment_details['type'])) {
                    $payment_details['status_message'] = '(' . $this
                        ->dependencies
                        ->l('payplug.buildPaymentDetails.deferred', 'paymentclass') . ')';
                }
            } else {
                $expiration = date('d/m/Y', $payment->authorization->expires_at);
                if ($can_be_captured) {
                    $payment_details['status_message'] = sprintf(
                        '(' . $this
                            ->dependencies
                            ->l('payplug.buildPaymentDetails.captureAuthorizedBefore', 'paymentclass') . ')',
                        $expiration
                    );
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                    $payment_details['date_expiration'] = $expiration;
                    $payment_details['expiration_display'] = sprintf(
                        $this->dependencies->l('payplug.buildPaymentDetails.captureAuthorizedBeforeWarning', 'paymentclass'),
                        $expiration
                    );
                } elseif (isset($payment->authorization->authorized_at)
                    && null != $payment->authorization->authorized_at
                ) {
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                }
            }
        } else {
            $payment_details['authorization'] = false;
            $payment_details['date'] = date('d/m/Y', $payment->created_at);
        }

        if ($this->validators['payment']->isFailed($payment)['result']) {
            $payment_details['error'] = '(' . $payment->failure->message . ')';
        }

        if ($is_oney) {
            unset($payment_details['card_brand'], $payment_details['card_mask'], $payment_details['card_date']);
        }
        if ($is_bancontact || $is_amex) {
            unset($payment_details['tds'], $payment_details['card_brand']);
        }

        return $payment_details;
    }

    /**
     * @description Capture the payment
     */
    public function capturePayment()
    {
        $this->logger->addLog('[Payplug] Start capture', 'notice');
        $pay_id = $this->tools->tool('getValue', 'pay_id');
        $id_order = $this->tools->tool('getValue', 'id_order');

        $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
        if (!$payment['result']) {
            $sandbox = (bool) $this->config->get($this->dependencies->getConfigurationKey('sandboxMode'));
            if ($sandbox) {
                $this->dependencies->apiClass->initializeApi(false);
                $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
            } else {
                $this->dependencies->apiClass->initializeApi(true);
                $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
            }

            if (!$payment['result']) {
                exit(json_encode([
                    'status' => 'error',
                    'data' => $this->dependencies->l('payplug.capturePayment.cannotCapture', 'paymentclass'),
                    'message' => $payment['message'],
                ]));
            }
        }

        $capture = $this->dependencies->apiClass->capturePayment($payment['resource']->id);
        if (!$capture['result']) {
            exit(json_encode([
                'status' => 'error',
                'data' => $this->dependencies->l('payplug.capturePayment.cannotCapture', 'paymentclass'),
                'message' => $capture['message'],
            ]));
        }

        $payment = $capture['resource'];

        if (null !== $payment->card->id) {
            $this->logger->addLog('Save the payment card', 'notice');
            $this->card->saveCard($payment);
        }

        $state_addons = ($payment->is_live ? '' : '_TEST');
        $new_state = (int) $this->config->get(
            $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID') . $state_addons
        );

        $order = $this->order->get((int) $id_order);
        if ($this->validate->validate('isLoadedObject', $order)) {
            if (!$this->dependencies->cartClass->createLockFromCartId((int) $order->id_cart)) {
                $this->logger->addLog('An error occured on lock creation', 'notice');

                exit(json_encode([
                    'status' => 'error',
                    'data' => $this->dependencies->l('payplug.capturePayment.errorOccurred', 'paymentclass'),
                ]));
            }

            $current_state = (int) $order->getCurrentState();
            $this->logger->addLog('Current order state: ' . $current_state, 'notice');
            if (0 != $current_state && $current_state != $new_state) {
                $order_history = $this->orderHistory->get();
                $order_history->id_order = (int) $order->id;
                $this->logger->addLog('New order state: ' . $new_state, 'notice');
                $order_history->changeIdOrderState($new_state, (int) $order->id, true);
                $order_history->addWithemail();
            }

            if (!$this->dependencies->cartClass->deleteLockFromCartId((int) $order->id_cart)) {
                $this->logger->addLog('Lock cannot be deleted.', 'error');
            } else {
                $this->logger->addLog('Lock deleted.', 'notice');
            }
        }

        exit(json_encode([
            'status' => 'ok',
            'data' => '',
            'message' => $this->dependencies->l('payplug.capturePayment.captured.', 'paymentclass'),
            'reload' => true,
        ]));
    }

    /**
     * @description Display payment errors messages template
     *
     * @param array $errors
     *
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
            if (false !== strpos($error, 'oney_required_field')) {
                $this->assign->assign(['is_popin_tpl' => true]);
                $fields = $this->oney->getOneyRequiredFields();
                $this->assign->assign([
                    'oney_type' => str_replace('oney_required_field_', '', $error),
                    'oney_required_fields' => $fields,
                ]);
                $formated[] = [
                    'type' => 'template',
                    'value' => 'oney/required.tpl',
                ];
            } else {
                $with_msg_button = true;
                $formated[] = [
                    'type' => 'string',
                    'value' => $error,
                ];
            }
        }

        $this->assign->assign([
            'is_error_message' => true,
            'messages' => $formated,
            'with_msg_button' => $with_msg_button,
        ]);

        return $this->dependencies->configClass->fetchTemplate('_partials/messages.tpl');
    }

    /**
     * @description Get the payment method for a given payment card
     * @unused
     *
     * @param string $card
     *
     * @return object PayPlugPaymentStandard|PayPlugPaymentInstallment|PayPlugPaymentOneClick|PayPlugPaymentOney
     */
    public function getCurrentPaymentMethod($card = null)
    {
        $card = null != $card ? $card : $this->tools->tool('getValue', 'pc', null);

        // check if is Installment
        if ($this->tools->tool('getValue', 'io') || 'oney' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentOney';
        } elseif ($this->tools->tool('getValue', 'i') || 'installment' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentInstallment';
        } elseif ((null != $card && 'new_card' != $card) || 'oneclick' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentOneClick';
        } elseif ('standard' == $this->tools->tool('getValue', 'type')) {
            $payment_method = 'PayPlugPaymentStandard';
        } else {
            $payment_method = 'PayPlugPaymentStandard';
        }

        return $payment_method;
    }

    /**
     * @description ONLY FOR VALIDATION
     * Retrieve payment stored
     *
     * @param int $id_cart
     *
     * @return string
     */
    public function getPaymentByCart($id_cart)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return '';
        }

        $payment = $this->dependencies
            ->getRepositories()['payment']
            ->getByCart((int) $id_cart);

        if (!$payment) {
            return '';
        }

        return 'installment' != $payment['payment_method'] ? $payment['id_payment'] : '';
    }

    /**
     * @description Get payment data from cookie
     *
     * @return mixed
     */
    public function getPaymentDataCookie()
    {
        // get payplug data
        $cookie_data = $this->context->cookie->__get($this->dependencies->name . '_data');
        $payplug_data = !empty($cookie_data) ? $cookie_data : false;

        // then flush to avoid repetition
        $this->context->cookie->__set($this->dependencies->name . '_data', '');

        // if no error all good then return true
        return json_decode($payplug_data, true);
    }

    /**
     * @description Get payment errors from cookie
     *
     * @return mixed
     */
    public function getPaymentErrorsCookie()
    {
        // get payplug errors
        $cookie_errors = $this->context->cookie->__get($this->dependencies->name . 'Errors');
        $payplug_errors = !empty($cookie_errors) ? $cookie_errors : false;

        // then flush to avoid repetition
        $this->context->cookie->__set($this->dependencies->name . 'Errors', '');

        // if no error all good then return true
        return json_decode($payplug_errors, true);
    }

    /**
     * @description Check payment method for given cart object
     *
     * @param object Cart
     * @param mixed $cart
     *
     * @return array|bool pay_id or inst_id or False
     */
    public function getPaymentMethodByCart($cart)
    {
        if (!is_object($cart)) {
            $cart = $this->cart->get((int) $cart);
        }

        if (!$this->validate->validate('isLoadedObject', $cart)) {
            return false;
        }

        $inst_id = $this->dependencies->installmentClass->getInstallmentByCart($cart->id);
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
     * @description Get the status name for a given status id
     *
     * @param $id_status
     *
     * @return mixed
     */
    public function getPaymentStatusById($id_status)
    {
        $paymentStatus = $this->dependencies->configClass->getPaymentStatus();

        return $paymentStatus[$id_status];
    }

    /**
     * @description Get the status for a given payment
     *
     * @param $payment
     *
     * @return int
     */
    public function getPaymentStatusByPayment($payment)
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
            $payment = $this->dependencies->apiClass->retrievePayment($payment);
            if (!$payment['result']) {
                return false;
            }
            $payment = $payment['resource'];
        }

        if (null !== $payment->installment_plan_id) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($payment->installment_plan_id);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        } else {
            $installment = null;
        }

        $pay_status = 1; //not paid
        if (1 == (int) $payment->is_paid) {
            $pay_status = 2; //paid
        } elseif (isset($payment->payment_method, $payment->payment_method['is_pending'])
            && 1 == (int) $payment->payment_method['is_pending']
        ) {
            $pay_status = 10; //oney pending
        } elseif ($this->validators['payment']->isFailed($payment)['result'] && 9 != $pay_status) {
            if ('aborted' == $payment->failure->code) {
                $pay_status = 7; //cancelled
            } elseif ('timeout' == $payment->failure->code) {
                $pay_status = 11; //abandoned
            } else {
                $pay_status = 3; //failed
            }
        } elseif (null !== $payment->authorization && ($payment->authorization->expires_at - time()) > 0) {
            $pay_status = 8; //authorized
        } elseif (null !== $payment->authorization && ($payment->authorization->expires_at - time()) <= 0) {
            $pay_status = 9; //authorization expired
        } elseif (null !== $payment->installment_plan_id && 1 == (int) $installment->is_active) {
            $pay_status = 6; //ongoing
        }
        if (1 == (int) $payment->is_refunded) {
            $pay_status = 5; //refunded
        } elseif ((int) $payment->amount_refunded > 0) {
            $pay_status = 4; //partially refunded
        }

        return $pay_status;
    }

    /**
     * @description Prepare the tab to create the payment resource
     * prepare payment
     *
     * @param $options
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function preparePayment($options)
    {
        if (!$this->validate->validate('isLoadedObject', $this->context->cart)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->dependencies->l('payplug.preparePayment.transactionNotCompleted', 'paymentclass'),
            ];
        }

        $cart = $this->context->cart;

        $default_options = [
            'id_card' => 'new_card',
            'is_installment' => false,
            'is_deferred' => false,
            'is_oney' => false,
            'is_integrated' => false,
            'is_bancontact' => false,
            'is_applepay' => false,
            'is_amex' => false,
            'is_giropay' => false,
            'is_ideal' => false,
            'is_mybank' => false,
            'is_satispay' => false,
            'is_sofort' => false,
        ];

        foreach ($default_options as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }

        $customer = $this->customer->get((int) $cart->id_customer);
        if (!$this->validate->validate('isLoadedObject', $customer)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->dependencies->l('payplug.preparePayment.transactionNotCompleted', 'paymentclass'),
            ];
        }

        $is_sandbox = (int) $this->config->get(
            $this->dependencies->getConfigurationKey('sandboxMode')
        );

        // get the config
        $payment_methods = json_decode($this->configuration->getValue('payment_methods'));
        $config = [
            'one_click' => (bool) $payment_methods->one_click,
            'installment' => (bool) $payment_methods->inst,
            'company' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('companyId') . ($is_sandbox ? '_TEST' : '')
            ),
            'inst_mode' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('instMode')
            ),
            'deferred' => (bool) $payment_methods->deferred,
            'oney' => (bool) $payment_methods->oney,
            'standard' => (bool) $payment_methods->standard,
            'bancontact' => (bool) $payment_methods->bancontact,
            'applepay' => (bool) $payment_methods->applepay,
            'amex' => (bool) $payment_methods->amex,
            'giropay' => (bool) $payment_methods->giropay,
            'ideal' => (bool) $payment_methods->ideal,
            'mybank' => (bool) $payment_methods->mybank,
            'satispay' => (bool) $payment_methods->satispay,
            'sofort' => (bool) $payment_methods->sofort,
        ];

        $is_one_click = 'new_card' != $options['id_card'] && $config['one_click'];
        $options['is_installment'] = $options['is_installment'] && $config['installment'];
        $options['is_bancontact'] = $options['is_bancontact'] && $config['bancontact'];
        $options['is_applepay'] = $options['is_applepay'] && $config['applepay'];
        $options['is_amex'] = $options['is_amex'] && $config['amex'];
        $options['is_giropay'] = $options['is_giropay'] && $config['giropay'];
        $options['is_ideal'] = $options['is_ideal'] && $config['ideal'];
        $options['is_mybank'] = $options['is_mybank'] && $config['mybank'];
        $options['is_satispay'] = $options['is_satispay'] && $config['satispay'];
        $options['is_sofort'] = $options['is_sofort'] && $config['sofort'];

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
        } elseif ($options['is_applepay']) {
            $payment_method = 'apple_pay';
        } elseif ($options['is_amex']) {
            $payment_method = 'amex';
        } elseif ($options['is_giropay']) {
            $payment_method = 'giropay';
        } elseif ($options['is_ideal']) {
            $payment_method = 'ideal';
        } elseif ($options['is_mybank']) {
            $payment_method = 'mybank';
        } elseif ($options['is_satispay']) {
            $payment_method = 'satispay';
        } elseif ($options['is_sofort']) {
            $payment_method = 'sofort';
        } else {
            $payment_method = 'standard';
        }

        // Build payment Tab

        // Currency
        $currency = $this->currency->get((int) $cart->id_currency);
        $supported_currencies = explode(';', $this->config->get(
            $this->dependencies->getConfigurationKey('currencies')
        ));
        $currency_iso_code = $currency->iso_code;

        // if unvalid iso code, return false
        if (!in_array($currency_iso_code, $supported_currencies, true)) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->dependencies->l('payplug.preparePayment.transactionNotCompleted', 'paymentclass'),
            ];
        }

        // Amount
        $cart_amount = $cart->getOrderTotal(true);
        $amount = $this->dependencies->amountCurrencyClass->convertAmount($cart_amount);
        $current_amounts = $this->dependencies->amountCurrencyClass->getAmountsByCurrency($currency_iso_code);
        $is_valid_amount = $this->validators['payment']->isAmount(
            $amount,
            [
                'min' => $current_amounts['min_amount'],
                'max' => $current_amounts['max_amount'],
            ]
        );
        if (!$is_valid_amount['result']) {
            // todo: add error log
            return [
                'result' => false,
                'response' => $this->dependencies->l('payplug.preparePayment.transactionNotCompleted', 'paymentclass'),
            ];
        }

        // Hosted url
        $hosted_url = [
            'return' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'validation',
                ['ps' => 1, 'cartid' => (int) $cart->id],
                true
            ),
            'cancel' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'validation',
                ['ps' => 2, 'cartid' => (int) $cart->id],
                true
            ),
            'notification' => $this->context->link->getModuleLink($this->dependencies->name, 'ipn', [], true),
        ];

        // Meta data
        $metadata = [
            'ID Client' => (int) $customer->id,
            'ID Cart' => (int) $cart->id,
            'Website' => $this->tools->tool('getShopDomainSsl', true, false),
        ];

        // Addresses
        $billing_address = $this->address->get((int) $cart->id_address_invoice);
        $shipping_address = $this->address->get((int) $cart->id_address_delivery);

        // ISO
        $billing_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $billing_address->id_country);
        $shipping_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $shipping_address->id_country);

        if (!$shipping_iso || !$billing_iso) {
            $default_language = $this->language->get((int) $this->config->get('PS_LANG_DEFAULT'));
            $iso_code_list = $this->dependencies->configClass->getIsoCodeList();
            if (in_array($this->tools->tool('strtoupper', $default_language->iso_code), $iso_code_list, true)) {
                $iso_code = $this->tools->tool('strtoupper', $default_language->iso_code);
            } else {
                $iso_code = 'FR';
            }
            if (!$shipping_iso) {
                $metadata['cms_shipping_country'] = $this
                    ->dependencies
                    ->configClass
                    ->getIsoCodeByCountryId((int) $shipping_address->id_country);
                $shipping_iso = $iso_code;
            }
            if (!$billing_iso) {
                $metadata['cms_billing_country'] = $this
                    ->dependencies
                    ->configClass
                    ->getIsoCodeByCountryId((int) $billing_address->id_country);
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
            'landline_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                $billing_address->phone,
                $billing_address->id_country
            ),
            'mobile_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                $billing_address->phone_mobile,
                $billing_address->id_country
            ),
            'address1' => !empty($billing_address->address1) ? $billing_address->address1 : null,
            'address2' => !empty($billing_address->address2) ? $billing_address->address2 : null,
            'postcode' => !empty($billing_address->postcode) ? $billing_address->postcode : null,
            'city' => !empty($billing_address->city) ? $billing_address->city : null,
            'country' => $billing_iso,
            'language' => $this->dependencies->configClass->getIsoFromLanguageCode($this->context->language),
        ];
        $billing['company_name'] = empty($billing['company_name']) || !$billing['company_name']
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
            'landline_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                $shipping_address->phone,
                $shipping_address->id_country
            ),
            'mobile_phone_number' => $this->dependencies->configClass->formatPhoneNumber(
                $shipping_address->phone_mobile,
                $shipping_address->id_country
            ),
            'address1' => !empty($shipping_address->address1) ? $shipping_address->address1 : null,
            'address2' => !empty($shipping_address->address2) ? $shipping_address->address2 : null,
            'postcode' => !empty($shipping_address->postcode) ? $shipping_address->postcode : null,
            'city' => !empty($shipping_address->city) ? $shipping_address->city : null,
            'country' => $shipping_iso,
            'language' => $this->dependencies->configClass->getIsoFromLanguageCode($this->context->language),
            'delivery_type' => $delivery_type,
        ];
        $shipping['company_name'] = empty($shipping['company_name']) || !$shipping['company_name']
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
            && 1 != $this->cart->isGuestCartByCartId($cart->id)
            && 'new_card' == $options['id_card'];

        $payment_tab = [
            'currency' => $currency_iso_code,
            'shipping' => $shipping,
            'billing' => $billing,
            'notification_url' => $hosted_url['notification'],
            'force_3ds' => $force_3ds,
            'hosted_payment' => [
                'return_url' => $hosted_url['return'],
                'cancel_url' => $hosted_url['cancel'],
            ],
            'metadata' => $metadata,
            'allow_save_card' => $allow_save_card,
        ];

        $can_deferred_payment = !$options['is_installment'] && !$options['is_bancontact'];
        if (($options['is_deferred'] || $options['is_oney']) && $can_deferred_payment) {
            $payment_tab['authorized_amount'] = $amount;
        } else {
            $payment_tab['amount'] = $amount;
        }

        // check payment tab from current payment method
        if ($options['is_installment']) {
            // remove useless field from payment table
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card'], $payment_tab['amount'], $payment_tab['authorized_amount']);

            // then add schedule
            $schedule = [];
            for ($i = 0; $i < $config['inst_mode']; ++$i) {
                if (0 == $i) {
                    $schedule[$i]['date'] = 'TODAY';
                    $int_part = (int) ($amount / $config['inst_mode']);
                    $schedule[$i]['amount'] = (int) ($int_part + ($amount - ($int_part * $config['inst_mode'])));
                } else {
                    $delay = $i * 30;
                    $schedule[$i]['date'] = date('Y-m-d', strtotime("+ {$delay} days"));
                    $schedule[$i]['amount'] = (int) ($amount / $config['inst_mode']);
                }
            }
            $payment_tab['schedule'] = $schedule;
        } elseif ($is_one_click) {
            $payment_tab['initiator'] = 'PAYER';
            $payment_tab['payment_method'] = null;
            if ($options['id_card'] && 'new_card' != $options['id_card']) {
                $card = $this->dependencies->getRepositories()['card']->get((int) $options['id_card']);
                if ($card['id_customer'] != $customer->id) {
                    return [
                        'result' => false,
                        'response' => 'Card customer differs from cart customer',
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
            $is_valid_phone = $this
                ->validators['payment']
                ->isPhoneNumber($payment_tab['billing']['mobile_phone_number'])['result'];
            if (!$is_valid_phone || !$this->dependencies->configClass->isValidMobilePhoneNumber(
                $payment_tab['billing']['country'],
                $payment_tab['billing']['mobile_phone_number']
            )) {
                $is_valid_phone = $this
                    ->validators['payment']
                    ->isPhoneNumber($payment_tab['billing']['landline_phone_number'])['result'];

                if ($is_valid_phone && $this->dependencies->configClass->isValidMobilePhoneNumber(
                    $payment_tab['billing']['country'],
                    $payment_tab['billing']['landline_phone_number']
                )) {
                    $payment_tab['billing']['mobile_phone_number'] = $payment_tab['billing']['landline_phone_number'];
                }
            }

            // check shipping phonenumber
            $is_valid_phone = $this
                ->validators['payment']
                ->isPhoneNumber($payment_tab['shipping']['mobile_phone_number'])['result'];
            if (!$is_valid_phone || !$this->dependencies->configClass->isValidMobilePhoneNumber(
                $payment_tab['shipping']['country'],
                $payment_tab['shipping']['mobile_phone_number']
            )) {
                $is_valid_phone = $this
                    ->validators['payment']
                    ->isPhoneNumber($payment_tab['shipping']['landline_phone_number'])['result'];
                if ($is_valid_phone && $this->dependencies->configClass->isValidMobilePhoneNumber(
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
                    $payment_data = $this->tools->tool('getValue', 'oney_form');
                }

                if ((bool) $payment_data) {
                    // hydrate with payment data
                    $payment_tab = $this->hydratePaymentTabFromPaymentData($payment_tab, $payment_data);

                    // then recheck
                    if ($this->oney->hasOneyRequiredFields($payment_tab)) {
                        $this->setPaymentErrorsCookie(['oney_required_field_' . $options['is_oney']]);

                        return [
                            'result' => false,
                            'response' => $this->dependencies->l('payplug.preparePayment.fieldsNotCompleted', 'paymentclass'),
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

            $return_url_params = ['ps' => 1, 'cartid' => (int) $cart->id, 'isoney' => $options['is_oney']];
            $return_url = $this->context->link->getModuleLink(
                $this->dependencies->name,
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
            $payment_tab['payment_method'] = 'bancontact';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_giropay']) {
            $payment_tab['payment_method'] = 'giropay';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_ideal']) {
            $payment_tab['payment_method'] = 'ideal';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_mybank']) {
            $payment_tab['payment_method'] = 'mybank';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_satispay']) {
            $payment_tab['payment_method'] = 'satispay';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_sofort']) {
            $payment_tab['payment_method'] = 'sofort';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_giropay']) {
            $payment_tab['payment_method'] = 'giropay';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_ideal']) {
            $payment_tab['payment_method'] = 'ideal';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_mybank']) {
            $payment_tab['payment_method'] = 'mybank';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_satispay']) {
            $payment_tab['payment_method'] = 'satispay';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_sofort']) {
            $payment_tab['payment_method'] = 'sofort';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
        }

        if ($options['is_applepay']) {
            $payment_tab['payment_method'] = 'apple_pay';
            $payment_tab['payment_context'] = [
                'apple_pay' => [
                    'domain_name' => $this->context->shop->domain_ssl,
                    'application_data' => base64_encode(json_encode([
                        'apple_pay_domain' => $this->context->shop->domain_ssl,
                    ])),
                ],
            ];
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card'], $payment_tab['shipping']['delivery_type']);
        }

        if ($options['is_amex']) {
            $payment_tab['payment_method'] = 'american_express';
            unset($payment_tab['force_3ds'], $payment_tab['allow_save_card']);
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
            'isEmbedded' => 'redirect' !== (string) $this->config->get(
                $this->dependencies->getConfigurationKey('embeddedMode')
            ),
            'isIntegrated' => $options['is_integrated'],
            'isMobileDevice' => ($this->validators['browser']->isMobileDevice($_SERVER['HTTP_USER_AGENT'])['result']),
            'cart' => $cart,
            'cartId' => (int) $payment_tab['metadata']['ID Cart'],
            'cartHash' => null,
            'oneyDetails' => isset($options['is_oney']) ? $options['is_oney'] : null,
        ];

        // Create payment if inexistent
        $force_payment_creation = $options['is_applepay'] || $options['is_oney'];
        $payment = $this->dependencies
            ->getRepositories()['payment']
            ->getByCart((int) $cart->id);

        if (empty($payment) || $force_payment_creation) {
            // Create payment or installment
            $createPayment = $this->payment->createPayment($this->paymentDetails);

            if ($createPayment['result'] && $createPayment['paymentDetails']) {
                $this->paymentDetails = $createPayment['paymentDetails'];
            } elseif (!$createPayment['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $createPayment['paymentDetails'],
                    'response' => $createPayment['response'],
                ];
            }

            // Insert payment to paymentTable
            $insertPaymentTable = $this->payment->insertPaymentTable($this->paymentDetails);
            if ($insertPaymentTable['result'] && $insertPaymentTable['paymentDetails']) {
                $this->paymentDetails = $insertPaymentTable['paymentDetails'];
            } elseif (!$insertPaymentTable['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $this->paymentDetails,
                    'response' => $insertPaymentTable['response'],
                ];
            }

            if ($options['is_applepay']) {
                return $createPayment;
            }

            // Generate the return URL
            $getpaymentReturnUrl = $this->payment->getPaymentReturnUrl($this->paymentDetails);
            if ($getpaymentReturnUrl['result'] && $getpaymentReturnUrl['url']) {
                return $getpaymentReturnUrl['url'];
            }
            if (!$getpaymentReturnUrl['result']) {
                return [
                    'result' => false,
                    'url' => $getpaymentReturnUrl['url'],
                    'response' => $getpaymentReturnUrl['response'],
                ];
            }
        } elseif (!$this->payment->checkTimeoutPayment($cart->id)) {
            // If payment already exists, and timeout > 3 min : Create a new payment

            // Create payment or installment
            $createPayment = $this->payment->createPayment($this->paymentDetails);
            if ($createPayment['result'] && $createPayment['paymentDetails']) {
                $this->paymentDetails = $createPayment['paymentDetails'];
            } elseif (!$createPayment['result']) {
                return [
                    'result' => false,
                    'paymentDetails' => $createPayment['paymentDetails'],
                    'response' => $createPayment['response'],
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
                    'response' => $updatePaymentTable['response'],
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
                    'response' => $checkHash['response'],
                ];
            }

            $getpaymentReturnUrl = $this->payment->getPaymentReturnUrl($this->paymentDetails);
            if ($getpaymentReturnUrl['result'] && $getpaymentReturnUrl['url']) {
                return $getpaymentReturnUrl['url'];
            }
            if (!$getpaymentReturnUrl['result']) {
                return [
                    'result' => false,
                    'url' => $getpaymentReturnUrl['url'],
                    'response' => $getpaymentReturnUrl['response'],
                ];
            }
        } elseif ($this->payment->checkTimeoutPayment($cart->id)
            && $this->payment->checkHash($this->paymentDetails)
            && $this->payment->isValidApiPayment($this->paymentDetails)) {
            // If timeout < 3 min and hash OK
            $store_payment = $this->dependencies
                ->getRepositories()['payment']
                ->getByCart((int) $cart->id);
            $this->paymentDetails['paymentId'] = $store_payment['id_payment'];

            $getpaymentReturnUrl = $this->payment->getPaymentReturnUrl($this->paymentDetails);

            if ($getpaymentReturnUrl['result'] && isset($getpaymentReturnUrl['url']) && $getpaymentReturnUrl['url']) {
                return $getpaymentReturnUrl['url'];
            }
            if (!$getpaymentReturnUrl['result']) {
                return [
                    'result' => false,
                    'url' => $getpaymentReturnUrl['url'],
                    'response' => $getpaymentReturnUrl['response'],
                ];
            }
        }
    }

    /**
     * @description Register transaction as pending to etablish link with order in case of error
     *
     * @param int $id_cart
     *
     * @return bool
     */
    public function registerPendingTransaction($id_cart = false)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return false;
        }

        return $this->query
            ->update()
            ->table($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->set('is_pending = 1')
            ->where('id_cart = ' . (int) $id_cart)
            ->build()
        ;
    }

    /**
     * @description Set payment data in cookie
     *
     * @param mixed $payplug_data
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function setPaymentDataCookie($payplug_data = [])
    {
        if (empty($payplug_data)) {
            return false;
        }

        $value = json_encode($payplug_data);

        $this->context->cookie->__set($this->dependencies->name . '_data', $value);

        return (bool) $this->context->cookie->__get($this->dependencies->name . '_data');
    }

    /**
     * @description Set payment errors in cookie
     *
     * @param array $payplug_errors
     *
     * @throws Exception
     *
     * @return mixed
     */
    public function setPaymentErrorsCookie($payplug_errors = [])
    {
        if (empty($payplug_errors)) {
            return false;
        }

        // Check if already setted
        if ((bool) $this->context->cookie->__get($this->dependencies->name . 'Errors')) {
            return true;
        }

        $value = json_encode($payplug_errors);

        $this->context->cookie->__set($this->dependencies->name . 'Errors', $value);

        return (bool) $this->context->cookie->__get($this->dependencies->name . 'Errors');
    }

    /**
     * @description update payment ressource
     *
     * @param $pay_id
     * @param $order_id
     */
    public function updatePayment($pay_id, $order_id)
    {
        $payment = $this->dependencies->apiClass->retrievePayment($pay_id);

        if (!$payment['result']) {
            exit(json_encode([
                                'data' => $this->dependencies->l('payplug.adminPayplugController.errorOccurred', 'paymentclass'),
                                'status' => 'error',
                            ]));
        }
        $payment = $payment['resource'];

        $state_addons = ($payment->is_live ? '' : '_TEST');
        if (1 == (int) $payment->is_paid) {
            $new_state = (int) $this->config->get(
                $this->dependencies->concatenateModuleNameTo('ORDER_STATE_PAID') . $state_addons
            );
        } elseif (0 == (int) $payment->is_paid) {
            if (1 == $payment->is_live) {
                $new_state = (int) $this->dependencies->getPlugin()->getConfiguration()->get(
                    $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ERROR')
                );
            } else {
                $new_state = $this->dependencies->concatenateModuleNameTo('ORDER_STATE_ERROR') . $state_addons;
            }
        }

        $order = $this->order->get((int) $order_id);

        if ($this->validate->validate('isLoadedObject', $order)) {
            $current_state = (int) $order->getCurrentState();
            if (0 != $current_state && $current_state != $new_state) {
                $history = $this->orderHistory->get();
                $history->id_order = (int) $order->id;
                $history->changeIdOrderState($new_state, (int) $order->id, true);
                $history->addWithemail();
                $this->logger->addLog('Change order state to ' . $new_state, 'notice');
            }
        }

        exit(json_encode([
                            'message' => $this->dependencies->l('payplug.adminPayplugController.orderUpdated', 'paymentclass'),
                            'reload' => true,
                        ]));
    }

    /**
     * @description Hydrate Oney Payment Tab from Cookie Payment Data
     *
     * @param array $payment_tab
     * @param array $payment_data
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     *
     * @return array
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

            if (false != strpos($field_name, 'phone')) {
                switch ($type) {
                    case 'billing':
                        $id_country = $this->country->getByIso($payment_tab['billing']['country']);
                        $country = $this->country->get((int) $id_country);
                        $field = $this->dependencies->configClass->formatPhoneNumber($field, $country);

                        break;

                    case 'same':
                    case 'shipping':
                    default:
                        $id_country = $this->country->getByIso($payment_tab['shipping']['country']);
                        $country = $this->country->get($id_country);
                        $field = $this->dependencies->configClass->formatPhoneNumber($field, $country);

                        break;
                }
            }

            if ('email' == $field_name) {
                $payment_tab['billing']['email'] = $field;
                $payment_tab['shipping']['email'] = $field;
            } elseif ('same' == $type) {
                $payment_tab['billing'][$field_name] = $field;
                $payment_tab['shipping'][$field_name] = $field;
            } else {
                $payment_tab[$type][$field_name] = $field;
            }
        }

        return $payment_tab;
    }
}
