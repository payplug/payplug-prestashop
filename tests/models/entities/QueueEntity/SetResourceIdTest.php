<?php

namespace PayPlug\tests\models\entities\QueueEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class SetResourceIdTest extends BaseQueueEntity
{
    public function testUpdateCartHash()
    {
        $this->entity->setResourceId($this->resource_id);
        $this->assertSame(
            $this->resource_id,
            $this->entity->getResourceId()
        );
    }

    public function testReturnQueueEntity()
    {
        $this->assertInstanceOf(
            QueueEntity::class,
            $this->entity->setResourceId($this->resource_id)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testThrowExceptionWhenNotAString($resource_id)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setResourceId($resource_id);
    }
}
