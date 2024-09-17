<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdCustomerCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setIdCustomer(42);
    }

    public function testReturnIdCustomer()
    {
        $this->assertSame(
            42,
            $this->entity->getIdCustomer()
        );
    }

    public function testIdCustomerIsAnInt()
    {
        $this->assertTrue(
            is_int($this->entity->getIdCustomer())
        );
    }
}
