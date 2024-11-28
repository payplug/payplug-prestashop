<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group installment_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
class updateInstallmentSchedulesTest extends BaseInstallmentPaymentMethod
{
    private $retrieve;

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
        $this->assertFalse($this->class->updateInstallmentSchedules($retrieve));
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
        $this->assertFalse($this->class->updateInstallmentSchedules($retrieve));
    }

    public function testWhenRetrievedInstallementHasNoSchedules()
    {
        $retrieve = $this->retrieve;
        unset($retrieve['schedule']);
        $this->assertFalse($this->class->updateInstallmentSchedules($retrieve));
    }

    public function testWhenNoStoredPaymentCantBeFound()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertFalse($this->class->updateInstallmentSchedules($this->retrieve));
    }

    public function testWhenStoredInstallmentCantBeUpdated()
    {
        $this->payment_method->shouldReceive([
            'getPaymentStatus' => [
                'id_status' => 1,
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => 'inst_azerty12345',
                'method' => 'installment',
                'schedules' => '[{"id_payment":"pay_azerty12345","step":"1\/2","amount":4242,"status":5,"scheduled_date":"1970-01-01"},{"id_payment":"","step":"2\/2","amount":4242,"status":7,"scheduled_date":"1970-02-01"}]',
            ],
            'updateBy' => false,
        ]);
        $this->assertFalse($this->class->updateInstallmentSchedules($this->retrieve));
    }

    public function testWhenStoredInstallmentIsUpdated()
    {
        $this->payment_method->shouldReceive([
            'getPaymentStatus' => [
                'id_status' => 1,
            ],
        ]);
        $this->payment_repository->shouldReceive([
            'getBy' => [
                'resource_id' => 'inst_azerty12345',
                'method' => 'installment',
                'schedules' => '[{"id_payment":"pay_azerty12345","step":"1\/2","amount":4242,"status":5,"scheduled_date":"1970-01-01"},{"id_payment":"","step":"2\/2","amount":4242,"status":7,"scheduled_date":"1970-02-01"}]',
            ],
            'updateBy' => true,
        ]);
        $this->assertTrue($this->class->updateInstallmentSchedules($this->retrieve));
    }
}
