<?php

use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class IsSandboxCardTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIsSandbox(true);
    }

    public function testReturnIsSandbox()
    {
        $this->assertSame(
            true,
            $this->card->getIsSandbox()
        );
    }

    public function testSandboxIsABool()
    {
        $this->assertTrue(
            is_bool($this->card->getIsSandbox())
        );
    }
}
