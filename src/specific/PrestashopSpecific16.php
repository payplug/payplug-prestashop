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
        $paymentOptions = array();
        $payment_class = 'payplug';
        $logo_class = 'paymentLogo';
        $oneyOptimized = (bool)$this->payplug->getConfiguration('PAYPLUG_ONEY_OPTIMIZED');

        $error = false;

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

        foreach($payment_options as $payment_option) {
            if ((isset($payment_option['name'])) && ($payment_option['name'] !== 'payplug_cards')) {
                $payplug_card = new PayPlugCard();
                $payplug_cards = $payplug_card->getByCustomer($cart->id_customer, true);
                $payplug_cards = (empty($payplug_cards)) ? '' : $payplug_cards;

                $extraClass = $img_lang;
                if (isset($payment_option['extra_classes'])) {
                    $extraClass = $payment_option['extra_classes'];
                }

                if ((bool)$this->payplug->getConfiguration('PAYPLUG_ONE_CLICK') && ($payment_option['name'] == 'standard')) {
                    continue;
                }
                else {
                    $paymentOptions[] = array(
                        'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . $extraClass . ($error ? '-alt' : ''),
                        'label' => $payment_option['callToActionText'],
                        'logo_url' => $payment_option['logo'],
                        'payment_url' => $payment_option['payment_controller_url'],
                        'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/hook_16/' . $payment_option['tpl'],
                    );
                }
                    if (isset($payment_option['payment_controller_url'])) {
                        $this->context->smarty->assign(array(
                            'payplug_cards' => $payplug_cards,
                            'payment_controller_url' => $payment_option['payment_controller_url'],
                        ));

                }
                    // Oney optimized : 1 payment option => 2 échéanciers, on sort de la boucle
                    // Oney non optimized : 2 payment options => 3x puis 4x
                    if ($oneyOptimized && ($payment_option['name'] == 'oney')) {
                        break;
                    }
            }
        }
        return $paymentOptions;
    }
}