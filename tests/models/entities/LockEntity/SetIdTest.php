<?php

namespace PayPlug\tests\models\entities\LockEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class SetIdTest extends BaseLockEntity
{
    public function testUpdateId()
    {
        $this->entity->setId(42);
        $this->assertSame(
            42,
            $this->entity->getId()
        );
    }

    public function testReturnLockEntity()
    {
        $this->assertInstanceOf(
            LockEntity::class,
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
