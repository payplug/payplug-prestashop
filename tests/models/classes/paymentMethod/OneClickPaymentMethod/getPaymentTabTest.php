<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneClickPaymentMethod;

use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oneclick_payment_method_class
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
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => [],
        ]);
        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenPaymentTabIsReturned()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);
        $this->tools_adapter->shouldReceive([
            'tool' => true,
        ]);
        $this->card_repository->shouldReceive([
            'getEntity' => [
                'id_customer' => ContextMock::get()->customer->id,
                'id_card' => 'card_azerty12345',
            ],
        ]);

        $expected_tab = $this->default_payment_tab;
        $expected_tab['initiator'] = 'PAYER';
        $expected_tab['authorized_amount'] = $expected_tab['amount'];
        $expected_tab['payment_method'] = 'card_azerty12345';
        unset($expected_tab['amount']);

        $this->assertSame($expected_tab, $this->class->getPaymentTab());
    }
}
