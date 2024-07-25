<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetExpMonthCardTest extends BaseCardEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->entity->setExpMonth('expiration');
    }

    public function testUpdateExpMonth()
    {
        $this->entity->setExpMonth('new_expiration');
        $this->assertSame(
            'new_expiration',
            $this->entity->getExpMonth()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setExpMonth('expiration')
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
        $this->entity->setExpMonth(42);
    }
}
