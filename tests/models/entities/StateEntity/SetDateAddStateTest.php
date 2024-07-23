<?php

namespace PayPlug\tests\models\entities\StateEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class SetDateAddStateTest extends BaseStateEntity
{
    public function testUpdateDateAdd()
    {
        $this->entity->setDateAdd('1920-12-31 23:59:42');
        $this->assertSame(
            '1920-12-31 23:59:42',
            $this->entity->getDateAdd()
        );
    }

    public function testReturnStateEntity()
    {
        $this->assertInstanceOf(
            StateEntity::class,
            $this->entity->setDateAdd('1920-12-31 23:59:42')
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date
     */
    public function testThrowExceptionWhenNotAString($date)
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateAdd($date);
    }

    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateAdd('1er Janvier 1970');
    }
}
