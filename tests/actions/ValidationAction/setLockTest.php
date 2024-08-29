<?php

namespace PayPlug\tests\actions\ValidationAction;

/**
 * @group unit
 * @group action
 * @group validation_action
 *
 * @runTestsInSeparateProcesses
 */
class setLockTest extends BaseValidationAction
{
    protected $configClass;
    protected $queue_action;
    protected $queue_entry;
    protected $queue_repository;
    protected $resource_id;

    public function setUp()
    {
        parent::setUp();

        $this->configClass = \Mockery::mock('ConfigClass');
        $this->payplugLock = \Mockery::mock('PayplugLock');
        $this->dependencies->configClass = $this->configClass;
        $this->dependencies->payplugLock = $this->payplugLock;

        $this->queue_entry = [
            'id_cart' => 42,
            'resource_id' => 'pay_azerty12345',
        ];
        $this->queue_action = \Mockery::mock('QueueAction');
        $this->queue_repository = \Mockery::mock('QueueRepository');
        $this->dependencies->configClass = $this->configClass;
        $this->resource_id = 'pay_azerty12345';
        $this->plugin->shouldReceive([
            'getQueueAction' => $this->queue_action,
            'getQueueRepository' => $this->queue_repository,
        ]);
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsInvalidStringFormat($resource_id)
    {
        $this->assertFalse($this->action->setLock($resource_id));
    }

    public function testwhenQueueAlreadyExists()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->queue_repository->shouldReceive([
            'getFirstNotTreatedEntry' => $this->queue_entry,
        ]);
        $this->assertFalse($this->action->setLock($this->resource_id));
    }

    public function testWhenQueueCantBeHydrated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->queue_repository->shouldReceive([
            'getFirstNotTreatedEntry' => [],
        ]);
        $this->queue_action->shouldReceive([
            'hydrateAction' => [
                'result' => false,
            ],
        ]);
        $this->assertFalse($this->action->setLock($this->resource_id));
    }

    public function testWhenQueueIsHydrated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->queue_repository->shouldReceive([
            'getFirstNotTreatedEntry' => [],
        ]);
        $this->queue_action->shouldReceive([
            'hydrateAction' => [
                'result' => true,
            ],
        ]);
        $this->assertTrue($this->action->setLock($this->resource_id));
    }

    public function testWhenLockCantBeCreated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->payplugLock->shouldReceive([
            'createLockG2' => false,
        ]);
        $this->assertFalse($this->action->setLock($this->resource_id));
    }

    public function testWhenLockIsCreated()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->payplugLock->shouldReceive([
            'createLockG2' => true,
        ]);
        $this->assertTrue($this->action->setLock($this->resource_id));
    }
}
