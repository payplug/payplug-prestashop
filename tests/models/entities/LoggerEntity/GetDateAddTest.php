<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetDateAddTest extends BaseLoggerEntity
{
    public $date;

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
