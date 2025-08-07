<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetIdTest extends BaseLoggerEntity
{
    public $id;

    public function setUp()
    {
        parent::setUp();
        $this->id = 42;
        $this->entity->setId($this->id);
    }

    public function testReturnId()
    {
        $this->assertSame(
            $this->id,
            $this->entity->getId()
        );
    }

    public function testIdIsAnInteger()
    {
        $this->assertTrue(
            is_int($this->entity->getId())
        );
    }
}
