<?php

namespace PayPlug\tests\models\entities\LockEntity;

/**
 * @group entity
 * @group lock
 * @group lock_entity
 */
final class GetDateAddTest extends BaseLockEntity
{
    private $date;

    public function setUp()
    {
        parent::setUp();
        $this->date = '2021-12-31 23:59:42';
        $this->entity->setDateAdd($this->date);
    }

    public function testReturnDateAdd()
    {
        $this->assertSame(
            $this->date,
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
