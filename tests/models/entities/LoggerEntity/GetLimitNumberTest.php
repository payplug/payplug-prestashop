<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

/**
 * @group entity
 * @group logger
 * @group logger_entity
 */
final class GetLimitNumberTest extends BaseLoggerEntity
{
    public $limit_number;

    public function setUp()
    {
        parent::setUp();
        $this->limit_number = 4000;
    }

    public function testReturntLimitNumber()
    {
        $this->assertSame(
            $this->limit_number,
            $this->entity->getLimitNumber()
        );
    }

    public function testtLimitNumberIsAnInt()
    {
        $this->assertTrue(
            is_int($this->entity->getLimitNumber())
        );
    }
}
