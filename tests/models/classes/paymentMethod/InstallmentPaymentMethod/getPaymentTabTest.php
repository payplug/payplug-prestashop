<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 */
class getPaymentTabTest extends BaseInstallmentPaymentMethod
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
        $this->configuration->shouldReceive('getValue')
            ->with('inst_mode')
            ->andReturn('2');
        $expected_tab = $this->default_payment_tab;
        $expected_tab['schedule'] = [
            [
                'date' => 'TODAY',
                'amount' => 2121,
            ],
            [
                'date' => date('Y-m-d', strtotime('+ 30 days')),
                'amount' => 2121,
            ],
        ];

        $this->assertSame(
            $expected_tab['schedule'],
            $this->class->getPaymentTab()['schedule']
        );
    }
}
