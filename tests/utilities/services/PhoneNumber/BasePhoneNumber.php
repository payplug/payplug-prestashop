<?php

namespace PayPlug\tests\utilities\services\PhoneNumber;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use PayPlug\src\utilities\services\PhoneNumber;
use PayPlug\tests\FormatDataProvider;
use PayPlug\tests\mock\MockHelper;

/**
 * @internal
 * @coversNothing
 */
class BasePhoneNumber extends MockeryTestCase
{
    use FormatDataProvider;

    public $dependencies;
    public $logger_adapter;
    public $plugin;
    public $service;
    public $phone_number_util;

    public $logs;

    public function setUp()
    {
        $this->dependencies = MockHelper::createMockFactory('PayPlug\classes\DependenciesClass');
        $this->plugin = \Mockery::mock('Plugin');
        $this->dependencies->name = 'payplug';
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);

        $this->logger_adapter = \Mockery::mock('LoggerAdapter');
        $this->phone_number_util = \Mockery::mock('PhoneNumberUtil');
        $this->logger_adapter
            ->shouldReceive('addLog')
            ->andReturnUsing(function ($log) {
                $this->logs[] = $log;

                return true;
            });
        $this->plugin->shouldReceive([
            'getLogger' => $this->logger_adapter,
        ]);
        $this->service = \Mockery::mock(PhoneNumber::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();
        $this->service->dependencies = $this->dependencies;
        $this->service->shouldReceive([
            'getLibInstance' => $this->phone_number_util,
        ]);
    }
}
