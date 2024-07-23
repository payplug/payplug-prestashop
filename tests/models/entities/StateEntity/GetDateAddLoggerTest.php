<?php

namespace PayPlug\tests\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class GetDateAddStateTest extends BaseStateEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setDateAdd('2021-12-31 23:59:42');
    }

    public function testReturnDateAdd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->entity->getDateAdd()
        );
    }

    public function testDateAddIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getDateAdd())
        );
    }

    public function testDateAddHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->entity->getDateAdd()
        );
    }
}
