<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetProcessTest extends BaseLoggerEntity
{
    protected $logger;

    public function setUp()
    {
        parent::setUp();
        $this->entity->setProcess('process');
    }

    public function testReturnProcess()
    {
        $this->assertSame(
            'process',
            $this->entity->getProcess()
        );
    }

    public function testProcessIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getProcess())
        );
    }
}
