<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

/**
 * @description
 * Treat ajax call
 */
class PayplugAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @description
     * Method that is executed after init() and checkAccess().
     * Used to process user input.
     *
     * return void
     */
    public function postProcess()
    {
        require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
        require_once(_PS_MODULE_DIR_ . '../init.php');
        include_once(_PS_MODULE_DIR_ . 'payplug/payplug.php');

        if (Tools::getValue('_ajax') == 1) {
            $payplug = new Payplug();
            $context = Context::getContext();

            if (Tools::getIsset('pc')) {
                if ((int)Tools::getValue('pay') == 1) {
                    $payment = $payplug->preparePayment(['id_card' => Tools::getValue('pc')]);
                    die($payment);
                } elseif ((int)Tools::getValue('delete') == 1) {
                    $cookie = $context->cookie;
                    $id_customer = (int)$cookie->id_customer;
                    if ((int)$id_customer == 0) {
                        die(false);
                    }
                    $id_payplug_card = Tools::getValue('pc');
                    $valid_key = Payplug::setAPIKey();
                    $deleted = $payplug->deleteCard($id_customer, $id_payplug_card, $valid_key);
                    if ($deleted) {
                        die(true);
                    } else {
                        die(false);
                    }
                }
            } elseif (Tools::getIsset('getOneyCta')) {
                die(json_encode(array(
                    'result' => true,
                    'tpl' => $payplug->getOneyCTA(),
                )));
            } elseif (Tools::getIsset('isOneyElligible')) {
                $use_taxes = (bool)Configuration::get('PS_TAX');

                if ($id_product = (int)Tools::getValue('id_product')) {
                    $group = Tools::getValue('group');
                    // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
                    if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
                        $id_product_attribute = $group ? (int)Product::getIdProductAttributesByIdAttributes($id_product, $group) : 0;
                    } else {
                        $id_product_attribute = $group ? (int)Product::getIdProductAttributeByIdAttributes($id_product, $group) : 0;
                    }
                    $quantity = (int)Tools::getValue('qty', 1);
                    $product_price = Product::getPriceStatic((int)$id_product, $use_taxes, $id_product_attribute, 6,null, false, true, $quantity);
                    $amount = $product_price * $quantity;
                    $id_currency = $context->currency->id;
                    $is_elligible = $payplug->isValidOneyAmount($amount, $id_currency);
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $delivery_address = new Address($context->cart->id_address_delivery);
                    $delivery_country = new Country($delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;
                    $cart = $context->cart;
                    $is_elligible = $payplug->isOneyElligible($cart, $amount, $iso_code);
                }

                die(json_encode($is_elligible));
            } elseif (Tools::getIsset('getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool)Configuration::get('PS_TAX');

                if ($id_product = (int)Tools::getValue('id_product')) {
                    $group = Tools::getValue('group');
                    $id_product_attribute = $group ? (int)Product::getIdProductAttributeByIdAttributes($id_product, $group) : 0;
                    // Some integration will not use qty data but quantity_wanted
                    $quantity = (int)Tools::getValue('qty', (int)Tools::getValue('quantity_wanted', 1));
                    $product_price = Product::getPriceStatic((int)$id_product, $use_taxes, $id_product_attribute, 6,null, false, true, $quantity);
                    $amount = $product_price * $quantity;
                    $cart = false;
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $cart = $context->cart;
                }

                $payment_options = $payplug->getOneyPriceAndPaymentOptions($cart, $amount);
                die(json_encode($payment_options));
            } elseif (Tools::getIsset('getPaymentErrors')) {
                // check if errors
                $errors = $payplug->getPaymentErrorsCookie();

                if ($errors) {
                    die(json_encode(['result' => true, 'template' => $payplug->displayPaymentErrors($errors)]));
                }

                die(json_encode(['result' => false]));
            } elseif (Tools::getIsset('savePaymentData')) {
                $payment_data = Tools::getValue('payment_data');

                if (empty($payment_data)) {
                    die(json_encode([
                        'result' => false,
                        'message' => [$payplug->l('Empty payment data')]
                    ]));
                } elseif ($payplug->checkOneyRequiredFields($payment_data)) {
                    die(json_encode([
                        'result' => false,
                        'message' => [$payplug->l('At least one of the fields is not correctly completed.')]
                    ]));
                }

                $result = $payplug->setPaymentDataCookie($payment_data);
                die(json_encode([
                    'result' => $result,
                    'message' => [$result ? $payplug->l('Your information has been saved') : $payplug->l('An error occured. Please retry in few seconds.')]
                ]));
            }
        }
    }
}
