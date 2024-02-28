<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionTest extends BaseApplepayPaymentMethod
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

    public function testWhenCurrentBrowserIsInvalid()
    {
        $payment_options = [];
        $browser = \Mockery::mock('Browser');
        $browser->shouldReceive([
            'getName' => 'browser',
        ]);
        $this->plugin->shouldReceive([
            'getBrowser' => $browser,
        ]);
        $this->validators['browser']->shouldReceive([
            'isApplePayCompatible' => [
                'result' => false,
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');

        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenCurrentBrowserIsValid()
    {
        $payment_options = [];
        $browser = \Mockery::mock('Browser');
        $browser->shouldReceive([
            'getName' => 'browser',
        ]);
        $this->plugin->shouldReceive([
            'getBrowser' => $browser,
        ]);
        $this->validators['browser']->shouldReceive([
            'isApplePayCompatible' => [
                'result' => true,
            ],
        ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'fetchTemplate' => 'template.tpl',
        ]);
        $this->dependencies->configClass = $configClass;

        $expected = [
            'applepay' => [
                'name' => 'applepay',
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
                        'value' => 'applepay',
                    ],
                ],
                'extra_classes' => 'applepay',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/svg/checkout/applepay.svg',
                'callToActionText' => 'paymentmethods.applepay.call_to_action',
                'action' => 'javascript:void(0)',
                'moduleName' => 'payplug',
                'additionalInformation' => 'template.tpl',
            ],
        ];

        $this->assertSame($expected, $this->classe->getPaymentOption($payment_options));
    }
}
