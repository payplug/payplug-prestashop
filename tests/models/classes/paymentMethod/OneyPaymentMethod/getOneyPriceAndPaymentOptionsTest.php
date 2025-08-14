<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\ContextMock;
use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
final class getOneyPriceAndPaymentOptionsTest extends BaseOneyPaymentMethod
{
    private $cartMock;

    public function setUp()
    {
        parent::setUp();

        $this->context_adapter->shouldReceive('get')
            ->andReturn(ContextMock::get());

        $this->context_adapter->cart = \Mockery::mock('CartAdapter');
        $this->context_adapter->cart->shouldReceive([
            'nbProducts' => 1001,
        ]);

        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;

        $this->context_adapter->country = \Mockery::mock('CountryAdapter');
        $this->context_adapter->country->shouldReceive([
            'getByIso' => 4,
        ]);

        $this->context_adapter->currency = \Mockery::mock('CurrencyAdapter');
        $this->context_adapter->currency->shouldReceive([
            'get' => CurrencyMock::get(),
        ]);

        $this->validators['payment']->shouldReceive([
            'isValidProductQuantity' => [
                'result' => true,
                'message' => '',
            ],
        ]);

        $this->class
            ->shouldAllowMockingProtectedMethods()->shouldReceive([
                'displayOneyPopin' => 'popin',
                'displayOneyPaymentOptions' => 'payment_option',
            ])
        ;

        $this->cartMock = CartMock::get();
        $this->cartMock->id_address_delivery = 42;
        $this->cartMock->id_address_invoice = 42;
    }

    public function testWithValidData()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 15000,
        ]);

        $this->class->shouldReceive([
            'isValidOneyAmount' => ['result' => true, 'error' => false],
            'getOneyPaymentOptionsList' => ['payment_option_list'],
            'isValidOneyCartQty' => ['result' => true, 'error' => false],
            'isValidOneyAddresses' => true,
        ])
        ;

        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
                'message' => '',
            ],
        ]);

        $this->assertSame(
            $this->class->getOneyPriceAndPaymentOptions($this->cartMock, 15000, false),
            [
                'result' => true,
                'error' => false,
                'popin' => 'popin',
            ]
        );
    }

    public function testWithIneligibleOney()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 15000,
        ]);

        $this->class->shouldReceive([
            'isValidOneyAmount' => ['result' => true, 'error' => false],
            'getOneyPaymentOptionsList' => ['payment_option_list'],
            'isValidOneyCartQty' => ['result' => true, 'error' => false],
            'isValidOneyAddresses' => true,
        ])
        ;

        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => false,
                'message' => '',
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.getOneyPriceAndPaymentOptions.unavailable',
                'popin' => 'popin',
            ],
            $this->class->getOneyPriceAndPaymentOptions($this->cartMock, 15000)
        );
    }

    public function testWithInvalidAmount()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 15000,
        ]);

        $this->class->shouldReceive([
            'isValidOneyAmount' => ['result' => false, 'error' => 'invalid_amount'],
            'getOneyPaymentOptionsList' => ['payment_option_list'],
            'isValidOneyCartQty' => ['result' => true, 'error' => false],
            'isValidOneyAddresses' => true,
        ])
        ;

        $this->assertSame(
            [
                'result' => false,
                'error' => 'invalid_amount',
                'popin' => 'popin',
            ],
            $this->class->getOneyPriceAndPaymentOptions($this->cartMock, 15000)
        );
    }

    public function testWithoutPaymentOption()
    {
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 15000,
        ]);

        $this->class->shouldReceive([
            'isValidOneyAmount' => ['result' => true, 'error' => false],
            'getOneyPaymentOptionsList' => [],
            'isValidOneyCartQty' => ['result' => true, 'error' => false],
            'isValidOneyAddresses' => true,
        ])
        ;

        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
                'message' => '',
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.getOneyPriceAndPaymentOptions.unavailable',
                'popin' => 'popin',
            ],
            $this->class->getOneyPriceAndPaymentOptions($this->cartMock, 15000)
        );
    }
}
