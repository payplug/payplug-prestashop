<?php

namespace PayPlug\src\utilities\services;

use PayPlug\classes\DependenciesClass;
use PhpMcp\Server\Attributes\McpTool;
use PhpMcp\Server\Attributes\Schema;
use PayplugPluginCore\Models\Entities\PaymentInputDTO;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Mcp
{
    private $dependencies;

    /** @var object */
    protected $context;

    /** @var object */
    protected $plugin;

    /** @var object */
    protected $configuration;

    /** @var object */
    protected $order;

    public function __construct(){
        $this->dependencies = new DependenciesClass();
    }

    protected function createPaymentInputDto(array $params)
    {
        if (!$params || !is_array($params)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $params given',
                'dto' => null,
            ];
        }
        try {
            $attributes = $params;
            $attributes['payment_method'] = 'email_link';

            // todo: This props should be in a generic method, something like "formatMCPAttributes"
            // Get Api bearer (pai_key or jwt)
            $configuration = $this->dependencies
                ->getPlugin()
                ->getConfigurationClass();
            $attributes['api_bearer'] = $this->dependencies
                ->getPlugin()
                ->getModule()
                ->getInstanceByName($this->dependencies->name)
                ->getService('payplug.utilities.service.api')
                ->getApiBearer(!(bool) $configuration->getValue('sandbox_mode'));

            // todo: Get reel currency
            $attributes['currency_iso_code'] = 'EUR';
            $base_url = $this->context->shop->getBaseURL(true);
            $module_name = $this->dependencies->name;
            $attributes['urls'] = [
                'return' => $base_url . "module/$module_name/validation",
                'cancel' =>  $base_url . "module/$module_name/cancel",
                'notification' => $base_url . "module/$module_name/notification",
            ];

            // todo: Set meta data and potential required context
            $attributes['metadata'] = [
                'source' => 'powered by MCP Payplug',
            ];
            $attributes['context'] = [];

            $dto = PaymentInputDTO::create($attributes);
            return [
                'result' => true,
                'code' => 200,
                'message' => 'DTO created',
                'dto' => $dto,
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
                'dto' => null,
            ];
        }
    }

    /**
     * Creates a payment link for a customer.
    * @return array|string
     */
    #[McpTool(
        name: "create_payment_link",
        description: "Creates a payment link for a customer. Requires customer ID, amount, and optional description. Fetches customer email and shop details from PrestaShop MCP.",
    )]
    #[Schema(
        properties: [
            'customer' => [
                'type' => 'object',
                'properties' => [
                    'customer_id' => ['type' => 'integer', 'description' => 'Customer ID.'],
                    'customer_address_title' => ['type' => 'string', 'description' => 'Customer gender, should be "mr", "mrs" or empty.'],
                    'customer_address_first_name' => ['type' => 'string', 'description' => 'Customer first name.'],
                    'customer_address_last_name' => ['type' => 'string', 'description' => 'Customer last name.'],
                    'customer_address_mobile_phone_number' => ['type' => 'string', 'description' => 'Phone number, should be in international format (E.164).'],
                    'customer_address_email' => ['type' => 'string', 'description' => 'Customer email address.'],
                    'customer_address_address1' => ['type' => 'string', 'description' => 'Customer address'],
                    'customer_address_address2' => ['type' => 'string', 'description' => 'Customer complementary address'],
                    'customer_address_postcode' => ['type' => 'string', 'description' => 'Customer post code.'],
                    'customer_address_city' => ['type' => 'string', 'description' => 'Customer city.'],
                    'customer_address_country' => ['type' => 'string', 'description' => 'Customer country, should be an iso code.'],
                    'customer_address_language' => ['type' => 'string', 'description' => 'Customer language, should be an iso code.'],
                ],
                'required' => ['customer_address_email']
            ],
            'cart' => [
                'type' => 'object',
                'description' => 'The detail of which products will be added to the shopping cart.',
                'properties' => [
                    'products' => [
                        'type' => 'array',
                        'description' => 'List of products in the cart.',
                        'items' => [
                            'type' => 'object',
                            'properties' => [
                                'reference' => ['type' => 'string', 'description' => 'SKU Produit.'],
                                'qty' => ['type' => 'integer', 'description' => 'Quantity.'],
                                'group' => ['type' => 'integer', 'description' => 'Combination ID, required if the product has combinations.'],
                            ],
                            'required' => ['reference', 'qty']
                        ],
                    ],
                    'carrier_name' => ['type' => 'string', 'description' => 'Shopping cart carrier name, not required.'],
                ],
                'required' => ['products']
            ]
        ],
        required: ['customer', 'cart']
    )]
    public function createByLink( array $customer, array $cart)
    {
        $this->setParameters();

        // create cart and add products
        $cart_adapter = $this->dependencies->getPlugin()->getCart();
        $cart_rule_adapter = $this->dependencies->getPlugin()->getCartRule();
        $product_adapter = $this->dependencies->getPlugin()->getProductAdapter();
        // Use customer_id if available, otherwise fallback to email
        $customerIdentifier = !empty($customer['customer_id'])
            ? $customer['customer_id']
            : $customer['customer_address_email'];
        $this->context->cookie->id_customer = $customerIdentifier;
        $current_cart = $cart_adapter->createNewCart($this->context, $customerIdentifier);
        $cart_rule_adapter->autoAddToCart($this->context);

        // Add products to the cart
        if (!empty($cart['products'])) {
            foreach ($cart['products'] as $product) {
                $id_product = (int)$product['reference'];
                $qty = (int)$product['qty'];
                $group = isset($product['group']) ? $product['group'] : null;

                // Check if product has combinations
                $combinations = $product_adapter->hasAttributes($id_product);
                if (!empty($combinations)) {
                    // Product has combinations, but group not provided
                    if (empty($group)) {
                        return [
                            'result' => false,
                            'message' => "Product with ID $id_product has combinations but no combination was selected. Please select a combination.",
                        ];
                    }
                    $id_product_attribute = (int)$product_adapter->getIdProductAttributeByIdAttributes($id_product, $group);
                } else {
                    // Simple product without combinations
                    $id_product_attribute = 0;
                }

                $cart_adapter->updateQty(
                    (int)$current_cart->id,
                    $qty,
                    $id_product,
                    $id_product_attribute
                );
            }
        }

        $cart_adapter->update($current_cart);

        $this->context->cart = $cart_adapter->get((int)$current_cart->id);
        $cart_total = $current_cart->getOrderTotal();
        $dto_params = [
            'amount' => $cart_total * 100,
            'customer' => [
                'identifier' => $customer['customer_id'], // Non mandatory
                'billing' => [
                    'title' => $customer['customer_address_title'],
                    'first_name' => $customer['customer_address_first_name'],
                    'last_name' => $customer['customer_address_last_name'],
                    'mobile_phone_number' => $customer['customer_address_mobile_phone_number'],
                    'email' => $customer['customer_address_email'],
                    'address1' => $customer['customer_address_address1'],
                    'address2' => $customer['customer_address_address2'],
                    'postcode' => $customer['customer_address_postcode'],
                    'city' => $customer['customer_address_city'],
                    'country' => $customer['customer_address_country'],
                    'language' => $customer['customer_address_language'],
                ],
                'shipping' => [
                    'title' => $customer['customer_address_title'],
                    'first_name' => $customer['customer_address_first_name'],
                    'last_name' => $customer['customer_address_last_name'],
                    'mobile_phone_number' => $customer['customer_address_mobile_phone_number'],
                    'email' => $customer['customer_address_email'],
                    'address1' => $customer['customer_address_address1'],
                    'address2' => $customer['customer_address_address2'],
                    'postcode' => $customer['customer_address_postcode'],
                    'city' => $customer['customer_address_city'],
                    'country' => $customer['customer_address_country'],
                    'language' => $customer['customer_address_language'],
                ],
            ],
        ];
        $dtoResult = $this->createPaymentInputDto($dto_params);
        if (!$dtoResult['result'] || !$dtoResult['dto']) {
            return [
                'result' => false,
                'code' => $dtoResult['code'],
                'message' => $dtoResult['message'],
            ];
        }

        $payment_dto = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.core')
            ->createCorePayment($dtoResult['dto']);
        if ($payment_dto['result']) {
            $resource = $payment_dto['resource'];
        } else {
            return [
                'result' => false,
                'code' => $payment_dto['code'],
                'message' => $payment_dto['message'],
            ];
        }

        $method = $dtoResult['dto']->getPaymentMethod();
        $payment_method = $this->plugin
            ->getPaymentMethodClass()
            ->getPaymentMethod($method);

        // create payment resource  and order in prestashop
        $payment_tab = [
            'billing' => $dtoResult['dto']->getCustomer()['billing'],
            'shipping' => $dtoResult['dto']->getCustomer()['shipping'],
            'hosted_payment' => [
                'return_url' => $dtoResult['dto']->getUrls()['return'],
                'cancel_url' => $dtoResult['dto']->getUrls()['cancel'],
                'notification_url' => $dtoResult['dto']->getUrls()['notification'],
            ],
            'metadata' => $dtoResult['dto']->getMetadata(),
        ];
        $payment_hash = $payment_method->getPaymentMethodHash($payment_tab, $resource->is_live);
        $parameters = [
            'resource_id' => $resource->id,
            'is_live' => $resource->is_live,
            'method' => $dtoResult['dto']->getPaymentMethod(),
            'id_cart' => (int)$current_cart->id,
            'cart_hash' => $payment_hash,
            'date_upd' => date('Y-m-d H:i:s'),
        ];
        $this->plugin
            ->getPaymentRepository()
            ->createEntity($parameters);


        $order_create = $this->dependencies
            ->getPlugin()
            ->getOrderAction()
            ->createAction($resource->id);
        $order = $this->order->get((int)$order_create['id_order'] );
        $state_addons = $resource->is_live ? '' : '_test';
        $pending_os = $this->configuration->getValue('order_state_email_link' . $state_addons);
        if ($order->getCurrentState() == $this->configuration->getValue('order_state_pending')) {
            $this->dependencies
                ->getPlugin()
                ->getOrderClass()
                ->updateOrderState($order, (int) $pending_os);
        }

        return [
            'result' => true,
            'code' => 200,
            'message' => 'Order and payment created successfully.',
            'order_id' => $order->id,
            'resource_id' => $resource->id,
            'payment_url' => isset($resource->hosted_payment) ? $resource->hosted_payment->payment_url : null,
            'cart_id' => $current_cart->id,
        ];
    }
    protected function setParameters()
    {
        if (null == $this->context) {
            $this->context = $this->dependencies
                ->getPlugin()
                ->getContext()
                ->get();
        }
        if (null == $this->plugin) {
           $this->plugin =  $this->dependencies
                ->getPlugin();
        }
        if (null == $this->configuration) {
            $this->configuration = $this->dependencies
                ->getPlugin()->getConfigurationClass();
        }

        if (null == $this->order ) {
            $this->order = $this->dependencies->getPlugin()->getOrder();
        }

    }

}
