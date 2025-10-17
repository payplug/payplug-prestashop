<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetDateUpdTest extends BaseLoggerEntity
{
    public $date;

    public function setUp()
    {
        parent::setUp();
        $this->date = '2021-12-31 23:59:42';
        $this->entity->setDateUpd($this->date);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->date,
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
