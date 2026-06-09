<?php

namespace PayPlug\tests\models\entities\StateEntity;

use PayPlug\src\models\entities\StateEntity;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

abstract class BaseStateEntity extends TestCase
{
    use FormatDataProvider;
    protected $entity;

    public function setUp(): void
    {
        parent::setUp();
        $this->entity = \Mockery::mock(StateEntity::class)->makePartial();
    }
}
