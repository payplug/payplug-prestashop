<?php

namespace PayPlug\src\repositories;

class PaymentRepository extends Repository
{
    // From classes/PPPayment.php
    public function retrieve($id)
    {
        try {
            $payment = \Payplug\Payment::retrieve($id);
        } catch (\Payplug\Exception $e) {
            $data = [
                'result' => false,
                'response' => $e->__toString(),
            ];
            return $data;
        }
        return $payment;
    }

    private function populateFromPayment($payment)
    {
        $this->resource = $payment;
    }

    public function capture()
    {
        try {
            \Payplug\Payment::capture($this->resource->id);
            $response = [
                'code' => 200,
                'message' => 'Amount successfully captured.',
                'resource' => $this,
            ];
        } catch (Payplug\Exception\NotAllowedException $e) {
            $httpResponse = json_decode($e->getHttpResponse());
            $response = [
                'code' => (int)$e->getCode(),
                'message' => $httpResponse->message,
                'resource' => $this,
            ];
        } catch (Payplug\Exception\ForbiddenException $e) {
            $httpResponse = json_decode($e->getHttpResponse());
            $response = [
                'code' => (int)$e->getCode(),
                'message' => $httpResponse->message,
                'resource' => $this,
            ];
        } catch (\Payplug\Exception\ConfigurationNotSetException $e) {
            $httpResponse = json_decode($e->getHttpResponse());
            $response = [
                'code' => (int)$e->getCode(),
                'message' => $httpResponse->message,
                'resource' => $this,
            ];
        }
        return $response;
    }

    public function isPaid()
    {
        return $this->resource->is_paid;
    }

    public function isDeferred()
    {
        return ($this->resource->authorization !== null);
    }

    public function refresh()
    {
        $payment = $this->retrieve($this->resource->id);
        $this->populateFromPayment($payment);
        return $this;
    }
    // (end PPPayment)

    // From payplug.php
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
                        $this->l('Capture of this payment is authorized before %s.').' '.
                        $this->l('After this date, you will not be able to get paid.'),
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

        return $this->display(__FILE__, '_partials/messages.tpl');
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
     * Send cURL request to PayPlug to patch a given payment
     *
     * @param String $pay_id
     * @param Array $data
     * @return Array
     * @throws ConfigurationNotSetException
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
        $this->logger->addLog('notice', '[Payplug] Start refund');
        $amount = Tools::getValue('amount');

        if (!$this->checkAmountToRefund($amount)) {
            $this->logger->addLog('notice', 'Incorrect amount to refund');
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('Incorrect amount to refund')
            ]));
        } elseif ($this->checkAmountToRefund($amount) && ($amount < 0.10)) {
            $this->logger->addLog('notice', 'The amount to be refunded must be at least 0.10 €');
            die(json_encode([
                'status' => 'error',
                'data' => $this->l('The amount to be refunded must be at least 0.10 €')
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
            $this->logger->addLog('notice', 'Cannot refund that amount.');
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
                        $current_state = (int)$this->getCurrentOrderState($order->id);
                        $this->logger->addLog('notice', 'Current order state: ' . $current_state);
                        if ($current_state != 0 && $current_state != $new_state) {
                            $history = new OrderHistory();
                            $history->id_order = (int)$order->id;
                            $history->changeIdOrderState($new_state, (int)$order->id);
                            $history->addWithemail();
                            $this->logger->addLog('notice', 'Change order state to ' . $new_state);
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
                        $current_state = (int)$this->getCurrentOrderState($order->id);
                        $this->logger->addLog('notice', 'Current order state: ' . $current_state);
                        if ($current_state != 0 && $current_state != $new_state) {
                            $history = new OrderHistory();
                            $history->id_order = (int)$order->id;
                            $history->changeIdOrderState($new_state, (int)$order->id);
                            $history->addWithemail();
                            $this->logger->addLog('notice', 'Change order state to ' . $new_state);
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

    /**
     * @description Set payment errors in cookie
     *
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
                if ($current_state != 0 && $current_state !== $new_state) {
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
            'landline_phone_number' => $this->formatPhoneNumber(
                $billing_address->phone,
                $billing_address->id_country
            ),
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
                    $this->setPaymentErrorsCookie([
                        $this->l('The transaction was not completed and your card was not charged.')
                    ]);

                    return [
                        'result' => false,
                        'response' => $payment->failure->message,
                    ];
                }
                $this->storeInstallment($payment->id, (int)$cart->id);
            } else {
                $payment = \Payplug\Payment::create($payment_tab);
                if ($payment->failure == true && !empty($payment->failure->message)) {
                    $this->setPaymentErrorsCookie([
                        $this->l('The transaction was not completed and your card was not charged.')
                    ]);

                    return [
                        'result' => false,
                        'response' => $payment->failure->message,
                    ];
                }
                $this->storePayment($payment->id, (int)$cart->id);
            }
        } catch (Exception $e) {
            $messages = $this->catchErrorsFromApi($e->__toString());

            $this->setPaymentErrorsCookie([
                $this->l('The transaction was not completed and your card was not charged.')
            ]);

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
}