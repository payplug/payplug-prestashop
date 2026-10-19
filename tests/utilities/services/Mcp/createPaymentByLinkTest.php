<?php

namespace PayPlug\tests\utilities\services\Mcp;

use PayPlug\src\utilities\services\Mcp;
use PHPUnit\Framework\TestCase;

/**
 * Mcp uses PHP 8 attributes and cannot be loaded on older PHP versions.
 *
 * @requires PHP >= 8.0
 *
 * @group unit
 * @group service
 * @group mcp_service
 */
class createPaymentByLinkTest extends TestCase
{
    private $amount_helper;
    private $cart;
    private $currency;
    private $dto_params;
    private $service;

    public function setUp(): void
    {
        $this->dto_params = null;

        $this->cart = \Mockery::mock('Cart');
        $this->cart->id = 42;
        $this->cart->id_currency = 7;
        $this->cart->shouldReceive('getOrderTotal')->andReturn(1500.0)->byDefault();

        $cart_adapter = \Mockery::mock('CartAdapter');
        $cart_adapter->shouldReceive([
            'createNewCart' => $this->cart,
            'update' => true,
            'get' => $this->cart,
        ]);
        $cart_rule_adapter = \Mockery::mock('CartRuleAdapter');
        $cart_rule_adapter->shouldReceive('autoAddToCart');
        $address_class = \Mockery::mock('AddressClass');
        $address_class->shouldReceive('checkAndSaveAddress')->andReturn(0);
        $country_adapter = \Mockery::mock('CountryAdapter');
        $country_adapter->shouldReceive('getByIso')->with('FR')->andReturn(8);

        $this->currency = new \stdClass();
        $this->currency->iso_code = 'JPY';
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $currency_adapter->shouldReceive('get')->with(7)->andReturn($this->currency);

        $this->amount_helper = \Mockery::mock('AmountHelper');
        $this->amount_helper->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $from_cents, $currency) {
                return 'JPY' === $currency ? (int) $amount : (int) round($amount * 100);
            })
            ->byDefault();
        $module_instance = \Mockery::mock('ModuleInstance');
        $module_instance->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($this->amount_helper);
        $module = \Mockery::mock('Module');
        $module->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module_instance);

        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive([
            'getAddressClass' => $address_class,
            'getCart' => $cart_adapter,
            'getCartRule' => $cart_rule_adapter,
            'getConfigurationClass' => \Mockery::mock('Configuration'),
            'getCountry' => $country_adapter,
            'getCurrency' => $currency_adapter,
            'getCustomer' => \Mockery::mock('CustomerAdapter'),
            'getLanguage' => \Mockery::mock('LanguageAdapter'),
            'getModule' => $module,
            'getOrder' => \Mockery::mock('OrderAdapter'),
            'getProductAdapter' => \Mockery::mock('ProductAdapter'),
            'getValidate' => \Mockery::mock('ValidateAdapter'),
        ]);

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';
        $dependencies->shouldReceive(['getPlugin' => $plugin]);

        $context = new \stdClass();
        $context->cookie = new \stdClass();

        $this->service = \Mockery::mock(Mcp::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->service->shouldReceive('createPaymentInputDto')
            ->andReturnUsing(function ($params) {
                $this->dto_params = $params;

                return [
                    'result' => false,
                    'code' => 400,
                    'message' => 'stopped after the DTO parameters',
                    'dto' => null,
                ];
            });

        $dependencies_property = new \ReflectionProperty(Mcp::class, 'dependencies');
        $dependencies_property->setAccessible(true);
        $dependencies_property->setValue($this->service, $dependencies);

        $context_property = new \ReflectionProperty(Mcp::class, 'context');
        $context_property->setAccessible(true);
        $context_property->setValue($this->service, $context);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testZeroDecimalCurrencyCartAmountIsNotMultipliedByHundred()
    {
        $this->service->createPaymentByLink($this->getCustomer(), ['products' => []]);

        $this->assertSame(1500, $this->dto_params['amount']);
        $this->assertSame('JPY', $this->dto_params['currency_iso_code']);
        $this->amount_helper->shouldHaveReceived('convertAmount')->with(1500.0, false, 'JPY')->once();
    }

    public function testTwoDecimalCurrencyCartAmountIsConvertedToCents()
    {
        $this->currency->iso_code = 'EUR';
        $this->cart->shouldReceive('getOrderTotal')->andReturn(10.5);

        $this->service->createPaymentByLink($this->getCustomer(), ['products' => []]);

        $this->assertSame(1050, $this->dto_params['amount']);
    }

    public function testCartAmountThatCannotBeConvertedIsRefusedBeforeThePaymentIsCreated()
    {
        $this->currency->iso_code = 'KWD';
        $this->amount_helper->shouldReceive('convertAmount')->andReturn(0);

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'The cart amount cannot be converted.',
            ],
            $this->service->createPaymentByLink($this->getCustomer(), ['products' => []])
        );
        $this->assertNull($this->dto_params);
    }

    /**
     * @return array
     */
    private function getCustomer()
    {
        return [
            'customer_id' => 4,
            'customer_address_title' => 'mr',
            'customer_address_first_name' => 'Jane',
            'customer_address_last_name' => 'Doe',
            'customer_address_mobile_phone_number' => '+33612345678',
            'customer_address_email' => 'jane@example.com',
            'customer_address_address1' => '1 rue de Paris',
            'customer_address_address2' => null,
            'customer_address_postcode' => '75001',
            'customer_address_city' => 'Paris',
            'customer_address_country' => 'FR',
            'customer_address_language' => 'fr',
        ];
    }
}
