<?php

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
