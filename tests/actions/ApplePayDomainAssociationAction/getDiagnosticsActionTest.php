<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

/**
 * @group unit
 * @group action
 */
class getDiagnosticsActionTest extends BaseApplePayDomainAssociationAction
{
    private $root;

    public function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir() . '/payplug-apple-pay-' . uniqid('', true);
        mkdir($this->root);
        $this->action->shouldReceive('getRootCandidates')->andReturn([$this->root])->byDefault();
    }

    public function tearDown(): void
    {
        $file = $this->root . '/.well-known/apple-developer-merchantid-domain-association';
        if (is_file($file)) {
            unlink($file);
        }
        if (is_dir($this->root . '/.well-known')) {
            rmdir($this->root . '/.well-known');
        }
        rmdir($this->root);
        parent::tearDown();
    }

    public function testDescribesASynchronisedFile()
    {
        $this->service->shouldReceive('resolve')->andReturn("ABCD\n");
        $this->storage->shouldReceive('readStatus')->andReturn([
            'lastAttemptAt' => 20,
            'lastSuccessAt' => 20,
            'lastError' => null,
        ]);

        $this->assertSame([
            'state' => 'synced',
            'last_attempt_at' => 20,
            'last_success_at' => 20,
            'last_error' => null,
            'root_file_present' => false,
        ], $this->action->getDiagnosticsAction());
    }

    public function testDescribesAMissingFile()
    {
        $this->service->shouldReceive('resolve')->andReturn(null);
        $this->storage->shouldReceive('readStatus')->andReturn([]);

        $this->assertSame([
            'state' => 'not_synced',
            'last_attempt_at' => null,
            'last_success_at' => null,
            'last_error' => null,
            'root_file_present' => false,
        ], $this->action->getDiagnosticsAction());
    }

    public function testEscapesTheLastError()
    {
        $this->service->shouldReceive('resolve')->andReturn("ABCD\n");
        $this->storage->shouldReceive('readStatus')->andReturn([
            'lastAttemptAt' => 30,
            'lastSuccessAt' => 20,
            'lastError' => 'Source unreachable: <img src=x onerror=alert(1)> & \'q\' "d"',
        ]);

        $diagnostics = $this->action->getDiagnosticsAction();

        $this->assertSame('synced', $diagnostics['state']);
        $this->assertSame(30, $diagnostics['last_attempt_at']);
        $this->assertSame(20, $diagnostics['last_success_at']);
        $this->assertSame(
            'Source unreachable: &lt;img src=x onerror=alert(1)&gt; &amp; &#039;q&#039; &quot;d&quot;',
            $diagnostics['last_error']
        );
    }

    public function testIgnoresStatusValuesOfTheWrongType()
    {
        $this->service->shouldReceive('resolve')->andReturn(null);
        $this->storage->shouldReceive('readStatus')->andReturn([
            'lastAttemptAt' => 'abc',
            'lastSuccessAt' => 1.5,
            'lastError' => ['nested'],
        ]);

        $diagnostics = $this->action->getDiagnosticsAction();

        $this->assertNull($diagnostics['last_attempt_at']);
        $this->assertNull($diagnostics['last_success_at']);
        $this->assertNull($diagnostics['last_error']);
    }

    public function testDetectsAFileAtTheWebRoot()
    {
        mkdir($this->root . '/.well-known');
        file_put_contents($this->root . '/.well-known/apple-developer-merchantid-domain-association', 'ABCD');
        $this->service->shouldReceive('resolve')->andReturn(null);
        $this->storage->shouldReceive('readStatus')->andReturn([]);

        $this->assertTrue($this->action->getDiagnosticsAction()['root_file_present']);
    }

    public function testReportsANotSynchronisedFileAndLogsWhenTheStorageFails()
    {
        $this->service->shouldReceive('resolve')->andReturn("ABCD\n");
        $this->storage->shouldReceive('readStatus')->andThrow(new \RuntimeException('db down'));

        $diagnostics = $this->action->getDiagnosticsAction();

        $this->assertSame('not_synced', $diagnostics['state']);
        $this->assertNull($diagnostics['last_error']);
        $this->assertErrorLogged();
    }

    public function testListsTheDocumentRootAndThePrestashopRootAsCandidates()
    {
        $action = \Mockery::mock('PayPlug\src\actions\ApplePayDomainAssociationAction')
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $action->dependencies = $this->dependencies;
        $this->constant_adapter->shouldReceive('get')->with('_PS_ROOT_DIR_')->andReturn('/var/www/shop');
        $server = $_SERVER;
        $_SERVER['DOCUMENT_ROOT'] = '/var/www/shop/';

        try {
            $candidates = $action->getRootCandidates();
        } finally {
            $_SERVER = $server;
        }

        $this->assertSame(['/var/www/shop'], $candidates);
    }
}
