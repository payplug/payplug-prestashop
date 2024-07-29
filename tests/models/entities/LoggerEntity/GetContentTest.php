<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetContentTest extends BaseLoggerEntity
{
    private $content;

    public function setUp()
    {
        parent::setUp();
        $this->content = 'content';
        $this->entity->setContent($this->content);
    }

    public function testReturnContent()
    {
        $this->assertSame(
            $this->content,
            $this->entity->getContent()
        );
    }

    public function testContentIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getContent())
        );
    }
}
