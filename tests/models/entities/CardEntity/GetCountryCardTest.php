<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetCountryCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setCountry('ISO');
    }

    public function testReturnCountry()
    {
        $this->assertSame(
            'ISO',
            $this->entity->getCountry()
        );
    }

    public function testCountryIsAnInt()
    {
        $this->assertTrue(
            is_string($this->entity->getCountry())
        );
    }
}
