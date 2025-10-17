<?php

namespace PayPlug\tests\models\entities\QueueEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class SetTypeTest extends BaseQueueEntity
{
    public function testUpdateCartHash()
    {
        $this->entity->setType($this->type);
        $this->assertSame(
            $this->type,
            $this->entity->getType()
        );
    }

    public function testReturnQueueEntity()
    {
        $this->assertInstanceOf(
            QueueEntity::class,
            $this->entity->setType($this->type)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $type
     */
    public function testThrowExceptionWhenNotAString($type)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setType($type);
    }
}
