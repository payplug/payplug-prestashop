<?php

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIsSandboxTest extends TestCase
{
    protected $card;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->card->setIsSandbox(true);
    }

    public function testUpdateIsSandbox()
    {
        $this->card->setIsSandbox(false);
        $this->assertSame(
            false,
            $this->card->getIsSandbox()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setIsSandbox(false)
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
        $this->card->setIsSandbox('test');
    }
}
