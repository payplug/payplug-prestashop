<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdCardCardTest extends BaseCardEntity
{
    protected function setUp()
    {
        parent::setUp();
        $this->entity->setIdCard('card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    public function testReturnIdCard()
    {
        $this->assertSame(
            'card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            $this->entity->getIdCard()
        );
    }

    public function testIdCardIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getIdCard())
        );
    }

    public function testIdCardHaveAValidFormat()
    {
        $this->assertRegExp(
            '/card_[a-z0-9]{32}/',
            $this->entity->getIdCard()
        );
    }
}
