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
    private $configurationAdapter;
    private $contextAdapter;
    private $logger;
    private $oney;
    private $dependencies;
    private $paymentClass;
    private $plugin;
    private $productAdapter;
    private $toolsAdapter;
    private $translate;

    /**
     * @description
     * Method that is executed after init() and checkAccess().
     * Used to process user input.
     *
     * return void
     *
     * @throws Exception
     */
    public function postProcess()
    {
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $ajax = new \PayPlug\classes\PayPlugAjax();
            $ajax->run();

            exit;
        }

        require_once _PS_ROOT_DIR_ . '/config/config.inc.php';

        $this->dependencies = new \PayPlug\classes\DependenciesClass();
        $this->apiClass = $this->dependencies->apiClass;
        $this->paymentClass = $this->dependencies->paymentClass;
        $this->plugin = $this->dependencies->getPlugin();
        $this->logger = $this->plugin->getLogger();
        $this->toolsAdapter = $this->plugin->getTools();

        if ($this->toolsAdapter->tool('getValue', '_ajax') == 1) {
            $this->card = $this->plugin->getCard();
            $this->configurationAdapter = $this->plugin->getConfiguration();
            $this->contextAdapter = $this->plugin->getContext(); // get ContextAdapter Repository object
            $this->oney = $this->plugin->getOney();
            $this->productAdapter = $this->plugin->getProduct();
            $this->translate = $this->plugin->getTranslate();

            $config = $this->configurationAdapter;
            $context = $this->contextAdapter->getContext(); // get the method
            $tools = $this->toolsAdapter;

            if ($tools->tool('getIsset', 'pc')) {
                if ((int) $tools->tool('getValue', 'delete') == 1) {
                    $cookie = $context->cookie;
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
            } elseif ($tools->tool('getIsset', 'getOneyCta')) {
                exit(json_encode([
                    'result' => true,
                    'tpl' => $this->oney->getOneyCTA(),
                ]));
            } elseif ($tools->tool('getIsset', 'isOneyElligible')) {
                $use_taxes = (bool) $config->get('PS_TAX');

                $is_elligible = null;
                if ($id_product = (int) $tools->tool('getValue', 'id_product')) {
                    $group = $tools->tool('getValue', 'group');
                    // Method getIdProductAttributesByIdAttributes deprecated in 1.7.3.1 version
                    if (version_compare(_PS_VERSION_, '1.7.3.1', '<')) {
                        $id_product_attribute = $group ?
                            (int) Product::getIdProductAttributesByIdAttributes($id_product, $group) :
                            0
                        ;
                    } else {
                        $id_product_attribute = $group ?
                            (int) $this->productAdapter->getIdProductAttributeByIdAttributes($id_product, $group) :
                            0
                        ;
                    }
                    $quantity = (int) $tools->tool(
                        'getValue',
                        'qty',
                        (int) $tools->tool('getValue', 'quantity_wanted', 1)
                    );
                    $quantity = $quantity ? $quantity : 1;
                    $product_price = Product::getPriceStatic(
                        (int) $id_product,
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

                exit(json_encode($is_elligible));
            } elseif ($tools->tool('getIsset', 'getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool) $config->get('PS_TAX');

                if ($id_product = (int) $tools->tool('getValue', 'id_product')) {
                    $group = $tools->tool('getValue', 'group');
                    $id_product_attribute = $group ?
                        (int) $this->productAdapter->getIdProductAttributeByIdAttributes($id_product, $group) :
                        0
                    ;
                    // Some integration will not use qty data but quantity_wanted
                    $quantity = (int) $tools->tool('getValue', 'qty');
                    $quantity = $quantity ? $quantity : (int) $tools->tool('getValue', 'quantity_wanted', 1);
                    $quantity = $quantity ? $quantity : 1;
                    $product_price = Product::getPriceStatic(
                        (int) $id_product,
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
                    exit(json_encode([
                        'exception' => $e->getMessage(),
                        'result' => false,
                        'error' => $this->translate->translate(5), //('Oney is momentarily unavailable.')
                    ]));
                }

                exit(json_encode($payment_options));
            } elseif ($tools->tool('getIsset', 'getPaymentErrors')) {
                // check if errors
                $errors = $this->paymentClass->getPaymentErrorsCookie();

                if ($errors) {
                    exit(json_encode(
                        ['result' => true, 'template' => $this->paymentClass->displayPaymentErrors($errors)]
                    ));
                }

                exit(json_encode(['result' => false]));
            } elseif ($tools->tool('getIsset', 'savePaymentData')) {
                $payment_data = $tools->tool('getValue', 'payment_data');

                if (empty($payment_data)) {
                    exit(json_encode([
                        'result' => false,
                        'message' => [
                            $this->translate->translate(1), // 'Empty payment data'
                        ],
                    ]));
                }
                if ($this->oney->checkOneyRequiredFields($payment_data)) {
                    exit(json_encode([
                        'result' => false,
                        'message' => [
                            $this->translate->translate(2), // 'At least one of the fields is not correctly completed.'
                        ],
                    ]));
                }

                $result = $this->paymentClass->setPaymentDataCookie($payment_data);

                exit(json_encode([
                    'result' => $result,
                    'message' => [
                        $result ?
                            $this->translate->translate(3) : //('Your information has been saved') :
                            $this->translate->translate(4), //('An error occurred. Please retry in few seconds.')
                    ],
                ]));
            } elseif ($tools->tool('getIsset', 'createIP')) {
                $token = $tools->tool('getValue', 'token');
                if ($token == false) {
                    exit(
                        json_encode(
                            [
                                'result' => true,
                                'message' => $token,
                            ]
                        )
                    );
                }
                $payment = $this->paymentClass->preparePayment([
                    'is_integrated' => 1,
                    'is_deferred' => (bool) $this->configurationAdapter->get('PAYPLUG_DEFERRED'),
                ]);

                exit(json_encode($payment));
            } elseif ($tools->tool('getIsset', 'confirmPayment')) {
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
                    ->where('id_cart = ' . (int) $cart_id)
                    ->build('unique_value')
                ;
                if ($payment_id != $current_payment_id) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'No correspondance with given payment id',
                    ]));
                }

                // Retrieve payment
                $payment = $this->apiClass->retrievePayment($payment_id);

                if (!$payment['result']) {
                    exit(json_encode([
                        'result' => false,
                        'message' => $payment['message'],
                    ]));
                }
                $payment = $payment['resource'];

                // Check if payment has failure
                if ($payment->failure != null) {
                    exit(json_encode([
                        'result' => false,
                        'message' => $payment->failure->message,
                    ]));
                }

                // Check if payment is paid
                $is_payment_deferred = isset($payment->authorization) && $payment->authorization;
                if (!$payment->is_paid && !$is_payment_deferred) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'Payment is not paid',
                    ]));
                }
                if (isset($payment->authorization->authorized_at) && !$payment->authorization->authorized_at) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'Deferred payment is not authorized',
                    ]));
                }

                $return_url = $context->link->getModuleLink(
                    $this->dependencies->name,
                    'validation',
                    ['ps' => 1, 'cartid' => (int) $cart_id],
                    true
                );

                exit(json_encode([
                    'result' => true,
                    'return_url' => $return_url,
                    'message' => 'Success',
                ]));
            } elseif ($tools->tool('getIsset', 'patchPayment')) {
                $paymentId = $tools->tool('getValue', 'pay_id');
                $cartId = (int) $tools->tool('getValue', 'cart_id');
                $token = $tools->tool('getValue', 'token');

                // Check if cart id is valid
                if (!$cartId || (int) $context->cart->id != $cartId) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'Invalid cart id given',
                    ]));
                }

                // Check if payment id is valid
                if (!$paymentId) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'Invalid payment id given',
                    ]));
                }

                // Check payment id correspondance between the given one and the one from the DB
                $query = $this->plugin->getQuery();
                $current_payment_id = $query
                    ->select()
                    ->fields('id_payment')
                    ->from(_DB_PREFIX_ . $this->dependencies->name . '_payment')
                    ->where('id_cart = ' . (int) $context->cart->id)
                    ->build('unique_value')
                ;
                if ($paymentId != $current_payment_id) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'No correspondance with given payment id',
                    ]));
                }

                // Check if token is valid
                if (!$token) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'Invalid token given',
                    ]));
                }

                $data = [
                    'apple_pay' => [
                        'payment_token' => $token,
                    ],
                ];
                $patchPayment = $this->apiClass->patchPayment($paymentId, $data);

                if (!$patchPayment['result']) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'An error occured during payment patch : ' . $patchPayment['message'],
                    ]));
                }

                $payment = $patchPayment['resource'];

                // Check if payment has failure...
                if ($payment->failure != null) {
                    exit(json_encode([
                        'result' => false,
                        'message' => $payment->failure->message,
                    ]));
                }

                // ... or if is paid
                if (!$payment->is_paid) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'Payment is not paid',
                    ]));
                }

                $return_url = $context->link->getModuleLink(
                    $this->dependencies->name,
                    'validation',
                    ['ps' => 1, 'cartid' => (int) $context->cart->id],
                    true
                );

                exit(json_encode([
                    'result' => true,
                    'return_url' => $return_url,
                    'message' => 'Success',
                ]));
            } elseif ($tools->tool('getIsset', 'addLogger')) {
                $message = $tools->tool('getValue', 'message');
                if (!$message || !is_string($message)) {
                    exit(json_encode([
                        'result' => true,
                        'message' => 'Failed to add log', // adapter error
                    ]));
                }
                $this->logger->addLog($message);

                exit(json_encode([
                    'result' => true,
                    'message' => $message, // adapter error
                ]));
            } elseif ($tools->tool('getIsset', 'updatePublishableKey')) {
                $publishable_keys = $this->dependencies->apiClass->setPublishableKeys();

                if (!$publishable_keys['result']) {
                    if (!empty($publishable_keys['error'])
                        && 'EMPTY_PUBLISHABLE_KEY' == $publishable_keys['error']['name']) {
                        $payment_options = [
                            'is_deferred' => (bool) $this->configurationAdapter->get('PAYPLUG_DEFERRED'),
                        ];
                        $payment = $this->paymentClass->preparePayment($payment_options);
                        if (!$payment['result']) {
                            exit(json_encode([
                                'result' => false,
                            ]));
                        }

                        exit(json_encode([
                            'result' => false,
                            'redirectUrl' => $payment['return_url'],
                        ]));
                    }

                    exit(json_encode($publishable_keys));
                }

                $sandbox = (bool) $this->configurationAdapter->get('PAYPLUG_SANDBOX_MODE');
                $publishable_keys['key'] = (string) $this->configurationAdapter->get(
                    'PAYPLUG_PUBLISHABLE_KEY' . ($sandbox ? '_TEST' : '')
                );

                exit(json_encode($publishable_keys));
            }
        }
    }
}
