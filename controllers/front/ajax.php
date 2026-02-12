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
    private $configurationAdapter;
    private $contextAdapter;
    private $logger;
    private $dependencies;
    private $plugin;
    private $productAdapter;
    private $tools_adapter;
    private $validators;

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
        $this->dependencies = new PayPlug\classes\DependenciesClass();
        $this->validators = $this->dependencies->getValidators();
        $this->plugin = $this->dependencies->getPlugin();
        $this->logger = $this->plugin->getLogger();
        $this->tools_adapter = $this->plugin->getTools();

        if (1 == $this->tools_adapter->tool('getValue', '_ajax')) {
            $this->configurationAdapter = $this->plugin->getConfiguration();

            $this->contextAdapter = $this->plugin->getContext(); // get ContextAdapter Repository object
            $this->productAdapter = $this->plugin->getProductAdapter();
            $context = $this->contextAdapter->get(); // get the method
            $tools = $this->tools_adapter;

            $request = $this->dependencies
                ->getPlugin()
                ->getModule()
                ->getInstanceByName($this->dependencies->name)
                ->getService('payplug.action.request');

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

                $stored_resource = $this->dependencies
                    ->getPlugin()
                    ->getPaymentRepository()
                    ->getBy('id_cart', (int) $cart_id);

                if (empty($stored_resource)) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'No payment id for given cart id',
                    ]));
                }

                if ($payment_id != $stored_resource['resource_id']) {
                    exit(json_encode([
                        'result' => false,
                        'message' => 'No correspondance with given payment id',
                    ]));
                }

                // Retrieve payment
                $retrieve = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod($stored_resource['method'])
                    ->retrieve($stored_resource['resource_id']);

                if (!$retrieve['result']) {
                    exit(json_encode([
                        'result' => false,
                        'message' => $retrieve['message'],
                    ]));
                }
                $payment = $retrieve['resource'];

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
            } elseif ($tools->tool('getValue', 'method')) {
                $method = $tools->tool('getValue', 'method');
                $params = $tools->tool('getValue', 'params', []);
                $result = $request->dispatchAction($method, $params);

                exit(json_encode($result));
            }
        }

        exit(json_encode([
            'result' => true,
            'message' => 'No ajax call',
        ]));
    }
}
