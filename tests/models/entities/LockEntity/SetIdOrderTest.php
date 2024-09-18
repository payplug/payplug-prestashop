<?php

namespace PayPlug\tests\models\entities\LockEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class SetIdOrderTest extends BaseLockEntity
{
    public function testUpdateId()
    {
        $this->entity->setIdOrder('order_id');
        $this->assertSame(
            'order_id',
            $this->entity->getIdOrder()
        );
    }

    public function testReturnLockEntity()
    {
        $this->assertInstanceOf(
            LockEntity::class,
            $this->entity->setIdOrder('order_id')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $id
     */
    public function testThrowExceptionWhenNotAnInteger($id)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setIdOrder($id);
    }
}
