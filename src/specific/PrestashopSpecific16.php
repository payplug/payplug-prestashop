<?php

namespace PayPlug\src\specific;

//use Media;
//use PayPlug\classes\PayPlugBackward;

use Context;
use PayplugBackward;
use PayPlugCard;
use PayPlugCarrier;

class PrestashopSpecific16
{
    public $context;

    public function __construct()
    {
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

    public function displayPaymentOption($cart, $paramPaymentOption)
    {
//        $paymentAllowedOptions = $this->getAllowedPaymentOptions($cart);
        $paymentAllowedOptions = $paramPaymentOption['getAllowedPaymentOptions'];
        $paymentOptions = array();
        $payment_class = 'payplug';
        $logo_class = 'paymentLogo';
        $unified_mode = $paramPaymentOption['unified_mode'];
//        $unified_mode = (bool)!$this->getConfiguration('PAYPLUG_ONEY_OPTIMIZED');
        $error = false;
        $is_oney_available = true;

        $current_lang = $paramPaymentOption['current_lang'];
//        $current_lang = explode('-', $this->context->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, array('it', 'en'))) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        if ($paramPaymentOption['getConfigPayplugOney']) {
//        if ($this->getConfiguration('PAYPLUG_ONEY')) {
            // check if at least one carrier is available for this cart
            // get the available carrier
            $package_list = $paramPaymentOption['getPackageList'];
//            $package_list = $cart->getPackageList();
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

            if ($paramPaymentOption['isLoadedObject'] && $paramPaymentOption['id_address_invoice'] && $paramPaymentOption['id_address_delivery']) {
//            if (Validate::isLoadedObject($cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
                $is_elligible = $paramPaymentOption['isOneyElligible'];
//                $is_elligible = $this->isOneyElligible($cart);
                $error = !$is_elligible['result'];
            } else {
//                $id_currency = $this->context->currency->id;
//                $amount = $cart->getOrderTotal(true, Cart::BOTH);
                $is_elligible = $paramPaymentOption['isValidOneyAmount'];
//                $is_elligible = $this->isValidOneyAmount($amount, $id_currency);
                $error = !$is_elligible['result'];
            }

            if (!$error && $has_valid_carrier) {
                $is_elligible = $paramPaymentOption['isValidOneyCarrier'];
//                $is_elligible = $this->isValidOneyCarrier($cart);
                $error = !$is_elligible['result'];
            }

            $this->context->smarty->assign(array(
                'payplug_module_dir' => _PS_MODULE_DIR_,
                'payplug_oney' => true,
                'payplug_oney_required_field' => $paramPaymentOption['displayOneyRequiredFields'],
//                'payplug_oney_required_field' => $this->displayOneyRequiredFields(),
                'payplug_oney_allowed' => $is_elligible['result'],
                'payplug_oney_error' => $is_elligible['error'],
                'payplug_oney_loading_msg' => $paramPaymentOption['tradLoading']
//                'payplug_oney_loading_msg' => $this->l('Loading')
            ));
        }

        $installment_mode = $paramPaymentOption['installment_mode'];
//        $installment_mode = $this->getConfiguration('PAYPLUG_INST_MODE');
        $this->context->smarty->assign(array(
            'installment_mode' => $installment_mode,
        ));

        // One-click Payment

        if ($paramPaymentOption['oneclick_mode']) {
//        if ($this->getConfiguration('PAYPLUG_ONE_CLICK')) {
            $payplug_card = new PayPlugCard();
            $payplug_cards = $payplug_card->getByCustomer($paramPaymentOption['id_customer'], true);

            if ($unified_mode && false) {
                $paymentOptions[] = array(
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . $img_lang,
                    'label' => $paramPaymentOption['tradCCpayment'],
//                    'label' => $this->l('Credit card payment'),
                    'logo_url' => $paramPaymentOption['thisPath'] . 'views/img/logos_schemes_' . $img_lang . '.png',
//                    'logo_url' => $this->_path . 'views/img/logos_schemes_' . $img_lang . '.png',
                    'payment_url' => PayplugBackward::getModuleLink($paramPaymentOption['thisName'], 'payment',
//                    'payment_url' => PayplugBackward::getModuleLink($this->name, 'payment',
                        array('type' => 'oneclick', 'pc' => null), true),
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                );
                if (is_array($payplug_cards) && count($payplug_cards) > 0) {
                    foreach ($payplug_cards as $card) {
                        $paymentOptions[] = array(
                            'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . strtolower($card['brand']),
                            'label' => '**** **** **** ' . $card['last4'] . ' ' . $card['brand'] . ' ' . $this->l('Expiry date: ') . $card['expiry_date'],
                            'logo_url' => $paramPaymentOption['thisPath'] . 'views/img/' . strtolower($card['brand']) . '.png',
//                            'logo_url' => $this->_path . 'views/img/' . strtolower($card['brand']) . '.png',
                            'payment_url' => PayplugBackward::getModuleLink($paramPaymentOption['thisName'], 'payment',
//                            'payment_url' => PayplugBackward::getModuleLink($this->name, 'payment',
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
                    'payment_controller_url' => PayplugBackward::getModuleLink($paramPaymentOption['thisName'], 'payment', array(), true),
//                    'payment_controller_url' => PayplugBackward::getModuleLink($this->name, 'payment', array(), true),
                ));
            }
        } else {
            // Standard Payment
            $paymentOptions[] = array(
                'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . $img_lang,
                'label' => $this->l('Pay with credit card'),
                'logo_url' => $paramPaymentOption['thisPath'] . 'views/img/logos_schemes_' . $img_lang . '.png',
//                'logo_url' => $this->_path . 'views/img/logos_schemes_' . $img_lang . '.png',
                'payment_url' => PayplugBackward::getModuleLink($paramPaymentOption['thisName'], 'payment', array('type' => 'standard'),
//                'payment_url' => PayplugBackward::getModuleLink($this->name, 'payment', array('type' => 'standard'),
                    true),
                //'tpl' => _PS_MODULE_DIR_.'payplug/views/templates/hook/' . ($unified_mode ? 'unified_payment' : 'standard_payment') . '.tpl',
                'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/standard_payment.tpl',
            );
        }

        // Oney
        if ($paramPaymentOption['oney_mode'] && $is_oney_available) {
//        if ($this->getConfiguration('PAYPLUG_ONEY') && $is_oney_available) {
            if ($unified_mode) {
                if ($error) {
                    switch ($is_elligible['error_type']) {
                        case 'invalid_addresses':
                            $err_label = $paramPaymentOption['tradAFFonly'];
//                            $err_label = $this->l('Available for France only');
                            break;
                        case 'invalid_amount_bottom':
                        case 'invalid_amount_top':
                            $err_label = $paramPaymentOption['tradB1A3only'];
//                            $err_label = $this->l('Between 100€ and 3000€ only');
                            break;
                        case 'invalid_carrier' :
                            $err_label = $paramPaymentOption['tradUFTSmethod'];
//                            $err_label = $this->l('Unavailable for this shipping method');
                            break;
                        default:
                        case 'invalid_cart' :
                            $err_label = $paramPaymentOption['tradYCIunav'];
//                            $err_label = $this->l('Your cart is unavailable');
                            break;
                    }
                }

                $paymentOptions[] = array(
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-oney3x' . ($error ? '-alt' : ''),
                    'label' => $error ? $err_label : $paramPaymentOption['tradPBCI3Woney'],
//                    'label' => $error ? $err_label : $this->l('Pay by card in 3x with Oney'),
                    'logo_url' => $paramPaymentOption['thisPath'] . 'views/img/oney/3x' . ($error ? '-alt' : '') . '.svg',
//                    'logo_url' => $this->_path . 'views/img/oney/3x' . ($error ? '-alt' : '') . '.svg',
                    'payment_url' => PayplugBackward::getModuleLink($paramPaymentOption['thisName'], 'payment',
                        array('type' => 'oney', 'io' => 3), true),
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                );
                $paymentOptions[] = array(
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-oney4x' . ($error ? '-alt' : ''),
                    'label' => $error ? $err_label : $paramPaymentOption['tradPBCI4Woney'],
//                    'label' => $error ? $err_label : $this->l('Pay by card in 4x with Oney'),
                    'logo_url' => $paramPaymentOption['thisPath'] . 'views/img/oney/4x' . ($error ? '-alt' : '') . '.svg',
//                    'logo_url' => $this->_path . 'views/img/oney/4x' . ($error ? '-alt' : '') . '.svg',
                    'payment_url' => PayplugBackward::getModuleLink($paramPaymentOption['thisName'], 'payment',
//                    'payment_url' => PayplugBackward::getModuleLink($this->name, 'payment',
                        array('type' => 'oney', 'io' => 4), true),
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
                    'label' => $paramPaymentOption['tradPBCin'] . (int)$installment_mode . $this->l('installments'),
//                    'label' => $this->l('Pay by card in') . (int)$installment_mode . $this->l('installments'),
                    'logo_url' => $paramPaymentOption['thisPath'] . 'views/img/logos_schemes_installment_' . (int)$installment_mode . '_' . $img_lang . '.png',
                    'payment_url' => PayplugBackward::getModuleLink($paramPaymentOption['thisName'], 'payment',
                        array('type' => 'installment', 'i' => (int)$installment_mode), true),
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/unified_payment.tpl',
                );
            } else {
                $this->context->smarty->assign(array(
                    'installment_controller_url' => PayplugBackward::getModuleLink($this->name, 'payment',
                        array('i' => 1), true),
                ));
                $paymentOptions[] = array(
                    'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/installment_payment.tpl',
                );
            }
        }

        return $paymentOptions;
    }
}