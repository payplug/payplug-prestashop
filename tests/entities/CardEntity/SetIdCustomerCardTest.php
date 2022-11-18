<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIdCustomerCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIdCustomer(42);
    }

    public function testUpdateIdCustomer()
    {
        $this->card->setIdCustomer(777);
        $this->assertSame(
            777,
            $this->card->getIdCustomer()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setIdCustomer(777)
        );
    }

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAnInt()
    {
        $this->expectException(BadParameterException::class);
        $this->card->setIdCustomer('test');
    }
}
