<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PHPUnit\Framework\TestCase;

final class GetIdTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setId(42);
    }

    public function testReturnId(): void
    {
        $this->assertSame(
            42,
            $this->card->getId()
        );
    }

    public function testIdIsAnInt(): void
    {
        $this->assertIsInt(
            $this->card->getId()
        );
    }
}
