<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetExpYearCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setExpYear('expiration');
    }

    public function testUpdateExpYear()
    {
        $this->entity->setExpYear('new_expiration');
        $this->assertSame(
            'new_expiration',
            $this->entity->getExpYear()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setExpYear('expiration')
        );
    }

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setExpYear(42);
    }
}
