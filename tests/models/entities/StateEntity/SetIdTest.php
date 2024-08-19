<?php

namespace PayPlug\tests\models\entities\StateEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class setIdTest extends BaseStateEntity
{
    public function testUpdateId()
    {
        $this->entity->setId(42);
        $this->assertSame(
            42,
            $this->entity->getId()
        );
    }

    public function testReturnStateEntity()
    {
        $this->assertInstanceOf(
            StateEntity::class,
            $this->entity->setId(42)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id
     */
    public function testThrowExceptionWhenNotAnInteger($id)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setId($id);
    }
}
