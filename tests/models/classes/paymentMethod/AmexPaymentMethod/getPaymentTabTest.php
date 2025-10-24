<?php

namespace PayPlug\tests\models\classes\paymentMethod\AmexPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group amex_payment_method_classe
 */
class getPaymentTabTest extends BaseAmexPaymentMethod
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
        $this->class->set('name', 'amex');
        $this->validate_adapter->shouldReceive([
            'validate' => true,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->tools_adapter->shouldReceive([
            'tool' => 'shop domain ssl',
        ]);
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
            'payment_method' => 'american_express',
        ];

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }
}
