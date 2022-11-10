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
use PayPlug\src\models\entities\CardEntity;
use PHPUnit\Framework\TestCase;

/**
 * @group entity
 * @group card
 * @group card_entity
 *
 * @internal
 * @coversNothing
 */
final class GetFieldsSizeCardTest extends TestCase
{
    protected $card;
    protected $fieldsSize;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $this->fieldsSize = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->card->setFieldsSize($this->fieldsSize);
    }

    public function testReturnFieldsSize()
    {
        $this->assertSame(
            $this->fieldsSize,
            $this->card->getFieldsSize()
        );
    }

    public function testFieldsSizeIsArray()
    {
        $this->assertTrue(
            is_array($this->card->getFieldsSize())
        );
    }

    public function testFieldsSizeIsNotEmpty()
    {
        $this->assertFalse(
            empty($this->card->getFieldsSize())
        );
    }
}
