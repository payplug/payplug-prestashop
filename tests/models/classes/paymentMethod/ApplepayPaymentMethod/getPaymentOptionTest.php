<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
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
        $this->assertSame([], $this->class->getPaymentOption($payment_options));
    }

    public function testWhenPaymentOptionIsGetted()
    {
        $payment_options = [];
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{}');
        $this->configuration->shouldReceive('getValue')
            ->with('applepay_display')
            ->andReturn('{"checkout":true,"cart":false,"product":false}');

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

        $this->assertSame($expected, $this->class->getPaymentOption($payment_options));
    }
}
