<?php

namespace PayPlug\tests\models\classes\paymentMethod\MybankPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group mybank_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentTabTest extends BaseMybankPaymentMethod
{
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
        $this->class->set('name', 'mybank');
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getShopDomainSsl', true, false)
            ->andReturn('shop domain ssl');
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'hfToken')
            ->andReturn('');
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

        $expected_tab = [
            'amount' => 4242,
            'currency' => 'EUR',
            'notification_url' => 'link',
            'hosted_payment' => [
                'return_url' => 'link',
                'cancel_url' => 'link',
            ],
            'metadata' => [
                'ID Client' => 1,
                'ID Cart' => 1,
                'Website' => 'shop domain ssl',
            ],
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
            'payment_method' => 'mybank',
        ];

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }
}
