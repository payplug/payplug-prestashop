<?php

namespace PayPlug\tests\models\entities\QueueEntity;

/**
 * @group entity
 * @group queue
 * @group queue_entity
 */
final class GetTreatedTest extends BaseQueueEntity
{
    public function setUp()
    {
        parent::setUp();
        $this->entity->setTreated($this->treated);
    }

    public function testReturnDateUpd()
    {
        $this->assertSame(
            $this->treated,
            $this->entity->getTreated()
        );
    }

    public function testDateUpdIsAString()
    {
        $this->assertTrue(
            is_bool($this->entity->getTreated())
        );
    }
}
