<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetLast4CardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setLast4('4242');
    }

    public function testReturnLast4()
    {
        $this->assertSame(
            '4242',
            $this->card->getLast4()
        );
    }

    public function testLast4IsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getLast4())
        );
    }
}
