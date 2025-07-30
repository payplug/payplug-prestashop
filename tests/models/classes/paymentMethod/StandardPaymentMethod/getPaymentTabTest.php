<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group standard_payment_method_class
 */
class getPaymentTabTest extends BaseStandardPaymentMethod
{
    private $expected_tab;

    public function setUp()
    {
        parent::setUp();

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
        $this->helpers['cookies']->shouldReceive([
            'setPaymentErrorsCookie' => true,
        ]);
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
            'formatPhoneNumber' => '0612345678',
            'getIsoFromLanguageCode' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $arg) {
                switch ($method) {
                    case 'getShopDomainSsl':
                        return true;

                    default:
                        if ('io' == $arg) {
                            return '2';
                        }

                        return '';
                }
            });

        $this->expected_tab = [
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
                'Website' => true,
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

        $this->oney = \Mockery::mock('StandardOldRepository');
        $this->plugin->shouldReceive([
            'getStandard' => $this->oney,
        ]);
    }

    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->class->set('name', '');
        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenDeferredPaymentIsAllowed()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": true, "one_click": false}');
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->expected_tab['authorized_amount'] = $this->expected_tab['amount'];
        unset($this->expected_tab['amount']);

        $this->assertSame(
            $this->expected_tab,
            $this->class->getPaymentTab()
        );
    }

    public function testWhenDisplayModeIsIntegrated()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": false, "one_click": false}');
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('integrated');

        $this->expected_tab['integration'] = 'INTEGRATED_PAYMENT';
        unset($this->expected_tab['hosted_payment']['cancel_url']);

        $this->assertSame(
            $this->expected_tab,
            $this->class->getPaymentTab()
        );
    }

    public function testWhenOneClickIsAllowed()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": false, "one_click": true}');
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');
        $this->cart_adapter->shouldReceive([
            'isGuestCartByCartId' => false,
        ]);
        $this->expected_tab['allow_save_card'] = true;

        $this->assertSame(
            $this->expected_tab,
            $this->class->getPaymentTab()
        );
    }
}
