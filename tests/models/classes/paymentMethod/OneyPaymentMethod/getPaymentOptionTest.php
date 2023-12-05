<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionTest extends BaseOneyPaymentMethod
{
    public function setUp()
    {
        parent::setUp();
        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');
        $this->helpers['amount']
            ->shouldReceive('validateAmount')
            ->andReturn([
                'result' => true,
                'message' => '',
            ]);
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function testWhenGivenPaymentOptionsIsNotValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenCurrentCartIsNotElligible()
    {
        $payment_options = [];

        $configuration = \Mockery::mock('Configuration');
        $configuration
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');

        $oney = \Mockery::mock('Oney');
        $oney->shouldReceive([
            'isOneyElligible' => [
                'result' => false,
                'error_type' => 'invalid_cart',
                'error' => 'An error occured',
            ],
            'getOperations' => [
                'x3_with_fees',
                'x3_without_fees',
                'x4_with_fees',
                'x4_without_fees',
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_optimized')
            ->andReturn(false);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn(true);

        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $iso) {
                if ('strtoupper' == $method) {
                    return strtoupper($iso);
                }
            });

        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $configuration,
                'getOney' => $oney,
            ]);

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
                        'value' => 1,
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
                'logo' => 'modules/payplug/views/img/oney/x3_with_fees_alt.svg',
                'callToActionText' => 'payplug.getPaymentOptions.invalidCart',
                'action' => 'link',
                'moduleName' => 'payplug',
                'is_optimized' => false,
                'type' => 'x3_with_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
                'err_label' => 'payplug.getPaymentOptions.invalidCart',
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
                        'value' => 1,
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
                'logo' => 'modules/payplug/views/img/oney/x4_with_fees_alt.svg',
                'callToActionText' => 'payplug.getPaymentOptions.invalidCart',
                'action' => 'link',
                'moduleName' => 'payplug',
                'is_optimized' => false,
                'type' => 'x4_with_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
                'err_label' => 'payplug.getPaymentOptions.invalidCart',
            ],
        ];

        $this->assertSame($expected, $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenCurrentCartIsElligibleAndWithoutFees()
    {
        $payment_options = [];

        $configuration = \Mockery::mock('Configuration');
        $configuration
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');

        $oney = \Mockery::mock('Oney');
        $oney->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
                'error' => '',
            ],
            'getOperations' => [
                'x3_with_fees',
                'x3_without_fees',
                'x4_with_fees',
                'x4_without_fees',
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_optimized')
            ->andReturn(false);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn(false);

        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $iso) {
                if ('strtoupper' == $method) {
                    return strtoupper($iso);
                }
            });

        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $configuration,
                'getOney' => $oney,
            ]);

        $expected = [
            'oney_x3_without_fees' => [
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
                        'value' => 1,
                    ],
                    'method' => [
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'oney',
                    ],
                    'oney_type' => [
                        'name' => 'payplugOney_type',
                        'type' => 'hidden',
                        'value' => 'x3_without_fees',
                    ],
                ],
                'extra_classes' => 'oney3x',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/oney/x3_without_fees_side_FR.svg',
                'callToActionText' => 'payplug.getPaymentOptions.payWithOneyWithout',
                'action' => 'link',
                'moduleName' => 'payplug',
                'is_optimized' => false,
                'type' => 'x3_without_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
                'err_label' => 'payplug.getPaymentOptions.errorOccurred',
            ],
            'oney_x4_without_fees' => [
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
                        'value' => 1,
                    ],
                    'method' => [
                        'name' => 'method',
                        'type' => 'hidden',
                        'value' => 'oney',
                    ],
                    'oney_type' => [
                        'name' => 'payplugOney_type',
                        'type' => 'hidden',
                        'value' => 'x4_without_fees',
                    ],
                ],
                'extra_classes' => 'oney4x',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/oney/x4_without_fees_side_FR.svg',
                'callToActionText' => 'payplug.getPaymentOptions.payWithOneyWithout',
                'action' => 'link',
                'moduleName' => 'payplug',
                'is_optimized' => false,
                'type' => 'x4_without_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
                'err_label' => 'payplug.getPaymentOptions.errorOccurred',
            ],
        ];

        $this->assertSame($expected, $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenCurrentCartIsElligibleAndWithFees()
    {
        $payment_options = [];

        $configuration = \Mockery::mock('Configuration');
        $configuration
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');

        $oney = \Mockery::mock('Oney');
        $oney->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
                'error' => '',
            ],
            'getOperations' => [
                'x3_with_fees',
                'x3_without_fees',
                'x4_with_fees',
                'x4_without_fees',
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_optimized')
            ->andReturn(false);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_fees')
            ->andReturn(true);

        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $iso) {
                if ('strtoupper' == $method) {
                    return strtoupper($iso);
                }
            });

        $this->plugin
            ->shouldReceive([
                'getConfiguration' => $configuration,
                'getOney' => $oney,
            ]);

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
                        'value' => 1,
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
                'action' => 'link',
                'moduleName' => 'payplug',
                'is_optimized' => false,
                'type' => 'x3_with_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
                'err_label' => 'payplug.getPaymentOptions.errorOccurred',
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
                        'value' => 1,
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
                'action' => 'link',
                'moduleName' => 'payplug',
                'is_optimized' => false,
                'type' => 'x4_with_fees',
                'amount' => 42.42,
                'iso_code' => 'FR',
                'err_label' => 'payplug.getPaymentOptions.errorOccurred',
            ],
        ];

        $this->assertSame(
            $expected,
            $this->classe->getPaymentOption($payment_options)
        );
    }
}
