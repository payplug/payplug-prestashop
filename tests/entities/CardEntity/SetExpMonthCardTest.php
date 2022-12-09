<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetExpMonthCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setExpMonth('expiration');
    }

    public function testUpdateExpMonth()
    {
        $this->card->setExpMonth('new_expiration');
        $this->assertSame(
            'new_expiration',
            $this->card->getExpMonth()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setExpMonth('expiration')
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
        $this->card->setExpMonth(42);
    }
}
