<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\application\adapter;

use Language;
use Media;
use PayPlug\classes\DependenciesClass;
use Tab;
use Tools;
use Validate;

class PrestashopAdapter16
{
    private $card;
    private $config;
    private $constant;
    private $dependencies;
    private $oney;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
        $this->card = $this->dependencies->getPlugin()->getCard();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->oney = $this->dependencies->getPlugin()->getOney();
    }

    public function displayHeader()
    {
        $views_path = $this->constant->get('__PS_BASE_URI__') . 'modules/' . $this->dependencies->name . '/views/';
        $this->context->controller->addCSS($views_path . '/css/front_1_6-v' . $this->dependencies->version . '.css');
        $this->context->controller->addJS($views_path . '/js/front_1_6-v' . $this->dependencies->version . '.js');
        $this->context->controller->addJS($views_path . '/js/utilities-v' . $this->dependencies->version . '.js');

        Media::addJsDef([
            'payplug_ajax_url' => $this->context->link->getModuleLink('payplug', 'ajax', [], true),
        ]);
        $this->oney->assignOneyJSVar();
    }

    public function customerAccount()
    {
        $payplug_icon_url = 'modules/' . $this->dependencies->name . '/views/img/logo26.png';

        $this->context->smarty->assign([
            'payplug_icon_url' => $payplug_icon_url,
        ]);
    }

    public function displayPaymentOption($payment_options, $cart)
    {
        $paymentOptions = [];
        $payment_class = 'payplug';
        $optimized_class = '';
        $logo_class = 'paymentLogo';
        $oneyOptimized = (bool) $this->config->get('PAYPLUG_ONEY_OPTIMIZED');
        $error = false;

        $current_lang = explode('-', $this->context->language->language_code);
        $current_lang = $current_lang[0];
        if (in_array($current_lang, ['it', 'en'], true)) {
            $img_lang = $current_lang;
        } else {
            $img_lang = 'default';
        }

        if ($this->config->get('PAYPLUG_ONEY')) {
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
                $amount = $cart->getOrderTotal(true);
                $is_elligible = $this->oney->isValidOneyAmount($amount);
                $error = !$is_elligible['result'];
            }

            try {
                $this->context->smarty->assign([
                    'payplug_module_dir' => _PS_MODULE_DIR_,
                    'payplug_oney' => true,
                    'payplug_oney_required_field' => $this->oney->displayOneyRequiredFields(),
                    'payplug_oney_allowed' => $is_elligible['result'],
                    'payplug_oney_error' => $is_elligible['error'],
                    'payplug_oney_loading_msg' => $this->dependencies->l('Loading', 'prestashopadapter16'),
                ]);
            } catch (\Exception $e) {
                var_dump($e);

                exit;
            }
        }

        $payplug_cards = $this->card->getByCustomer((int) $cart->id_customer, true);
        $payplug_cards = (empty($payplug_cards)) ? '' : $payplug_cards;

        foreach ($payment_options as &$payment_option) {
            if ((isset($payment_option['name']))) {
                $payment_method = $payment_option['name'];
                $extraClass = (isset($payment_option['extra_classes'])) ? $payment_option['extra_classes'] : $img_lang;
                if ((bool) $this->config->get('PAYPLUG_ONE_CLICK')
                    && !empty($payplug_cards)
                    && ($payment_method == 'standard')) {
                    continue;
                }
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
                        $optimized_class = ' -optimized-16';
                    }
                }
                if ($payment_method == 'oney' && $oneyOptimized) {
                    $optimized_class = ' -optimized-16';
                    $oneyImageOptimized = '/views/img/oney/x3x4_with';
                    $oneyImagex3 = '/views/img/oney/x3_with';
                    $oneyImagex4 = '/views/img/oney/x4_with';
                    $oneyImage = '';

                    $use_fees = (bool) $this->config->get('PAYPLUG_ONEY_FEES');
                    if (!$use_fees) {
                        $oneyImage .= 'out';
                    }

                    $oneyImage .= '_fees';

                    if (strpos($payment_option['type'], 'without_fees')) {
                        $iso = Tools::strtoupper($this->context->getContext()->language->iso_code);
                        $merchant_company_iso = (string) $this->config->get('PAYPLUG_COMPANY_ISO');
                        if ($iso != 'IT' && $iso != 'FR') {
                            $iso = $merchant_company_iso;
                        }

                        $oneyImage .= '_' . $iso;
                    }

                    if ($error !== false) {
                        $oneyImage .= '_alt.svg';
                        $payment_option['logo'] = Media::getMediaPath(
                            _PS_MODULE_DIR_ . $this->dependencies->name . $oneyImageOptimized . $oneyImage
                        );
                    } else {
                        $oneyImage .= '.svg';
                        $payment_option['logo'] = [
                            'optimized' => Media::getMediaPath(
                                _PS_MODULE_DIR_ . $this->dependencies->name . $oneyImageOptimized . $oneyImage
                            ),
                            'x3' => Media::getMediaPath(
                                _PS_MODULE_DIR_ . $this->dependencies->name . $oneyImagex3 . $oneyImage
                            ),
                            'x4' => Media::getMediaPath(
                                _PS_MODULE_DIR_ . $this->dependencies->name . $oneyImagex4 . $oneyImage
                            ),
                        ];
                    }
                }

                $paymentOptions[$payment_method . '-' . $extraClass] = [
                    'extra_classes' => $payment_class . ' ' . $logo_class . ' ' . $extraClass .
                        ($error ? '_alt' : '') . ' ' . $optimized_class . ' ' . $extraClass,
                    'label' => $payment_option['callToActionText'],
                    'logo_url' => $payment_method == 'one_click' ?
                        $payment_options['standard']['logo'] :
                        $payment_option['logo'],
                    'payment_url' => $payment_option['payment_controller_url'],
                    'tpl' => _PS_MODULE_DIR_ . $this->dependencies->name . '/views/templates/hook/checkout/payment/' .
                        $payment_option['tpl'],
                    'oney_error' => $payment_option['oney_error'],
                ];

                if (isset($payment_option['payment_controller_url'])) {
                    $this->context->smarty->assign([
                        'payplug_cards' => $payplug_cards,
                        'payment_controller_url' => $payment_option['payment_controller_url'],
                    ]);
                }
            }
        }

        // Pour qu'il n'y ait qu'Oney avec échéancier 3x 4x
        if ($oneyOptimized) {
            unset($paymentOptions['oney-oney4x']);
        }

        return $paymentOptions;
    }

    public function getPaymentOption()
    {
        return [
            'oneyLogo' => '3x4x.svg',
            'oneyCallToActionText' => 'Pay by card in 3 or 4',
        ];
    }

    // todo: set Tab install process in a adapter
    public function installTab()
    {
        if ('payplug' != $this->dependencies->name) {
            return true;
        }

        $installed = true;

        if (!Tab::getIdFromClassName('AdminPayPlug')) {
            $translations = [
                'en' => 'Payplug',
                'gb' => 'Payplug',
                'it' => 'Payplug',
                'fr' => 'Payplug',
            ];

            $tab = new Tab();
            foreach (Language::getLanguages(false) as $language) {
                $iso_code = Tools::strtolower($language['iso_code']);
                if (isset($translations[$iso_code])) {
                    $tab->name[(int) $language['id_lang']] = $translations[$iso_code];
                } else {
                    $tab->name[(int) $language['id_lang']] = $translations['en'];
                }
            }

            $tab->class_name = 'AdminPayPlug';
            $tab->module = $this->dependencies->name;
            $tab->id_parent = 0;
            $tab->active = false;
            $installed = $tab->save();
        }

        if (!$installed) {
            return false;
        }

        if (!Tab::getIdFromClassName('AdminPayPlugInstallment')) {
            $translations = [
                'en' => 'Installment Plans',
                'gb' => 'Installment Plans',
                'it' => 'Pagamenti frazionati',
                'fr' => 'Paiements en plusieurs fois',
            ];

            $adminPayPlugId = Tab::getIdFromClassName('AdminPayPlug');

            $tab = new Tab();
            foreach (Language::getLanguages(false) as $language) {
                $iso_code = Tools::strtolower($language['iso_code']);
                if (isset($translations[$iso_code])) {
                    $tab->name[(int) $language['id_lang']] = $translations[$iso_code];
                } else {
                    $tab->name[(int) $language['id_lang']] = $translations['en'];
                }
            }

            $tab->class_name = 'AdminPayPlugInstallment';
            $tab->module = $this->dependencies->name;
            $tab->id_parent = $adminPayPlugId;
            $installed = $installed && $tab->save();
        }

        return $installed;
    }

    // todo: set Tab uninstall process in a adapter
    public function uninstallTab()
    {
        if ('payplug' != $this->dependencies->name) {
            return true;
        }

        $flag = true;

        $idTab = Tab::getIdFromClassName('AdminPayPlug');
        if ($idTab) {
            $tab = new Tab($idTab);
            $flag = $flag && $tab->delete();
            unset($idTab);
        }

        if (!$flag) {
            return false;
        }

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
     *
     * @return string
     */
    public function getOrdersByStateLink($order_state)
    {
        if ($this->context->cookie->__get('submitFilterorder')) {
            $this->context->cookie->__unset('submitFilterorder');
        }
        $this->context->cookie->__set('submitFilterorder', 1);

        if ($this->context->cookie->__get('ordersorderFilter_os!id_order_state')) {
            $this->context->cookie->__unset('ordersorderFilter_os!id_order_state');
        }
        $this->context->cookie->__set('ordersorderFilter_os!id_order_state', $order_state);

        if ($this->context->cookie->__get('ordersorderFilter_a!date_add')) {
            $this->context->cookie->__unset('ordersorderFilter_a!date_add');
        }
        $this->context->cookie->__set('ordersorderFilter_a!date_add', '["",""]');

        $this->context->cookie->write();

        return $this->context->link->getAdminLink('AdminOrders', true);
    }

    /**
     * @description Check if string is Plaintext Password
     *
     * @param $plaintextPasswd
     * @param int $size
     *
     * @return bool
     */
    public function isPlaintextPassword($plaintextPasswd, $size = 5)
    {
        return Tools::strlen($plaintextPasswd) >= $size && Tools::strlen($plaintextPasswd) <= 72;
    }
}
