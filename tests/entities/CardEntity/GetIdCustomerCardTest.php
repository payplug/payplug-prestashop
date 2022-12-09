<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdCustomerCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIdCustomer(42);
    }

    public function testReturnIdCustomer()
    {
        $this->assertSame(
            42,
            $this->card->getIdCustomer()
        );
    }

    public function testIdCustomerIsAnInt()
    {
        $this->assertTrue(
            is_int($this->card->getIdCustomer())
        );
    }
}
