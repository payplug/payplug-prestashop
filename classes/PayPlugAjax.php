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

use PayPlug\src\repositories\CardRepository;

require_once(_PS_MODULE_DIR_ . 'payplug/classes/PayplugLock.php');

/**
 * Class PayPlugAjax
 * use for treat ajax on prestashop 1.6
 */
class PayPlugAjax
{
    /**
     * @description entry point
     */
    public function run()
    {
        //todo: split code into different functions if needed
        $this->postProcess();
    }


    /**
     * Manage ajax processing
     */
    public function postProcess()
    {
        if (Tools::getValue('_ajax') == 1) {
            try {
                $payplug = new Payplug();
            } catch (Exception $e) {
                throw Exception($e);
            }
            $context = Context::getContext();
            if (Tools::getIsset('pc')) {
                if ((int)Tools::getValue('pay') == 1) {
                    $is_installment = Tools::getValue('i');
                    $is_installment = (isset($is_installment)) && (Tools::getValue('i') == 1);
                    $is_deferred = $payplug->getConfiguration('PAYPLUG_DEFERRED') == 1;
                    $is_oney = Tools::getValue('io');
                    $options = [
                        'id_card' => Tools::getValue('pc'),
                        'is_installment' => $is_installment,
                        'is_deferred' => $is_deferred,
                        'is_oney' => $is_oney,
                        '_ajax' => 1
                    ];
                    $payment = $payplug->preparePayment($options,Tools::getValue('pc'));
                    die(Tools::jsonEncode($payment));
                } else {
                    $cookie = $context->cookie;
                    $id_customer = (int)$cookie->id_customer;
                    if ((int)$id_customer == 0) {
                        die(false);
                    }
                    $payplug_card = new CardRepository();

                    if ($payplug_card->delete((int)Tools::getValue('pc'))) {
                        die(true);
                    } else {
                        die(false);
                    }
                }
            } elseif (Tools::getIsset('checkOneyAddresses')) {
                if (!$payplug->getConfiguration('PAYPLUG_ONEY')) {
                    return die(Tools::jsonEncode(array('result' => false, 'error' => false)));
                }
                $id_shipping = Tools::getValue('id_address_delivery');
                $id_billing = Tools::getValue('id_address_invoice');
                die(Tools::jsonEncode($payplug->oneyRepository->isValidOneyAddresses($id_shipping, $id_billing)));
            } elseif (Tools::getIsset('isOneyElligible')) {
                $use_taxes = (bool)$payplug->getConfiguration('PS_TAX');

                if ($id_product = (int)Tools::getValue('id_product')) {
                    $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);
                    $quantity = (int)Tools::getValue('quantity', 1);
                    $product_price = Product::getPriceStatic(
                        $id_product,
                        $use_taxes,
                        $id_product_attribute,
                        6,
                        null,
                        false,
                        true,
                        $quantity
                    );
                    $amount = $product_price * $quantity;
                    $id_currency = $context->currency->id;
                    $is_elligible = $payplug->oneyRepository->isValidOneyAmount($amount, $id_currency);
                } elseif ((int)Tools::getValue('is_summary_cta') === 1) {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $id_currency = $context->currency->id;
                    $is_elligible = $payplug->oneyRepository->isValidOneyAmount($amount, $id_currency);
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $cart = $context->cart;
                    $delivery_address = new Address($context->cart->id_address_delivery);
                    $delivery_country = new Country($delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;

                    if (Validate::isLoadedObject($cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
                        $is_elligible = $payplug->oneyRepository->isOneyElligible($cart, $amount, $iso_code);
                    } else {
                        $id_currency = $context->currency->id;
                        $is_elligible = $payplug->oneyRepository->isValidOneyAmount($amount, $id_currency, $iso_code);
                    }
                }

                die(Tools::jsonEncode($is_elligible));
            } elseif (Tools::getIsset('getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool)$payplug->getConfiguration('PS_TAX');

                if ($id_product = (int)Tools::getValue('id_product')) {
                    $id_product_attribute = (int)Tools::getValue('id_product_attribute', 0);
                    $quantity = (int)Tools::getValue('quantity', 1);
                    $product_price = Product::getPriceStatic(
                        $id_product,
                        $use_taxes,
                        $id_product_attribute,
                        6,
                        null,
                        false,
                        true,
                        $quantity
                    );
                    $amount = $product_price * $quantity;
                    $iso_code = false;
                    $cart = false;
                } elseif ((int)Tools::getValue('is_summary_cta') === 1) {
                    $cart = false;
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $iso_code = false;
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $delivery_address = new Address($context->cart->id_address_delivery);
                    $delivery_country = new Country($delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;
                    $cart = $context->cart;
                }

                try {
                    $payment_options = $payplug->oneyRepository->getOneyPriceAndPaymentOptions($cart, $amount, $iso_code);
                } catch (Exception $e) {
                    throw Exception($e);
                }

                die(Tools::jsonEncode($payment_options));
            } elseif (Tools::getIsset('getPaymentErrors')) {
                // check if errors
                $errors = $payplug->getPaymentErrorsCookie();

                if ($errors) {
                    die(Tools::jsonEncode(array('result' => $payplug->displayPaymentErrors($errors))));
                }

                die(Tools::jsonEncode(array('result' => false)));
            } elseif (Tools::getIsset('savePaymentData')) {
                $payment_data = Tools::getValue('payment_data');

                try {
                    if (empty($payment_data)) {
                        die(Tools::jsonEncode(array(
                            'result' => false,
                            'message' => $payplug->l('Empty payment data')
                        )));
                    } elseif ($payplug->oneyRepository->checkOneyRequiredFields($payment_data)) {
                        die(Tools::jsonEncode(array(
                            'result' => false,
                            'message' => $payplug->l('At least one of the fields is not correctly completed.')
                        )));
                    }
                } catch (Exception $e) {
                    throw Exception($e);
                }

                $result = $payplug->setPaymentDataCookie($payment_data);

                die(Tools::jsonEncode(array(
                    'result' => $result,
                    'message' => $result ? $payplug->l('Your information has been saved') : $payplug->l('An error occured. Please retry in few seconds.')
                )));
            }
        }

        die(false);
    }
}



