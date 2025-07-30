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

    public function setUp()
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
        $parent_payment_option = [
            $name => [
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
            ],
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

        $expected = [
            'oney_x3_with_fees' => [
                'name' => 'oney',
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
                        'value' => 'oney',
                    ],
                    'oney_type' => [
                        'name' => 'payplugOney_type',
                        'type' => 'hidden',
                        'value' => 'x3_with_fees',
                    ],
                ],
                'extra_classes' => 'oney3x',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/oney/x3_with_fees.svg',
                'callToActionText' => 'payplug.getPaymentOptions.payWithOney',
                'action' => 'action_url',
                'moduleName' => 'payplug',
                'is_optimized' => true,
                'type' => 'x3_with_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
            ],
            'oney_x4_with_fees' => [
                'name' => 'oney',
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
                        'value' => 'oney',
                    ],
                    'oney_type' => [
                        'name' => 'payplugOney_type',
                        'type' => 'hidden',
                        'value' => 'x4_with_fees',
                    ],
                ],
                'extra_classes' => 'oney4x',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/oney/x4_with_fees.svg',
                'callToActionText' => 'payplug.getPaymentOptions.payWithOney',
                'action' => 'action_url',
                'moduleName' => 'payplug',
                'is_optimized' => true,
                'type' => 'x4_with_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
            ],
        ];

        $this->assertSame(
            $expected,
            $this->class->getPaymentOption($this->payment_options)
        );
    }
}
