<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 */
class getPaymentTabTest extends BaseApplepayPaymentMethod
{
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
            'tool' => 'shop domain ssl',
        ]);

        $expected_tab = $this->default_payment_tab;
        $expected_tab['payment_context'] = [
            'apple_pay' => [
                'domain_name' => 'my-mock.com',
                'application_data' => 'eyJhcHBsZV9wYXlfZG9tYWluIjoibXktbW9jay5jb20ifQ==',
            ],
        ];

        $this->assertSame(
            $expected_tab['payment_context'],
            $this->class->getPaymentTab()['payment_context']
        );
    }
}
