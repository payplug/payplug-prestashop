<?php

namespace PayPlug\tests\models\repositories\UnifiedLockRepository;

/**
 * @group unit
 * @group repository
 * @group unified_lock_repository
 */
class initializeTest extends BaseUnifiedLockRepository
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
        $this->mockCreateTable(false);
        $this->assertFalse($this->repository->initialize($this->engine));
    }

    public function testWhenTableIsInitialized()
    {
        $this->mockCreateTable(true);
        $this->assertTrue($this->repository->initialize($this->engine));
    }

    public function testLockKeyIsDeclaredUnique()
    {
        $this->repository->shouldReceive('condition')
            ->once()
            ->with('CONSTRAINT payplug_unified_lock_unique UNIQUE (lock_key)')
            ->andReturn($this->repository);
        $this->mockCreateTable(true);

        $this->assertTrue($this->repository->initialize($this->engine));
    }

    private function mockCreateTable($built)
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'create' => $this->repository,
            'table' => $this->repository,
            'fields' => $this->repository,
            'condition' => $this->repository,
            'engine' => $this->repository,
            'build' => $built,
        ]);
    }
}
