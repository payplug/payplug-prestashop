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

namespace PayPlug\classes;

use Exception;

require_once _PS_MODULE_DIR_ . 'payplug/classes/PayplugLock.php';

/**
 * Class PayPlugAjax
 * use for treat ajax on prestashop 1.6
 */
class PayPlugAjax
{
    private $address;
    private $card;
    private $config;
    private $context;
    private $country;
    private $dependencies;
    private $oney;
    private $product;
    private $paymentClass;
    private $toolsAdapter;
    private $translate;
    private $validate;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();

        $this->address = $this->dependencies->getPlugin()->getAddress();
        $this->card = $this->dependencies->getPlugin()->getCard();
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->context = $this->dependencies->getPlugin()->getContext()->get();
        $this->country = $this->dependencies->getPlugin()->getCountry();
        $this->oney = $this->dependencies->getPlugin()->getOney();
        $this->product = $this->dependencies->getPlugin()->getProduct();
        $this->toolsAdapter = $this->dependencies->getPlugin()->getTools();
        $this->translate = $this->dependencies->getPlugin()->getTranslate();
        $this->validate = $this->dependencies->getPlugin()->getValidate();

        $this->paymentClass = $this->dependencies->paymentClass;
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
     * @description  Manage ajax processing
     *
     * @throws Exception
     */
    public function postProcess()
    {
        $this->context = $this->context->getContext(); // get the method
        $tools = $this->toolsAdapter;

        if (($tools->tool('getValue', '_ajax')) == 1) {
            if ($tools->tool('getIsset', 'pc')) {
                if ((int) $tools->tool('getValue', 'pay') == 1) {
                    $is_installment = $tools->tool('getValue', 'i');
                    $is_installment = (isset($is_installment)) && (($tools->tool('getValue', 'i')) == 1);
                    $is_deferred = $this->config->get(
                        $this->dependencies->getConfigurationKey('deferred')
                    ) == 1;
                    $is_oney = $tools->tool('getValue', 'io');
                    $is_bancontact = $tools->tool('getValue', 'bancontact');
                    $is_amex = $tools->tool('getValue', 'amex');
                    $options = [
                        'id_card' => $tools->tool('getValue', 'pc'),
                        'is_installment' => $is_installment,
                        'is_deferred' => $is_deferred,
                        'is_bancontact' => $is_bancontact,
                        'is_oney' => $is_oney,
                        'is_amex' => $is_amex,
                        '_ajax' => 1,
                    ];
                    $payment = $this->paymentClass->preparePayment($options);

                    exit(json_encode($payment));
                }
                $cookie = $this->context->cookie;
                $id_customer = (int) $cookie->id_customer;
                if ((int) $id_customer == 0) {
                    exit(false);
                }
                $id_payplug_card = $tools->tool('getValue', 'pc');
                $deleted = $this->card->deleteCard((int) $id_customer, (int) $id_payplug_card);

                if ($deleted) {
                    exit(true);
                }

                exit(false);
            }
            if ($tools->tool('getIsset', 'checkOneyAddresses')) {
                if (!$this->config->get(
                    $this->dependencies->getConfigurationKey('oney')
                )) {
                    exit(json_encode(['result' => false, 'error' => false]));
                }
                $id_shipping = $tools->tool('getValue', 'id_address_delivery');
                $id_billing = $tools->tool('getValue', 'id_address_invoice');

                exit(json_encode($this->oney->isValidOneyAddresses($id_shipping, $id_billing)));
            }
            if ($tools->tool('getIsset', 'isOneyElligible')) {
                $use_taxes = (bool) $this->config->get('PS_TAX');

                if ($id_product = (int) $tools->tool('getValue', 'id_product')) {
                    $id_product_attribute = (int) $tools->tool('getValue', 'id_product_attribute', 0);
                    $quantity = (int) $tools->tool('getValue', 'quantity', 1);
                    $quantity = $quantity ? $quantity : 1;
                    $product_price = $this->product->getPriceStatic(
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
                    $is_elligible = $this->oney->isValidOneyAmount($amount);
                } elseif (((int) $tools->tool('getValue', 'is_summary_cta')) === 1) {
                    $amount = $this->context->cart->getOrderTotal($use_taxes);
                    $is_elligible = $this->oney->isValidOneyAmount($amount);
                } else {
                    $amount = $this->context->cart->getOrderTotal($use_taxes);
                    $cart = $this->context->cart;
                    $delivery_address = $this->address->get((int) $this->context->cart->id_address_delivery);
                    $delivery_country = $this->country->get((int) $delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;

                    if ($this->validate->validate('isLoadedObject', $cart) && $cart->id_address_invoice && $cart->id_address_delivery) {
                        $is_elligible = $this->oney->isOneyElligible($cart, $amount, $iso_code);
                    } else {
                        $is_elligible = $this->oney->isValidOneyAmount($amount);
                    }
                }

                exit(json_encode($is_elligible));
            }
            if ($tools->tool('getIsset', 'getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool) $this->config->get('PS_TAX');

                if ($id_product = (int) $tools->tool('getValue', 'id_product')) {
                    $id_product_attribute = (int) $tools->tool('getValue', 'id_product_attribute', 0);
                    $quantity = (int) $tools->tool('getValue', 'quantity', 1);
                    $quantity = $quantity ? $quantity : 1;
                    $product_price = $this->product->getPriceStatic(
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
                } elseif (((int) $tools->tool('getValue', 'is_summary_cta')) === 1) {
                    $cart = false;
                    $amount = $this->context->cart->getOrderTotal($use_taxes);
                    $iso_code = false;
                } else {
                    $amount = $this->context->cart->getOrderTotal($use_taxes);
                    $delivery_address = $this->address->get((int) $this->context->cart->id_address_delivery);
                    $delivery_country = $this->country->get((int) $delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;
                    $cart = $this->context->cart;
                }

                try {
                    $payment_options = $this->oney->getOneyPriceAndPaymentOptions($cart, $amount, $iso_code);
                } catch (Exception $e) {
                    exit(json_encode([
                        'result' => false,
                        'error' => $this->translate->translate(5), //('Oney is momentarily unavailable.')
                    ]));
                }

                exit(json_encode($payment_options));
            }
            if ($tools->tool('getIsset', 'getPaymentErrors')) {
                // check if errors
                $errors = $this->paymentClass->getPaymentErrorsCookie();

                if ($errors) {
                    exit(json_encode(['result' => $this->paymentClass->displayPaymentErrors($errors)]));
                }

                exit(json_encode(['result' => false]));
            }
            if ($tools->tool('getIsset', 'savePaymentData')) {
                $payment_data = $tools->tool('getValue', 'payment_data');

                try {
                    if (empty($payment_data)) {
                        exit(json_encode([
                            'result' => false,
                            'message' => $this->translate->translate(1), //('Empty payment data')
                        ]));
                    }
                    if ($this->oney->checkOneyRequiredFields($payment_data)) {
                        exit(json_encode([
                            'result' => false,
                            'message' => $this->translate->translate(2),
                        ]));
                    }
                } catch (Exception $e) {
                    throw new Exception($e);
                }

                $result = $this->paymentClass->setPaymentDataCookie($payment_data);

                exit(json_encode([
                    'result' => $result,
                    'message' => $result ?
                        $this->translate->translate(3) : //('Your information has been saved')
                        $this->translate->translate(4), //('An error occurred. Please retry in few seconds.')
                ]));
            }
        }

        exit(false);
    }
}
