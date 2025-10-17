<?php

namespace PayPlug\tests\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class GetIdTest extends BaseQueueEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setId($this->id);
    }

    public function testReturnId()
    {
        $this->assertSame(
            $this->id,
            $this->entity->getId()
        );
    }

    public function testIdIsAnInteger()
    {
        $this->assertTrue(
            is_int($this->entity->getId())
        );
    }
}
