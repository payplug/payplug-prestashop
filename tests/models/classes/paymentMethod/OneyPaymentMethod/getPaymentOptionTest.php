<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getPaymentOptionTest extends BaseOneyPaymentMethod
{
    public $payment_options;

    public function setUp(): void
    {
        parent::setUp();
        $this->payment_options = [];
        $this->configuration->shouldReceive('getValue')
            ->with('PS_TAX')
            ->andReturn(true);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function atestWhenGivenPaymentOptionsIsntValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->class->getPaymentOption($payment_options));
    }

    public function testWhenCartQtyIsNotValid()
    {
        $this->class->shouldReceive(
            [
                'isValidOneyCartQty' => [
                    'result' => false,
                    'message' => 'error given',
                ],
            ]
        );
        $this->assertSame([], $this->class->getPaymentOption($this->payment_options));
    }

    public function testWhenCartAmountIsNotValid()
    {
        $this->class->shouldReceive(
            [
                'isValidOneyCartQty' => [
                    'result' => true,
                    'message' => '',
                ],
                'isValidOneyAmount' => [
                    'result' => false,
                    'message' => 'error given',
                ],
            ]
        );
        $this->assertSame([], $this->class->getPaymentOption($this->payment_options));
    }

    public function testWhenCartAddressesDoesNotMatches()
    {
        $this->class->shouldReceive(
            [
                'isValidOneyCartQty' => [
                    'result' => true,
                    'message' => '',
                ],
                'isValidOneyAmount' => [
                    'result' => true,
                    'message' => '',
                ],
                'isValidOneyAddresses' => [
                    'result' => false,
                    'message' => 'error given',
                ],
            ]
        );
        $this->assertSame([], $this->class->getPaymentOption($this->payment_options));
    }

    public function testWhenOneyPaymentMethodsAreGiven()
    {
        $name = $this->class->get('name');
        $payment_option = [
            'name' => $name,
            'inputs' => [
                'pc' => [
                    'name' => 'pc',
                    'type' => 'hidden',
                    'value' => 'new_card',
                ],
                'pay' => [
                    'name' => 'pay',
                    'type' => 'hidden',
                    'value' => '1',
                ],
                'id_cart' => [
                    'name' => 'id_cart',
                    'type' => 'hidden',
                    'value' => '2',
                ],
                'method' => [
                    'name' => 'method',
                    'type' => 'hidden',
                    'value' => $name,
                ],
            ],
            'extra_classes' => $name,
            'payment_controller_url' => 'payment_controller_url',
            'logo' => 'logo_url',
            'callToActionText' => '',
            'action' => 'action_url',
            'moduleName' => 'payplug',
        ];
        $parent_payment_option = [
            $name => $payment_option,
        ];

        $this->class->shouldReceive(
            [
                'isValidOneyCartQty' => [
                    'result' => true,
                    'message' => '',
                ],
                'isValidOneyAmount' => [
                    'result' => true,
                    'message' => '',
                ],
                'isValidOneyAddresses' => [
                    'result' => true,
                    'message' => '',
                ],
                'getParentPaymentOption' => $parent_payment_option,
            ]
        );

        $this->configuration->shouldReceive('getValue')
            ->with('oney_optimized')
            ->andReturn(true);
        $this->configuration->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn(true);
        $this->tools_adapter->shouldReceive(
            [
                'tool' => 'FR',
            ]
        );

        $configClass = \Mockery::mock('configClass');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->assertSame(
            $parent_payment_option,
            $this->class->getPaymentOption($this->payment_options)
        );
    }

    public function testWhenCartCurrencyIsNotSupported()
    {
        $payment_options = ['standard' => ['name' => 'standard']];
        $this->context->currency->iso_code = 'USD';
        $this->class->shouldReceive('isValidOneyCartQty')
            ->andThrow(new \RuntimeException('Oney cart checks must not run for an unsupported currency'));

        $this->assertSame($payment_options, $this->class->getPaymentOption($payment_options));
        $this->logger->shouldNotHaveReceived('addLog', [\Mockery::any(), 'error']);
        $this->context->cart->shouldNotHaveReceived('getOrderTotal');
    }

    public function testWhenCartQtyIsNotValidTheErrorIsLogged()
    {
        $this->class->shouldReceive([
            'isValidOneyCartQty' => [
                'result' => false,
                'message' => 'error given',
            ],
        ]);

        $this->class->getPaymentOption([]);

        $this->logger->shouldHaveReceived('addLog')
            ->with('OneyPaymentMethod::getPaymentOption - error given', 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testWhenCartAddressesDoesNotMatchTheErrorIsLogged()
    {
        $this->class->shouldReceive([
            'isValidOneyCartQty' => ['result' => true, 'message' => ''],
            'isValidOneyAmount' => ['result' => true, 'message' => ''],
            'isValidOneyAddresses' => ['result' => false, 'message' => 'invalid addresses'],
        ]);

        $this->class->getPaymentOption([]);

        $this->logger->shouldHaveReceived('addLog')
            ->with('OneyPaymentMethod::getPaymentOption - invalid addresses', 'error')
            ->once();
        $this->addToAssertionCount(1);
    }
}
