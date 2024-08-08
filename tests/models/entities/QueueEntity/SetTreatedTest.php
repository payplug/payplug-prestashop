<?php

namespace PayPlug\tests\models\entities\QueueEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class SetTreatedTest extends BaseQueueEntity
{
    public function testUpdateCartHash()
    {
        $this->entity->setTreated($this->treated);
        $this->assertSame(
            $this->treated,
            $this->entity->getTreated()
        );
    }

    public function testReturnQueueEntity()
    {
        $this->assertInstanceOf(
            QueueEntity::class,
            $this->entity->setTreated($this->treated)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $treated
     */
    public function testThrowExceptionWhenNotAString($treated)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setTreated($treated);
    }
}
