<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @author    Payplug SAS
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @description
 * Treat ajax call
 */
class PayplugAjaxModuleFrontController extends ModuleFrontController
{
    private $apiClass;
    private $cart_adapter;
    private $configurationAdapter;
    private $configurationClass;
    private $contextAdapter;
    private $country;
    private $logger;
    private $oney;
    private $dependencies;
    private $paymentClass;
    private $plugin;
    private $productAdapter;
    private $tools_adapter;
    private $translate;
    private $validators;
    private $address_class;

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
            $ajax = new PayPlug\classes\PayPlugAjax();
            $ajax->run();

            exit;
        }

        require_once _PS_ROOT_DIR_ . '/config/config.inc.php';

        $this->dependencies = new PayPlug\classes\DependenciesClass();
        $this->validators = $this->dependencies->getValidators();
        $this->apiClass = $this->dependencies->apiClass;
        $this->paymentClass = $this->dependencies->paymentClass;
        $this->plugin = $this->dependencies->getPlugin();
        $this->logger = $this->plugin->getLogger();
        $this->cart_adapter = $this->plugin->getCart();
        $this->tools_adapter = $this->plugin->getTools();
        $this->country = $this->dependencies->getPlugin()->getCountry();

        if (1 == $this->tools_adapter->tool('getValue', '_ajax')) {
            $this->configurationAdapter = $this->plugin->getConfiguration();
            $this->configurationClass = $this->plugin->getConfigurationClass();

            $this->contextAdapter = $this->plugin->getContext(); // get ContextAdapter Repository object
            $this->oney = $this->plugin->getOney();
            $this->productAdapter = $this->plugin->getProduct();
            $this->translate = $this->plugin->getTranslate();
            $context = $this->contextAdapter->get(); // get the method
            $tools = $this->tools_adapter;
            $this->address_class = $this->plugin->getAddressClass();

            // todo: Create a ajaxDispatcher to avoid this "infinite" list of condition
            if ($tools->tool('getIsset', 'pc')) {
                if (1 == (int) $tools->tool('getValue', 'delete')) {
                    $cookie = $context->cookie;
                    $id_customer = (int) $cookie->id_customer;
                    if (0 == (int) $id_customer) {
                        exit(false);
                    }
                    $id_payplug_card = $tools->tool('getValue', 'pc');
                    $deleted = $this->dependencies
                        ->getPlugin()
                        ->getCardAction()
                        ->deleteAction((int) $id_customer, (int) $id_payplug_card);
                    if ($deleted) {
                        exit(true);
                    }

                    exit(false);
                }
            } elseif ($tools->tool('getIsset', 'getOneyCta')) {
                exit(json_encode([
                    'result' => true,
                    'tpl' => $this->dependencies
                        ->getPlugin()
                        ->getOneyAction()
                        ->renderCTA(),
                ]));
            } elseif ($tools->tool('getIsset', 'isOneyElligible')) {
                $use_taxes = (bool) $this->configurationAdapter->get('PS_TAX');

                $is_elligible = null;
                if ($id_product = (int) $tools->tool('getValue', 'id_product')) {
                    $group = $tools->tool('getValue', 'group');
                    $id_product_attribute = $group ?
                        (int) $this->productAdapter->getIdProductAttributeByIdAttributes($id_product, $group) :
                        0;

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
                    $is_elligible = $this->dependencies
                        ->getPlugin()
                        ->getPaymentMethodClass()
                        ->getPaymentMethod('oney')
                        ->isValidOneyAmount($amount);
                } else {
                    $amount = $context->cart->getOrderTotal($use_taxes);
                    $is_elligible = $this->dependencies
                        ->getPlugin()
                        ->getPaymentMethodClass()
                        ->getPaymentMethod('oney')
                        ->isValidOneyAmount($amount);
                }

                exit(json_encode($is_elligible));
            } elseif ($tools->tool('getIsset', 'getOneyPriceAndPaymentOptions')) {
                $use_taxes = (bool) $this->configurationAdapter->get('PS_TAX');

                if ($id_product = (int) $tools->tool('getValue', 'id_product')) {
                    $group = $tools->tool('getValue', 'group');
                    $id_product_attribute = $group ?
                        (int) $this->productAdapter->getIdProductAttributeByIdAttributes($id_product, $group) :
                        0;
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
                    $payment_options = $this->dependencies
                        ->getPlugin()
                        ->getPaymentMethodClass()
                        ->getPaymentMethod('oney')
                        ->getOneyPriceAndPaymentOptions($cart, $amount);
                } catch (Exception $e) {
                    exit(json_encode([
                        'exception' => $e->getMessage(),
                        'result' => false,
                        'error' => $this->dependencies
                            ->getPlugin()
                            ->getTranslationClass()
                            ->l('Oney is momentarily unavailable.', 'ajax'),
                    ]));
                }

                exit(json_encode($payment_options));
            } elseif ($tools->tool('getIsset', 'getPaymentErrors')) {
                // check if errors
                $errors = $this->dependencies->getHelpers()['cookies']->getPaymentErrorsCookie();

                if ($errors) {
                    exit(json_encode(
                        [
                            'result' => true,
                            'template' => $this->dependencies
                                ->getPlugin()
                                ->getPaymentAction()
                                ->renderPaymentErrors($errors),
                            'errors' => $errors,
                        ]
                    ));
                }

                exit(json_encode(['result' => false]));
            } elseif ($tools->tool('getIsset', 'savePaymentData')) {
                $payment_data = $tools->tool('getValue', 'payment_data');

                if (empty($payment_data)) {
                    exit(json_encode([
                        'result' => false,
                        'message' => [
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Empty payment data', 'ajax'),
                        ],
                    ]));
                }

                $check_oney_required_fields = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('oney')
                    ->checkOneyRequiredFields($payment_data);

                if ($check_oney_required_fields) {
                    exit(json_encode([
                        'result' => false,
                        'message' => [
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('At least one of the fields is not correctly completed.', 'ajax'),
                        ],
                    ]));
                }

                $result = $this->dependencies->getHelpers()['cookies']->setPaymentDataCookie($payment_data);

                exit(json_encode([
                    'result' => $result,
                    'message' => [
                        $result ?
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('Your information has been saved', 'ajax') :
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('An error occurred. Please retry in few seconds.', 'ajax'),
                    ],
                ]));
            } elseif ($tools->tool('getIsset', 'createIP')) {
                $token = $tools->tool('getValue', 'token');
                if (false == $token) {
                    exit(json_encode([
                        'result' => true,
                        'message' => $token,
                    ]));
                }

                $payment = $this->dependencies
                    ->getPlugin()
                    ->getPaymentAction()
                    ->dispatchAction('standard', true);
                $payment['force_reload'] = false;

                if (empty($payment)) {
                    $this->dependencies->getHelpers()['cookies']->setPaymentErrorsCookie([
                        $this->dependencies
                            ->getPlugin()
                            ->getTranslationClass()
                            ->l('The transaction was not completed and your card was not charged.', 'ajax'),
                    ]);
                    $payment['force_reload'] = true;
                    $payment['return_url'] = $context->link->getPageLink('order', true, $context->language->id, [
                        'step' => '3',
                        'has_error' => '1',
                        'modulename' => $this->dependencies->name,
                    ]);
                }

                exit(json_encode($payment));
            } elseif ($tools->tool('getIsset', 'confirmPayment')) {
                $payment_id = $tools->tool('getValue', 'pay_id');
                $cart_id = $tools->tool('getValue', 'cart_id');

                $payment = $this->dependencies
                    ->getPlugin()
                    ->getPaymentRepository()
                    ->getByCart((int) $cart_id);

                if (empty($payment)) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'No payment id for given cart id',
                    ]));
                }

                if ($payment_id != $payment['resource_id']) {
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
                if ($this->validators['payment']->isFailed($payment)['result']) {
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
                $resource_id = $tools->tool('getValue', 'pay_id');
                $token = $tools->tool('getValue', 'token');
                $workflow = $tools->tool('getValue', 'workflow');
                $carrier = $tools->tool('getValue', 'carrier');
                $user = $tools->tool('getValue', 'user');

                $patch = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('applepay')
                    ->patchPaymentResource($resource_id, $token, $workflow, $carrier, $user);

                if (!$patch['result']) {
                    exit(json_encode($patch));
                }

                exit(json_encode([
                    'result' => true,
                    'return_url' => $patch['return_url'],
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
            } elseif ($tools->tool('getIsset', 'applepayUpdate')) {
                $request = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('applepay')
                    ->getRequest();

                exit(json_encode([
                    'result' => true,
                    'request' => $request,
                ]));
            } elseif ($tools->tool('getIsset', 'applepayCancel')) {
                $cancel = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('applepay')
                    ->cancelPaymentResource();

                exit(json_encode($cancel));
            }
        }
    }
}
