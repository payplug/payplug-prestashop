<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetBrandCardTest extends BaseCardEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->entity->setBrand('brand_name');
    }

    public function testUpdateBrand()
    {
        $this->entity->setBrand('new_brand_name');
        $this->assertSame(
            'new_brand_name',
            $this->entity->getBrand()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setBrand('brand_name')
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
        $this->entity->setBrand(42);
    }
}
