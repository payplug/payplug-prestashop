<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetLimitDateTest extends BaseLoggerEntity
{
    public $limit_date;

    public function setUp()
    {
        parent::setUp();
        $this->limit_date = 'P1M';
    }

    public function testReturntLimitDate()
    {
        $this->assertSame(
            $this->limit_date,
            $this->entity->getLimitDate()
        );
    }

    public function testtLimitDateIsAnInt()
    {
        $this->assertTrue(
            is_string($this->entity->getLimitDate())
        );
    }
}
