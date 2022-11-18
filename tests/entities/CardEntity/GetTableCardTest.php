<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetTableTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setTable('table_name');
    }

    public function testReturnTable()
    {
        $this->assertSame(
            'table_name',
            $this->card->getTable()
        );
    }

    public function testTableIsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getTable())
        );
    }
}
