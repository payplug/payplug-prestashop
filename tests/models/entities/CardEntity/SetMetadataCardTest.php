<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetMetadataCardTest extends BaseCardEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setMetadata('metadata');
    }

    public function testUpdateMetadata()
    {
        $this->entity->setMetadata('new_metadata');
        $this->assertSame(
            'new_metadata',
            $this->entity->getMetadata()
        );
    }

    public function testReturnCardEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setMetadata('metadata')
        );
    }

    /**
     * @group entity_exception
     * @group card_exception
     * @group card_entity_exception
     * @group exception
     */
    public function testThrowExceptionWhenNotAString()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setMetadata(42);
    }
}
