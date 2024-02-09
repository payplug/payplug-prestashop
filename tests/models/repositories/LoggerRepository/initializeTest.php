<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @runTestsInSeparateProcesses
 */
class initializeTest extends BaseLoggerRepository
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

    public function testWhenTableCantBeInitialized()
    {
        $engine = 'sql_engine';
        $this
            ->repository
            ->shouldReceive([
                'create' => $this->repository,
                'table' => $this->repository,
                'fields' => $this->repository,
                'engine' => $this->repository,
                'build' => false,
            ]);
        $this->assertFalse($this->repository->initialize($engine));
    }

    public function testWhenTableIsInitialized()
    {
        $engine = 'sql_engine';
        $this
            ->repository
            ->shouldReceive([
                'create' => $this->repository,
                'table' => $this->repository,
                'fields' => $this->repository,
                'engine' => $this->repository,
                'build' => true,
            ]);
        $this->assertTrue($this->repository->initialize($engine));
    }
}
