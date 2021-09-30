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

use Configuration;
use Language;
use Media;
use Validate;
use Tab;
use Tools;

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
        $this->payplug->mediaClass->addCSSRC(__PS_BASE_URI__ . 'modules/payplug/views/css/front_1_6.css');
        $this->payplug->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/front_1_6.js');
        $this->payplug->mediaClass->addJsRC(__PS_BASE_URI__ . 'modules/payplug/views/js/utilities.js');

        Media::addJsDef([
            'payplug_ajax_url' => $this->contextSpecific->link->getModuleLink('payplug', 'ajax', [], true),
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
        $oneyOptimized = (bool)Configuration::get('PAYPLUG_ONEY_OPTIMIZED');
        $error = false;

        $current_lang = explode('-', $this->contextSpecific->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, ['it', 'en'], true)) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        if (Configuration::get('PAYPLUG_ONEY')) {
            // check if at least one carrier is available for this cart
            // get the available carrier

            $package_list = $cart->getPackageList();
            $carrier_ids = [];
            foreach ($package_list as $address) {
                foreach ($address as $package) {
                    $carrier_ids = array_merge($carrier_ids, $package['carrier_list']);
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

        $payplug_cards = $this->payplug->getPlugin()->getCard()->getByCustomer((int)$cart->id_customer, true);
        $payplug_cards = (empty($payplug_cards)) ? '' : $payplug_cards;

        foreach ($payment_options as $payment_option) {
            if ((isset($payment_option['name']))) {
                $payment_method = $payment_option['name'];
                $extraClass = (isset($payment_option['extra_classes'])) ? $payment_option['extra_classes'] : $img_lang;
                if ((bool)Configuration::get('PAYPLUG_ONE_CLICK')
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

                    // Check if Oney is on error
                    $payment_option['oney_error'] = false;
                    if ($payment_method == 'oney'
                        && (isset($payment_option['err_label']) && $payment_option['err_label'])) {
                        $payment_option['oney_error'] = '-disabled';
                        if ($oneyOptimized) {
                            $payment_option['logo'] = str_replace('x3_', 'x3x4_', $payment_option['logo']);
                        }
                    }

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
                        'oney_error' => $payment_option['oney_error'],
                    ];
                }

                if (isset($payment_option['payment_controller_url'])) {
                    $this->contextSpecific->smarty->assign([
                        'payplug_cards' => $payplug_cards,
                        'payment_controller_url' => $payment_option['payment_controller_url'],
                    ]);
                }

                // Pour qu'il n'y ait qu'Oney avec échéancier 3x 4x
                if ($oneyOptimized && $payment_method == 'oney') {
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

    // todo: set Tab install process in a specific
    public function installTab()
    {
        $installed = true;

        if (!Tab::getIdFromClassName('AdminPayPlugInstallment')) {
            $translations = [
                'en' => 'Installment Plans',
                'gb' => 'Installment Plans',
                'it' => 'Pagamenti frazionati',
                'fr' => 'Paiements en plusieurs fois'
            ];

            $adminPayPlugId = Tab::getIdFromClassName('AdminPayPlug');

            $tab = new Tab();
            foreach (Language::getLanguages(false) as $language) {
                $iso_code = Tools::strtolower($language['iso_code']);
                if (isset($translations[$iso_code])) {
                    $tab->name[(int)$language['id_lang']] = $translations[$iso_code];
                } else {
                    $tab->name[(int)$language['id_lang']] = $translations['en'];
                }
            }

            $tab->class_name = 'AdminPayPlugInstallment';
            $tab->module = $this->payplug->name;
            $tab->id_parent = $adminPayPlugId;
            $installed = $installed && $tab->save();
        }

        return $installed;
    }

    // todo: set Tab uninstall process in a specific
    public function uninstallTab()
    {
        $flag = true;

        $idTab = Tab::getIdFromClassName('AdminPayPlugInstallment');
        if ($idTab) {
            $tab = new Tab($idTab);
            $flag = $flag && $tab->delete();
            unset($idTab);
        }

        return $flag;
    }
    /**
     * @description Link to order by order state
     *
     * @param int $order_state
     * @return string
     */
    public function getOrdersByStateLink($order_state)
    {
        if ($this->contextSpecific->cookie->__get('submitFilterorder')) {
            $this->contextSpecific->cookie->__unset('submitFilterorder');
        }
        $this->contextSpecific->cookie->__set('submitFilterorder', 1);

        if ($this->contextSpecific->cookie->__get('ordersorderFilter_os!id_order_state')) {
            $this->contextSpecific->cookie->__unset('ordersorderFilter_os!id_order_state');
        }
        $this->contextSpecific->cookie->__set('ordersorderFilter_os!id_order_state', $order_state);

        if ($this->contextSpecific->cookie->__get('ordersorderFilter_a!date_add')) {
            $this->contextSpecific->cookie->__unset('ordersorderFilter_a!date_add');
        }
        $this->contextSpecific->cookie->__set('ordersorderFilter_a!date_add', '["",""]');

        $this->contextSpecific->cookie->write();

        $link = $this->contextSpecific->link->getAdminLink('AdminOrders', true);
        return $link;
    }
}
