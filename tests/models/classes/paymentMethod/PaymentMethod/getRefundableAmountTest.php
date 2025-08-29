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
class getRefundableAmountTest extends BasePaymentMethod
{
    public $resource_id;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'inst_azerty1234';
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
            $this->class->getRefundableAmount($resource_id)
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
            $this->class->getRefundableAmount($this->resource_id)
        );
    }

    public function testWhenRefundableAmountIsReturned()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);
        $this->assertSame(
            31320,
            $this->class->getRefundableAmount($this->resource_id)
        );
    }
}
