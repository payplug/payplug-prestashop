<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIsSandboxTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setIsSandbox(true);
    }

    public function testUpdateIsSandbox()
    {
        $this->entity->setIsSandbox(false);
        $this->assertSame(
            false,
            $this->entity->getIsSandbox()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setIsSandbox(false)
        );
    }

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotABool()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setIsSandbox('test');
    }
}
