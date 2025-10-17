<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class getRefundedAmountTest extends BasePaymentMethod
{
    public $resource_id;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'pay_azerty1234';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenRetrieveIsntValidStringFormat($resource_id)
    {
        $this->assertSame(
            0,
            $this->class->getRefundedAmount($resource_id)
        );
    }

    public function testWhenResourceCantBeRetrieve()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            0,
            $this->class->getRefundedAmount($this->resource_id)
        );
    }

    public function testWhenRefundedAmountIsReturned()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['amount_refunded' => 4242]),
            ],
        ]);
        $this->assertSame(
            4242,
            $this->class->getRefundedAmount($this->resource_id)
        );
    }
}
