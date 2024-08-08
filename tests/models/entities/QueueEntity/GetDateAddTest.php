<?php

namespace PayPlug\tests\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class GetDateAddTest extends BaseQueueEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setDateAdd($this->date);
    }

    public function testReturnDateAdd()
    {
        $this->assertSame(
            $this->date,
            $this->entity->getDateAdd()
        );
    }

    public function testDateAddIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getDateAdd())
        );
    }

    public function testDateAddHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->entity->getDateAdd()
        );
    }
}
