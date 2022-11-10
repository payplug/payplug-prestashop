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

class PaymentClass
{
    private $address;
    private $assign;
    private $card;
    private $cart;
    private $config;
    private $constant;
    private $context;
    private $country;
    private $currency;
    private $customer;
    private $dependencies;
    private $language;
    private $logger;
    private $oney;
    private $order;
    private $orderHistory;
    private $payment;
    private $query;
    private $tools;
    private $validate;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->address = $this->dependencies->getPlugin()->getAddress();
        $this->assign = $this->dependencies->getPlugin()->getAssign();
        $this->card = $this->dependencies->getPlugin()->getCard();
        $this->cart = $this->dependencies->getPlugin()->getCart();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->country = $this->dependencies->getPlugin()->getCountry();
        $this->currency = $this->dependencies->getPlugin()->getCurrency();
        $this->customer = $this->dependencies->getPlugin()->getCustomer();
        $this->language = $this->dependencies->getPlugin()->getLanguage();
        $this->logger = $this->dependencies->getPlugin()->getLogger();
        $this->module = $this->dependencies->getPlugin()->getModule();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->order = $this->dependencies->getPlugin()->getOrder();
        $this->orderHistory = $this->dependencies->getPlugin()->getOrderHistory();
        $this->payment = $this->dependencies->getPlugin()->getPayment();
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->tools = $this->dependencies->getPlugin()->getTools();
        $this->validate = $this->dependencies->getPlugin()->getValidate();
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

        if ($installment->is_live == 1) {
            $new_state = (int) $this->config->get('PS_OS_CANCELED');
        } else {
            $new_state = (int) $this->config->get('PS_OS_CANCELED');
        }

        $order = $this->order->get((int) $id_order);

        if ($this->validate->validate('isLoadedObject', $order)) {
            $current_state = (int) $order->getCurrentState();
            if ($current_state != 0 && $current_state !== $new_state) {
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

        if (!empty($payplug_cards) && $one_click == 1) {
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
            && ($card_details['brand'] !== 'none')) {
            $card_brand = $this
                ->dependencies
                ->l('payplug.adminAjaxController.card', 'paymentclass') . ' ' . $card_details['brand'];
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
        if ($payment->is_3ds !== null) {
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

        if ($payment->authorization !== null && !$is_oney) {
            $payment_details['authorization'] = true;
            if ($payment->is_paid) {
                $payment_details['date'] = date('d/m/Y', $payment->paid_at);
                $payment_details['can_be_cancelled'] = false;
                $payment_details['can_be_captured'] = false;
                if (!isset($payment_details['type'])) {
                    $payment_details['status_message'] = '(' . $this
                        ->dependencies
                        ->l('payplug.buildPaymentDetails.deferred', 'paymentclass') . ')';
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
                            '(' . $this
                                ->dependencies
                                ->l('payplug.buildPaymentDetails.captureAuthorizedBefore', 'paymentclass') . ')',
                            $expiration
                        );
                    }
                    $payment_details['date'] = date('d/m/Y', $payment->authorization->authorized_at);
                    $payment_details['date_expiration'] = $expiration;
                    $payment_details['expiration_display'] = sprintf(
                        $this->dependencies->l('payplug.buildPaymentDetails.captureAuthorizedBeforeWarning', 'paymentclass'),
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

        if (isset($payment->failure, $payment->failure->message)) {
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
     * @description Delete stored payment
     * @unused
     *
     * @param int    $cart_id
     * @param string $pay_id
     *
     * @return bool
     */
    public function deletePayment($cart_id, $pay_id = '')
    {
        $this->query
            ->delete()
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('id_cart = ' . (int) $cart_id)
        ;

        if ($pay_id != '') {
            $this->query->where('id_payment = "' . $this->query->escape($pay_id) . '"');
        }

        return $this->query->build();
    }

    /**
     * @description Capture the payment
     */
    public function capturePayment()
    {
        $this->logger->addLog('[Payplug] Start capture', 'notice');
        $pay_id = $this->tools->tool('getValue', 'pay_id');
        $id_order = $this->tools->tool('getValue', 'id_order');

        $capture = $this->dependencies->apiClass->capturePayment($pay_id);
        if (!$capture['result']) {
            exit(json_encode([
                'status' => 'error',
                'data' => $this->dependencies->l('payplug.capturePayment.cannotCapture', 'paymentclass'),
                'message' => $capture['message'],
            ]));
        }

        $payment = $capture['resource'];

        if ($payment->card->id !== null) {
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
            if ($current_state != 0 && $current_state != $new_state) {
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
            if (strpos($error, 'oney_required_field') !== false) {
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
     * @description Get allowed payment options for customer
     * @unused
     *
     * @param $cart
     *
     * @return array
     */
    public function getAllowedPaymentOptions($cart)
    {
        $options = [
            'standard' => false,
            'oneclick' => false,
            'installment' => false,
            'oney' => false,
        ];

        if (!$this->active
            || !$this->config->get($this->dependencies->getConfigurationKey('show'))
            || !$this->dependencies->amountCurrencyClass->checkCurrency($cart)
            || !$this->dependencies->amountCurrencyClass->checkAmount($cart)) {
            return $options;
        }

        // check if installment allowed
        $installment = $this->config->get(
            $this->dependencies->getConfigurationKey('inst')
        );
        $installment_min_amount = $this->config->get(
            $this->dependencies->getConfigurationKey('instMinAmount')
        );
        $order_total = $cart->getOrderTotal(true);
        $installment = $installment && $order_total >= $installment_min_amount;

        // check if one click allowed
        $one_click = $this->config->get(
            $this->dependencies->getConfigurationKey('oneClick')
        );
        $payplug_cards = $this->card->getByCustomer((int) $cart->id_customer, true);
        $one_click = (bool) ($one_click && !empty($payplug_cards));

        // check if oney is allowed
        $oney = $this->config->get(
            $this->dependencies->getConfigurationKey('oney')
        );

        return [
            'standard' => true,
            'oneclick' => $one_click,
            'installment' => $installment,
            'oney' => $oney,
        ];
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
        $card = $card != null ? $card : $this->tools->tool('getValue', 'pc', null);

        // check if is Installment
        if ($this->tools->tool('getValue', 'io') || $this->tools->tool('getValue', 'type') == 'oney') {
            $payment_method = 'PayPlugPaymentOney';
        } elseif ($this->tools->tool('getValue', 'i') || $this->tools->tool('getValue', 'type') == 'installment') {
            $payment_method = 'PayPlugPaymentInstallment';
        } elseif (($card != null && $card != 'new_card') || $this->tools->tool('getValue', 'type') == 'oneclick') {
            $payment_method = 'PayPlugPaymentOneClick';
        } elseif ($this->tools->tool('getValue', 'type') == 'standard') {
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
     * @param int $cart_id
     *
     * @return bool|int
     */
    public function getPaymentByCart($cart_id)
    {
        $this->query
            ->select()
            ->fields('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('payment_method != "installment"')
            ->where('id_cart = ' . (int) $cart_id)
        ;

        return $this->query->build('unique_value');
    }

    /**
     * @description Get payment data from cookie
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
     * @description Get available method to confire the module
     *
     * @return array
     */
    public function getPaymentMethods()
    {
        $paymentMethods = [];
        $availablePaymentMethods = [
            'standard',
            'amex',
            'applepay',
            'bancontact',
            'oney',
        ];

        $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        foreach ($availablePaymentMethods as $availablePaymentMethod) {
            $featureName = 'feature_' . $availablePaymentMethod;
            if ($this->dependencies->configClass->isValidFeature($featureName)) {
                $method = 'get' . $this->tools->tool('ucfirst', $availablePaymentMethod) . 'PaymentMethod';
                // @todo: check if method exists
                $paymentMethod = $this->{$method}($views_path);
                if ($paymentMethod) {
                    $paymentMethods[$availablePaymentMethod] = $paymentMethod;
                }
            }
        }

        $this->assign->assign([
            'faq_links' => $this->dependencies->configClass->configurations['faq_links'],
        ]);

        return $paymentMethods;
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
     * @description Get the valid payment options from payplug configuration
     *
     * @param $cart
     *
     * @throws Exception
     *
     * @return array
     */
    public function getPaymentOptions()
    {
        $payment_options = [];
        $options = $this->dependencies->configClass->getAvailableOptions($this->context->cart);
        $available_payment_options = [
            'standard',
            'installment',
            'oney',
            'bancontact',
            'applepay',
            'amex',
        ];

        foreach ($available_payment_options as $available_payment_option) {
            $allowed_feature = $this->dependencies->configClass->isValidFeature('feature_' . $available_payment_option);
            if (isset($options[$available_payment_option]) && $options[$available_payment_option] && $allowed_feature) {
                $method = 'get' . $this->tools->tool('ucfirst', $available_payment_option) . 'PaymentOption';
                $payment_options = $this->{$method}($payment_options, $options);
            }
        }

        return $payment_options;
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

        if ($payment->installment_plan_id !== null) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($payment->installment_plan_id);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        } else {
            $installment = null;
        }

        $pay_status = 1; //not paid
        if ((int) $payment->is_paid == 1) {
            $pay_status = 2; //paid
        } elseif (isset($payment->payment_method, $payment->payment_method['is_pending'])
            && (int) $payment->payment_method['is_pending'] == 1
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
        } elseif ($payment->installment_plan_id !== null && (int) $installment->is_active == 1) {
            $pay_status = 6; //ongoing
        }
        if ((int) $payment->is_refunded == 1) {
            $pay_status = 5; //refunded
        } elseif ((int) $payment->amount_refunded > 0) {
            $pay_status = 4; //partially refunded
        }

        return $pay_status;
    }

    /**
     * @description Check if payment method is valid for given id
     * @unused
     *
     * @param string $payment_id
     * @param string $type       default payment
     *
     * @return bool
     */
    public function isPaidPaymentMethod($payment_id, $type = 'payment')
    {
        switch ($type) {
            case 'installment':
                $installment = $this->dependencies->apiClass->retrieveInstallment($payment_id);
                if ($installment['result'] && $installment['resource']->is_active) {
                    $schedules = $installment['resource']->schedule;
                    foreach ($schedules as $schedule) {
                        foreach ($schedule->payment_ids as $pay_id) {
                            $inst_payment = $this->dependencies->apiClass->retrievePayment($pay_id);
                            if ($inst_payment['result'] && $inst_payment['resource']->is_paid) {
                                return true;
                            }
                        }
                    }
                }

                break;

            case 'payment':
            default:
                $payment = $this->dependencies->apiClass->retrievePayment($payment_id);

                return $payment['result'] && $payment['resource']->is_paid;
        }

        return false;
    }

    /**
     * @description Check if a payment for the same id cart is pending
     * @unused
     *
     * @param int $id_cart
     *
     * @return bool
     */
    public function isPaymentPending($id_cart)
    {
        $current_time = strtotime(date('Y-m-d H:i:s'));
        $timeout_delay = 9;

        $payment_cart = $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('id_cart = ' . (int) $id_cart)
            ->where('id_payment LIKE "pending"')
            ->build()
        ;

        $payment_cart = reset($payment_cart);

        if (!$payment_cart || (($current_time - strtotime($payment_cart['date_upd'])) >= $timeout_delay)) {
            return false;
        }

        return true;
    }

    /**
     * @description Get id_payment from a pending transaction for a given cart
     *
     * @param int $id_cart
     *
     * @return string id_payment OR bool
     */
    public function isTransactionPending($id_cart)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return false;
        }

        return $this->query
            ->select()
            ->fields('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('id_cart = ' . (int) $id_cart)
            ->where('is_pending = 1')
            ->build('unique_value')
        ;
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
        $config = [
            'one_click' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('oneClick')
            ),
            'installment' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('inst')
            ),
            'company' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('companyId') . ($is_sandbox ? '_TEST' : '')
            ),
            'inst_mode' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('instMode')
            ),
            'deferred' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('deferred')
            ),
            'oney' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('oney')
            ),
            'standard' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('standard')
            ),
            'bancontact' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('bancontact')
            ),
            'applepay' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('applepay')
            ),
            'amex' => (int) $this->config->get(
                $this->dependencies->getConfigurationKey('amex')
            ),
        ];

        $is_one_click = $options['id_card'] != 'new_card' && $config['one_click'];
        $options['is_installment'] = $options['is_installment'] && $config['installment'];
        $options['is_bancontact'] = $options['is_bancontact'] && $config['bancontact'];
        $options['is_applepay'] = $options['is_applepay'] && $config['applepay'];
        $options['is_amex'] = $options['is_amex'] && $config['amex'];

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
        $amount = $cart->getOrderTotal(true);
        $amount = $this->dependencies->amountCurrencyClass->convertAmount($amount);
        $current_amounts = $this->dependencies->amountCurrencyClass->getAmountsByCurrency($currency_iso_code);
        if ($amount < $current_amounts['min_amount'] || $amount > $current_amounts['max_amount']) {
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
                $shipping_country = $this->country->get($shipping_address->id_country);
                $metadata['cms_shipping_country'] = $shipping_country->iso_code;
                $shipping_iso = $iso_code;
            }
            if (!$billing_iso) {
                $billing_country = $this->country->get($billing_address->id_country);
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
            && $this->cart->isGuestCartByCartId($cart->id) != 1
            && $options['id_card'] == 'new_card';

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
                if ($i == 0) {
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
            if ($options['id_card'] && $options['id_card'] != 'new_card') {
                $card = $this->card->getCard((int) $options['id_card']);
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
            if (!$payment_tab['billing']['mobile_phone_number'] || !$this->dependencies->configClass->isValidMobilePhoneNumber(
                $payment_tab['billing']['country'],
                $payment_tab['billing']['mobile_phone_number']
            )) {
                if ($this->dependencies->configClass->isValidMobilePhoneNumber(
                    $payment_tab['billing']['country'],
                    $payment_tab['billing']['landline_phone_number']
                )) {
                    $payment_tab['billing']['mobile_phone_number'] = $payment_tab['billing']['landline_phone_number'];
                }
            }

            // check shipping phonenumber
            if (!$payment_tab['shipping']['mobile_phone_number'] || !$this->dependencies->configClass->isValidMobilePhoneNumber(
                $payment_tab['shipping']['country'],
                $payment_tab['shipping']['mobile_phone_number']
            )) {
                if ($this->dependencies->configClass->isValidMobilePhoneNumber(
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

                if ($payment_data) {
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
            'isEmbedded' => (string) $this->config->get(
                $this->dependencies->getConfigurationKey('embeddedMode')
            ) !== 'redirected',
            'isIntegrated' => $options['is_integrated'],
            'isMobileDevice' => $this->dependencies->configClass->isMobiledevice(),
            'cart' => $cart,
            'cartId' => (int) $payment_tab['metadata']['ID Cart'],
            'cartHash' => null,
            'oneyDetails' => isset($options['is_oney']) ? $options['is_oney'] : null,
        ];

        // Create payment if inexistent
        $force_payment_creation = $options['is_applepay'] || $options['is_oney'];
        if (!$this->payment->checkPaymentTable($cart->id) || $force_payment_creation) {
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
            $store_payment = $this->payment->checkPaymentTable($cart->id);
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

        $this->context->cookie->__set('payplug_data', $value);

        return (bool) $this->context->cookie->__get('payplug_data');
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

        $value = json_encode($payplug_errors);

        $this->context->cookie->__set($this->dependencies->name . 'Errors', $value);

        return (bool) $this->context->cookie->__get($this->dependencies->name . 'Errors');
    }

    public function getShippingAddress($address, $cart)
    {
        return $address->get((int) $cart->id_address_delivery);
    }

    public function getBillingAddress($address, $cart)
    {
        return $address->get((int) $cart->id_address_invoice);
    }

    public function getShippingIso($shipping_address)
    {
        return $this->dependencies->configClass->getIsoCodeByCountryId((int) $shipping_address->id_country);
    }

    public function getBillingIso($billing_address)
    {
        return $this->dependencies->configClass->getIsoCodeByCountryId((int) $billing_address->id_country);
    }

    public function getBrowser()
    {
        $arr_browsers = ['Opera', 'Edg', 'Chrome', 'Safari', 'Firefox', 'MSIE', 'Trident'];
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $user_browser = '';

        foreach ($arr_browsers as $browser) {
            if (strpos($agent, $browser) !== false) {
                $user_browser = $browser;

                break;
            }
        }

        switch ($user_browser) {
            case 'MSIE':
            case 'Trident':
                $user_browser = 'Internet Explorer';

                break;

            case 'Edg':
                $user_browser = 'Microsoft Edge';

                break;
        }

        return $user_browser;
    }

    private function getStandardPaymentMethod($views_path)
    {
        $advancedOptions = [];

        // EmbeddedMode
        $embeddedModeItems = [
            [
                'value' => 'integrated',
                'dataName' => 'embeddedModeIntegrated',
                'text' => $this->dependencies->l('payment.getPaymentMethod.embeddedMode.integrated', 'paymentclass'),
            ],
            [
                'value' => 'popup',
                'dataName' => 'embeddedModePopup',
                'text' => $this->dependencies->l('payment.getPaymentMethod.embeddedMode.popup', 'paymentclass'),
            ],
            [
                'value' => 'redirected',
                'dataName' => 'embeddedModeRedirected',
                'text' => $this->dependencies->l('payment.getPaymentMethod.embeddedMode.redirected', 'paymentclass'),
            ],
        ];
        if (!$this->dependencies->configClass->isValidFeature('feature_integrated')) {
            array_shift($embeddedModeItems);
        }

        // Installment
        if ($this->dependencies->configClass->isValidFeature('feature_installment')) {
            $this->assign->assign([
                'installments_panel_url' => $this->dependencies->configClass->configurations['installments_panel_url'],
                'inst_mode' => $this->dependencies->configClass->configurations['inst_mode'],
                'inst_min_amount' => $this->dependencies->configClass->configurations['inst_min_amount'],
            ]);
            $advancedOptions['installment'] = [
                'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('inst')),
                'title' => $this->dependencies->l('paymentMethod.standard.installment.title', 'paymentclass'),
                'checked' => $this->config->get($this->dependencies->getConfigurationKey('inst')),
                'premium' => true,
            ];
        }

        // Deferred State
        if ($this->dependencies->configClass->isValidFeature('feature_deferred')) {
            $order_states = $this->dependencies->orderClass->getOrderStates();
            $deferred_state = $this->dependencies->configClass->configurations['deferred_state'];
            $order_states_values = [
                0 => [
                    'key' => 0,
                    'value' => $this->dependencies->l('payment.getPaymentMethod.deferred.capture.default', 'paymentclass'),
                    'selected' => (int) $deferred_state ? false : true,
                ],
            ];
            foreach ($order_states as $order_state) {
                $order_states_values[$order_state['id_order_state']] = [
                    'key' => $order_state['id_order_state'],
                    'value' => sprintf(
                        $this->dependencies->l('payment.getPaymentMethod.deferred.capture.state', 'paymentclass'),
                        $order_state['name']
                    ),
                    'selected' => $order_state['id_order_state'] == $deferred_state ? true : false,
                ];
            }
            ksort($order_states_values);
            $this->assign->assign([
                'order_states_values' => $order_states_values,
            ]);
            $advancedOptions['deferred'] = [
                'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('deferred')),
                'title' => $this->dependencies->l('paymentMethod.standard.deferred.title', 'paymentclass'),
                'checked' => $this->config->get($this->dependencies->getConfigurationKey('deferred')),
                'premium' => true,
            ];
        }

        return [
            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('standard')),
            'title' => $this->dependencies->l('paymentMethod.standard.title', 'paymentclass'),
            'image_url' => $views_path . 'img/svg/payment/standard.svg',
            'description' => [
                'live' => [
                    'text' => $this->dependencies->l('paymentMethod.standard.description', 'paymentclass'),
                ],
            ],
            'checked' => (bool) $this->config->get($this->dependencies->getConfigurationKey('standard')),
            'useSandbox' => true,
            'options' => [
                [
                    'title' => $this->dependencies->l('paymentMethod.standard.embedded.title', 'paymentclass'),
                    'description' => $this->dependencies->l('paymentMethod.standard.embedded.description', 'paymentclass'),
                    'link' => $this->dependencies->configClass->configurations['faq_links']['support'],
                    'action' => [
                        'type' => 'options',
                        'params' => [
                            'items' => $embeddedModeItems,
                            'className' => '_sandboxRadioButton',
                            'selected' => $this->config->get($this->dependencies->getConfigurationKey('embeddedMode')),
                            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('embeddedMode')),
                        ],
                    ],
                ],
                [
                    'title' => $this->dependencies->l('paymentMethod.standard.oneclick.title', 'paymentclass'),
                    'description' => $this->dependencies->l('paymentMethod.standard.oneclick.description', 'paymentclass'),
                    'link' => $this->dependencies->configClass->configurations['faq_links']['one_click'],
                    'action' => [
                        'type' => 'switch',
                        'params' => [
                            'enabledLabel' => 'On',
                            'disabledLabel' => 'Off',
                            'dataName' => 'oneClickSwitch',
                            'checked' => $this->config->get($this->dependencies->getConfigurationKey('oneClick')),
                            'className' => 'oneClickSwitch',
                            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('oneClick')),
                        ],
                    ],
                ],
            ],
            'advancedOptions' => $advancedOptions,
        ];
    }

    private function getApplepayPaymentMethod($views_path)
    {
        // If CMS version under 1.7 return empty has the method is not available
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            return [];
        }

        return [
            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('applepay')),
            'title' => $this->dependencies->l('paymentMethod.applepay.title', 'paymentclass'),
            'image_url' => $views_path . 'img/svg/payment/applepay.svg',
            'premium' => true,
            'checked' => (bool) $this->config->get($this->dependencies->getConfigurationKey('applepay')),
            'description' => [
                'sandbox' => [
                    'text' => $this->dependencies->l('paymentMethod.applepay.testDescription', 'paymentclass'),
                ],
                'live' => [
                    'text' => $this->dependencies->l('paymentMethod.applepay.liveDescription', 'paymentclass'),
                    'link' => $this->dependencies->configClass->configurations['faq_links']['applepay'],
                ],
            ],
        ];
    }

    private function getAmexPaymentMethod($views_path)
    {
        return [
            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('amex')),
            'title' => $this->dependencies->l('paymentMethod.amex.title', 'paymentclass'),
            'image_url' => $views_path . 'img/svg/payment/amex.svg',
            'premium' => true,
            'checked' => (bool) $this->config->get($this->dependencies->getConfigurationKey('amex')),
            'description' => [
                'sandbox' => [
                    'text' => $this->dependencies->l('paymentMethod.amex.testDescription', 'paymentclass'),
                ],
                'live' => [
                    'text' => $this->dependencies->l('paymentMethod.amex.liveDescription', 'paymentclass'),
                    'link' => $this->dependencies->configClass->configurations['faq_links']['amex'],
                ],
            ],
        ];
    }

    private function getBancontactPaymentMethod($views_path)
    {
        return [
            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('bancontact')),
            'title' => $this->dependencies->l('paymentMethod.bancontact.title', 'paymentclass'),
            'image_url' => $views_path . 'img/svg/payment/bancontact.svg',
            'description' => [
                'sandbox' => [
                    'text' => $this->dependencies->l('paymentMethod.bancontact.testDescription', 'paymentclass'),
                ],
                'live' => [
                    'text' => $this->dependencies->l('paymentMethod.bancontact.liveDescription', 'paymentclass'),
                    'link' => $this->dependencies->configClass->configurations['faq_links']['bancontact'],
                ],
            ],
            'premium' => true,
            'checked' => (bool) $this->config->get($this->dependencies->getConfigurationKey('bancontact')),
            'options' => [
                [
                    'title' => $this->dependencies->l('paymentMethod.bancontactOption.title', 'paymentclass'),
                    'description' => $this->dependencies->l('paymentMethod.bancontactOption.description', 'paymentclass'),
                    'action' => [
                        'type' => 'switch',
                        'params' => [
                            'enabledLabel' => 'On',
                            'disabledLabel' => 'Off',
                            'dataName' => 'bancontactCountrySwitch',
                            'checked' => (bool) $this->config->get(
                                $this->dependencies->getConfigurationKey('bancontactCountry')
                            ),
                            'className' => 'bancontactCountrySwitch',
                            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('bancontactCountry')),
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getOneyPaymentMethod($views_path)
    {
        $oneyFeesItems = [
            [
                'value' => '1',
                'dataName' => 'oneyWithFees',
                'text' => $this->dependencies->l('paymentMethod.oneyWithFees.title', 'paymentclass'),
                'subText' => $this->dependencies->l('paymentMethod.oneyWithFees.subtitle', 'paymentclass'),
                'className' => '_paylaterLabel',
            ],
            [
                'value' => '0',
                'dataName' => 'oneyWithoutFees',
                'text' => $this->dependencies->l('paymentMethod.oneyWithoutFees.title', 'paymentclass'),
                'subText' => $this->dependencies->l('paymentMethod.oneyWithoutFees.subtitle', 'paymentclass'),
                'className' => '_paylaterLabel',
            ],
        ];

        return [
            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('oney')),
            'title' => $this->dependencies->l('paymentMethod.oney.title', 'paymentclass'),
            'premium' => true,
            'description' => $this->dependencies->l('paymentMethod.oney.description', 'paymentclass'),
            'link' => $this->dependencies->configClass->configurations['faq_links']['oney'],
            'checked' => (bool) $this->config->get($this->dependencies->getConfigurationKey('oney')),
            'options' => [
                [
                    'action' => [
                        'type' => 'option',
                        'params' => [
                            'items' => $oneyFeesItems,
                            'className' => '_paylaterOptions',
                            'dataName' => 'oneyOptions',
                            'selected' => $this->config->get($this->dependencies->getConfigurationKey('oney_fees')),
                            'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('oney_fees')),
                        ],
                    ],
                ],
            ],
            'advancedOptions' => [
                'thresholds' => [
                    'image_url' => $views_path . 'img/admin/screen/' . $this->dependencies->name . '-thresholds.jpg',
                    'title' => $this->dependencies->l('paymentMethod.oneyThresholdsOption.title', 'paymentclass'),
                    'switch' => false,
                ],
                'optimised' => [
                    'image_url' => $views_path . 'img/admin/screen/' . $this->dependencies->name . '-optimized.jpg',
                    'title' => $this->dependencies->l('paymentMethod.oneyOptimisedOption.title', 'paymentclass'),
                    'switch' => true,
                    'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('oney_optimized')),
                    'checked' => $this->config->get($this->dependencies->getConfigurationKey('oney_optimized')),
                ],
                'product' => [
                    'image_url' => $views_path . 'img/admin/screen/' . $this->dependencies->name . '-productOneyCta.jpg',
                    'title' => $this->dependencies->l('paymentMethod.productOneyCta.title', 'paymentclass'),
                    'switch' => true,
                    'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('oney_product_cta')),
                    'checked' => $this->config->get($this->dependencies->getConfigurationKey('oney_product_cta')),
                ],
                'cart' => [
                    'image_url' => $views_path . 'img/admin/screen/' . $this->dependencies->name . '-cartOneyCta.jpg',
                    'title' => $this->dependencies->l('paymentMethod.productOneyCta.title', 'paymentclass'),
                    'switch' => true,
                    'name' => $this->tools->tool('strtolower', $this->dependencies->getConfigurationKey('oney_cart_cta')),
                    'checked' => $this->config->get($this->dependencies->getConfigurationKey('oney_cart_cta')),
                ],
            ],
        ];
    }

    private function getAmexPaymentOption($payment_options, $options = [])
    {
        $payment_options['amex'] = [
            'name' => 'amex',
            'inputs' => [
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
                    'value' => (int) $this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'amex',
                ],
            ],
            'tpl' => 'amex.tpl',
            'extra_classes' => 'amex',
            'payment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['type' => 'amex']
            ),
            'logo' => $this->dependencies->mediaClass->getMediaPath(
                $this->constant->get('_PS_MODULE_DIR_')
                . $this->dependencies->name . '/views/img/svg/payment/amex.svg'
            ),
            'callToActionText' => $this->dependencies->l(
                'payplug.getPaymentOptions.payWithAmex',
                'paymentclass'
            ),
            'action' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'dispatcher',
                [],
                true
            ),
            'moduleName' => $this->dependencies->name,
        ];

        return $payment_options;
    }

    private function getApplepayPaymentOption($payment_options)
    {
        if ('Safari' != $this->getBrowser()) {
            return $payment_options;
        }

        $payment_options['applepay'] = [
            'name' => 'applepay',
            'inputs' => [
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
                    'value' => (int) $this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'applepay',
                ],
            ],
            'tpl' => 'applepay.tpl',
            'additionalInformation' => $this->dependencies->configClass->fetchTemplate('checkout/payment/applepay.tpl'),
            'callToActionText' => $this->dependencies->l(
                'payplug.getPaymentOptions.payWithApplePay',
                'paymentclass'
            ),
            'extra_classes' => 'payplug default',
            'payment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['type' => 'applepay']
            ),
            'logo' => $this->dependencies->mediaClass->getMediaPath(
                $this->constant->get('_PS_MODULE_DIR_')
                . $this->dependencies->name . '/views/img/svg/payment/apple_pay.svg'
            ),
            'moduleName' => $this->dependencies->name,
        ];

        $this->assign->assign([
            'language' => $this->context->language,
        ]);

        return $payment_options;
    }

    private function getBancontactPaymentOption($payment_options)
    {
        $shipping_address = $this->address->get((int) $this->context->cart->id_address_delivery);
        $shipping_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $shipping_address->id_country);
        $invoice_address = $this->address->get((int) $this->context->cart->id_address_invoice);
        $invoice_iso = $this->dependencies->configClass->getIsoCodeByCountryId((int) $invoice_address->id_country);

        if ((bool) $this->config->get($this->dependencies->getConfigurationKey('bancontactCountry')) && ($shipping_iso != 'BE' || $invoice_iso != 'BE')) {
            return $payment_options;
        }

        $payment_options['bancontact'] = [
            'name' => 'bancontact',
            'tpl' => 'bancontact.tpl',
            'logo' => $this->dependencies->mediaClass->getMediaPath(
                $this->constant->get('_PS_MODULE_DIR_')
                . $this->dependencies->name . '/views/img/bancontact/bancontact.svg'
            ),
            'callToActionText' => $this->dependencies->l(
                'payplug.getPaymentOptions.payWithBancontact',
                'paymentclass'
            ),
            'extra_classes' => 'bancontact',
            'action' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'dispatcher',
                [],
                true
            ),
            'payment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['type' => 'bancontact'],
                true
            ),
            'moduleName' => $this->dependencies->name,
            'inputs' => [
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
                    'value' => (int) $this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'bancontact',
                ],
            ],
        ];

        return $payment_options;
    }

    private function getInstallmentPaymentOption($payment_options, $options = [])
    {
        $use_taxes = (bool) $this->config->get('PS_TAX');
        $cart_amount = (float) $this->context->cart->getOrderTotal($use_taxes);
        $min_amount = (float) $this->config->get($this->dependencies->getConfigurationKey('instMinAmount'));
        if ($min_amount > $cart_amount) {
            return $payment_options;
        }

        $installment_mode = $this->config->get(
            $this->dependencies->getConfigurationKey('instMode')
        );

        $payment_options['installment'] = [
            'name' => 'installment',
            'inputs' => [
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
                    'value' => (int) $this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'installment',
                ],
            ],
            'tpl' => 'installment.tpl',
            'payment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['type' => 'installment', 'i' => 1],
                true
            ),
            'logo' => $this->dependencies->mediaClass->getMediaPath(
                $this->constant->get('_PS_MODULE_DIR_')
                . $this->dependencies->name . '/views/img/logos_schemes_installment_'
                . $this
                    ->config->get(
                        $this->dependencies->getConfigurationKey('instMode')
                    ) . '_' . $this
                    ->dependencies->configClass->getImgLang() . '.png'
            ),
            'callToActionText' => sprintf(
                $this->dependencies->l('payplug.getPaymentOptions.payByCardInstallment', 'paymentclass'),
                $this->config->get(
                    $this->dependencies->getConfigurationKey('instMode')
                )
            ),
            'action' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'dispatcher',
                ['def' => isset($options['deferred']) ? (int) $options['deferred'] : 0],
                true
            ),
            'moduleName' => $this->dependencies->name,
        ];

        $this->assign->assign([
            'installment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['i' => 1],
                true
            ),
            'installment_mode' => $installment_mode,
        ]);

        return $payment_options;
    }

    private function getOneyPaymentOption($payment_options)
    {
        $use_taxes = (bool) $this->config->get('PS_TAX');
        $cart_amount = $this->context->cart->getOrderTotal($use_taxes);

        $is_elligible = $this->oney->isOneyElligible($this->context->cart, $cart_amount, true);
        $error = $is_elligible['result'] ? false : $is_elligible['error_type'];

        switch ($error) {
            case 'invalid_addresses':
                $err_label =
                    $this->dependencies->l('payplug.getPaymentOptions.invalidAddresses', 'paymentclass');

                break;

            case 'invalid_amount_bottom':
            case 'invalid_amount_top':
                $limits = $this->oney->getOneyPriceLimit(true);
                $err_label = sprintf(
                    $this->dependencies->l('payplug.getPaymentOptions.invalidAmount', 'paymentclass'),
                    $limits['min'],
                    $limits['max']
                );

                break;

            case 'invalid_carrier':
                $err_label = $this->dependencies->l('payplug.getPaymentOptions.invalidCarrier', 'paymentclass');

                break;

            case 'invalid_cart':
                $err_label = $this->dependencies->l('payplug.getPaymentOptions.invalidCart', 'paymentclass');

                break;

            default:
                $err_label = $this->dependencies->l('payplug.getPaymentOptions.errorOccurred', 'paymentclass');

                break;
        }

        $optimized = $this->config->get($this->dependencies->getConfigurationKey('oneyOptimized')) && !$error;
        $oney_template = $optimized ? 'oney.tpl' : 'unified.tpl';

        $use_fees = (bool) $this->config->get($this->dependencies->getConfigurationKey('oneyFees'));
        $delivery_address = $this->address->get($this->context->cart->id_address_delivery);
        $delivery_country = $this->country->get($delivery_address->id_country);
        $iso = $this->tools->tool('strtoupper', $this->context->language->iso_code);
        if ($iso != 'IT' && $iso != 'FR') {
            $iso = $this->config->get($this->dependencies->getConfigurationKey('companyIso'));
        }
        $shipping_address = $this->getShippingAddress($this->address, $this->context->cart);
        $billing_address = $this->getBillingAddress($this->address, $this->context->cart);
        $shipping_iso = $this->getShippingIso($shipping_address);
        $billing_iso = $this->getBillingIso($billing_address);

        if ($this->dependencies->configClass->isValidFeature('feature_belgium_oney')
            && in_array('BE', explode(',', $this->config->get($this->dependencies->getConfigurationKey('oneyAllowedCountries'))))
            && ($shipping_iso == $billing_iso) && ($shipping_iso == 'BE')) {
            $available_oney_payments = $this->oney->oneyEntity->getOperations(['x4_without_fees', 'x4_with_fees']);
        } else {
            $available_oney_payments = $this->oney->oneyEntity->getOperations();
        }
        foreach ($available_oney_payments as $oney_payment) {
            $with_fees = (bool) strpos($oney_payment, 'with_fees') !== false;
            if (($use_fees && !$with_fees) || (!$use_fees && $with_fees)) {
                continue;
            }

            $payment_key = 'oney_' . $oney_payment;
            $type = explode('_', $oney_payment);
            $split = (int) str_replace('x', '', $type[0]);

            $oneyLogo = $oney_payment . (!$use_fees ? '_side_' . $iso : '') . ($error ? '_alt' : '') . '.svg';
            $text = $use_fees
                ? $this->dependencies->l('payplug.getPaymentOptions.payWithOney', 'paymentclass')
                : $this->dependencies->l('payplug.getPaymentOptions.payWithOneyWithout', 'paymentclass');

            $oneyLabel = $error ? $err_label : sprintf($text, $split);

            if ($optimized) {
                $adapter = $this->dependencies->loadAdapterPresta();
                if ($adapter
                    && (method_exists($adapter, 'getPaymentOption'))) {
                    $oneyData = $adapter->getPaymentOption();
                    $oneyLogo = $oneyData['oneyLogo'];
                    $oneyLabel = $oneyData['oneyCallToActionText'];
                }
            }

            $payment_options[$payment_key] = [
                'name' => 'oney',
                'is_optimized' => $optimized,
                'type' => $oney_payment,
                'amount' => $cart_amount,
                'iso_code' => $delivery_country->iso_code,
                'inputs' => [
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
                        'value' => (int) $this->context->cart->id,
                    ],
                    'method' => [
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'oney',
                    ],
                    'oney_type' => [
                        'name' => $this->dependencies->name . 'Oney_type',
                        'type' => 'hidden',
                        'value' => $oney_payment,
                    ],
                ],
                'tpl' => $oney_template,
                'extra_classes' => sprintf('oney%sx', $split),
                'payment_controller_url' => $this->context->link->getModuleLink(
                    $this->dependencies->name,
                    'payment',
                    ['type' => 'oney', 'io' => sprintf('%s', $split)],
                    true
                ),
                'logo' => $this->dependencies->mediaClass->getMediaPath(
                    $this->constant->get('_PS_MODULE_DIR_')
                    . $this->dependencies->name . '/views/img/oney/' . $oneyLogo
                ),
                'callToActionText' => $oneyLabel,
                'action' => $this->context->link->getModuleLink(
                    $this->dependencies->name,
                    'dispatcher',
                    [],
                    true
                ),
                'moduleName' => $this->dependencies->name,
                'err_label' => $err_label,
            ];
        }

        return $payment_options;
    }

    private function getStandardPaymentOption($payment_options, $options = [])
    {
        //One Click Payment
        if (isset($options['one_click']) && $options['one_click']) {
            $cards = $this->card->getByCustomer((int) $this->context->customer->id, true);
            foreach ($cards as $card) {
                $brand = ($card['brand'] != 'none')
                    ? $this->tools->tool('ucfirst', $card['brand'])
                    : $this->dependencies->l('payplug.getPaymentOptions.card', 'paymentclass');
                $payment_key = 'one_click_' . $card['id_payplug_card'];
                $logo = $card['brand'] != 'none' ? $this->dependencies->mediaClass->getMediaPath(
                    $this
                        ->constant
                        ->get('_PS_MODULE_DIR_') . $this->dependencies->name . '/views/img/' . $this
                        ->tools
                        ->tool('strtolower', $card['brand']) . '.svg'
                ) : '';

                $payment_options[$payment_key] = [
                    'name' => 'one_click',
                    'inputs' => [
                        'pc' => [
                            'name' => 'pc',
                            'type' => 'hidden',
                            'value' => (int) $card['id_payplug_card'],
                        ],
                        'pay' => [
                            'name' => 'pay',
                            'type' => 'hidden',
                            'value' => '1',
                        ],
                        'id_cart' => [
                            'name' => 'id_cart',
                            'type' => 'hidden',
                            'value' => (int) $this->context->cart->id,
                        ],
                        'method' => [
                            'name' => 'method',
                            'type' => 'hidden',
                            'value' => 'one_click',
                        ],
                    ],
                    'tpl' => 'one_click.tpl',
                    'payment_controller_url' => $this->context->link->getModuleLink(
                        $this->dependencies->name,
                        'payment',
                        [],
                        true
                    ),
                    'logo' => $logo,
                    'callToActionText' => $brand . ' **** **** **** ' . $card['last4'],
                    'expiry_date_card' => $this->dependencies->l('payplug.getPaymentOptions.expiryDate', 'paymentclass') . ': ' . $card['expiry_date'],
                    'action' => $this->context->link->getModuleLink(
                        $this->dependencies->name,
                        'dispatcher',
                        ['def' => isset($options['deferred']) ? (int) $options['deferred'] : 0],
                        true
                    ),
                    'moduleName' => $this->dependencies->name,
                ];
            }
        }

        // Standard Payment or new card from one-click
        $payment_options['standard'] = [
            'name' => 'standard',
            'inputs' => [
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
                    'value' => (int) $this->context->cart->id,
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => 'standard',
                ],
            ],
            'tpl' => 'standard.tpl',
            'extra_classes' => 'payplug default',
            'payment_controller_url' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'payment',
                ['type' => 'standard']
            ),
            'logo' => $this->dependencies->mediaClass->getMediaPath(
                $this->constant->get('_PS_MODULE_DIR_')
                . $this->dependencies->name . '/views/img/logos_schemes_'
                . $this->dependencies->configClass->getImgLang() . '.svg'
            ),
            'callToActionText' => isset($cards) && $cards
                ? $this->dependencies->l('payplug.getPaymentOptions.payDifferentCard', 'paymentclass')
                : $this->dependencies->l('payplug.getPaymentOptions.payCreditCard', 'paymentclass'),
            'action' => $this->context->link->getModuleLink(
                $this->dependencies->name,
                'dispatcher',
                ['def' => isset($options['deferred']) ? (int) $options['deferred'] : 0],
                true
            ),
            'moduleName' => $this->dependencies->name,
        ];

        return $payment_options;
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

            if (strpos($field_name, 'phone') != false) {
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
}
