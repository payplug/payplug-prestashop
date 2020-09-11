<?php

namespace PayPlug\src\specific;

//use Media;
//use PayPlug\classes\PayPlugBackward;

use Context;
use PayplugBackward;
use PayPlugCard;
use PayPlugCarrier;
use Validate;

class PrestashopSpecific16
{
    public $payplug;
    public $context;

    public function __construct($payplug)
    {
        $this->payplug = $payplug;
        $this->context = Context::getContext();
    }


    public function hookHeader()
    {
//        Media::addJsDef(array(
//            'payplug_ajax_url' => PayplugBackward::getModuleLink($this->name, 'ajax', array(), true),
//        ));
//        $this->assignOneyJSVar();
    }

    public function hookCustomerAccount()
    {
//        $payplug_icon_url = 'modules/payplug/views/img/logo26.png';
//
//        $this->smarty->assign(array(
//            'payplug_icon_url' => $payplug_icon_url
//        ));
    }

    public function displayPaymentOption($payment_options, $cart)
    {

        $paymentAllowedOptions = $this->payplug->getAllowedPaymentOptions($cart);
        $paymentOptions = array();
        $payment_class = 'payplug';
        $logo_class = 'paymentLogo';
        $unified_mode = (bool)!$this->payplug->getConfiguration('PAYPLUG_ONEY_OPTIMIZED');
        $error = false;
        $is_oney_available = true;

        $current_lang = explode('-', $this->context->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, array('it', 'en'))) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        if ($this->payplug->getConfiguration('PAYPLUG_ONEY')) {
            // check if at least one carrier is available for this cart
            // get the available carrier

            $package_list = $cart->getPackageList();
            $carrier_ids = array();
            foreach ($package_list as $address) {
                foreach ($address as $package) {
                    $carrier_ids = array_merge($carrier_ids, $package['carrier_list']);
                }
            }

            // only if we have carrier need for this cart
            // check the carrier type of each available carrier
            if ($carrier_ids) {
                $has_valid_carrier = false;
                foreach ($carrier_ids as $carrier_id) {
                    if ($has_valid_carrier) {
                        continue;
                    }

                    $pc = new PayPlugCarrier();
                    $pc = $pc->getByIdCarrier($carrier_id);
                    if ($pc->delivery_type) {
                        $has_valid_carrier = true;
                    }
                }

                // if no carrier available for Oney, return false
                if (!$has_valid_carrier) {
                    $is_oney_available = false;
                }
            }

            if (Validate::isLoadedObject($cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
                $is_elligible = $this->payplug->isOneyElligible($cart);
                $error = !$is_elligible['result'];
            } else {
                $id_currency = $this->context->currency->id;
                $amount = $cart->getOrderTotal(true, Cart::BOTH);
                $is_elligible = $this->isValidOneyAmount($amount, $id_currency);
                $error = !$is_elligible['result'];
            }

            if (!$error && $has_valid_carrier) {
                $is_elligible = $this->payplug->isValidOneyCarrier($cart);
                $error = !$is_elligible['result'];
            }

            $this->context->smarty->assign(array(
                'payplug_module_dir' => _PS_MODULE_DIR_,
                'payplug_oney' => true,
                'payplug_oney_required_field' => $this->payplug->displayOneyRequiredFields(),
                'payplug_oney_allowed' => $is_elligible['result'],
                'payplug_oney_error' => $is_elligible['error'],
                'payplug_oney_loading_msg' => $this->payplug->l('Loading')
            ));
        }

        $installment_mode = $this->payplug->getConfiguration('PAYPLUG_INST_MODE');
        $this->context->smarty->assign(array(
            'installment_mode' => $installment_mode,
        ));

        // One-click Payment

        if ($this->payplug->getConfiguration('PAYPLUG_ONE_CLICK')) {
            $payplug_card = new PayPlugCard();
            $payplug_cards = $payplug_card->getByCustomer($cart->id_customer, true);

            if ($unified_mode && false) {
                $paymentOptions[] = array(
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . $img_lang,
                    'label' => $this->payplug->l('Credit card payment'),
                    'logo_url' => $this->payplug->_path . 'views/img/logos_schemes_' . $img_lang . '.png',
                    'payment_url' => PayplugBackward::getModuleLink($this->payplug->name, 'payment',
                        array('type' => 'oneclick', 'pc' => null), true),
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                );
                if (is_array($payplug_cards) && count($payplug_cards) > 0) {
                    foreach ($payplug_cards as $card) {
                        $paymentOptions[] = array(
                            'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . strtolower($card['brand']),
                            'label' => '**** **** **** ' . $card['last4'] . ' ' . $card['brand'] . ' ' . $this->payplug->l('Expiry date: ') . $card['expiry_date'],
                            'logo_url' => $this->payplug->_path . 'views/img/' . strtolower($card['brand']) . '.png',
                            'payment_url' => PayplugBackward::getModuleLink($this->payplug->name, 'payment',
                                array('type' => 'oneclick', 'pc' => (int)$card['id_payplug_card']), true),
                            'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                        );
                    }
                }
            } else {
                $paymentOptions[] = array(
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/one_click_payment.tpl',
                );
                $this->context->smarty->assign(array(
                    'payplug_cards' => $payplug_cards,
                    'payment_controller_url' => PayplugBackward::getModuleLink($this->payplug->name, 'payment', array(), true),
                ));
            }
        } else {
            // Standard Payment
            $paymentOptions[] = array(
                'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . $img_lang,
                'label' => $this->payplug->l('Pay with credit card'),
                'logo_url' => $this->payplug->_path . 'views/img/logos_schemes_' . $img_lang . '.png',
                'payment_url' => PayplugBackward::getModuleLink($this->payplug->name, 'payment', array('type' => 'standard'),
                    true),
                //'tpl' => _PS_MODULE_DIR_.'payplug/views/templates/hook/' . ($unified_mode ? 'unified_payment' : 'standard_payment') . '.tpl',
                'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/standard_payment.tpl',
            );
        }

        // Oney
        if ($this->payplug->getConfiguration('PAYPLUG_ONEY') && $is_oney_available) {
            if ($unified_mode) {
                if ($error) {
                    switch ($is_elligible['error_type']) {
                        case 'invalid_addresses':
                            $err_label = $this->payplug->l('Available for France only');
                            break;
                        case 'invalid_amount_bottom':
                        case 'invalid_amount_top':
                            $err_label = $this->payplug->l('Between 100€ and 3000€ only');
                            break;
                        case 'invalid_carrier' :
                            $err_label = $this->payplug->l('Unavailable for this shipping method');
                            break;
                        default:
                        case 'invalid_cart' :
                            $err_label = $this->payplug->l('Your cart is unavailable');
                            break;
                    }
                }

                $paymentOptions[] = array(
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-oney3x' . ($error ? '-alt' : ''),
                    'label' => $error ? $err_label : $this->payplug->l('Pay by card in 3x with Oney'),
                    'logo_url' => PayplugBackward::getHttpHost(true) . __PS_BASE_URI__ . 'modules/payplug/views/img/oney/3x' . ($error ? '-alt' : '') . '.svg',
                    'payment_url' => PayplugBackward::getModuleLink('payplug', 'payment', array('type' => 'oney', 'io' => 3), true),
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                );
                $paymentOptions[] = array(
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-oney4x' . ($error ? '-alt' : ''),
                    'label' => $error ? $err_label : $this->payplug->l('Pay by card in 4x with Oney'),
                    'logo_url' => PayplugBackward::getHttpHost(true) . __PS_BASE_URI__ . 'modules/payplug/views/img/oney/4x' . ($error ? '-alt' : '') . '.svg',
                    'payment_url' => PayplugBackward::getModuleLink('payplug', 'payment', array('type' => 'oney', 'io' => 4), true),
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                );
            } else {
                $paymentOptions[] = array(
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/oney_payment.tpl',
                );
            }
        }

        // Installments
        if ($paymentAllowedOptions['installment']) {
            if ($unified_mode && false) {
                $paymentOptions[] = array(
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . $img_lang . '-x' . (int)$installment_mode,
                    'label' => $this->payplug->l('Pay by card in') . (int)$installment_mode . $this->payplug->l('installments'),
                    'logo_url' => PayplugBackward::getHttpHost(true) . __PS_BASE_URI__ . 'modules/payplug/views/img/logos_schemes_installment_' . (int)$installment_mode . '_' . $img_lang . '.png',
                    'payment_url' => PayplugBackward::getModuleLink('payplug', 'payment', array('type' => 'installment', 'i' => (int)$installment_mode), true),
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                );
            } else {
                $this->context->smarty->assign(array(
                    'installment_controller_url' => PayplugBackward::getModuleLink('payplug', 'payment', array('i' => 1), true),
                ));
                $paymentOptions[] = array(
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/installment_payment.tpl',
                );
            }
        }

        return $paymentOptions;
    }
}