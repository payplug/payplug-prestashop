<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetCountryCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setCountry('ISO');
    }

    public function testReturnCountry()
    {
        $this->assertSame(
            'ISO',
            $this->card->getCountry()
        );
    }

    public function testCountryIsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getCountry())
        );
    }
}
