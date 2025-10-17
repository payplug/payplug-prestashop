<?php

namespace PayPlug\tests\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class GetDefinitionTest extends BaseQueueEntity
{
    public function testReturnDefinition()
    {
        $this->assertSame(
            $this->definition,
            $this->entity->getDefinition()
        );
    }

    public function testDefinitionIsAnArray()
    {
        $this->assertTrue(
            is_array($this->entity->getDefinition())
        );
    }
}
