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
 */
final class GetMetadataTest extends TestCase
{
    protected $card;
    protected $metadata;

    protected function setUp()
    {
        $this->card = new CardEntity();
        $metadata = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->metadata = json_encode($metadata);
        $this->card->setMetadata(
            $this->metadata
        );
    }

    public function testReturnMetadata()
    {
        $this->assertSame(
            $this->metadata,
            $this->card->getMetadata()
        );
    }

    public function testMetadataIsAnInt()
    {
        $this->assertTrue(
            is_string($this->card->getMetadata())
        );
    }

    public function testMetadataIsAnJsonEncode()
    {
        $metadata = json_decode($this->card->getMetadata(), true);
        $this->assertTrue(
            is_array($metadata)
        );
    }
}
