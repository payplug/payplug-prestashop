<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 */
class getRefundableAmountTest extends BaseInstallmentPaymentMethod
{
    private $resource_id;

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

    public function testWhenResourceRetrievedHasNoSchedule()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => true,
                'resource' => PaymentMock::getInstallment(),
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
                'resource' => PaymentMock::getInstallment(),
                'schedule' => [
                    [
                        'amount' => 4242,
                        'date' => '1970-01-01',
                        'resource' => PaymentMock::getStandard(['is_paid' => true]),
                    ],
                ],
            ],
        ]);
        $this->assertSame(
            31320,
            $this->class->getRefundableAmount($this->resource_id)
        );
    }
}
