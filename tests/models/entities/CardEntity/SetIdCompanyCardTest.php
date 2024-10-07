<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIdCompanyCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setIdCompany(42);
    }

    public function testUpdateIdCompany()
    {
        $this->entity->setIdCompany(777);
        $this->assertSame(
            777,
            $this->entity->getIdCompany()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setIdCompany(777)
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
        $this->entity->setIdCompany('test');
    }
}
