<?php

namespace PayPlug\tests\models\repositories\UnifiedAliasRepository;

/**
 * @group unit
 * @group repository
 * @group unified_alias_repository
 */
class initializeTest extends BaseUnifiedAliasRepository
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
        $this->repository->shouldReceive([
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
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'create' => $this->repository,
            'table' => $this->repository,
            'fields' => $this->repository,
            'engine' => $this->repository,
            'build' => true,
        ]);
        $this->repository->shouldReceive('condition')
            ->once()
            ->with('CONSTRAINT payplug_unified_alias_unique UNIQUE (alias_id), KEY payplug_unified_alias_id_customer (id_customer)')
            ->andReturn($this->repository);

        $this->assertTrue($this->repository->initialize($this->engine));
    }
}
