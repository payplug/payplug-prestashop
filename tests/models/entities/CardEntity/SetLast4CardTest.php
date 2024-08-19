<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetLast4CardTest extends BaseCardEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->entity->setLast4('last4');
    }

    public function testUpdateLast4()
    {
        $this->entity->setLast4('new_last4');
        $this->assertSame(
            'new_last4',
            $this->entity->getLast4()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setLast4('last4')
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
        $this->entity->setLast4(42);
    }
}
