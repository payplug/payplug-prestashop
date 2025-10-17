<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class getFromScheduleTest extends BasePaymentRepository
{
    public $resource_id;

    public function setUp()
    {
        parent::setUp();
        $this->resource_id = 'pay_azerty12345';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsInvalidStringFormat($resource_id)
    {
        $this->assertSame([], $this->repository->getFromSchedule($resource_id));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $this
            ->repository->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => [],
            ]);

        $this->assertSame([], $this->repository->getFromSchedule($this->resource_id));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $payment = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty12345',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => '4cbaebd7df677672ac3d571012ea0498129a5314271b0c38603c66425560bf43',
            'schedules' => '',
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this
            ->repository->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => $payment,
            ]);

        $this->assertSame($payment, $this->repository->getFromSchedule($this->resource_id));
    }
}
