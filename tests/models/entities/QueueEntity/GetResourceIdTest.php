<?php

namespace PayPlug\tests\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class GetResourceIdTest extends BaseQueueEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setResourceId($this->resource_id);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->resource_id,
            $this->entity->getResourceId()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getResourceId())
        );
    }
}
