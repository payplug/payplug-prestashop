<?php

namespace PayPlug\tests\models\repositories\LoggerRepository;

/**
 * @group unit
 * @group repository
 * @group logger_repository
 *
 * @runTestsInSeparateProcesses
 */
class getLastLimitLogTest extends BaseLoggerRepository
{
    public $limit;

    public function setUp()
    {
        parent::setUp();
        $this->limit = 42;
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $limit
     */
    public function testWhenGivenLimitIsInvalidIntegerFormat($limit)
    {
        $this->assertSame([], $this->repository->getLastLimitLog($limit));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->repository->entity_name = '';
        $this->assertSame([], $this->repository->getLastLimitLog($this->limit));
    }

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertSame([], $this->repository->getLastLimitLog($this->limit));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'orderBy' => $this->repository,
            'limit' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame([], $this->repository->getLastLimitLog($this->limit));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $logger = [
            'id_payplug_logger' => 42,
            'process' => 'process',
            'content' => '{}',
            'date_add' => date('Y-m-d H:i:s'),
            'date_upd' => date('Y-m-d H:i:s'),
        ];

        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'orderBy' => $this->repository,
            'limit' => $this->repository,
            'build' => $logger,
        ]);

        $this->assertSame($logger, $this->repository->getLastLimitLog($this->limit));
    }
}
