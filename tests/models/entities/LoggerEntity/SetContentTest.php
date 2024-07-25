<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class SetContentTest extends BaseLoggerEntity
{
    public function testUpdateContent()
    {
        $this->entity->setContent('another_content');
        $this->assertSame(
            'another_content',
            $this->entity->getContent()
        );
    }

    public function testReturnLoggerEntity()
    {
        $this->assertInstanceOf(
            LoggerEntity::class,
            $this->entity->setContent('another_content')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $content
     */
    public function testThrowExceptionWhenNotAString($content)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setContent($content);
    }
}
