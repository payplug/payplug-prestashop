<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetMetadataTest extends BaseCardEntity
{
    protected $metadata;

    public function setUp()
    {
        parent::setUp();
        $metadata = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->metadata = json_encode($metadata);
        $this->entity->setMetadata(
            $this->metadata
        );
    }

    public function testReturnMetadata()
    {
        $this->assertSame(
            $this->metadata,
            $this->entity->getMetadata()
        );
    }

    public function testMetadataIsAnInt()
    {
        $this->assertTrue(
            is_string($this->entity->getMetadata())
        );
    }

    public function testMetadataIsAnJsonEncode()
    {
        $metadata = json_decode($this->entity->getMetadata(), true);
        $this->assertTrue(
            is_array($metadata)
        );
    }
}
