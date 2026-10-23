<?php

namespace PayPlug\tests\models\repositories\UnifiedOperationRepository;

/**
 * @group unit
 * @group repository
 * @group unified_operation_repository
 */
class initializeTest extends BaseUnifiedOperationRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $engine
     */
    public function testWhenGivenEngineIsInvalidStringFormat($engine)
    {
        $this->assertFalse($this->repository->initialize($engine));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->repository->entity_name = '';
        $this->assertFalse($this->repository->initialize($this->engine));
    }

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertFalse($this->repository->initialize($this->engine));
    }

    public function testWhenTableCantBeInitialized()
    {
        $this
            ->repository->shouldReceive([
                'getEntityObject' => $this->entity,
                'create' => $this->repository,
                'table' => $this->repository,
                'fields' => $this->repository,
                'condition' => $this->repository,
                'engine' => $this->repository,
                'build' => false,
            ]);
        $this->assertFalse($this->repository->initialize($this->engine));
    }

    public function testWhenTableIsInitialized()
    {
        $this
            ->repository->shouldReceive([
                'getEntityObject' => $this->entity,
                'create' => $this->repository,
                'table' => $this->repository,
                'fields' => $this->repository,
                'condition' => $this->repository,
                'engine' => $this->repository,
                'build' => true,
            ]);
        $this->assertTrue($this->repository->initialize($this->engine));
    }

    public function testCreatesTheRequestedAmountColumn()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'create' => $this->repository,
            'table' => $this->repository,
            'condition' => $this->repository,
            'engine' => $this->repository,
            'build' => true,
        ]);
        $this->repository->shouldReceive('fields')
            ->once()
            ->with('`requested_amount` INT(11) UNSIGNED NULL')
            ->andReturn($this->repository);
        $this->repository->shouldReceive('fields')->andReturn($this->repository);

        $this->assertTrue($this->repository->initialize($this->engine));
    }
}
