<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class SetIdTest extends BaseCardEntity
{
    public function testUpdateId()
    {
        $this->entity->setId($this->id);
        $this->assertSame(
            $this->id,
            $this->entity->getId()
        );
    }

    public function testReturnPaymentEntity()
    {
        $this->assertInstanceOf(
            CardEntity::class,
            $this->entity->setId($this->id)
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
