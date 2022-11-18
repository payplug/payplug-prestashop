<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetIdentifierCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIdentifier('identifier');
    }

    public function testReturnIdentifier()
    {
        $this->assertSame(
            'identifier',
            $this->card->getIdentifier()
        );
    }

    public function testIdentifierIsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getIdentifier())
        );
    }
}
