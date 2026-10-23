<?php

namespace PayPlug\tests\models\repositories\UnifiedRefundRepository;

/**
 * @group unit
 * @group repository
 * @group unified_refund_repository
 */
class initializeTest extends BaseUnifiedRefundRepository
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

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->shouldReceive(['getEntityObject' => null]);
        $this->assertFalse($this->repository->initialize($this->engine));
    }

    public function testWhenTableIsInitialized()
    {
        $this->repository->shouldReceive([
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
