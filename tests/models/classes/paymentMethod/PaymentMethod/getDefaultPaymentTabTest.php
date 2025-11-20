<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getDefaultPaymentTabTest extends BasePaymentMethod
{
    public function testWhenCartInContextIsntAValidObject()
    {
        $this->class->set('name', 'standard');
        $this->context->cart = null;
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertSame([], $this->class->getDefaultPaymentTab());
    }

    public function testWhenCurrencyInContextIsntAValidObject()
    {
        $this->class->set('name', 'standard');
        $this->context->currency = null;
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertSame([], $this->class->getDefaultPaymentTab());
    }

    public function testWhenCurrentCurrencyIsoCodeIsntSupported()
    {
        $this->class->set('name', 'standard');
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('USD');
        $this->assertSame([], $this->class->getDefaultPaymentTab());
    }

    public function testWhenCartIsntValidAmount()
    {
        $this->class->set('name', 'standard');
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturn([
                'result' => false,
            ]);
        $this->assertSame([], $this->class->getDefaultPaymentTab());
    }

    public function testWhenPaymentTabIsReturn()
    {
        $this->class->set('name', 'standard');
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']->shouldReceive([
            'validateAmount' => [
                'result' => true,
            ],
            'convertAmount' => 4242,
        ]);
        $this->phone_number_service->shouldReceive([
            'formatPhoneNumber' => '0612345678',
        ]);
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter->shouldReceive([
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

        $this->assertSame($expected_tab, $this->class->getDefaultPaymentTab());
    }

    public function testWhenPaymentTabIsReturnWithoutAddresses()
    {
        $this->class->set('name', 'standard');
        $this->context->customer = null;
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']->shouldReceive([
            'validateAmount' => [
                'result' => true,
            ],
            'convertAmount' => 4242,
        ]);

        $this->phone_number_service->shouldReceive([
            'formatPhoneNumber' => '0612345678',
        ]);
        $this->tools_adapter->shouldReceive([
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

        $this->assertSame($expected_tab, $this->class->getDefaultPaymentTab());
    }
}
