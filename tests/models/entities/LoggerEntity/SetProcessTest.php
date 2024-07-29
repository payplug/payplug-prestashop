<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetProcessTest extends BaseLoggerEntity
{
    public function testUpdateProcess()
    {
        $this->entity->setProcess('another_process');
        $this->assertSame(
            'another_process',
            $this->entity->getProcess()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->entity->setProcess('another_process')
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
        $this->entity->setProcess($date);
    }
}
