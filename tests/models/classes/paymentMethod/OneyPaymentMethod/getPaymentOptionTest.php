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

        $this->oney = \Mockery::mock('OneyOldRepository');

        $cart_adapter = \Mockery::mock('CardAdapter');
        $cart_adapter->shouldReceive([
            'isGuestCartByCartId' => false,
        ]);
        $cart_adapter->shouldReceive([
            'nbProducts' => 1001,
        ]);
        $this->plugin
            ->shouldReceive([
                'getCart' => $cart_adapter,
            ]);

        $this->validate_adapter
            ->shouldReceive([
                'validate' => false,
            ]);

        $country_adapter = \Mockery::mock('CountryAdapter');
        $currency_adapter = \Mockery::mock('CurrencyAdapter');
        $currency_adapter->shouldReceive([
            'get' => 1,
        ]);

        $this->plugin->shouldReceive([
            'getCountry' => $country_adapter,
            'getCurrency' => $currency_adapter,
        ]);

        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);

        $this->dependencies->amountCurrencyClass = \Mockery::mock('alias:PayPlug\classes\AmountCurrencyClass');
        $this->dependencies->amountCurrencyClass
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $to_cents = false) {
                if ($to_cents) {
                    return (float) ($amount / 100);
                }
                $amount = (float) ($amount * 1000);
                $amount = (float) ($amount / 10);

                return (int) ($this->toolsAdapter->tool('ps_round', $amount));
            })
        ;
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
        $configuration
            ->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');

        $this->oney->shouldReceive([
            'getOperations' => [
                'x3_with_fees',
                'x3_without_fees',
                'x4_with_fees',
                'x4_without_fees',
            ],
            'isValidOneyCart' => [
                'result' => false,
                'error' => true,
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn(['PL']);

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
                'getOney' => $this->oney,
                'getOneyPriceLimit' => [
                    'min' => 100,
                    'max' => 3000,
                ],
            ]);

        $this->validators['payment']
            ->shouldReceive([
                'isOneyElligible' => [
                    'error_type' => 'invalid_cart',
                    'error' => 'An error occured',
                ],
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
        $configuration
            ->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');

        $this->oney->shouldReceive([
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn(['FR', 'IT', 'ES', 'NL']);

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
                'getOney' => $this->oney,
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
        $configuration
            ->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');

        $this->oney->shouldReceive([
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn(['FR', 'IT', 'ES', 'NL']);

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
                'getOney' => $this->oney,
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
