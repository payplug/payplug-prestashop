<?php

namespace PayPlug\tests\models\entities\CardEntity;

use PayPlug\src\models\entities\CardEntity;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseCardEntity extends TestCase
{
    use FormatDataProvider;
    protected $entity;

    protected function setUp()
    {
        parent::setUp();
        $this->entity = \Mockery::mock(CardEntity::class)->makePartial();
    }
}
