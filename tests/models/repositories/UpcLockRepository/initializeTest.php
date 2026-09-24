<?php

namespace PayPlug\tests\models\repositories\UpcLockRepository;

/**
 * @group unit
 * @group repository
 * @group upc_lock_repository
 */
class initializeTest extends BaseUpcLockRepository
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
}
