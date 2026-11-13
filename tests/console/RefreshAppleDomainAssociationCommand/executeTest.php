<?php

namespace PayPlug\tests\console\RefreshAppleDomainAssociationCommand;

use PayPlug\src\console\RefreshAppleDomainAssociationCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group unit
 * @group console
 */
class executeTest extends TestCase
{
    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testIsNamedAfterTheTicketCommand()
    {
        $this->assertSame('payplug:apple-pay:refresh-well-known', (new RefreshAppleDomainAssociationCommand())->getName());
    }

    public function successfulResultProvider()
    {
        yield 'updated' => ['updated'];

        yield 'unchanged' => ['unchanged'];
    }

    /**
     * @dataProvider successfulResultProvider
     *
     * @param string $status
     */
    public function testExitsWithZeroWhenTheFileIsSynchronised($status)
    {
        $tester = $this->runCommand(['success' => true, 'status' => $status, 'error' => null]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString($status, $tester->getDisplay());
    }

    public function testExitsWithOneAndPrintsTheRawErrorWhenTheSynchronisationFails()
    {
        $error = 'Source unreachable: <b>boom</b> & bad < thing';

        $tester = $this->runCommand(['success' => false, 'status' => 'failed', 'error' => $error]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString($error, $tester->getDisplay());
    }

    public function testDoesNotResolveTheActionWhenBuilt()
    {
        $command = \Mockery::mock(RefreshAppleDomainAssociationCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $command->shouldNotReceive('getAction');

        $command->__construct();

        $this->assertSame('payplug:apple-pay:refresh-well-known', $command->getName());
    }

    private function runCommand(array $result)
    {
        $action = \Mockery::mock('ApplePayDomainAssociationAction');
        $action->shouldReceive('refreshAction')->once()->andReturn($result);
        $command = \Mockery::mock(RefreshAppleDomainAssociationCommand::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $command->__construct();
        $command->shouldReceive('getAction')->andReturn($action);

        $tester = new CommandTester($command);
        $tester->execute([]);

        return $tester;
    }
}
