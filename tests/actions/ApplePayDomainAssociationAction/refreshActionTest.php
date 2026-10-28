<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

use PayplugUnifiedCore\Output\RefreshResult;

/**
 * @group unit
 * @group action
 */
class refreshActionTest extends BaseApplePayDomainAssociationAction
{
    public function testReportsAnUpdatedFile()
    {
        $this->service->shouldReceive('refresh')->once()->andReturn(RefreshResult::updated());

        $this->assertSame(
            ['success' => true, 'status' => 'updated', 'error' => null],
            $this->action->refreshAction()
        );
    }

    public function testReportsAnUnchangedFile()
    {
        $this->service->shouldReceive('refresh')->once()->andReturn(RefreshResult::unchanged());

        $this->assertSame(
            ['success' => true, 'status' => 'unchanged', 'error' => null],
            $this->action->refreshAction()
        );
    }

    public function testReportsAFailedRefreshWithItsRawError()
    {
        $this->service->shouldReceive('refresh')->once()->andReturn(
            RefreshResult::failed('Unexpected HTTP status 500 from secure.payplug.com.')
        );

        $this->assertSame(
            ['success' => false, 'status' => 'failed', 'error' => 'Unexpected HTTP status 500 from secure.payplug.com.'],
            $this->action->refreshAction()
        );
    }

    public function testReportsAFailureAndLogsWhenTheServiceCannotBeBuilt()
    {
        $factory = \Mockery::mock('UnifiedApiPaymentServiceFactory');
        $factory->shouldReceive('createDomainAssociationService')->andThrow(new \RuntimeException('<b>boom</b>'));
        $this->useFactory($factory);

        $this->assertSame(
            ['success' => false, 'status' => 'failed', 'error' => '<b>boom</b>'],
            $this->action->refreshAction()
        );
        $this->assertErrorLogged();
    }

    public function testReportsAFailureWhenTheServiceAnswersSomethingElse()
    {
        $this->service->shouldReceive('refresh')->once()->andReturn(null);

        $result = $this->action->refreshAction();

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $result['status']);
        $this->assertErrorLogged();
    }
}
