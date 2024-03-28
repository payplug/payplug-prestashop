<?php

namespace PayPlug\tests\models\classes\paymentMethod\BancontactPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group bancontact_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentOptionTest extends BaseBancontactPaymentMethod
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
    public function testWhenGivenPaymentOptionsIsntValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenThereIsCountryRestrictionAndAddressDoesNotCorrespond()
    {
        $payment_options = [];
        $this->configuration
            ->shouldReceive('getValue')
            ->with('bancontact_country')
            ->andReturn(true);
        $this->validators['payment']
            ->shouldReceive([
                'isAllowedCountry' => [
                    'result' => false,
                ],
            ]);
        $this->assertSame([], $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenThereIsCountryRestrictionAndAddressCorrespond()
    {
        $payment_options = [];
        $this->configuration
            ->shouldReceive('getValue')
            ->with('bancontact_country')
            ->andReturn(true);
        $this->validators['payment']
            ->shouldReceive([
                'isAllowedCountry' => [
                    'result' => true,
                ],
            ]);
        $expected = [
            'bancontact' => [
                'name' => 'bancontact',
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
                        'value' => 'bancontact',
                    ],
                ],
                'extra_classes' => 'bancontact',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/svg/checkout/bancontact.svg',
                'callToActionText' => 'paymentmethods.bancontact.call_to_action',
                'action' => 'link',
                'moduleName' => 'payplug',
            ],
        ];
        $this->assertSame($expected, $this->classe->getPaymentOption($payment_options));
    }

    public function testWhenThereIsNoCountryRestriction()
    {
        $payment_options = [];
        $this->configuration
            ->shouldReceive('getValue')
            ->with('bancontact_country')
            ->andReturn(false);
        $expected = [
            'bancontact' => [
                'name' => 'bancontact',
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
                        'value' => 'bancontact',
                    ],
                ],
                'extra_classes' => 'bancontact',
                'payment_controller_url' => 'link',
                'logo' => 'modules/payplug/views/img/svg/checkout/bancontact.svg',
                'callToActionText' => 'paymentmethods.bancontact.call_to_action',
                'action' => 'link',
                'moduleName' => 'payplug',
            ],
        ];
        $this->assertSame($expected, $this->classe->getPaymentOption($payment_options));
    }
}
