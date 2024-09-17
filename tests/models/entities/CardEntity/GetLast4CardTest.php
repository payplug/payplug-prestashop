<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetLast4CardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setLast4('4242');
    }

    public function testReturnLast4()
    {
        $this->assertSame(
            '4242',
            $this->entity->getLast4()
        );
    }

    public function testLast4IsAnInt()
    {
        $this->assertTrue(
            is_string($this->entity->getLast4())
        );
    }
}
