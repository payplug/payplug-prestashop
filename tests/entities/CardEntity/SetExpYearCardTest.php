<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetExpYearCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setExpYear('expiration');
    }

    public function testUpdateExpYear()
    {
        $this->card->setExpYear('new_expiration');
        $this->assertSame(
            'new_expiration',
            $this->card->getExpYear()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setExpYear('expiration')
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
        $this->card->setExpYear(42);
    }
}
