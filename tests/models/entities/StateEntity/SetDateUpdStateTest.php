<?php

namespace PayPlug\tests\models\entities\StateEntity;

use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class SetDateUpdStateTest extends BaseStateEntity
{
    public function testUpdateDateUpd()
    {
        $this->entity->setDateUpd('2021-12-31 23:59:42');
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->entity->getDateUpd()
        );
    }

    public function testReturnStateEntity()
    {
        $this->assertInstanceOf(
            StateEntity::class,
            $this->entity->setDateUpd('1920-12-31 23:59:42')
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
        $this->entity->setDateUpd($date);
    }

    public function testThrowExceptionWhenNotWellFormatted()
    {
        $this->expectException(BadParameterException::class);
        $this->entity->setDateUpd('1er Janvier 1970');
    }
}
