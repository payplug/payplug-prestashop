<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PHPUnit\Framework\TestCase;

final class GetIdCardTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setIdCard('card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    public function testReturnIdCard(): void
    {
        $this->assertSame(
            'card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            $this->card->getIdCard()
        );
    }

    public function testIdCardIsAString(): void
    {
        $this->assertIsString(
            $this->card->getIdCard()
        );
    }

    public function testIdCardHaveAValidFormat(): void
    {
        $this->assertMatchesRegularExpression(
            '/card_[a-z0-9]{32}/',
            $this->card->getIdCard()
        );
    }
}
