<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

/**
 * @group unit
 * @group action
 */
class discardOutputBuffersTest extends BaseApplePayDomainAssociationAction
{
    const MAX_CALLS = 10;

    private $end_calls;

    public function setUp(): void
    {
        parent::setUp();
        $this->end_calls = 0;
    }

    public function testDoesNothingWithoutBuffer()
    {
        $this->action->shouldReceive('getOutputBufferLevel')->andReturn(0);
        $this->expectEndOutputBuffer(true);

        $this->discardOutputBuffers();

        $this->assertSame(0, $this->end_calls);
    }

    public function testDiscardsEveryBuffer()
    {
        $this->action->shouldReceive('getOutputBufferLevel')->andReturn(2, 1, 0);
        $this->expectEndOutputBuffer(true);

        $this->discardOutputBuffers();

        $this->assertSame(2, $this->end_calls);
    }

    public function testStopsAtABufferThatCannotBeRemoved()
    {
        $this->action->shouldReceive('getOutputBufferLevel')->andReturn(2);
        $this->expectEndOutputBuffer(false);

        $this->discardOutputBuffers();

        $this->assertSame(1, $this->end_calls);
    }

    public function testStopsWhenTheLevelDoesNotDecrease()
    {
        $this->action->shouldReceive('getOutputBufferLevel')->andReturn(2, 1, 1);
        $this->expectEndOutputBuffer(true);

        $this->discardOutputBuffers();

        $this->assertSame(2, $this->end_calls);
    }

    /**
     * @param bool $result
     */
    private function expectEndOutputBuffer($result)
    {
        $this->action->shouldReceive('endOutputBuffer')->andReturnUsing(function () use ($result) {
            if (++$this->end_calls > self::MAX_CALLS) {
                throw new \LogicException('The output buffers loop does not stop.');
            }

            return $result;
        });
    }

    private function discardOutputBuffers()
    {
        $method = new \ReflectionMethod($this->action, 'discardOutputBuffers');
        $method->setAccessible(true);
        $method->invoke($this->action);
    }
}
