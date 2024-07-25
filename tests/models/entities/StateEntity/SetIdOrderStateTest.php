<?php

namespace PayPlug\tests\models\entities\StateEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class setIdOrderStateTest extends BaseStateEntity
{
    public function testUpdateId()
    {
        $this->entity->setIdOrderState(42);
        $this->assertSame(
            42,
            $this->entity->getIdOrderState()
        );
    }

    public function testReturnStateEntity()
    {
        $this->assertInstanceOf(
            StateEntity::class,
            $this->entity->setIdOrderState(42)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_order_state
     */
    public function testThrowExceptionWhenNotAnInteger($id_order_state)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setIdOrderState($id_order_state);
    }
}
