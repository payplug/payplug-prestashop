<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionTest extends BaseInstallmentPaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function testWhenGivenPaymentOptionsIsntValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenAmountIsTooLow()
    {
        $payment_options = [];

        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');

        $this->configuration
            ->shouldReceive('getValue')
            ->with('inst_min_amount')
            ->andReturn('150');

        $this->plugin->shouldReceive([
            'getConfiguration' => $this->configuration,
        ]);

        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenInstallmentPaymentMethodIsDisplayed()
    {
        $payment_options = [];

        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');

        $this->configuration
            ->shouldReceive('getValue')
            ->with('inst_min_amount')
            ->andReturn('30');

        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');

        $this->configuration
            ->shouldReceive('getValue')
            ->with('inst_mode')
            ->andReturn('3');

        $this->plugin->shouldReceive([
            'getConfiguration' => $this->configuration,
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getImgLang' => 'fr',
        ]);
        $this->dependencies->configClass = $configClass;

        $expected = [
            'installment' => [
                'name' => 'installment',
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
                        'value' => 'installment',
                    ],
                ],
                'extra_classes' => 'installment',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/svg/checkout/installment/logos_schemes_installment_3_fr.png',
                'callToActionText' => 'paymentmethods.installment.call_to_action',
                'action' => 'link',
                'moduleName' => 'payplug',
            ],
        ];

        $this->assertSame($expected, $this->classe->getPaymentOption($payment_options));
    }
}
