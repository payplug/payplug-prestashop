<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentTabTest extends BasePaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->assertSame([], $this->classe->getPaymentTab());
    }

    public function testWhenCartInContextIsntAValidObject()
    {
        $this->classe->set('name', 'standard');
        $this->context->cart = null;
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertSame([], $this->classe->getPaymentTab());
    }

    public function testWhenCurrencyInContextIsntAValidObject()
    {
        $this->classe->set('name', 'standard');
        $this->context->currency = null;
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertSame([], $this->classe->getPaymentTab());
    }

    public function testWhenCurrentCurrencyIsoCodeIsntSupported()
    {
        $this->classe->set('name', 'standard');
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration
            ->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('USD');
        $this->assertSame([], $this->classe->getPaymentTab());
    }

    public function testWhenCartIsntValidAmount()
    {
        $this->classe->set('name', 'standard');
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration
            ->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']
            ->shouldReceive('validateAmount')
            ->andReturn([
                'result' => false,
            ]);
        $this->assertSame([], $this->classe->getPaymentTab());
    }

    public function testWhenPaymentTabIsReturn()
    {
        $this->classe->set('name', 'standard');
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration
            ->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']
            ->shouldReceive([
                'validateAmount' => [
                    'result' => true,
                ],
                'convertAmount' => 4242,
            ]);
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
            'formatPhoneNumber' => '0612345678',
            'getIsoFromLanguageCode' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter
            ->shouldReceive([
                'tool' => 'shop domain ssl',
            ]);

        $expected_tab = [
            'amount' => 4242,
            'currency' => 'EUR',
            'notification_url' => 'link',
            'force_3ds' => false,
            'hosted_payment' => [
                'return_url' => 'link',
                'cancel_url' => 'link',
            ],
            'metadata' => [
                'ID Client' => 1,
                'ID Cart' => 1,
                'Website' => 'shop domain ssl',
            ],
            'allow_save_card' => false,
            'shipping' => [
                'title' => null,
                'first_name' => 'Ipsum',
                'last_name' => 'Lorem',
                'company_name' => 'Payplug',
                'email' => 'customer@payplug.com',
                'landline_phone_number' => '0612345678',
                'mobile_phone_number' => '0612345678',
                'address1' => '1 rue de l\'avenue',
                'address2' => null,
                'postcode' => '75000',
                'city' => 'Paris',
                'country' => 'fr',
                'language' => 'fr',
                'delivery_type' => 'BILLING',
            ],
            'billing' => [
                'title' => null,
                'first_name' => 'Ipsum',
                'last_name' => 'Lorem',
                'company_name' => 'Payplug',
                'email' => 'customer@payplug.com',
                'landline_phone_number' => '0612345678',
                'mobile_phone_number' => '0612345678',
                'address1' => '1 rue de l\'avenue',
                'address2' => null,
                'postcode' => '75000',
                'city' => 'Paris',
                'country' => 'fr',
                'language' => 'fr',
            ],
        ];

        $this->assertSame($expected_tab, $this->classe->getPaymentTab());
    }

    public function testWhenPaymentTabIsReturnWithoutAddresses()
    {
        $this->classe->set('name', 'standard');
        $this->context->customer = null;
        $this->validate_adapter
            ->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration
            ->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']
            ->shouldReceive([
                'validateAmount' => [
                    'result' => true,
                ],
                'convertAmount' => 4242,
            ]);
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
            'formatPhoneNumber' => '0612345678',
            'getIsoFromLanguageCode' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter
            ->shouldReceive([
                'tool' => 'shop domain ssl',
            ]);

        $expected_tab = [
            'amount' => 4242,
            'currency' => 'EUR',
            'notification_url' => 'link',
            'force_3ds' => false,
            'hosted_payment' => [
                'return_url' => 'link',
                'cancel_url' => 'link',
            ],
            'metadata' => [
                'ID Client' => 0,
                'ID Cart' => 1,
                'Website' => 'shop domain ssl',
            ],
            'allow_save_card' => false,
        ];

        $this->assertSame($expected_tab, $this->classe->getPaymentTab());
    }
}
