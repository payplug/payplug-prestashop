<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneClickPaymentMethod;

use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oneclick_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentTabTest extends BaseOneClickPaymentMethod
{
    public function setUp()
    {
        parent::setUp();
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": true, "one_click": true, "installment": true}');
    }

    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->class->set('name', '');
        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenPaymentTabIsReturned()
    {
        $this->class->set('name', 'one_click');
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

        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'pc', 'new_card')
            ->andReturn('some_expected_value');

        $this->card_repository->shouldReceive([
            'getEntity' => [
                'id_customer' => ContextMock::get()->customer->id,
                'id_card' => 'card_azerty12345',
            ],
        ]);

        $expected_tab = [
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
            'initiator' => 'PAYER',
            'authorized_amount' => 4242,
            'payment_method' => 'card_azerty12345',
        ];

        $this->assertSame($expected_tab, $this->class->getPaymentTab());
    }
}
