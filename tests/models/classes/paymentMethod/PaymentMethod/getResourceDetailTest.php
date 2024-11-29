<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_classe
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class getResourceDetailTest extends BasePaymentMethod
{
    private $resource_id;
    private $retrieve;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'pay_12345azerty';
        $this->retrieve = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsntValidString($resource_id)
    {
        $this->assertSame(
            [],
            $this->class->getResourceDetail($resource_id)
        );
    }

    public function testWhenResourceCantBeRetrieved()
    {
        $this->class->shouldReceive([
            'retrieve' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [],
            $this->class->getResourceDetail($this->resource_id)
        );
    }

    public function testWhenPaymentStatusCantBeGetted()
    {
        $this->class->shouldReceive([
            'retrieve' => $this->retrieve,
            'getPaymentStatus' => [],
        ]);
        $this->assertSame(
            [],
            $this->class->getResourceDetail($this->resource_id)
        );
    }

    public function testWhenResourceDetailIsGetted()
    {
        $this->class->shouldReceive([
            'retrieve' => $this->retrieve,
            'getPaymentStatus' => [
                'id_status' => 2,
                'code' => 'paid',
            ],
        ]);
        $expected = [
            'id' => 'pay_5ktNvd3BNCp6GPcqIZvY9j',
            'status' => 'order.detail.status.paid',
            'status_code' => 'paid',
            'status_class' => 'pp_success',
            'amount' => 313.2,
            'card_brand' => null,
            'card_mask' => null,
            'card_date' => null,
            'card_country' => null,
            'mode' => 'order.detail.mode.live',
            'paid' => false,
            'authorization' => false,
            'date' => '05/03/2021',
            'error' => '',
            'tds' => false,
            'type' => '',
            'type_code' => '',
            'refund' => [
                'refunded' => 0,
                'available' => 0,
                'is_refunded' => false,
            ],
            'currency' => 'EUR',
        ];
        $this->assertSame(
            $expected,
            $this->class->getResourceDetail($this->resource_id)
        );
    }
}
