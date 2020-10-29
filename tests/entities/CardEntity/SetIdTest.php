<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PHPUnit\Framework\TestCase;

final class SetIdTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setId(42);
    }

    public function testUpdateId(): void
    {
        $this->card->setId(777);
        $this->assertSame(
            777,
            $this->card->getId()
        );
    }

    public function testReturnCardEntity(): void
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setId(777)
        );
    }

    public function testThrowExceptionWhenNotAnInt(): void
    {
        $this->expectException(TypeError::class);
        $this->card->setId('test');
    }
}
