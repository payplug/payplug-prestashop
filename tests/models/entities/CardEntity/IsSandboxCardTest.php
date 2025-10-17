<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class IsSandboxCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setIsSandbox(true);
    }

    public function testReturnIsSandbox()
    {
        $this->assertSame(
            true,
            $this->entity->getIsSandbox()
        );
    }

    public function testSandboxIsABool()
    {
        $this->assertTrue(
            is_bool($this->entity->getIsSandbox())
        );
    }
}
