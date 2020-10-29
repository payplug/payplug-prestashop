<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PHPUnit\Framework\TestCase;

final class GetIdCustomerTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setIdCustomer(42);
    }

    public function testReturnIdCustomer(): void
    {
        $this->assertSame(
            42,
            $this->card->getIdCustomer()
        );
    }

    public function testIdCustomerIsAnInt(): void
    {
        $this->assertIsInt(
            $this->card->getIdCustomer()
        );
    }
}
