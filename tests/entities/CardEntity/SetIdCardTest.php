<?php declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

final class SetIdCardTest extends TestCase
{
    protected $card;

    protected function setUp(): void
    {
        $this->card = new CardEntity();
        $this->card->setIdCard('card_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
    }

    public function testUpdateIdCard(): void
    {
        $this->card->setIdCard('card_azertyuiop1234567890qsdfghjklm12');
        $this->assertSame(
            'card_azertyuiop1234567890qsdfghjklm12',
            $this->card->getIdCard()
        );
    }

    public function testReturnCardEntity(): void
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->card->setIdCard('card_azertyuiop1234567890qsdfghjklm12')
        );
    }

    public function testThrowExceptionWhenNotAString(): void
    {
        $this->expectException(BadParameterException::class);
        $this->card->setIdCard(42);
    }

    public function testThrowExceptionWhenNotWellFormatted(): void
    {
        $this->expectException(BadParameterException::class);
        $this->card->setIdCard('Kart_AZERT&é"');
    }
}
