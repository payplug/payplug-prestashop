<?php

namespace PayPlug\tests\models\entities\QueueEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class SetIdTest extends BaseQueueEntity
{
    public function testUpdateId()
    {
        $this->entity->setId($this->id);
        $this->assertSame(
            $this->id,
            $this->entity->getId()
        );
    }

    public function testReturnQueueEntity()
    {
        $this->assertInstanceOf(
            QueueEntity::class,
            $this->entity->setId($this->id)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id
     */
    public function testThrowExceptionWhenNotAnInteger($id)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateUpd($id);
    }
}
