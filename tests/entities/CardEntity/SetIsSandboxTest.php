<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PHPUnit\Framework\TestCase;

final class SetIsSandboxTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setIsSandbox(true);
    }

    public function testUpdateIsSandbox(): void
    {
        $this->card->setIsSandbox(false);
        $this->assertSame(
            false,
            $this->card->isSandbox()
        );
    }

    public function testReturnCardEntity(): void
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setIsSandbox(false)
        );
    }

    public function testThrowExceptionWhenNotABool(): void
    {
        $this->expectException(TypeError::class);
        $this->card->setIdCompany('test');
    }
}
