<?php

namespace PayPlug\tests\models\entities\StateEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class SetTypeStateTest extends BaseStateEntity
{
    public function testUpdateType()
    {
        $this->entity->setType('another_type');
        $this->assertSame(
            'another_type',
            $this->entity->getType()
        );
    }

    public function testReturnStateEntity()
    {
        $this->assertInstanceOf(
            StateEntity::class,
            $this->entity->setType('another_type')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $type
     */
    public function testThrowExceptionWhenNotAString($type)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setType($type);
    }
}
