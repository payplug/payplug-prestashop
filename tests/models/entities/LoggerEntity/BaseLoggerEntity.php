<?php

namespace PayPlug\tests\models\entities\LoggerEntity;

use PayPlug\src\models\entities\LoggerEntity;
use PayPlug\tests\traits\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseLoggerEntity extends TestCase
{
    use FormatDataProvider;
    protected $entity;

    public function setUp()
    {
        parent::setUp();
        $this->entity = \Mockery::mock(LoggerEntity::class)->makePartial();
    }
}
