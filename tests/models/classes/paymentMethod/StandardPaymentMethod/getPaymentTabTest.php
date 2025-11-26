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
    public $expected_tab;

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

        $this->phone_number_service->shouldReceive([
            'formatPhoneNumber' => '0612345678',
        ]);
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

        $this->oney = \Mockery::mock('StandardOldRepository');
        $this->plugin->shouldReceive([
            'getStandard' => $this->oney,
        ]);
    }

    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => [],
        ]);
        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenDeferredPaymentIsAllowed()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": true, "one_click": false}');
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $expected_tab = $this->default_payment_tab;
        $expected_tab['authorized_amount'] = $expected_tab['amount'];
        unset($expected_tab['amount']);

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }

    public function testWhenDisplayModeIsIntegrated()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": false, "one_click": false}');
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('integrated');

        $expected_tab = $this->default_payment_tab;
        $expected_tab['integration'] = 'INTEGRATED_PAYMENT';
        unset($expected_tab['hosted_payment']['cancel_url']);

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }

    public function testWhenOneClickIsAllowed()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true, "deferred": false, "one_click": true}');
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');
        $this->cart_adapter->shouldReceive([
            'isGuestCartByCartId' => false,
        ]);

        $expected_tab = $this->default_payment_tab;
        $expected_tab['allow_save_card'] = true;

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }
}
