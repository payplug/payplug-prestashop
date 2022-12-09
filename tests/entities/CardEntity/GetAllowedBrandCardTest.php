<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetAllowedBrandCardTest extends TestCase
{
    protected $card;
    protected $allowedBrands;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->allowedBrands = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->card->setAllowedBrand($this->allowedBrands);
    }

    public function testReturnAllowedBrand()
    {
        $this->assertSame(
            $this->allowedBrands,
            $this->card->getAllowedBrand()
        );
    }

    public function testAllowedBrandIsArray()
    {
        $this->assertTrue(
            is_array($this->card->getAllowedBrand())
        );
    }

    public function testAllowedBrandIsNotEmpty()
    {
        $this->assertFalse(
            empty($this->card->getAllowedBrand())
        );
    }
}
