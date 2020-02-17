<?php
/**
 * 2013 - 2019 PayPlug SAS
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
 * @copyright 2013 - 2019 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

class PayplugAjaxModuleFrontController extends ModuleFrontController
{
    public function postProcess()
    {
        require_once(dirname(__FILE__) . '/../../../../config/config.inc.php');
        require_once(_PS_MODULE_DIR_ . '../init.php');
        include_once(_PS_MODULE_DIR_ . 'payplug/payplug.php');

        if (Tools::getValue('_ajax') == 1) {
            $payplug = new Payplug();
            if (Tools::getIsset('pc')) {
                if ((int)Tools::getValue('pay') == 1) {
                    $id_cart = (int)Tools::getValue('cart');
                    $id_card = Tools::getValue('pc');
                    $payment = $payplug->preparePayment($id_cart, $id_card, false);
                    die($payment);
                } elseif ((int)Tools::getValue('delete') == 1) {
                    $context = Context::getContext();
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
            } elseif (Tools::getIsset('getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool)Configuration::get('PS_TAX');

                if ($id_product = (int)Tools::getValue('id_product')) {
                    $id_product_attribute = (int)Product::getIdProductAttributeByIdAttributes(
                        $id_product,
                        Tools::getValue('group')
                    );
                    $quantity = (int)Tools::getValue('quantity', 1);

                    $product_price = Product::getPriceStatic((int) $id_product, $use_taxes, $id_product_attribute, 6, null, false, true, $quantity);
                    $amount = $product_price * $quantity;
                    $iso_code = false;
                    $cart = false;
                } else {
                    $context = Context::getContext();
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $delivery_address = new Address($context->cart->id_address_delivery);
                    $delivery_country = new Country($delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;
                    $cart = $context->cart;
                }

                $payment_options = $payplug->getOneyPriceAndPaymentOptions($cart, $amount, $iso_code);
                die(json_encode($payment_options));
            }
        }
    }
}
