<?php

namespace PayPlug\tests\models\entities\LockEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class SetDateAddTest extends BaseLockEntity
{
    public function testUpdateDateAdd()
    {
        $this->entity->setDateAdd('1920-12-31 23:59:42');
        $this->assertSame(
            '1920-12-31 23:59:42',
            $this->entity->getDateAdd()
        );
    }

    public function testReturnLockEntity()
    {
        $this->assertInstanceOf(
            LockEntity::class,
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
