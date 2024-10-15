<?php

namespace PayPlug\tests\models\classes\Merchant;

use PayPlug\src\models\classes\Merchant;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

class BaseMerchant extends TestCase
{
    use FormatDataProvider;

    protected $dependencies;
    protected $logger_adapter;
    protected $class;
    protected $plugin;

    public function setUp()
    {
        $this->dependencies = \Mockery::mock('DependenciesClass');
        $this->logger_adapter = \Mockery::mock('LoggerAdapter');
        $this->plugin = \Mockery::mock('Plugin');

        $this->logger_adapter->shouldReceive([
            'addLog' => true,
        ]);
        $this->plugin->shouldReceive([
            'getLogger' => $this->logger_adapter,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->class = \Mockery::mock(Merchant::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->class->dependencies = $this->dependencies;
    }
}
