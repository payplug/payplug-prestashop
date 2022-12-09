<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIdCardCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIdCard('card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    public function testUpdateIdCard()
    {
        $this->card->setIdCard('card_azertyuiop1234567890qsdfghjklm12');
        $this->assertSame(
            'card_azertyuiop1234567890qsdfghjklm12',
            $this->card->getIdCard()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setIdCard('card_azertyuiop1234567890qsdfghjklm12')
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
        $this->card->setIdCard(42);
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
        $this->card->setIdCard('Kart_AZERT&é"');
    }
}
