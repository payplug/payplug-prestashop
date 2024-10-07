<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIdCardCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setIdCard('card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    public function testUpdateIdCard()
    {
        $this->entity->setIdCard('card_azertyuiop1234567890qsdfghjklm12');
        $this->assertSame(
            'card_azertyuiop1234567890qsdfghjklm12',
            $this->entity->getIdCard()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setIdCard('card_azertyuiop1234567890qsdfghjklm12')
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
        $this->entity->setIdCard(42);
    }

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setIdCard('Kart_AZERT&é"');
    }
}
