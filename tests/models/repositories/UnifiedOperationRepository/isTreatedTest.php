<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class isTreatedTest extends BaseUnifiedOperationRepository
{
    /**
     * @dataProvider unknownOperationProvider
     *
     * @param mixed $row
     */
    public function testReturnsFalseWhenTheOperationIsUnknown($row)
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn($row);

        $this->assertFalse($this->repository->isTreated('op_1'));
    }

    public function unknownOperationProvider()
    {
        yield 'empty row' => [[]];

        yield 'null' => [null];

        yield 'false' => [false];
    }

    /**
     * @dataProvider treatedFlagProvider
     *
     * @param mixed $treated
     * @param bool $expected
     */
    public function testReturnsTheStoredTreatedFlag($treated, $expected)
    {
        $this->repository->shouldReceive('getBy')->with('operation_id', 'op_1')->andReturn([
            'id_payplug_unified_operation' => '12',
            'operation_id' => 'op_1',
            'treated' => $treated,
        ]);

        $this->assertSame($expected, $this->repository->isTreated('op_1'));
    }

    public function treatedFlagProvider()
    {
        yield 'not treated (database string)' => ['0', false];

        yield 'treated (database string)' => ['1', true];

        yield 'not treated (int)' => [0, false];

        yield 'treated (int)' => [1, true];
    }
}
