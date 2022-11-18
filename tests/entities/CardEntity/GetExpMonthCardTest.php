<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetExpMonthCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setExpMonth('1');
    }

    public function testReturnExpMonth()
    {
        $this->assertSame(
            '1',
            $this->card->getExpMonth()
        );
    }

    public function testExpMonthIsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getExpMonth())
        );
    }
}
