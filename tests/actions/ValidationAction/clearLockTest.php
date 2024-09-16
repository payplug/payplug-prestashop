<?php

namespace PayPlug\tests\actions\ValidationAction;

/**
 * @group unit
 * @group action
 * @group validation_action
 *
 * @runTestsInSeparateProcesses
 */
class clearLockTest extends BaseValidationAction
{
    protected $configClass;
    protected $lock_repository;
    protected $queue_action;

    public function setUp()
    {
        parent::setUp();

        $this->configClass = \Mockery::mock('ConfigClass');
        $this->dependencies->configClass = $this->configClass;
        $this->queue_action = \Mockery::mock('QueueAction');
        $this->lock_repository = \Mockery::mock('LockRepository');
        $this->plugin->shouldReceive([
            'getQueueAction' => $this->queue_action,
            'getLockRepository' => $this->lock_repository,
        ]);
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $cart_id
     */
    public function testWhenGivenCartIdIsInvalidIntegerFormat($cart_id)
    {
        $this->assertFalse($this->action->clearLock($cart_id));
    }

    public function testWhenQueueCanBeUpdate()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->queue_action->shouldReceive([
            'updateAction' => [
                'result' => false,
            ],
        ]);
        $this->assertFalse($this->action->clearLock($this->cart_id));
    }

    public function testWhenQueueCanUpdate()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->queue_action->shouldReceive([
            'updateAction' => [
                'result' => true,
            ],
        ]);
        $this->assertTrue($this->action->clearLock($this->cart_id));
    }

    public function testWhenLockCanBeUpdate()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->lock_repository->shouldReceive([
            'deleteLock' => false,
        ]);
        $this->assertFalse($this->action->clearLock($this->cart_id));
    }

    public function testWhenLockCanUpdate()
    {
        $this->configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->lock_repository->shouldReceive([
            'deleteLock' => true,
        ]);
        $this->assertTrue($this->action->clearLock($this->cart_id));
    }
}
