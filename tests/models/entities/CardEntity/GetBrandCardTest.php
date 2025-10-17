<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetBrandCardTest extends BaseCardEntity
{
    protected $card;

    public function setUp()
    {
        parent::setUp();
        $this->entity->setBrand('brand');
    }

    public function testReturnBrand()
    {
        $this->assertSame(
            'brand',
            $this->entity->getBrand()
        );
    }

    public function testBrandIsAnInt()
    {
        $this->assertTrue(
            is_string($this->entity->getBrand())
        );
    }
}
