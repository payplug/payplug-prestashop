<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetCountryCardTest extends BaseCardEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->entity->setCountry('country_name');
    }

    public function testUpdateCountry()
    {
        $this->entity->setCountry('new_country_name');
        $this->assertSame(
            'new_country_name',
            $this->entity->getCountry()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setCountry('country_name')
        );
    }

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setCountry(42);
    }
}
