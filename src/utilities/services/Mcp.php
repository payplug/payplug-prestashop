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

            // todo: Get required url
            $attributes['urls'] = [
                'return' => 'https://example.net/success?id=42',
                'cancel' => 'https://example.net/cancel?id=42',
                'notification' => 'https://example.net/notifications?id=42',
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
            'amount' => ['type' => 'integer', 'description' => 'Price given in cents, this is the value of the cart'],
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
    public function createByLink(int $amount, array $customer, array $cart)
    {
        // Before create cart on CMS database (same as applepay product page)

        // Then generate ressource adn store the result in database payplug_payment
        $dto_params = [
            'amount' => $amount, // todo: FOR MVP should be define by the value of a cart
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
        try {
            $action = new \PayplugPluginCore\Actions\PaymentAction();
            return $action->createAction($dtoResult['dto']);
        } catch (\Throwable $e) {
            return [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        // And generate order from payment resource (use orderAction related, given resource id)

        // Cheers
    }

}
