<?php

namespace PayPlug\tests\models\entities\QueueEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class SetDateAddTest extends BaseQueueEntity
{
    public function testUpdateDateAdd()
    {
        $this->entity->setDateAdd('1920-12-31 23:59:42');
        $this->assertSame(
            '1920-12-31 23:59:42',
            $this->entity->getDateAdd()
        );
    }

    public function testReturnQueueEntity()
    {
        $this->assertInstanceOf(
            QueueEntity::class,
            $this->entity->setDateAdd('1920-12-31 23:59:42')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date
     */
    public function testThrowExceptionWhenNotAString($date)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateAdd($date);
    }

    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateAdd('1er Janvier 1970');
    }
}
