<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetExpYearCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setExpYear('2021');
    }

    public function testReturnExpYear()
    {
        $this->assertSame(
            '2021',
            $this->entity->getExpYear()
        );
    }

    public function testExpYearIsAnInt()
    {
        $this->assertTrue(
            is_string($this->entity->getExpYear())
        );
    }
}
