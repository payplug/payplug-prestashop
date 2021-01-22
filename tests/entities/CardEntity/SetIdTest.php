<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

declare(strict_types=1);

use PayPlug\src\entities\CardEntity;
use PayPlug\src\exceptions\BadParameterException;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
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

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAnInt(): void
    {
        $this->expectException(BadParameterException::class);
        $this->card->setId('test');
    }
}
