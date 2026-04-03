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


    public function __construct(){
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @desccription create a PaymentInputDTO from given parameters
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
            $attributes = $params;
            $attributes = $this->formatMCPAttributes($attributes);

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
     * @param array $attributes
     * @return array
     */
    protected function formatMCPAttributes(array $attributes)
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
            ->getApiBearer(!(bool) $configuration->getValue('sandbox_mode'));

        $base_url = $this->context->shop->getBaseURL(true);
        $module_name = $this->dependencies->name;
        $attributes['urls'] = [
            'return' => $base_url . "module/$module_name/validation",
            'cancel' =>  $base_url . "module/$module_name/cancel",
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
                'required' => ['customer_id','customer_address_email','customer_address_first_name','customer_address_last_name','customer_address_mobile_phone_number','customer_address_address1','customer_address_postcode','customer_address_city','customer_address_country','customer_address_language']
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
                                'group' => ['type' => 'integer', 'description' => 'Product combination/variant ID (id_product_attribute). REQUIRED for products with variants (different sizes, colors, etc.). Obtain this value using PrestaShop MCP "Get product combinations" tool with the id_product. Example: For a T-shirt size S in black, get the specific id_product_attribute that matches "Taille: S, Couleur: Noir". If product has no combinations, omit this field.'],
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

        // Build formatted address only with fields that exist
        $formated_address = array_filter([
            'firstname' => isset($customer['customer_address_first_name']) ? $customer['customer_address_first_name'] : null,
            'lastname' => isset($customer['customer_address_last_name']) ? $customer['customer_address_last_name'] : null,
            'address1' => isset($customer['customer_address_address1']) ? $customer['customer_address_address1'] : null,
            'address2' => isset($customer['customer_address_address2']) ? $customer['customer_address_address2'] : null,
            'postcode' => isset($customer['customer_address_postcode']) ? $customer['customer_address_postcode'] : null,
            'city' => isset($customer['customer_address_city']) ? $customer['customer_address_city'] : null,
            'id_country' => isset($customer['customer_address_country'])
                ? $this->dependencies->getPlugin()->getCountry()->getByIso($customer['customer_address_country'])
                : null,
        ]);

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
        $currency = $this->currency->get((int) $current_cart->id_currency);
        $dto_params = [
            'amount' => $cart_total * 100,
            'currency_iso_code' => $currency->iso_code,
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


    /**
     * Search for a customer by email and return their information including addresses.
     * @return array
     */
    #[McpTool(
        name: "get_customer_info",
        description: "Search for a customer in PrestaShop database by email address or customer ID. Returns customer details and all their saved addresses. Use this tool FIRST to get customer information before creating a payment link.",
    )]
    #[Schema(
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
        $id_lang = (int) $this->context->language->id;

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
                $customer = $this->customer_adapter->get((int) $customer_id_by_email);
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
                $country = $this->country_adapter->get((int) $address['id_country']);
                $country_iso = $country->iso_code;
            }

            $formatted_addresses[] = array(
                'id_address' => (int) $address['id_address'],
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
            $lang = $this->language_adapter->get((int) $customer->id_lang);
            $customer_lang_iso = $lang->iso_code;
        } else {
            $customer_lang_iso = $this->context->language->iso_code;
        }

        return array(
            'result' => true,
            'code' => 200,
            'message' => 'Customer found successfully.',
            'customer' => array(
                'customer_id' => (int) $customer->id,
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
            $this->plugin =  $this->dependencies
                ->getPlugin();
        }
        if (null == $this->configuration) {
            $this->configuration = $this->dependencies
                ->getPlugin()->getConfigurationClass();
        }

        if (null == $this->order ) {
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

