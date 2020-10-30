<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

final class SetIdCustomerTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setIdCustomer(42);
    }

    public function testUpdateIdCustomer(): void
    {
        $this->card->setIdCustomer(777);
        $this->assertSame(
            777,
            $this->card->getIdCustomer()
        );
    }

    public function testReturnCardEntity(): void
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setIdCustomer(777)
        );
    }

    public function testThrowExceptionWhenNotAnInt(): void
    {
        $this->expectException(BadParameterException::class);
        $this->card->setIdCustomer('test');
    }
}
