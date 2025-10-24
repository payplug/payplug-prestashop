<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 */
class addInstallmentSchedulesTest extends BaseInstallmentPaymentMethod
{
    public $retrieve;

    public function setUp()
    {
        parent::setUp();
        $this->retrieve = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getInstallment(),
            'schedule' => [
                [
                    'amount' => 4242,
                    'date' => '1970-01-01',
                    'resource' => PaymentMock::getStandard(),
                ],
            ],
        ];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $retrieve
     */
    public function testWhenGivenRetrieveIsntValidArrayFormat($retrieve)
    {
        $this->assertFalse($this->class->addInstallmentSchedules($retrieve));
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenRetrieveResourceIsntValidObjectFormat($resource)
    {
        $retrieve = $this->retrieve;
        $retrieve['resource'] = $resource;
        $this->assertFalse($this->class->addInstallmentSchedules($retrieve));
    }

    public function testWhenStoredPaymentHasSchedules()
    {
        $this->class->shouldReceive([
            'updateInstallmentSchedules' => true,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'schedules' => 'not empty schedules',
            ],
        ]);
        $this->assertTrue($this->class->addInstallmentSchedules($this->retrieve));
    }

    public function testWhenRetrievedInstallementHasNoSchedules()
    {
        $retrieve = $this->retrieve;
        unset($retrieve['schedule']);
        $this->class->shouldReceive([
            'updateInstallmentSchedules' => true,
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertFalse($this->class->addInstallmentSchedules($retrieve));
    }

    public function testWhenStoredInstallmentCantBeUpdated()
    {
        $this->class->shouldReceive([
            'updateInstallmentSchedules' => true,
        ]);
        $this->payment_method->shouldReceive([
            'getPaymentStatus' => [
                'id_status' => 1,
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [],
            'updateBy' => false,
        ]);
        $this->assertFalse($this->class->addInstallmentSchedules($this->retrieve));
    }

    public function testWhenStoredInstallmentIsUpdated()
    {
        $this->class->shouldReceive([
            'updateInstallmentSchedules' => true,
        ]);
        $this->payment_method->shouldReceive([
            'getPaymentStatus' => [
                'id_status' => 1,
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [],
            'updateBy' => true,
        ]);
        $this->assertTrue($this->class->addInstallmentSchedules($this->retrieve));
    }
}
