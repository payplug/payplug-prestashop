<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetDefinitionCardTest extends TestCase
{
    protected $card;
    protected $brands;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->brands = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->card->setDefinition($this->brands);
    }

    public function testReturnDefinition()
    {
        $this->assertSame(
            $this->brands,
            $this->card->getDefinition()
        );
    }

    public function testDefinitionIsAnArray()
    {
        $this->assertTrue(
            is_array($this->card->getDefinition())
        );
    }
}
