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
 * @copyright 2013 - 2019 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

/**
 * @description
 * Treat ajax call
 */
class PayplugAjaxModuleFrontController extends ModuleFrontController
{
    private $card;
    private $configurationSpecific;
    private $contextSpecific;
    private $oney;
    private $payplug;
    private $plugin;
    private $productSpecific;
    private $toolsSpecific;

    /**
     * @description
     * Method that is executed after init() and checkAccess().
     * Used to process user input.
     *
     * return void
     * @throws Exception
     */
    public function postProcess()
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $ajax = new PayPlugAjax();
            $ajax->run();
            exit;
        }

        require_once(_PS_ROOT_DIR_.'/config/config.inc.php');
        require_once(_PS_MODULE_DIR_ . '../init.php');
        include_once(_PS_MODULE_DIR_ . 'payplug/payplug.php');

        $this->payplug = new \Payplug();
        $this->plugin = $this->payplug->getPlugin();
        $this->toolsSpecific = $this->plugin->getTools();

        if ($this->toolsSpecific->tool('getValue','_ajax') == 1) {
            $this->card = $this->plugin->getCard();
            $this->configurationSpecific = $this->plugin->getConfiguration();
            $this->contextSpecific = $this->plugin->getContext(); // get ContextSpecific Repository object
            $this->oney = $this->plugin->getOney();
            $this->productSpecific = $this->plugin->getProduct();

            $config = $this->configurationSpecific;
            $context = $this->contextSpecific->getContext(); // get the method
            $tools = $this->toolsSpecific;

            if ($tools->tool('getIsset','pc')) {
                if ((int)$tools->tool('getValue','delete') == 1) {
                    $cookie = $context->cookie;
                    $id_customer = (int)$cookie->id_customer;
                    if ((int)$id_customer == 0) {
                        die(false);
                    }
                    $id_payplug_card = $tools->tool('getValue','pc');
                    $valid_key = Payplug::setAPIKey();
                    $deleted = $this->card->deleteCard($id_customer, $id_payplug_card, $valid_key);
                    if ($deleted) {
                        die(true);
                    } else {
                        die(false);
                    }
                }
            } elseif ($tools->tool('getIsset','getOneyCta')) {
                die(json_encode(array(
                    'result' => true,
                    'tpl' => $this->oney->getOneyCTA(),
                )));
            } elseif ($tools->tool('getIsset','isOneyElligible')) {
                $use_taxes = (bool)$config->get('PS_TAX');

                if ($id_product = (int)$tools->tool('getValue','id_product')) {
                    $group = $tools->tool('getValue','group');
                    // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
                    if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
                        $id_product_attribute = $group ? (int)Product::getIdProductAttributesByIdAttributes($id_product, $group) : 0;
                    } else {
                        $id_product_attribute = $group ? (int)Product::getIdProductAttributeByIdAttributes($id_product, $group) : 0;
                    }
                    $quantity = (int)$tools->tool('getValue','qty', (int)$tools->tool('getValue','quantity_wanted', 1));
                    $product_price = Product::getPriceStatic((int)$id_product, $use_taxes, $id_product_attribute, 6,null, false, true, $quantity);
                    $amount = $product_price * $quantity;
                    $id_currency = $context->currency->id;
                    $is_elligible = $this->oney->isValidOneyAmount($amount, $id_currency);
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $delivery_address = new Address($context->cart->id_address_delivery);
                    $delivery_country = new Country($delivery_address->id_country);
                    $iso_code = $delivery_country->iso_code;
                    $cart = $context->cart;
                    $is_elligible = $this->oney->isOneyElligible($cart, $amount, $iso_code);
                }

                die(json_encode($is_elligible));
            } elseif ($tools->tool('getIsset','getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool)$config->get('PS_TAX');

                if ($id_product = (int)$tools->tool('getValue','id_product')) {
                    $group = $tools->tool('getValue','group');
                    $id_product_attribute = $group ? (int)Product::getIdProductAttributeByIdAttributes($id_product, $group) : 0;
                    // Some integration will not use qty data but quantity_wanted
                    $quantity = (int)$tools->tool('getValue','qty');
                    $quantity = $quantity ? $quantity : (int)$tools->tool('getValue','quantity_wanted', 1);
                    $product_price = Product::getPriceStatic((int)$id_product, $use_taxes, $id_product_attribute, 6,null, false, true, $quantity);
                    $amount = $product_price * $quantity;
                    $cart = false;
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $cart = $context->cart;
                }

                $payment_options = $this->oney->getOneyPriceAndPaymentOptions($cart, $amount);
                die(json_encode($payment_options));
            } elseif ($tools->tool('getIsset','getPaymentErrors')) {
                // check if errors
                $errors = $this->payplug->getPaymentErrorsCookie();

                if ($errors) {
                    die(json_encode(['result' => true, 'template' => $this->payplug->displayPaymentErrors($errors)]));
                }

                die(json_encode(['result' => false]));
            } elseif ($tools->tool('getIsset','savePaymentData')) {
                $payment_data = $tools->tool('getValue','payment_data');

                if (empty($payment_data)) {
                    die(json_encode([
                        'result' => false,
                        'message' => [$this->payplug->l('Empty payment data')]
                    ]));
                } elseif ($this->oney->checkOneyRequiredFields($payment_data)) {
                    die(json_encode([
                        'result' => false,
                        'message' => [$this->payplug->l('At least one of the fields is not correctly completed.')]
                    ]));
                }

                $result = $this->payplug->setPaymentDataCookie($payment_data);
                die(json_encode([
                    'result' => $result,
                    'message' => [$result ? $this->payplug->l('Your information has been saved') : $this->payplug->l('An error occured. Please retry in few seconds.')]
                ]));
            }
        }
    }
}
