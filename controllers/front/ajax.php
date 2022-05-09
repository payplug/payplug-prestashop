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

/**
 * @description
 * Treat ajax call
 */
class PayplugAjaxModuleFrontController extends ModuleFrontController
{
    private $card;
    private $configurationSpecific;
    private $contextSpecific;
    private $logger;
    private $oney;
    private $dependencies;
    private $paymentClass;
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
            $ajax = new \PayPlugModule\classes\PayPlugAjax();
            $ajax->run();
            exit;
        }

        require_once(_PS_ROOT_DIR_.'/config/config.inc.php');

        $this->dependencies = new \PayPlugModule\classes\DependenciesClass();
        $this->apiClass = $this->dependencies->apiClass;
        $this->paymentClass = $this->dependencies->paymentClass;
        $this->plugin = $this->dependencies->getPlugin();
        $this->logger = $this->plugin->getLogger();
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
                $errors = $this->paymentClass->getPaymentErrorsCookie();

                if ($errors) {
                    die(json_encode(
                        ['result' => true, 'template' => $this->paymentClass->displayPaymentErrors($errors)]
                    ));
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

                $result = $this->paymentClass->setPaymentDataCookie($payment_data);
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
                    $payment = $this->paymentClass->preparePayment([
                        'is_integrated' => 1,
                        'is_deferred' => (bool)$this->configurationSpecific->get('PAYPLUG_DEFERRED')
                    ]);
                    die(json_encode($payment));
                }
            } elseif ($tools->tool('getIsset', 'confirmIP')) {
                $payment_id = $tools->tool('getValue', 'pay_id');
                $cart_id = $tools->tool('getValue', 'cart_id');

                // Check payment correspondence
                $query = $this->plugin->getQuery();

                // todo: request should not be here but to many different method are used to get the payment
                // todo: extract this part with PaymentClass
                $current_payment_id = $query
                    ->select()
                    ->fields('id_payment')
                    ->from(_DB_PREFIX_ . $this->dependencies->name . '_payment')
                    ->where('id_cart = ' . (int)$cart_id)
                    ->build('unique_value');
                if ($payment_id != $current_payment_id) {
                    die(json_encode([
                        'result' => false,
                        'message' => 'invalid payment id, giver: ' . $payment_id . ' and current:' . $current_payment_id
                    ]));
                }

                // Retrieve payment
                $payment = $this->apiClass->retrievePayment($payment_id);

                if (!$payment['result']) {
                    die(json_encode([
                        'result' => false,
                        'message' => $payment['message']
                    ]));
                }
                $payment = $payment['resource'];

                // Check if payment has failure
                if ($payment->failure != null) {
                    die(json_encode([
                        'result' => false,
                        'message' => $payment->failure->message
                    ]));
                }

                // Check if payment is paid
                $is_payment_deferred = isset($payment->authorization) && $payment->authorization;
                if (!$payment->is_paid && !$is_payment_deferred) {
                    die(json_encode([
                        'result' => false,
                        'message' => 'Payment is not paid'
                    ]));
                } elseif (isset($payment->authorization->authorized_at) && !$payment->authorization->authorized_at) {
                    die(json_encode([
                        'result' => false,
                        'message' => 'Deferred payment is not authorized'
                    ]));
                }

                $return_url = $context->link->getModuleLink(
                    $this->dependencies->name,
                    'validation',
                    ['ps' => 1, 'cartid' => (int)$cart_id],
                    true
                );

                die(json_encode([
                    'result' => true,
                    'return_url' => $return_url,
                    'message' => 'Success'
                ]));
            } elseif ($tools->tool('getIsset', 'addLogger')) {
                $message = $tools->tool('getValue', 'message');
                if (!$message || !is_string($message)) {
                    die(json_encode([
                        'result' => true,
                        'message' =>  'Failed to add log' // specific error
                    ]));
                } else {
                    $this->logger->addLog($message);
                    die(json_encode([
                        'result' => true,
                        'message' =>  $message // specific error
                    ]));
                }
            } elseif ($tools->tool('getIsset', 'updatePublishableKey')) {
                $publishable_keys = $this->dependencies->apiClass->setPublishableKeys();

                if (!$publishable_keys['result']) {
                    if (!empty($publishable_keys['error'])
                        && 'EMPTY_PUBLISHABLE_KEY' == $publishable_keys['error']['name']) {
                        $payment_options = [
                            'is_deferred' => (bool)$this->configurationSpecific->get('PAYPLUG_DEFERRED'),
                        ];
                        $payment = $this->paymentClass->preparePayment($payment_options);
                        if (!$payment['result']) {
                            die(json_encode([
                                'result' => false
                            ]));
                        }
                        die(json_encode([
                            'result' => false,
                            'redirectUrl' => $payment['return_url']
                        ]));
                    }

                    die(json_encode($publishable_keys));
                }

                $sandbox = (bool)$this->configurationSpecific->get('PAYPLUG_SANDBOX_MODE');
                $publishable_keys['key'] = (string)$this->configurationSpecific->get(
                    'PAYPLUG_PUBLISHABLE_KEY' . ($sandbox ? '_TEST' : '')
                );
                die(json_encode($publishable_keys));
            }
        }
    }
}
