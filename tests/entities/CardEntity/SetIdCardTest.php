<?php declare(strict_types=1);
/**
 * 2013 - 2020 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

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
