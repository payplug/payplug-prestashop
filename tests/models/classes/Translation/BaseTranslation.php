<?php

namespace PayPlug\tests\models\classes\Translation;

use PayPlug\src\models\classes\Translation;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseTranslation extends TestCase
{
    use FormatDataProvider;

    public $dependencies;
    public $classe;

    protected function setUp()
    {
        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
            ]);
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->dependencies->name = 'payplug';
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);
        $this->classe = \Mockery::mock(Translation::class, [$this->dependencies])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }
}
