<?php

namespace PayPlug\tests\models\entities\StateEntity;

/**
 * @group entity
 * @group state
 * @group state_entity
 */
final class GetDateUpdStateTest extends BaseStateEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setDateUpd('2021-12-31 23:59:42');
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            '2021-12-31 23:59:42',
            $this->entity->getDateUpd()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_string($this->entity->getDateUpd())
        );
    }

    public function testDateUpdHaveAValidDatetimeFormat()
    {
        $this->assertRegExp(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $this->entity->getDateUpd()
        );
    }
}
