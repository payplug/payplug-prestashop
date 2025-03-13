<?php

namespace PayPlug\tests\models\entities\StateEntity;

use PayPlug\src\models\entities\StateEntity;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseStateEntity extends TestCase
{
    use FormatDataProvider;
    protected $entity;

    public function setUp()
    {
        parent::setUp();
        $this->entity = \Mockery::mock(StateEntity::class)->makePartial();
    }
}
