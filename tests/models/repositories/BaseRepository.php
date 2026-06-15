<?php

namespace PayPlug\tests\models\repositories;

use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

abstract class BaseRepository extends TestCase
{
    use FormatDataProvider;

    protected $dependencies;
    protected $repository;
    protected $engine;
    protected $entity;
    protected $entity_id;

    public function setUp(): void
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([]);
        $this->engine = 'sql_engine';
        $this->entity_id = 42;
        $this->entity = \Mockery::mock('EntityObject');
    }

    public function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
