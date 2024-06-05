<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group oney_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getPaymentTabTest extends BaseOneyPaymentMethod
{
    private $expected_tab;
    private $oney;

    public function setUp()
    {
        parent::setUp();

        $this->classe->set('name', 'oney');
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
        $this->helpers['cookies']
            ->shouldReceive([
                'setPaymentErrorsCookie' => true,
            ]);
        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive([
            'getIsoCodeByCountryId' => 'fr',
            'formatPhoneNumber' => '0612345678',
            'getIsoFromLanguageCode' => 'fr',
        ]);
        $this->dependencies->configClass = $config_class;
        $this->tools_adapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $arg) {
                switch ($method) {
                    case 'getShopDomainSsl':
                        return true;
                    default:
                        if ('payplugOney_type' == $arg) {
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

        $this->oney = \Mockery::mock('OneyOldRepository');
        $this->plugin->shouldReceive([
            'getOney' => $this->oney,
        ]);
    }

    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->classe->set('name', '');
        $this->assertSame(
            [],
            $this->classe->getPaymentTab()
        );
    }

    public function testWhenCurrentCartIsntElligible()
    {
        $this->classe
            ->shouldReceive([
                                'getOneyPriceLimit' => [
                                    'min' => 100,
                                    'max' => 3000,
                                ],
                            ]);

        $this->validators['payment']
            ->shouldReceive([
                'isOneyElligible' => [
                    'result' => false,
                    'code' => 'product_quantity',
                ],
            ]);

        $this->cart_adapter->shouldReceive([
            'isGuestCartByCartId' => false,
        ]);
        $this->cart_adapter->shouldReceive([
            'nbProducts' => 1001,
        ]);
        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');
        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');
        $this->currency_adapter->shouldReceive([
                                                   'get' => 1,
                                               ]);
        $this->assertSame(
            [],
            $this->classe->getPaymentTab()
        );
    }

    public function testWhenRequiredFieldsIsNeeded()
    {
        $this->classe
            ->shouldReceive([
                'hasOneyRequiredFields' => true,
                'getOneyPriceLimit' => [
                    'min' => 100,
                    'max' => 3000,
                ],
            ]);

        $this->validators['payment']
            ->shouldReceive([
                'isOneyElligible' => [
                    'result' => true,
                ],
            ]);
        $this->helpers['phone']
            ->shouldReceive([
                'isPhoneNumber' => [
                    'result' => true,
                ],
            ]);
        $this->helpers['cookies']
            ->shouldReceive([
                'getPaymentDataCookie' => [],
            ]);

        $this->cart_adapter->shouldReceive([
            'isGuestCartByCartId' => false,
        ]);
        $this->cart_adapter->shouldReceive([
            'nbProducts' => 1001,
        ]);
        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');
        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');

        $this->currency_adapter->shouldReceive([
                                                   'get' => 1,
                                               ]);
        $this->assertSame(
            [],
            $this->classe->getPaymentTab()
        );
    }

    public function testWhenPaymentTabIsReturned()
    {
        $this->classe
            ->shouldReceive([
                'hasOneyRequiredFields' => false,
                'getOneyPaymentContext' => [],
                                'getOneyPriceLimit' => [
                                    'min' => 100,
                                    'max' => 3000,
                                ],
            ]);

        $this->validators['payment']
            ->shouldReceive([
                'isOneyElligible' => [
                    'result' => true,
                ],
            ]);
        $this->helpers['phone']
            ->shouldReceive([
                'isPhoneNumber' => [
                    'result' => true,
                ],
            ]);
        $oney_schedule = 2;
        $this->tools_adapter
            ->shouldReceive('tool')
            ->with('payplugOney_type')
            ->andReturn($oney_schedule);

        $this->expected_tab['authorized_amount'] = $this->expected_tab['amount'];
        $this->expected_tab['auto_capture'] = true;
        $this->expected_tab['payment_method'] = 'oney_' . $oney_schedule;
        $this->expected_tab['payment_context'] = [];

        unset($this->expected_tab['allow_save_card'], $this->expected_tab['amount']);

        $this->cart_adapter->shouldReceive([
            'isGuestCartByCartId' => false,
        ]);
        $this->cart_adapter->shouldReceive([
            'nbProducts' => 1001,
        ]);
        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');
        $this->currency_adapter->shouldReceive([
                                                   'get' => 1,
                                               ]);

        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_TAX')
            ->andReturn('1');
        $this->assertSame(
            $this->expected_tab,
            $this->classe->getPaymentTab()
        );
    }
}
