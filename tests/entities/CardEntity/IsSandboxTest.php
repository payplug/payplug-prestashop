<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PHPUnit\Framework\TestCase;

final class IsSandboxTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setIsSandbox(true);
    }

    public function testReturnIsSandbox(): void
    {
        $this->assertSame(
            true,
            $this->card->isSandbox()
        );
    }

    public function testSandboxIsABool(): void
    {
        $this->assertIsBool(
            $this->card->isSandbox()
        );
    }
}
