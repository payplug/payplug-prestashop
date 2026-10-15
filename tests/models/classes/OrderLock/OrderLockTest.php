<?php

namespace PayPlug\tests\models\classes\OrderLock;

use PayPlug\src\models\classes\OrderLock;

/**
 * @group unit
 * @group model
 * @group order_lock_class
 */
class OrderLockTest extends BaseOrderLock
{
    public function testLockNameIsDeterministicAndShort()
    {
        $name = $this->class->getLockName(42);

        $this->assertSame($name, $this->class->getLockName(42));
        $this->assertLessThanOrEqual(64, strlen($name));
        $this->assertStringStartsWith('payplug_order_', $name);
        $this->assertStringEndsWith('_42', $name);
        $this->assertNotSame($name, $this->class->getLockName(43));
    }

    public function testLockNameDependsOnDatabase()
    {
        $this->class->shouldReceive('getScope')
            ->andReturn(md5('shop_a' . 'ps_'), md5('shop_b' . 'ps_'));

        $this->assertNotSame($this->class->getLockName(42), $this->class->getLockName(42));
    }

    /**
     * @dataProvider mysqlAnswerDataProvider
     *
     * @param mixed $answer
     * @param bool $expected
     */
    public function testAcquireReturnsTrueOnlyWhenMysqlAnswersOne($answer, $expected)
    {
        $this->query_adapter->shouldReceive('getNamedLock')
            ->with($this->class->getLockName(42), OrderLock::TIMEOUT)
            ->once()
            ->andReturn($answer);

        $this->assertSame($expected, $this->class->acquire(42));
    }

    public function mysqlAnswerDataProvider()
    {
        return [
            ['1', true],
            [1, true],
            ['0', false],
            [0, false],
            [null, false],
            [false, false],
            ['', false],
        ];
    }

    public function testAcquireTimeoutIsNotAnError()
    {
        $this->logger->shouldReceive('addLog')
            ->with(\Mockery::any(), 'info')
            ->once()
            ->andReturn(true);
        $this->logger->shouldNotReceive('addLog')
            ->with(\Mockery::any(), 'error');
        $this->query_adapter->shouldReceive([
            'getNamedLock' => '0',
        ]);

        $this->assertFalse($this->class->acquire(42));
    }

    /**
     * @dataProvider mysqlFailureDataProvider
     *
     * @param mixed $answer
     */
    public function testAcquireLogsErrorWhenMysqlFails($answer)
    {
        $this->logger->shouldReceive('addLog')
            ->with(\Mockery::pattern('/GET_LOCK failed/'), 'error')
            ->once()
            ->andReturn(true);
        $this->query_adapter->shouldReceive([
            'getNamedLock' => $answer,
        ]);

        $this->assertFalse($this->class->acquire(42));
    }

    public function testAcquireLogsTheMysqlErrorCause()
    {
        $this->logger->shouldReceive('addLog')
            ->with(\Mockery::pattern('/Order lock unavailable, order creation blocked for cart 42 \(GET_LOCK failed: Lost connection\)/'), 'error')
            ->once()
            ->andReturn(true);
        $this->query_adapter->shouldReceive([
            'getNamedLock' => false,
            'getLastError' => 'Lost connection',
        ]);

        $this->assertFalse($this->class->acquire(42));
    }

    public function mysqlFailureDataProvider()
    {
        return [
            [null],
            [false],
        ];
    }

    /**
     * @dataProvider invalidCartIdDataProvider
     *
     * @param mixed $id_cart
     */
    public function testAcquireRejectsInvalidCartId($id_cart)
    {
        $this->query_adapter->shouldNotReceive('getNamedLock');

        $this->assertFalse($this->class->acquire($id_cart));
    }

    public function invalidCartIdDataProvider()
    {
        return [
            [0],
            [-1],
            ['12'],
            [null],
            [1.5],
        ];
    }

    public function testReleaseWithoutAcquireDoesNotTouchMysql()
    {
        $this->query_adapter->shouldNotReceive('releaseNamedLock');

        $this->assertFalse($this->class->release(42));
    }

    public function testReleaseAfterFailedAcquireDoesNotTouchMysql()
    {
        $this->query_adapter->shouldReceive([
            'getNamedLock' => '0',
        ]);
        $this->query_adapter->shouldNotReceive('releaseNamedLock');

        $this->class->acquire(42);

        $this->assertFalse($this->class->release(42));
    }

    public function testReleaseOnlyOnceWhenHeld()
    {
        $this->query_adapter->shouldReceive([
            'getNamedLock' => '1',
        ]);
        $this->query_adapter->shouldReceive('releaseNamedLock')
            ->with($this->class->getLockName(42))
            ->once()
            ->andReturn('1');

        $this->assertTrue($this->class->acquire(42));
        $this->assertTrue($this->class->release(42));
        $this->assertFalse($this->class->release(42));
    }

    public function testReleaseLogsErrorWhenMysqlDoesNotConfirm()
    {
        $this->logger->shouldReceive('addLog')
            ->with(\Mockery::any(), 'info')
            ->andReturn(true);
        $this->logger->shouldReceive('addLog')
            ->with(\Mockery::pattern('/Order lock not confirmed, race protection may be inactive for cart 42/'), 'error')
            ->once()
            ->andReturn(true);
        $this->query_adapter->shouldReceive([
            'getNamedLock' => '1',
            'releaseNamedLock' => '0',
        ]);

        $this->assertTrue($this->class->acquire(42));
        $this->assertFalse($this->class->release(42));
    }
}
