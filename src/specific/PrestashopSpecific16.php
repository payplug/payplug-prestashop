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

namespace PayPlug\src\specific;

use Media;

use PayplugBackward;
use PayPlugCarrier;
use Validate;

class PrestashopSpecific16
{
    private $oney;
    private $payplug;
    private $contextSpecific;

    public function __construct($payplug)
    {
        $this->oney = $payplug->getPlugin()->getOney();
        $this->payplug = $payplug;
        $this->contextSpecific = (new ContextSpecific())->getContext();
    }


    public function hookHeader()
    {
        $this->payplug->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/front_1_6.css');
        $this->payplug->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/front_1_6.js');

        Media::addJsDef([
            'payplug_ajax_url' => PayplugBackward::getModuleLink('payplug', 'ajax', [], true),
        ]);
        $this->payplug->getPlugin()->getOney()->assignOneyJSVar();
    }

    public function hookCustomerAccount()
    {
        $payplug_icon_url = 'modules/payplug/views/img/logo26.png';

        $this->contextSpecific->smarty->assign([
            'payplug_icon_url' => $payplug_icon_url
        ]);
    }

    public function displayPaymentOption($payment_options, $cart)
    {
        $paymentOptions = [];
        $payment_class = 'payplug';
        $logo_class = 'paymentLogo';
        $oneyOptimized = (bool)$this->payplug->getConfiguration('PAYPLUG_ONEY_OPTIMIZED');
        $error = false;

        $current_lang = explode('-', $this->contextSpecific->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, ['it', 'en'], true)) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        if ($this->payplug->getConfiguration('PAYPLUG_ONEY')) {
            // check if at least one carrier is available for this cart
            // get the available carrier

            $package_list = $cart->getPackageList();
            $carrier_ids = [];
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
            }

            if (Validate::isLoadedObject($cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
                $is_elligible = $this->oney->isOneyElligible($cart);
                $error = !$is_elligible['result'];
            } else {
                $id_currency = $this->contextSpecific->currency->id;
                $amount = $cart->getOrderTotal(true, Cart::BOTH);
                $is_elligible = $this->oney->isValidOneyAmount($amount, $id_currency);
                $error = !$is_elligible['result'];
            }

            if (!$error && $has_valid_carrier) {
                $is_elligible = $this->oney->isValidOneyCarrier($cart);
                $error = !$is_elligible['result'];
            }

            try {
                $this->contextSpecific->smarty->assign([
                    'payplug_module_dir' => _PS_MODULE_DIR_,
                    'payplug_oney' => true,
                    'payplug_oney_required_field' => $this->oney->displayOneyRequiredFields(),
                    'payplug_oney_allowed' => $is_elligible['result'],
                    'payplug_oney_error' => $is_elligible['error'],
                    'payplug_oney_loading_msg' => $this->payplug->l('Loading')
                ]);
            } catch (\Exception $e) {
                var_dump($e);
                exit;
            }
        }

        $payplug_cards = $this->payplug->getPlugin()->getCard()->getByCustomer($cart->id_customer, true);

        $payplug_cards = (empty($payplug_cards)) ? '' : $payplug_cards;

        foreach ($payment_options as $payment_option) {
            if ((isset($payment_option['name']))) {
                $payment_method = $payment_option['name'];
                $extraClass = (isset($payment_option['extra_classes'])) ? $payment_option['extra_classes'] : $img_lang;
                if ((bool)$this->payplug->getConfiguration('PAYPLUG_ONE_CLICK')
                    && !empty($payplug_cards)
                    && ($payment_method == 'standard')) {
                    continue;
                } else {
                    /*
                     * var_dump($payment_option['tpl']); :
                     * one_click.tpl (oneClick activé)
                     * standard.tpl (oneClick désactivé)
                     * installment.tpl
                     * oney.tpl (Oney optimisé)
                     * unified.tpl (Oney non optimisé)
                     */
                    $paymentOptions[$payment_method.'-'.$extraClass] = [
                        'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $logo_class . '-' . $extraClass .
                            ($error ? '-alt' : ''),
                        'label' => $payment_option['callToActionText'],
                        'logo_url' => $payment_method == 'one_click' ?
                            $payment_options['standard']['logo'] :
                            $payment_option['logo'],
                        'payment_url' => $payment_option['payment_controller_url'],
                        'tpl' => _PS_MODULE_DIR_ . 'payplug/views/templates/hook/checkout/payment/' .
                            $payment_option['tpl'],
                    ];
                }

                if (isset($payment_option['payment_controller_url'])) {
                    $this->contextSpecific->smarty->assign([
                        'payplug_cards' => $payplug_cards,
                        'payment_controller_url' => $payment_option['payment_controller_url'],
                    ]);
                }

                // Pour qu'il n'y ait qu'Oney avec échéancier 3x 4x
                if ($oneyOptimized && ($payment_method == 'oney')) {
                    break;
                }
            }
        }
        return $paymentOptions;
    }

    public function getPaymentOption()
    {
        return [
            'oneyLogo' => '3x4x.svg',
            'oneyCallToActionText' => 'Pay by card in 3 or 4'
        ];
    }

    public function installTab()
    {
        $translationsAdminPayPlugInstallment = [
            'en' => 'Installment Plans',
            'fr' => 'Paiements en plusieurs fois'
        ];

        $flag = $this->payplug->installModuleTab('AdminPayPlugInstallment', $translationsAdminPayPlugInstallment, 0);

        return $flag;
    }

    public function uninstallTab()
    {
        return ($this->payplug->uninstallModuleTab('AdminPayPlugInstallment'));
    }
}
