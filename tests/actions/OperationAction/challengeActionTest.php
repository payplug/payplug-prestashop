<?php

namespace PayPlug\tests\actions\OperationAction;

use PayPlug\src\actions\OperationAction;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group action
 * @group operation_action
 */
class challengeActionTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testReturnsFalseWhenIdCartIsInvalid()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $result = $action->challengeAction(['id_cart' => 0]);

        $this->assertSame(['result' => false], $result);
    }

    public function testReturnsTheCachedChallengeHtml()
    {
        [$action, $mocks] = $this->mockActionWithFactory();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_challenge_html:42')->andReturn('<html>3DS form</html>');
        $mocks['logger']->shouldReceive('error')->never();

        $result = $action->challengeAction(['id_cart' => 42]);

        $this->assertSame(['result' => true, 'html' => '<html>3DS form</html>'], $result);
    }

    public function testReturnsFalseAndLogsWhenNoChallengeIsCached()
    {
        [$action, $mocks] = $this->mockActionWithFactory();

        $mocks['token_cache']->shouldReceive('get')->with('uhf_challenge_html:42')->andReturn(null);
        $mocks['logger']->shouldReceive('error')->once();

        $result = $action->challengeAction(['id_cart' => 42]);

        $this->assertSame(['result' => false], $result);
    }

    /**
     * @return array{0: OperationAction, 1: array<string, object>}
     */
    private function mockActionWithFactory()
    {
        $action = (new \ReflectionClass(OperationAction::class))->newInstanceWithoutConstructor();

        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->name = 'payplug';

        $plugin = \Mockery::mock('Plugin');
        $module_adapter = \Mockery::mock('ModuleAdapter');
        $module = \Mockery::mock('Module');
        $factory = \Mockery::mock('Factory');
        $logger = \Mockery::mock('Logger');
        $token_cache = \Mockery::mock('TokenCache');

        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $plugin->shouldReceive('getModule')->andReturn($module_adapter);
        $module_adapter->shouldReceive('getInstanceByName')->with('payplug')->andReturn($module);
        $module->shouldReceive('getService')->with('payplug.utilities.service.unified_api_payment_service_factory')->andReturn($factory);
        $factory->shouldReceive('createTokenCache')->andReturn($token_cache);
        $factory->shouldReceive('createLogger')->andReturn($logger);

        $action->dependencies = $dependencies;

        return [$action, [
            'logger' => $logger,
            'token_cache' => $token_cache,
        ]];
    }
}
