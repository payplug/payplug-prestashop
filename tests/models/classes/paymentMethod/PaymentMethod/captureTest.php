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
class captureTest extends BasePaymentMethod
{
    private $stored_payment;

    public function setUp()
    {
        parent::setUp();
        $this->stored_payment = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
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
            $this->class->capture($resource_id)
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
            $this->class->capture($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceCantBeCaptured()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $capture_failed = [
            'result' => false,
            'code' => 500,
            'message' => 'An error occured during resource capture',
        ];

        $this->api_service->shouldReceive([
            'initialize' => true,
            'capturePayment' => $capture_failed,
        ]);
        $this->assertSame(
            $capture_failed,
            $this->class->capture($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceIsCapturedOnSecondTry()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $capture_failed = [
            'result' => false,
            'code' => 500,
            'message' => 'An error occured during resource capture',
        ];
        $capture_success = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getStandard(),
        ];

        $this->api_service
            ->shouldReceive('capturePayment')
            ->once()
            ->andReturn($capture_failed);

        $this->api_service->shouldReceive([
            'initialize' => true,
            'capturePayment' => $capture_success,
        ]);
        $this->assertSame(
            $capture_success,
            $this->class->capture($this->stored_payment['resource_id'])
        );
    }

    public function testWhenResourceIsCaptured()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_payment,
        ]);
        $this->configuration->shouldReceive([
            'getValue' => true,
        ]);

        $capture_success = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getStandard(),
        ];

        $this->api_service->shouldReceive([
            'initialize' => true,
            'capturePayment' => $capture_success,
        ]);
        $this->assertSame(
            $capture_success,
            $this->class->capture($this->stored_payment['resource_id'])
        );
    }
}
