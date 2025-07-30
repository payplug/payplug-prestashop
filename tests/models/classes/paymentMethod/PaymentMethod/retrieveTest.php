<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 */
class retrieveTest extends BasePaymentMethod
{
    private $stored_payment;

    public function setUp()
    {
        parent::setUp();
        $this->stored_payment = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'is_live' => true,
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsInvalidStringFormat($resource_id)
    {
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Invalid argument, $resource_id must be a non empty string.',
            ],
            $this->class->retrieve($resource_id)
        );
    }

    public function testWhenStoredPaymentCantBeFoundInDataBase()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame(
            [
                'code' => 500,
                'result' => false,
                'message' => 'Can\'t find stored payment from given resource id',
            ],
            $this->class->retrieve($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceCantBeRetrieved()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $retrieve_failed = [
            'result' => false,
            'code' => 500,
            'message' => 'An error occured during resource retrieve',
        ];

        $this->api_service->shouldReceive([
            'initialize' => true,
            'retrievePayment' => $retrieve_failed,
        ]);
        $this->assertSame(
            $retrieve_failed,
            $this->class->retrieve($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceIsRetrievedOnSecondTry()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $retrieve_failed = [
            'result' => false,
            'code' => 500,
            'message' => 'An error occured during resource retrieve',
        ];
        $retrieve_success = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getStandard(),
        ];

        $this->api_service
            ->shouldReceive('retrievePayment')
            ->once()
            ->andReturn($retrieve_failed);

        $this->api_service->shouldReceive([
            'initialize' => true,
            'retrievePayment' => $retrieve_success,
        ]);
        $this->assertSame(
            $retrieve_success,
            $this->class->retrieve($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceIsRetrieved()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $retrieve_success = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getStandard(),
        ];

        $this->api_service->shouldReceive([
            'initialize' => true,
            'retrievePayment' => $retrieve_success,
        ]);
        $this->assertSame(
            $retrieve_success,
            $this->class->retrieve($this->stored_payment['resource_id'])
        );
    }
}
