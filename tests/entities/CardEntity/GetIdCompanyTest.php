<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PHPUnit\Framework\TestCase;

final class GetIdCompanyTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setIdCompany(42);
    }

    public function testReturnIdCompany(): void
    {
        $this->assertSame(
            42,
            $this->card->getIdCompany()
        );
    }

    public function testIdCompanyIsAnInt(): void
    {
        $this->assertIsInt(
            $this->card->getIdCompany()
        );
    }
}
