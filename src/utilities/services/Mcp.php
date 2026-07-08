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

namespace PayPlug\src\utilities\services;

use PayPlug\classes\DependenciesClass;
use PayPlugPluginMcp\Models\Entities\PaymentInputDTO;
use PayPlugPluginMcp\Models\Entities\RefundInputDTO;
use PrestaShop\Module\PsMcpServer\Server\Attributes\PsMcpTool;
use PrestaShop\Module\PsMcpServer\Server\Attributes\PsMcpSchema;
use PrestaShop\Module\PsMcpServer\Server\Attributes\PsMcpToolAnnotations;
use PrestaShop\Module\PsMcpServer\Server\Exceptions\PsMcpToolCallException;

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

    /** @var object */
    protected $currency;

    /** @var object */
    protected $customer_adapter;

    /** @var object */
    protected $product_adapter;

    /** @var object */
    protected $validate_adapter;

    /** @var object */
    protected $country_adapter;

    /** @var object */
    protected $language_adapter;


    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description create a PaymentInputDTO from given parameters
     * @param array $params
     * @return array
     */
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
            $attributes = $this->formatMCPPaymentAttributes($params);

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
                'code' => (int)$e->getCode(),
                'message' => $e->getMessage(),
                'dto' => null,
            ];
        }
    }

    /**
     * @description create a RefundInputDTO from given parameters
     * @param array $params
     * @return array
     */
    protected function createRefundInputDto(array $params)
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
            $attributes = $this->formatMCPRefundAttributes($params);
            $dto = RefundInputDTO::create($attributes);
            return [
                'result' => true,
                'code' => 200,
                'message' => 'DTO created',
                'dto' => $dto,
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'code' => (int)$e->getCode(),
                'message' => $e->getMessage(),
                'dto' => null,
            ];
        }
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function formatMCPPaymentAttributes(array $attributes)
    {
        $attributes['payment_method'] = 'email_link';

        // Get Api bearer (api_key or jwt)
        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
        $attributes['api_bearer'] = $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name)
            ->getService('payplug.utilities.service.api')
            ->getMcpApiBearer(!(bool)$configuration->getValue('sandbox_mode'));

        $base_url = $this->context->shop->getBaseURL(true);
        $module_name = $this->dependencies->name;
        $attributes['urls'] = [
            'return' => $base_url . "module/$module_name/validation",
            'cancel' => $base_url . "module/$module_name/cancel",
            'notification' => $base_url . "module/$module_name/notification",
        ];

        // Set meta data and potential required context
        $attributes['metadata'] = [
            'source' => 'powered by MCP Payplug',
        ];
        $attributes['context'] = [];

        return $attributes;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function formatMCPRefundAttributes(array $attributes)
    {
        $resource = $attributes['resource'];
        $amount = $attributes['amount'];
        $cart_id = $attributes['cart_id'];

        $plugin = $this->dependencies->getPlugin();

        // Determine refund amount (in cents) and validate it against remaining refundable amount.
        $remaining_amount = (int)$resource->amount - (int)$resource->amount_refunded;
        if ((int)$amount === 0) {
            $orders_count = $plugin
                ->getOrderRepository()
                ->getByIdCart((int) $cart_id);

            if (count($orders_count) > 1) {
                throw new \Exception('Full refund (amount=0) is not allowed for multishipping orders. Please provide an explicit refund amount in cents.');
            }
        }

        $refund_amount = (int)$amount === 0 ? $remaining_amount : (int)$amount;
        if ($refund_amount < 10) {
            throw new \Exception('Refund amount must be at least 10 cents.');
        }
        if ($refund_amount > $remaining_amount) {
            throw new \Exception('Refund amount exceeds remaining refundable amount.');
        }
        $attributes['amount'] = $refund_amount;
        unset($attributes['cart_id']);

        return $attributes;
    }

    /**
     * Creates a payment link for a customer.
     * @return array|string
     */
    #[PsMcpTool(
        name: "create_payment_link",
        title: "Create payment link",
        description: "Creates a PrestaShop order and a Payplug payment link for a customer. The payment link is sent by email to the customer who completes payment on the Payplug hosted page. This tool creates the cart, the order, and the Payplug payment resource in one call.

        STEP 1 — ALWAYS call payplug-get_customer_info first to retrieve customer data before calling this tool. Do not invent or assume customer data.

        STEP 2 — MAP the fields from payplug-get_customer_info to this tool as follows. customer.email goes to customer_address_email. customer.firstname goes to customer_address_first_name. customer.lastname goes to customer_address_last_name. customer.gender goes to customer_address_title, accepted values are \"mr\" or \"mrs\" exactly. customer.language goes to customer_address_language. addresses[0].address1 goes to customer_address_address1. addresses[0].address2 goes to customer_address_address2, leave empty string if not present. addresses[0].postcode goes to customer_address_postcode. addresses[0].city goes to customer_address_city. addresses[0].country goes to customer_address_country, already in ISO 3166-1 alpha-2 format.

        STEP 3 — PHONE NUMBER CONVERSION (critical, Payplug rejects wrong format). Use addresses[0].phone_mobile as customer_address_mobile_phone_number. The value must be in E.164 international format starting with +. If the value starts with 0, replace the leading 0 with the country calling code prefix. For France (country FR): \"0612345678\" becomes \"+33612345678\". For Belgium (BE): \"0412345678\" becomes \"+32412345678\". For Spain (ES): \"0612345678\" becomes \"+34612345678\". If phone_mobile is empty or null, fall back to addresses[0].phone and apply the same conversion. If both phone and phone_mobile are empty, stop and ask the user to provide a mobile phone number before proceeding.

        ADDRESS SELECTION RULE: if the customer has multiple addresses in the addresses array, always use addresses[0] (the first one in the list).

        CARRIER: the carrier_name field is optional. If omitted, the default shop carrier is used automatically. Do not invent a carrier name. Only provide it if the user explicitly specifies one.

        PRODUCTS: use the PrestaShop MCP tool \"Search product\" to find id_product values. For products with variants (sizes, colors), also call \"Get product combinations\" to find the correct id_product_attribute and pass it as the group field. If you only have an order reference from a previous PS MCP call, find product_id in associations.order_rows of that order response.",
        annotations: new PsMcpToolAnnotations(
            title: 'Create payment link',
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: false,
            openWorldHint: false,
        )
    )]
    #[PsMcpSchema(
        properties: [
            'customer' => [
                'type' => 'object',
                'description' => 'Customer information object containing all required customer details for billing and shipping.',
                'properties' => [
                    'customer_id' => ['type' => 'integer', 'description' => 'Unique identifier of the customer in PrestaShop database (id_customer). Required.'],
                    'customer_address_title' => ['type' => 'string', 'description' => 'Customer civility/salutation. Accepted values: "mr" for male, "mrs" for female, or empty string if unknown. Optional.'],
                    'customer_address_first_name' => ['type' => 'string', 'description' => 'Customer first name. Required. Example: "Jean".'],
                    'customer_address_last_name' => ['type' => 'string', 'description' => 'Customer last name/family name. Required. Example: "Dupont".'],
                    'customer_address_mobile_phone_number' => ['type' => 'string', 'description' => 'Customer mobile phone number in E.164 international format. Required. Must start with country code. Required. Example: "+33612345678" for France.'],
                    'customer_address_email' => ['type' => 'string', 'description' => 'Customer email address. Required. Must be a valid email format. Example: "customer@example.com".'],
                    'customer_address_address1' => ['type' => 'string', 'description' => 'Primary street address line. Required. Example: "123 Rue de la Paix".'],
                    'customer_address_address2' => ['type' => 'string', 'description' => 'Secondary address line for additional details like apartment, building, floor. Optional. Example: "Apartment 4B".'],
                    'customer_address_postcode' => ['type' => 'string', 'description' => 'Postal/ZIP code. Required. Format depends on country. Example: "75001" for Paris, France.'],
                    'customer_address_city' => ['type' => 'string', 'description' => 'City name. Required. Example: "Paris".'],
                    'customer_address_country' => ['type' => 'string', 'description' => 'Country as ISO 3166-1 alpha-2 code (2 letters uppercase). Required. Example: "FR" for France, "DE" for Germany, "ES" for Spain.'],
                    'customer_address_language' => ['type' => 'string', 'description' => 'Preferred language as ISO 639-1 code (2 letters lowercase). Required. Used for payment page and communications. Example: "fr" for French, "en" for English.'],
                ],
                'required' => ['customer_id', 'customer_address_email', 'customer_address_first_name', 'customer_address_last_name', 'customer_address_mobile_phone_number', 'customer_address_address1', 'customer_address_postcode', 'customer_address_city', 'customer_address_country', 'customer_address_language']
            ],
            'cart' => [
                'type' => 'object',
                'description' => 'Shopping cart object containing the list of products to purchase. Use PrestaShop MCP tools "Search product" and "Get product combinations" to find the correct product and combination IDs before creating the cart.',
                'properties' => [
                    'products' => [
                        'type' => 'array',
                        'description' => 'Array of product objects to add to the cart. Must contain at least one product. Each product requires reference (id_product) and qty. For products with variants (sizes, colors), you MUST also provide the group (id_product_attribute) obtained from "Get product combinations" tool.',
                        'items' => [
                            'type' => 'object',
                            'description' => 'Individual product object representing one item to add to the cart.',
                            'properties' => [
                                'reference' => ['type' => 'integer', 'description' => 'PrestaShop product ID (id_product). Required. Obtain this value using PrestaShop MCP "Search product" tool. This is the unique identifier of the base product, NOT the SKU/reference string.'],
                                'qty' => ['type' => 'integer', 'description' => 'Quantity of this product to add to cart. Required. Must be a positive integer >= 1. Example: 1 for one item, 3 for three items.'],
                                'group' => [
                                    'type' => 'array',
                                    'description' => 'List of selected attribute value IDs that identify the product combination. REQUIRED for products with variants (sizes, colors, etc.). Use PrestaShop MCP "Get product combinations" tool to get combinations, then collect the "id" of each selected attribute value into this array. Example: if the customer wants Taille=L and Couleur=Rouge, and "Get product combinations" returns attributes [{\"id\": 10, \"name\": \"L\"}, {\"id\": 5, \"name\": \"Rouge\"}], pass group: [10, 5]. Do NOT pass the top-level combination id. Omit this field if the product has no combinations.',
                                    'items' => ['type' => 'integer'],
                                ],
                            ],
                            'required' => ['reference', 'qty']
                        ],
                    ],
                    'carrier_name' => ['type' => 'string', 'description' => 'Name of the shipping carrier to use. Optional. If not provided, default carrier will be used. Example: "Colissimo", "Chronopost".'],
                ],
                'required' => ['products']
            ]
        ],
        required: ['customer', 'cart']
    )]
    public function createPaymentByLink(array $customer, array $cart)
    {
        try {
            $this->setParameters();

            // create cart and add products
            $cart_adapter = $this->dependencies->getPlugin()->getCart();
            $cart_rule_adapter = $this->dependencies->getPlugin()->getCartRule();
            $product_adapter = $this->dependencies->getPlugin()->getProductAdapter();
            // customer_id is required and must resolve to a positive integer.
            if (empty($customer['customer_id']) || !is_numeric($customer['customer_id']) || (int)$customer['customer_id'] <= 0) {
                return [
                    'result' => false,
                    'code' => 400,
                    'message' => 'customer_id is required and must be a positive integer.',
                ];
            }
            $customer_id = (int)$customer['customer_id'];
            $this->context->cookie->id_customer = $customer_id;
            $current_cart = $cart_adapter->createNewCart($this->context, $customer_id);
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
                        // group is an array of attribute value IDs (e.g. [10, 5] for Taille=L + Couleur=Rouge).
                        // Resolve to the matching id_product_attribute via DB lookup.
                        if (!is_array($group)) {
                            $group = [$group]; // safety: handle integer passed by mistake
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

            // Build formatted address only with fields that exist.
            $formated_address = array_filter([
                'firstname' => isset($customer['customer_address_first_name']) ? $customer['customer_address_first_name'] : null,
                'lastname' => isset($customer['customer_address_last_name']) ? $customer['customer_address_last_name'] : null,
                'address1' => isset($customer['customer_address_address1']) ? $customer['customer_address_address1'] : null,
                'address2' => array_key_exists('customer_address_address2', $customer) ? $customer['customer_address_address2'] : null,
                'postcode' => isset($customer['customer_address_postcode']) ? $customer['customer_address_postcode'] : null,
                'city' => isset($customer['customer_address_city']) ? $customer['customer_address_city'] : null,
                'id_country' => isset($customer['customer_address_country'])
                    ? $this->dependencies->getPlugin()->getCountry()->getByIso($customer['customer_address_country'])
                    : null,
            ], function ($value) {
                return $value !== null;
            });

            // Only update cart address if we have formatted address data
            if (!empty($formated_address)) {
                $new_address_id = $this->dependencies
                    ->getPlugin()
                    ->getAddressClass()
                    ->checkAndSaveAddress($formated_address);

                if (!empty($new_address_id)) {
                    $cart_adapter->updateAddresses($current_cart, $new_address_id, $new_address_id);
                }
            }


            $this->context->cart = $cart_adapter->get((int)$current_cart->id);
            $cart_total = $current_cart->getOrderTotal();
            $currency = $this->currency->get((int)$current_cart->id_currency);
            $dto_params = [
                'amount' => (int)round($cart_total * 100),
                'currency_iso_code' => $currency->iso_code,
                'customer' => [
                    'identifier' => $customer_id, // Non mandatory
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

            if (empty($order_create['result']) || empty($order_create['id_order'])) {
                return [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Order creation failed: ' . (isset($order_create['message']) ? $order_create['message'] : 'createAction() returned an unexpected response.'),
                ];
            }

            $order = $this->order->get((int)$order_create['id_order']);
            $state_addons = $resource->is_live ? '' : '_test';
            $pending_os = $this->configuration->getValue('order_state_email_link' . $state_addons);
            if ($order->getCurrentState() == $this->configuration->getValue('order_state_pending')) {
                $this->dependencies
                    ->getPlugin()
                    ->getOrderClass()
                    ->updateOrderState($order, (int)$pending_os);
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
        } catch (\Exception $e) {
            throw new PsMcpToolCallException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Creates a refund for a given order.
     * @return array|string
     */
    #[PsMcpTool(
        name: "create_refund",
        title: "Create refund",
        description: "Refunds a Payplug payment linked to a PrestaShop order, partially or in full. Use this when a customer or merchant requests a refund on a completed order. The PrestaShop order status is automatically updated after the refund. This tool is fully autonomous — it does not require calling any other tool beforehand.
        
        YOU ONLY NEED THE ORDER ID AND THE AMOUNT: pass id_order and amount. The tool resolves the Payplug payment and customer internally from the order. Do not look for or pass a resource_id or id_customer — they are not parameters of this tool.
        
        ⚠️ MANDATORY CONFIRMATION — THIS TOOL MUST NEVER BE CALLED WITHOUT EXPLICIT USER APPROVAL. A refund is irreversible. Before calling this tool, you MUST:
        1. Ask the user to confirm the order ID and the exact amount to refund in euros.
        2. Display a clear confirmation summary:
           - Order ID
           - Amount that will be refunded (in euros)
           - Whether it is a full or partial refund
        3. Ask the user to explicitly confirm: \"Confirmez-vous le remboursement de X€ sur la commande #Y ?\" (or equivalent in the conversation language).
        4. Only call this tool AFTER the user has replied with a clear confirmation (\"oui\", \"confirme\", \"ok\", \"yes\", etc.).
        If the user does not confirm or expresses doubt, do NOT proceed.
        
        AMOUNT RULES: amount must be an integer in euro CENTS, not euros. Example: €49.99 must be passed as 4999. Minimum value is 10 cents. To refund the full remaining amount, pass 0 and the tool calculates the correct amount automatically. Partial refunds are supported — you can call this tool multiple times on the same payment as long as the cumulative refunded amount does not exceed the original payment amount.
        
        MULTISHIPPING WARNING: in PrestaShop, a multishipping cart generates multiple orders (multiple id_order values) all linked to the same single Payplug payment. If the order is part of a multishipping cart, NEVER pass amount 0. Passing 0 would refund the entire payment across all sub-orders, not just the one requested. Always ask the merchant to confirm the exact euro amount for that specific sub-order.
        
        WHAT HAPPENS AUTOMATICALLY AFTER REFUND: if the full amount is refunded, the PrestaShop order status changes to \"Remboursé\". If a partial refund, the order status changes to \"Partiellement remboursé\". The customer ID is logged in the Payplug refund metadata for traceability.
        
        DO NOT USE THIS TOOL IF: the payment is not yet captured (is_paid is false) — you are unsure of the amount, always ask the user to confirm the exact euro amount before converting to cents — the payment is an installment plan (paiement en plusieurs fois) — the user has not explicitly confirmed the refund.
        
        MODE: works automatically in both live and test mode. The mode is determined from the payment linked to the order.",
        annotations: new PsMcpToolAnnotations(
            title: 'Create refund',
            readOnlyHint: false,
            destructiveHint: true,
            idempotentHint: false,
            openWorldHint: false,
        )
    )]
    #[PsMcpSchema(
        properties: [
            'order_id' => ['type' => 'integer', 'description' => 'Unique identifier of the order in PrestaShop database. Required.'],
            'amount' => ['type' => 'integer', 'description' => 'Amount that should be refund on the order, if you want a full refund given 0 value. Required.'],
        ],
        required: ['order_id', 'amount']
    )]
    public function createRefund(int $order_id, int $amount)
    {
        try {
            $this->setParameters();

            $plugin = $this->dependencies->getPlugin();

            //
            $order = $plugin
                ->getOrder()
                ->get((int)$order_id);
            if (!\Validate::isLoadedObject($order)) {
                throw new \Exception('Order #' . $order_id . ' not found.');
            }
            $stored_resource = $plugin
                ->getPaymentRepository()
                ->getBy('id_cart', (int)$order->id_cart);
            if (empty($stored_resource) || empty($stored_resource['method'])) {
                throw new \Exception('No Payplug payment found for order #' . $order_id . '.');
            }
            $retrieve = $plugin
                ->getPaymentMethodClass()
                ->getPaymentMethod($stored_resource['method'])
                ->retrieve($stored_resource['resource_id']);
            if (empty($retrieve['result']) || empty($retrieve['resource'])) {
                throw new \Exception('Unable to retrieve Payplug payment resource for order #' . $order_id . '.');
            }

            $resource = $retrieve['resource'];
            $api_bearer = $plugin
                ->getModule()
                ->getInstanceByName($this->dependencies->name)
                ->getService('payplug.utilities.service.api')
                ->getMcpApiBearer((bool)$resource->is_live);

            //
            $dto_params = [
                'api_bearer' => (string)$api_bearer,
                'resource' => $resource,
                'amount' => $amount,
                'customer_id' => (int)$order->id_customer,
                'cart_id' => (int)$order->id_cart,
                'reason' => 'Refund requested by customer or merchant via PrestaShop MCP tool.',
            ];
            $dtoResult = $this->createRefundInputDto($dto_params);
            if (!$dtoResult['result'] || !$dtoResult['dto']) {
                return [
                    'result' => false,
                    'code' => $dtoResult['code'],
                    'message' => $dtoResult['message'],
                ];
            }

            $refund_dto = $this->dependencies
                ->getPlugin()
                ->getModule()
                ->getInstanceByName($this->dependencies->name)
                ->getService('payplug.utilities.service.core')
                ->createCoreRefund($dtoResult['dto']);
            if (!$refund_dto['result']) {
                return [
                    'result' => false,
                    'code' => $refund_dto['code'],
                    'message' => $refund_dto['message'],
                ];
            }

            // Update order state based on refund amount
            $retrieve = $plugin
                ->getPaymentMethodClass()
                ->getPaymentMethod($stored_resource['method'])
                ->retrieve($stored_resource['resource_id']);
            if (empty($retrieve['result']) || empty($retrieve['resource'])) {
                throw new \Exception('Unable to reload Payplug payment resource after refund for order #' . $order_id . '.');
            }

            $state_addons = $retrieve['resource']->is_live ? '' : '_test';
            $new_state = $retrieve['resource']->is_refunded
                ? (int)$this->plugin->getConfigurationClass()->getValue('order_state_refund' . $state_addons)
                : (int)$this->plugin->getConfigurationClass()->getValue('order_state_partial_refund' . $state_addons);

            $this->plugin->getOrderClass()->updateOrderState($order, $new_state);

            return [
                'result' => true,
                'code' => 200,
                'message' => 'Refund created successfully.',
                'order_id' => $order->id,
                'new_state' => $new_state,
            ];
        } catch (\Throwable $e) {
            throw new PsMcpToolCallException($e->getMessage(), $e->getCode());
        }
    }


    /**
     * Search for a customer by email and return their information including addresses.
     * @return array
     */
    #[PsMcpTool(
        name: "get_customer_info",
        title: "Get customer info",
        description: "Search for a customer in PrestaShop database by email address or customer ID. Returns customer details and all their saved addresses. Use this tool FIRST to get customer information before creating a payment link.",
        annotations: new PsMcpToolAnnotations(
            title: 'Get customer info',
            readOnlyHint: true,
            destructiveHint: false,
            idempotentHint: true,
            openWorldHint: false,
        )
    )]
    #[PsMcpSchema(
        properties: [
            'email' => ['type' => 'string', 'description' => 'Customer email address to search for. Example: "customer@example.com". Either email or customer_id must be provided.'],
            'customer_id' => ['type' => 'integer', 'description' => 'Customer ID (id_customer) to search for. Either email or customer_id must be provided.'],
        ],
        required: []
    )]
    public function getCustomerInfo($email = null, $customer_id = null)
    {
        $this->setParameters();

        if (empty($email) && empty($customer_id)) {
            return array(
                'result' => false,
                'code' => 400,
                'message' => 'Either email or customer_id must be provided.',
            );
        }

        $customer = null;
        $id_lang = (int)$this->context->language->id;

        // Search by customer_id first if provided
        if (!empty($customer_id)) {
            $customer = $this->customer_adapter->get($customer_id);
            if (!$this->validate_adapter->validate('isLoadedObject', $customer)) {
                $customer = null;
            }
        }

        // Search by email if customer_id not provided or not found
        if ($customer === null && !empty($email)) {
            $customer_id_by_email = $this->customer_adapter->customerExists($email, true);
            if ($customer_id_by_email) {
                $customer = $this->customer_adapter->get((int)$customer_id_by_email);
            }
        }

        if ($customer === null || !$this->validate_adapter->validate('isLoadedObject', $customer)) {
            return array(
                'result' => false,
                'code' => 404,
                'message' => 'Customer not found with the provided email or customer_id.',
            );
        }

        // Get all addresses for this customer
        $addresses = $customer->getAddresses($id_lang);
        $formatted_addresses = array();

        foreach ($addresses as $address) {
            // Get country ISO code
            $country_iso = '';
            if (!empty($address['id_country'])) {
                $country = $this->country_adapter->get((int)$address['id_country']);
                $country_iso = $country->iso_code;
            }

            $formatted_addresses[] = array(
                'id_address' => (int)$address['id_address'],
                'alias' => isset($address['alias']) ? $address['alias'] : '',
                'firstname' => isset($address['firstname']) ? $address['firstname'] : '',
                'lastname' => isset($address['lastname']) ? $address['lastname'] : '',
                'address1' => isset($address['address1']) ? $address['address1'] : '',
                'address2' => isset($address['address2']) ? $address['address2'] : '',
                'postcode' => isset($address['postcode']) ? $address['postcode'] : '',
                'city' => isset($address['city']) ? $address['city'] : '',
                'country' => $country_iso,
                'phone' => isset($address['phone']) ? $address['phone'] : '',
                'phone_mobile' => isset($address['phone_mobile']) ? $address['phone_mobile'] : '',
            );
        }

        // Get customer language ISO code
        $customer_lang_iso = '';
        if (!empty($customer->id_lang)) {
            $lang = $this->language_adapter->get((int)$customer->id_lang);
            $customer_lang_iso = $lang->iso_code;
        } else {
            $customer_lang_iso = $this->context->language->iso_code;
        }

        return array(
            'result' => true,
            'code' => 200,
            'message' => 'Customer found successfully.',
            'customer' => array(
                'customer_id' => (int)$customer->id,
                'email' => $customer->email,
                'firstname' => $customer->firstname,
                'lastname' => $customer->lastname,
                'language' => $customer_lang_iso,
                'gender' => $customer->id_gender == 1 ? 'mr' : ($customer->id_gender == 2 ? 'mrs' : ''),
            ),
            'addresses' => $formatted_addresses,
            'addresses_count' => count($formatted_addresses),
        );
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
            $this->plugin = $this->dependencies
                ->getPlugin();
        }
        if (null == $this->configuration) {
            $this->configuration = $this->dependencies
                ->getPlugin()->getConfigurationClass();
        }

        if (null == $this->order) {
            $this->order = $this->dependencies
                ->getPlugin()->getOrder();
        }
        if (null == $this->currency) {
            $this->currency = $this->dependencies
                ->getPlugin()->getCurrency();
        }
        if (null == $this->product_adapter) {
            $this->product_adapter = $this->dependencies
                ->getPlugin()->getProductAdapter();
        }
        if (null == $this->customer_adapter) {
            $this->customer_adapter = $this->dependencies
                ->getPlugin()->getCustomer();
        }
        if (null == $this->validate_adapter) {
            $this->validate_adapter = $this->dependencies
                ->getPlugin()->getValidate();
        }
        if (null == $this->country_adapter) {
            $this->country_adapter = $this->dependencies
                ->getPlugin()->getCountry();
        }
        if (null == $this->language_adapter) {
            $this->language_adapter = $this->dependencies
                ->getPlugin()->getLanguage();
        }

    }
}
