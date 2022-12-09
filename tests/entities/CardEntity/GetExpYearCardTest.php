<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetExpYearCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setExpYear('2021');
    }

    public function testReturnExpYear()
    {
        $this->assertSame(
            '2021',
            $this->card->getExpYear()
        );
    }

    public function testExpYearIsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getExpYear())
        );
    }
}
