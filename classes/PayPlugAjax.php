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
    private $card;
    private $contextSpecific;
    private $oney;
    private $payplug;
    private $plugin;
    private $toolsSpecific;

    public function __construct()
    {
        try {
            $this->payplug = new \Payplug();
            $this->plugin = $this->payplug->getPlugin();
            $this->card = $this->plugin->getCard();
            $this->contextSpecific = $this->plugin->getContext(); // get ContextSpecific Repository object
            $this->oney = $this->plugin->getOney();
            $this->toolsSpecific = $this->plugin->getTools();
        } catch (Exception $e) {
            throw new Exception($e);
        }
    }

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
        $context = $this->contextSpecific->getContext(); // get the method
        $tools = $this->toolsSpecific;

        if (($tools->tool('getValue', '_ajax')) == 1) {
            if ($tools->tool('getIsset') == 'pc') {
                if ((int)$tools->tool('getValue', 'pay' == 1)) {
                    $is_installment = $tools->tool(getValue('i'));
                    $is_installment = (isset($is_installment)) && (($tools->tool('getValue', 'i')) == 1);
                    $is_deferred = $this->payplug->getConfiguration('PAYPLUG_DEFERRED') == 1;
                    $is_oney = $tools->tool(getValue('io'));
                    $options = [
                        'id_card' => $tools->tool(getValue('pc')),
                        'is_installment' => $is_installment,
                        'is_deferred' => $is_deferred,
                        'is_oney' => $is_oney,
                        '_ajax' => 1
                    ];
                    $payment = $this->payplug->preparePayment($options,$tools->tool('getValue', 'pc'));
                    die($tools->tool('jsonEncode', $payment));
                } else {
                    $cookie = $context->cookie;
                    $id_customer = (int)$cookie->id_customer;
                    if ((int)$id_customer == 0) {
                        die(false);
                    }
                    $payplug_card = new CardRepository($this->payplug);

                    if ($payplug_card->delete((int)$tools->tool('getValue', 'pc'))) {
                        die(true);
                    } else {
                        die(false);
                    }
                }
            } elseif ($tools->tool('getIsset', 'checkOneyAddresses')) {
                if (!$this->payplug->getConfiguration('PAYPLUG_ONEY')) {
                    die ($tools->tool('jsonEncode', array('result' => false, 'error' => false)));
                }
                $id_shipping = $tools->tool('getValue', 'id_address_delivery');
                $id_billing = $tools->tool('getValue', 'id_address_invoice');
                die ($tools->tool('jsonEncode', $this->oney->isValidOneyAddresses($id_shipping, $id_billing)));
            } elseif ($tools->tool('getIsset', 'isOneyElligible')) {
                $use_taxes = (bool)$this->payplug->getConfiguration('PS_TAX');

                if ($id_product = (int)$tools->tool('getValue', 'id_product')) {
                    $id_product_attribute = (int)$tools->tool('getValue', 'id_product_attribute', 0);
                    $quantity = (int)$tools->tool('getValue', 'quantity', 1);
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
                    $is_elligible = $this->oney->isValidOneyAmount($amount, $id_currency);
                } elseif (((int)$tools->tool('getValue', 'is_summary_cta')) === 1) {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $id_currency = $context->currency->id;
                    $is_elligible = $this->oney->isValidOneyAmount($amount, $id_currency);
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $cart = $context->cart;
                    $delivery_address = new Address($context->cart->id_address_delivery);
                    $delivery_country = new Country($delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;

                    if (Validate::isLoadedObject($cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
                        $is_elligible = $this->oney->isOneyElligible($cart, $amount, $iso_code);
                    } else {
                        $id_currency = $context->currency->id;
                        $is_elligible = $this->oney->isValidOneyAmount($amount, $id_currency, $iso_code);
                    }
                }

                die($tools->tool('jsonEncode', $is_elligible));
            } elseif ($tools->tool('getIsset', 'getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool)$this->payplug->getConfiguration('PS_TAX');

                if ($id_product = (int)$tools->tool('getValue', 'id_product')) {
                    $id_product_attribute = (int)$tools->tool('getValue', 'id_product_attribute', 0);
                    $quantity = (int)$tools->tool('getValue', 'quantity', 1);
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
                } elseif (((int)$tools->tool('getValue', 'is_summary_cta')) === 1) {
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
                    $payment_options = $this->oney->getOneyPriceAndPaymentOptions($cart, $amount, $iso_code);
                } catch (Exception $e) {
                    throw Exception($e);
                }

                die($tools->tool('jsonEncode', $payment_options));
            } elseif ($tools->tool('getIsset', 'getPaymentErrors')) {
                // check if errors
                $errors = $this->payplug->getPaymentErrorsCookie();

                if ($errors) {
                    die($tools->tool('jsonEncode', array('result' => $this->payplug->displayPaymentErrors($errors))));
                }

                die($tools->tool('jsonEncode', array('result' => false)));
            } elseif ($tools->tool('getIsset', 'savePaymentData')) {
                $payment_data = $tools->tool('getValue', 'payment_data');

                try {
                    if (empty($payment_data)) {
                        die($tools->tool('jsonEncode', array(
                            'result' => false,
                            'message' => $this->payplug->l('Empty payment data')
                        )));
                    } elseif ($this->oney->checkOneyRequiredFields($payment_data)) {
                        die($tools->tool('jsonEncode', array(
                            'result' => false,
                            'message' => $this->payplug->l('At least one of the fields is not correctly completed.')
                        )));
                    }
                } catch (Exception $e) {
                    throw Exception($e);
                }

                $result = $this->payplug->setPaymentDataCookie($payment_data);

                die($tools->tool('jsonEncode', array(
                    'result' => $result,
                    'message' => $result ? $this->payplug->l('Your information has been saved') : $this->payplug->l('An error occured. Please retry in few seconds.')
                )));
            }
        }
        die(false);
    }
}



