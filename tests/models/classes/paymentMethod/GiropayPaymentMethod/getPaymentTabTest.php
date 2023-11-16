<?php

namespace PayPlug\tests\models\classes\paymentMethod\GiropayPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentTabTest extends BaseGiropayPaymentMethod
{
    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->classe->set('name', '');
        $this->assertSame(
            [],
            $this->classe->getPaymentTab()
        );
    }

    public function testWhenPaymentTabIsReturned()
    {
        $this->classe->set('name', 'giropay');
        $this->validate_adapter
            ->shouldReceive([
                'validate' => true,
            ]);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->tools_adapter
            ->shouldReceive([
                'tool' => 'shop domain ssl',
            ]);
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

        $expected_tab = [
            'amount' => 4242,
            'currency' => 'EUR',
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
            'payment_method' => 'giropay',
        ];

        $this->assertSame(
            $expected_tab,
            $this->classe->getPaymentTab()
        );
    }
}
