<?php

namespace PayPlug\tests\models\classes\paymentMethod\BancontactPaymentMethod;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group bancontact_payment_method_class
 */
class getPaymentOptionTest extends BaseBancontactPaymentMethod
{
    use MockeryPHPUnitIntegration;

    protected $config_class;

    public function setUp(): void
    {
        parent::setUp();

        $this->context->cart->id_address_delivery = 43;
        $this->context->cart->id_address_invoice = 42;
        $this->config_class = \Mockery::mock('Config');
        $this->dependencies->configClass = $this->config_class;
        $this->configuration->shouldNotReceive('getValue')
            ->with('bancontact_country');
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_options
     */
    public function testWhenGivenPaymentOptionsIsntValidArrayFormat($payment_options)
    {
        $this->assertSame([], $this->class->getPaymentOption($payment_options));
    }

    public function testBancontactIsShownForABelgianBillingAddressWhenOnlyBelgiumIsSelected()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"bancontact":{"countries":["BE"]}}');
        $this->config_class->shouldReceive('getIsoCodeByCountryId')
            ->once()
            ->andReturn('BE');

        $this->assertSame($this->getExpectedOption(), $this->class->getPaymentOption([]));
    }

    public function testBancontactIsHiddenForANonBelgianBillingAddressWhenOnlyBelgiumIsSelected()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"bancontact":{"countries":["BE"]}}');
        $this->config_class->shouldReceive('getIsoCodeByCountryId')
            ->once()
            ->andReturn('FR');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    public function testBancontactIsShownForAnyBillingCountryWithoutRestriction()
    {
        $this->config_class->shouldReceive('getIsoCodeByCountryId')
            ->once()
            ->andReturn('FR');

        $this->assertSame($this->getExpectedOption(), $this->class->getPaymentOption([]));
    }

    public function testBancontactIsHiddenWhenTheBillingCountryIsNotAllowedByTheApi()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn('{"bancontact":["BE","NL"]}');
        $this->config_class->shouldReceive('getIsoCodeByCountryId')
            ->once()
            ->andReturn('FR');

        $this->assertSame([], $this->class->getPaymentOption([]));
    }

    /**
     * @return array
     */
    private function getExpectedOption()
    {
        return [
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
    }
}
