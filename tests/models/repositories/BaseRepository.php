<?php

namespace PayPlug\tests\models\repositories;

use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseRepository extends TestCase
{
    use FormatDataProvider;

    protected $dependencies;
    protected $repository;

    protected function setUp()
    {
        parent::setUp();
        $this->dependencies = \Mockery::mock('Dependencies');
        $this->dependencies->name = 'payplug';
        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([]);
    }
}
