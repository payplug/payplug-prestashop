<?php

namespace PayPlug\tests\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class GetTypeTest extends BaseQueueEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setType($this->type);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->type,
            $this->entity->getType()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getType())
        );
    }
}
