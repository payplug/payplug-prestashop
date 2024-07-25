<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetExpMonthCardTest extends BaseCardEntity
{
    protected $card;

    protected function setUp()
    {
        parent::setUp();
        $this->entity->setExpMonth('1');
    }

    public function testReturnExpMonth()
    {
        $this->assertSame(
            '1',
            $this->entity->getExpMonth()
        );
    }

    public function testExpMonthIsAnInt()
    {
        $this->assertTrue(
            is_string($this->entity->getExpMonth())
        );
    }
}
