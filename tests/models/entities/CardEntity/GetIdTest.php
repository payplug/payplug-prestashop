<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
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
