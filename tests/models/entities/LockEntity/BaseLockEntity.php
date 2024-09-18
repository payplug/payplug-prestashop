<?php

namespace PayPlug\tests\models\entities\LockEntity;

use PayPlug\src\models\entities\LockEntity;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseLockEntity extends TestCase
{
    use FormatDataProvider;
    protected $entity;

    public function setUp()
    {
        parent::setUp();
        $this->entity = \Mockery::mock(LockEntity::class)->makePartial();
    }
}
