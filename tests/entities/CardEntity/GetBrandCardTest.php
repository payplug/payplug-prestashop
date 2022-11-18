<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetBrandCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setBrand('brand');
    }

    public function testReturnBrand()
    {
        $this->assertSame(
            'brand',
            $this->card->getBrand()
        );
    }

    public function testBrandIsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getBrand())
        );
    }
}
