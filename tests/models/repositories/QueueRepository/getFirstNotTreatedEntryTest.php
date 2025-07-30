<?php

namespace PayPlug\tests\models\repositories\QueueRepository;

/**
 * @group unit
 * @group repository
 * @group queue_repository
 */
class getFirstNotTreatedEntryTest extends BaseQueueRepository
{
    private $id_cart;

    public function setUp()
    {
        parent::setUp();
        $this->id_cart = 42;
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_cart
     */
    public function testWhenGivenLimitIsInvalidIntegerFormat($id_cart)
    {
        $this->assertSame([], $this->repository->getFirstNotTreatedEntry($id_cart));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->repository->entity_name = '';
        $this->assertSame([], $this->repository->getFirstNotTreatedEntry($this->id_cart));
    }

    public function testWhenEntityObjectCantBeGetted()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->assertSame([], $this->repository->getFirstNotTreatedEntry($this->id_cart));
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

        $this->assertSame([], $this->repository->getFirstNotTreatedEntry($this->id_cart));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $queue = [
            'id_payplug_queue' => 42,
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
            'id_cart' => $this->repository,
            'build' => $queue,
        ]);

        $this->assertSame($queue, $this->repository->getFirstNotTreatedEntry($this->id_cart));
    }
}
