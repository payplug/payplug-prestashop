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
    private $translate;

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
            $ajax = new \PayPlug\classes\PayPlugAjax();
            $ajax->run();
            exit;
        }

        require_once(_PS_ROOT_DIR_.'/config/config.inc.php');

        $this->payplug = new \PayPlug\classes\PayPlugClass();
        $this->plugin = $this->payplug->getPlugin();
        $this->toolsSpecific = $this->plugin->getTools();

        if ($this->toolsSpecific->tool('getValue', '_ajax') == 1) {
            $this->card = $this->plugin->getCard();
            $this->configurationSpecific = $this->plugin->getConfiguration();
            $this->contextSpecific = $this->plugin->getContext(); // get ContextSpecific Repository object
            $this->oney = $this->plugin->getOney();
            $this->productSpecific = $this->plugin->getProduct();
            $this->translate = $this->plugin->getTranslate();

            $config = $this->configurationSpecific;
            $context = $this->contextSpecific->getContext(); // get the method
            $tools = $this->toolsSpecific;

            if ($tools->tool('getIsset', 'pc')) {
                if ((int)$tools->tool('getValue', 'delete') == 1) {
                    $cookie = $context->cookie;
                    $id_customer = (int)$cookie->id_customer;
                    if ((int)$id_customer == 0) {
                        die(false);
                    }
                    $id_payplug_card = $tools->tool('getValue', 'pc');
                    $deleted = $this->card->deleteCard((int)$id_customer, (int)$id_payplug_card);
                    if ($deleted) {
                        die(true);
                    } else {
                        die(false);
                    }
                }
            } elseif ($tools->tool('getIsset', 'getOneyCta')) {
                die(json_encode([
                    'result' => true,
                    'tpl' => $this->oney->getOneyCTA(),
                ]));
            } elseif ($tools->tool('getIsset', 'isOneyElligible')) {
                $use_taxes = (bool)$config->get('PS_TAX');

                $is_elligible = null;
                if ($id_product = (int)$tools->tool('getValue', 'id_product')) {
                    $group = $tools->tool('getValue', 'group');
                    // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
                    if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
                        $id_product_attribute = $group ?
                            (int)Product::getIdProductAttributesByIdAttributes($id_product, $group) :
                            0
                        ;
                    } else {
                        $id_product_attribute = $group ?
                            (int)Product::getIdProductAttributeByIdAttributes($id_product, $group) :
                            0
                        ;
                    }
                    $quantity = (int)$tools->tool(
                        'getValue',
                        'qty',
                        (int)$tools->tool('getValue', 'quantity_wanted', 1)
                    );
                    $quantity = $quantity ? $quantity : 1;
                    $product_price = Product::getPriceStatic(
                        (int)$id_product,
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
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $is_elligible = $this->oney->isValidOneyAmount($amount);
                }

                die(json_encode($is_elligible));
            } elseif ($tools->tool('getIsset', 'getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool)$config->get('PS_TAX');

                if ($id_product = (int)$tools->tool('getValue', 'id_product')) {
                    $group = $tools->tool('getValue', 'group');
                    $id_product_attribute = $group ?
                        (int)Product::getIdProductAttributeByIdAttributes($id_product, $group) :
                        0
                    ;
                    // Some integration will not use qty data but quantity_wanted
                    $quantity = (int)$tools->tool('getValue', 'qty');
                    $quantity = $quantity ? $quantity : (int)$tools->tool('getValue', 'quantity_wanted', 1);
                    $quantity = $quantity ? $quantity : 1;
                    $product_price = Product::getPriceStatic(
                        (int)$id_product,
                        $use_taxes,
                        $id_product_attribute,
                        6,
                        null,
                        false,
                        true,
                        $quantity
                    );
                    $amount = $product_price * $quantity;
                    $cart = false;
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $cart = $context->cart;
                }


                try {
                    $payment_options = $this->oney->getOneyPriceAndPaymentOptions($cart, $amount);
                } catch (Exception $e) {
                    die($tools->tool('jsonEncode', [
                        'exception' => $e->getMessage(),
                        'result' => false,
                        'error' => $this->translate->translate(5) //('Oney is momentarily unavailable.')
                    ]));
                }

                die(json_encode($payment_options));
            } elseif ($tools->tool('getIsset', 'getPaymentErrors')) {
                // check if errors
                $errors = $this->payplug->getPaymentErrorsCookie();

                if ($errors) {
                    die(json_encode(['result' => true, 'template' => $this->payplug->displayPaymentErrors($errors)]));
                }

                die(json_encode(['result' => false]));
            } elseif ($tools->tool('getIsset', 'savePaymentData')) {
                $payment_data = $tools->tool('getValue', 'payment_data');

                if (empty($payment_data)) {
                    die(json_encode([
                        'result' => false,
                        'message' => [
                            $this->translate->translate(1) // 'Empty payment data'
                        ]
                    ]));
                } elseif ($this->oney->checkOneyRequiredFields($payment_data)) {
                    die(json_encode([
                        'result' => false,
                        'message' => [
                            $this->translate->translate(2) // 'At least one of the fields is not correctly completed.'
                        ]
                    ]));
                }

                $result = $this->payplug->setPaymentDataCookie($payment_data);
                die(json_encode([
                    'result' => $result,
                    'message' => [
                        $result ?
                            $this->translate->translate(3) : //('Your information has been saved') :
                            $this->translate->translate(4) //('An error occurred. Please retry in few seconds.')
                    ]
                    ]));
            } elseif ($tools->tool('getIsset', 'createIP')) {
                $token = $tools->tool('getValue', 'token');
                if ($token == false) {
                    die(
                    json_encode(
                        [
                            'result' => true,
                            'message' => $token,
                        ]
                    )
                    );
                } else {
                    $payment = $this->payplug->preparePayment([
                        'is_integrated' => 1,
                        'is_deferred' => (bool)$this->configurationSpecific->get('PAYPLUG_DEFERRED')
                    ]);
                    die(json_encode($payment));
                }
            } elseif ($tools->tool('getIsset', 'confirmIP')) {
                $payment_id = $tools->tool('getValue', 'pay_id');

                // Check payment correspondence
                $current_payment_id = $this->payplug->getPaymentByCart($context->cart->id);
                if ($payment_id != $current_payment_id) {
                    die(json_encode([
                        'result' => false,
                        'message' => 'invalid payment id'
                    ]));
                }

                // Retrieve payment
                $payment = $this->payplug->retrievePayment($payment_id);

                // Check if payment has failure
                if ($payment->failure != null) {
                    die(json_encode([
                        'result' => false,
                        'message' => $payment->failure->message
                    ]));
                }

                $return_url = $context->link->getModuleLink(
                    $this->payplug->name,
                    'validation',
                    ['ps' => 1, 'cartid' => (int)$context->cart->id],
                    true
                );

                die(json_encode([
                    'result' => true,
                    'return_url' => $return_url,
                    'message' => 'Success'
                ]));
            }
        }
    }
}
