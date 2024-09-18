<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetIdTest extends BaseLoggerEntity
{
    public function testUpdateId()
    {
        $this->entity->setId(42);
        $this->assertSame(
            42,
            $this->entity->getId()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->entity->setId(42)
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
        $this->entity->setId($id);
    }
}
