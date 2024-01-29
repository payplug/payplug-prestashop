<?php

namespace PayPlug\tests\actions\RefundAction;

use PayPlug\src\actions\RefundAction;
use PayPlug\src\models\classes\Translation;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

class BaseRefundAction extends TestCase
{
    use FormatDataProvider;
    public $action;
    public $dependencies;
    public $plugin;
    protected $logger;
    protected $toolsAdapter;
    protected $payment_validator;

    protected function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');

        $this->plugin = \Mockery::mock('Plugin');
        $this->action = \Mockery::mock(RefundAction::class, [$this->dependencies])->makePartial();
        $this->toolsAdapter = \Mockery::mock('ToolsAdapter');
        $this->payment_validator = \Mockery::mock('PaymentValidator');
        $this->dependencies->apiClass = \Mockery::mock('ApiClass');
        $this->dependencies->installmentClass = \Mockery::mock('InstallmentClass');

        $this->dependencies
            ->shouldReceive(
                [
                    'getPlugin' => $this->plugin,
                    'getValidators' => [
                        'payment' => $this->payment_validator,
                    ],
                ]
            );
        $this->logger = \Mockery::mock('Logger');
        $this->logger
            ->shouldReceive([
                                'addLog' => true,
                            ]);
        $this->translation = \Mockery::mock(Translation::class, [$this->dependencies])->makePartial();
        $this->translation
            ->shouldReceive('l')
            ->andReturnUsing(
                function ($str) {
                    return $str;
                }
            );

        $this->plugin
            ->shouldReceive([

                                'getLogger' => $this->logger,
                                'getTools' => $this->toolsAdapter,

                            ]);
    }
}
