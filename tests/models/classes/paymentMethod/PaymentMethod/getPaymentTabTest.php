<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentTabTest extends BasePaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->assertSame([], $this->class->getPaymentTab());
    }

    public function testWhenCartInContextIsntAValidObject()
    {
        $this->class->set('name', 'standard');
        $this->context->cart = null;
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertSame([], $this->class->getPaymentTab());
    }

    public function testWhenCurrencyInContextIsntAValidObject()
    {
        $this->class->set('name', 'standard');
        $this->context->currency = null;
        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->assertSame([], $this->class->getPaymentTab());
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
        $this->assertSame([], $this->class->getPaymentTab());
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
        $this->assertSame([], $this->class->getPaymentTab());
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
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
            'formatPhoneNumber' => '0612345678',
            'getIsoFromLanguageCode' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter->shouldReceive('tool')
            ->with('getShopDomainSsl', true, false)
            ->andReturn('shop domain ssl');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'hfToken')
            ->andReturn('');

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

        $this->assertSame($expected_tab, $this->class->getPaymentTab());
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
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
            'formatPhoneNumber' => '0612345678',
            'getIsoFromLanguageCode' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter->shouldReceive('tool')
            ->with('getShopDomainSsl', true, false)
            ->andReturn('shop domain ssl');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'hfToken')
            ->andReturn('');

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

        $this->assertSame($expected_tab, $this->class->getPaymentTab());
    }

    public function testWhenHostedFieldsPaymentTabIsReturn()
    {
        $this->class->set('name', 'standard');
        $this->validate_adapter->shouldReceive('validate')->andReturn(true);
        $this->configuration->shouldReceive('getValue')->with('currencies')->andReturn('EUR');
        $this->helpers['amount']->shouldReceive([
            'validateAmount' => ['result' => true],
            'convertAmount' => 4242,
        ]);

        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('USD');
        $config_class = \Mockery::mock('ConfigClass');

        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
            'formatPhoneNumber' => '0612345678',
            'getIsoFromLanguageCode' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter->shouldReceive('tool')->with('getShopDomainSsl', true, false)->andReturn('shop domain ssl');
        $this->tools_adapter->shouldReceive('tool')->with('getValue', 'hfToken')->andReturn('hf token value');
        $this->tools_adapter->shouldReceive('tool')->with('getValue', 'save_card')->andReturn('0');
        $this->configuration->shouldReceive('getValue')->with('multi_account')->andReturn(json_encode([
            'identifier_eur' => 'IDENTIFIER',
            'api_key_id' => 'APIKEYID',
            'api_key' => 'APIKEY',
        ]));
        $result = $this->class->getPaymentTab();
        $expected_tab = [
            'method' => 'payment',
            'params' => [
                'IDENTIFIER' => 'IDENTIFIER',
                'OPERATIONTYPE' => 'payment',
                'AMOUNT' => 4242,
                'VERSION' => '3.0',
                'CARDFULLNAME' => 'Ipsum Lorem',
                'CLIENTIDENT' => 'IpsumLorem',
                'CLIENTEMAIL' => 'customer@payplug.com',
                'CLIENTREFERRER' => 'shop domain ssl',
                'CLIENTUSERAGENT' => 'Unknown',
                'CLIENTIP' => 'Unknown',
                'ORDERID' => 1,
                'DESCRIPTION' => 'N.a.',
                'CREATEALIAS' => 'no',
                'APIKEYID' => 'APIKEYID',
                'HFTOKEN' => 'hf token value',
                'BILLINGADDRESS' => '1 rue de l\'avenue',
                'BILLINGPOSTALCODE' => '75000',
                'BILLINGCOUNTRY' => 'fr',
                'MOBILEPHONE' => '0612345678',
                'SHIPTOADDRESS' => '1 rue de l\'avenue',
                'SHIPTOPOSTALCODE' => '75000',
                'SHIPTOCOUNTRY' => 'fr',
            ],
        ];
        $hash = $this->class->buildHashContent($expected_tab['params'], false);
        $expected_tab['params']['HASH'] = $hash;
        $this->assertSame($expected_tab, $result);
    }
}
