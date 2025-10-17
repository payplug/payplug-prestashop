<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class initializeTest extends BasePaymentRepository
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
                'create' => $this->repository,
                'table' => $this->repository,
                'fields' => $this->repository,
                'engine' => $this->repository,
                'build' => false,
            ]);
        $this->assertFalse($this->repository->initialize($this->engine));
    }

    public function testWhenTableIsInitialized()
    {
        $this
            ->repository->shouldReceive([
                'create' => $this->repository,
                'table' => $this->repository,
                'fields' => $this->repository,
                'engine' => $this->repository,
                'build' => true,
            ]);
        $this->assertTrue($this->repository->initialize($this->engine));
    }
}
