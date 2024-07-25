<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdCompanyCardTest extends BaseCardEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->entity->setIdCompany(42);
    }

    public function testReturnIdCompany()
    {
        $this->assertSame(
            42,
            $this->entity->getIdCompany()
        );
    }

    public function testIdCompanyIsAnInt()
    {
        $this->assertTrue(
            is_int($this->entity->getIdCompany())
        );
    }
}
