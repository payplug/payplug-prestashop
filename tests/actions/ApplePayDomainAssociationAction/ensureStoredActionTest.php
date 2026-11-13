<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

/**
 * @group unit
 * @group action
 */
class ensureStoredActionTest extends BaseApplePayDomainAssociationAction
{
    public function testAsksTheServiceToStoreTheFileWhenMissing()
    {
        $this->service->shouldReceive('ensureStored')->once();

        $this->assertTrue($this->action->ensureStoredAction());
        $this->assertSame([], $this->logs);
    }

    public function testLogsAndNeverThrowsWhenTheServiceCannotBeBuilt()
    {
        $factory = \Mockery::mock('UnifiedApiPaymentServiceFactory');
        $factory->shouldReceive('createDomainAssociationService')->andThrow(new \RuntimeException('boom'));
        $this->useFactory($factory);

        $this->assertFalse($this->action->ensureStoredAction());
        $this->assertErrorLogged();
    }

    public function testNeverThrowsWhenTheLoggerFails()
    {
        $factory = \Mockery::mock('UnifiedApiPaymentServiceFactory');
        $factory->shouldReceive('createDomainAssociationService')->andThrow(new \RuntimeException('boom'));
        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')->once()->andThrow(new \Exception('log table missing'));
        $this->useFactory($factory, $logger);

        $this->assertFalse($this->action->ensureStoredAction());
    }
}
