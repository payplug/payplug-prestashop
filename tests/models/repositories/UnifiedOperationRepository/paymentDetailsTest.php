<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class paymentDetailsTest extends BaseUnifiedOperationRepository
{
    private const DETAILS = [
        'brand' => 'VISA',
        'last4' => '0001',
        'exp_month' => '12',
        'exp_year' => '2029',
        'tds' => 'yes',
    ];

    public function testStoresTheDetailsAsJsonOnTheOperationRow()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['id_payplug_unified_operation' => '12']);
        $this->repository->shouldReceive('updateEntity')
            ->once()
            ->with(12, ['payment_details' => json_encode(self::DETAILS)])
            ->andReturn(true);

        $this->assertTrue($this->repository->savePaymentDetails('op_1', self::DETAILS));
    }

    public function testStoresNothingForAnUnknownOperation()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([]);
        $this->repository->shouldReceive('updateEntity')->never();

        $this->assertFalse($this->repository->savePaymentDetails('op_1', self::DETAILS));
    }

    /**
     * An install that ran an earlier build of upgrade-5.2.0.php has no payment_details column.
     */
    public function testReportsAFailedWriteWithoutThrowing()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['id_payplug_unified_operation' => '12']);
        $this->repository->shouldReceive('updateEntity')->andThrow(new \Exception("Unknown column 'payment_details'"));

        $this->assertFalse($this->repository->savePaymentDetails('op_1', self::DETAILS));
    }

    public function testReadsTheStoredDetails()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['payment_details' => json_encode(self::DETAILS)]);

        $this->assertSame(self::DETAILS, $this->repository->getPaymentDetails('op_1'));
    }

    /**
     * @dataProvider missingDetailsProvider
     *
     * @param mixed $row
     */
    public function testReadsNoDetailsWhenNoneAreUsable($row)
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn($row);

        $this->assertNull($this->repository->getPaymentDetails('op_1'));
    }

    public function missingDetailsProvider()
    {
        return [
            'unknown operation' => [[]],
            'column missing' => [['operation_id' => 'op_1']],
            'never recorded' => [['payment_details' => null]],
            'invalid json' => [['payment_details' => '{"brand":']],
            'json scalar' => [['payment_details' => '"VISA"']],
        ];
    }

    public function testReadsNoDetailsWhenTheReadThrows()
    {
        $this->repository->shouldReceive('getBy')->andThrow(new \Exception('database unavailable'));

        $this->assertNull($this->repository->getPaymentDetails('op_1'));
    }

    public function testReadsTheCreationDateOfTheOperationRow()
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn(['date_add' => '2026-11-10 14:05:00']);

        $this->assertSame('2026-11-10 14:05:00', $this->repository->getDateAddByOperationId('op_1'));
    }

    /**
     * @dataProvider missingDateProvider
     *
     * @param mixed $row
     */
    public function testReadsNoCreationDateWhenTheRowHasNone($row)
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn($row);

        $this->assertNull($this->repository->getDateAddByOperationId('op_1'));
    }

    public function missingDateProvider()
    {
        return [
            'unknown operation' => [[]],
            'never dated' => [['date_add' => null]],
            'empty' => [['date_add' => '']],
        ];
    }
}
