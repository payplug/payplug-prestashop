<?php

namespace PayPlug\tests\models\entities\LockEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class SetIdCartTest extends BaseLockEntity
{
    public function testUpdateId()
    {
        $this->entity->setIdCart(42);
        $this->assertSame(
            42,
            $this->entity->getIdCart()
        );
    }

    public function testReturnLockEntity()
    {
        $this->assertInstanceOf(
            LockEntity::class,
            $this->entity->setIdCart(42)
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
        $this->entity->setIdCart($id);
    }
}
