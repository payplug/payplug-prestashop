<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIdentifierCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIdentifier('identifier');
    }

    public function testUpdateIdentifier()
    {
        $this->card->setIdentifier('new_identifier');
        $this->assertSame(
            'new_identifier',
            $this->card->getIdentifier()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setIdentifier('identifier')
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
        $this->card->setIdentifier(42);
    }
}
