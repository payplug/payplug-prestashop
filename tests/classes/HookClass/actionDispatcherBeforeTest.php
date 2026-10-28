<?php

namespace PayPlug\tests\classes\HookClass;

/**
 * @group unit
 * @group class
 * @group hook_class
 */
class actionDispatcherBeforeTest extends BaseHookClass
{
    const PATH = '/.well-known/apple-developer-merchantid-domain-association';
    const SERVICE = 'payplug.action.apple_pay_domain_association';

    private $domain_association_action;
    private $logger;
    private $logs = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->logs = [];

        $this->domain_association_action = \Mockery::mock('ApplePayDomainAssociationAction');
        $this->module->shouldReceive('getService')
            ->with(self::SERVICE)
            ->andReturn($this->domain_association_action)
            ->byDefault();

        $this->logger = \Mockery::mock('Logger');
        $this->logger->shouldReceive('addLog')->andReturnUsing(function ($message, $level = 'info') {
            $this->logs[] = ['level' => $level, 'message' => $message];

            return true;
        })->byDefault();
        $this->plugin->shouldReceive('getLogger')->andReturn($this->logger);
    }

    public function testReturnsTrueWhenTheActionSentTheResponse()
    {
        $this->domain_association_action->shouldReceive('serveAction')
            ->once()
            ->with('GET', self::PATH)
            ->andReturn(true);

        $this->assertTrue($this->hook->actionDispatcherBefore('GET', self::PATH));
        $this->assertSame([], $this->logs);
    }

    public function testReturnsFalseWhenTheRequestIsNotForTheAction()
    {
        $this->domain_association_action->shouldReceive('serveAction')
            ->once()
            ->with('POST', self::PATH)
            ->andReturn(false);

        $this->assertFalse($this->hook->actionDispatcherBefore('POST', self::PATH));
        $this->assertSame([], $this->logs);
    }

    public function testReturnsFalseAndLogsWhenTheActionCannotBeBuilt()
    {
        $this->module->shouldReceive('getService')
            ->with(self::SERVICE)
            ->andThrow(new \RuntimeException('container broken'));

        $this->assertFalse($this->hook->actionDispatcherBefore('GET', self::PATH));
        $this->assertCount(1, $this->logs);
        $this->assertSame('error', $this->logs[0]['level']);
        $this->assertStringContainsString('HookClass::actionDispatcherBefore', $this->logs[0]['message']);
        $this->assertStringContainsString('container broken', $this->logs[0]['message']);
    }

    public function testReturnsFalseAndLogsWhenTheActionIsNotRegistered()
    {
        $this->module->shouldReceive('getService')->with(self::SERVICE)->andReturn(null);

        $this->assertFalse($this->hook->actionDispatcherBefore('GET', self::PATH));
        $this->assertCount(1, $this->logs);
        $this->assertSame('error', $this->logs[0]['level']);
    }

    public function testReturnsFalseWhenTheLoggerFails()
    {
        $this->module->shouldReceive('getService')
            ->with(self::SERVICE)
            ->andThrow(new \RuntimeException('container broken'));
        $this->logger->shouldReceive('addLog')->once()->andThrow(new \Exception('log table missing'));

        $this->assertFalse($this->hook->actionDispatcherBefore('GET', self::PATH));
    }
}
