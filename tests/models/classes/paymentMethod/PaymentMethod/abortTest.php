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
class abortTest extends BasePaymentMethod
{
    public $stored_payment;

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
            $this->class->abort($resource_id)
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
            $this->class->abort($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceCantBeAborted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $abort_failed = [
            'result' => false,
            'code' => 500,
            'message' => 'An error occured during resource abortion',
        ];

        $this->api_service->shouldReceive([
            'initialize' => true,
            'abortPayment' => $abort_failed,
        ]);
        $this->assertSame(
            $abort_failed,
            $this->class->abort($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceIsAbortedOnSecondTry()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $abort_failed = [
            'result' => false,
            'code' => 500,
            'message' => 'An error occured during resource abortion',
        ];
        $abort_success = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getStandard(),
        ];

        $this->api_service
            ->shouldReceive('abortPayment')
            ->once()
            ->andReturn($abort_failed);

        $this->api_service->shouldReceive([
            'initialize' => true,
            'abortPayment' => $abort_success,
        ]);
        $this->assertSame(
            $abort_success,
            $this->class->abort($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceIsAborted()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $abort_success = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getStandard(),
        ];

        $this->api_service->shouldReceive([
            'initialize' => true,
            'abortPayment' => $abort_success,
        ]);
        $this->assertSame(
            $abort_success,
            $this->class->abort($this->stored_payment['resource_id'])
        );
    }
}
