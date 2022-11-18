<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdCardCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIdCard('card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    public function testReturnIdCard()
    {
        $this->assertSame(
            'card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            $this->card->getIdCard()
        );
    }

    public function testIdCardIsAString()
    {
        $this->assertTrue(
            is_string($this->card->getIdCard())
        );
    }

    public function testIdCardHaveAValidFormat()
    {
        $this->assertRegExp(
            '/card_[a-z0-9]{32}/',
            $this->card->getIdCard()
        );
    }
}
