<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

use PayPlug\src\unified\contract\UnifiedDomainAssociationStorage;
use PayplugUnifiedCore\Services\DomainAssociationService;
use PayplugUnifiedCore\Validators\DomainAssociationValidator;

/**
 * @group unit
 * @group action
 */
class getRouteResponseTest extends BaseApplePayDomainAssociationAction
{
    const PATH = '/.well-known/apple-developer-merchantid-domain-association';

    public function testIgnoresAnotherPath()
    {
        $this->service->shouldNotReceive('resolve');

        $this->assertNull($this->action->getRouteResponse('GET', '/.well-known/security.txt'));
    }

    public function unhandledMethodProvider()
    {
        yield 'POST' => ['POST'];

        yield 'PUT' => ['PUT'];

        yield 'OPTIONS' => ['OPTIONS'];

        yield 'empty' => [''];
    }

    /**
     * @dataProvider unhandledMethodProvider
     *
     * @param string $method
     */
    public function testIgnoresAnUnhandledMethod($method)
    {
        $this->service->shouldNotReceive('resolve');

        $this->assertNull($this->action->getRouteResponse($method, self::PATH));
    }

    public function contentProvider()
    {
        yield 'LF' => ["ABCD\n"];

        yield 'CRLF' => ["ABCD\r\n"];

        yield 'no trailing newline' => ['ABCD'];

        yield 'repository domain association file' => [
            file_get_contents(__DIR__ . '/../../../.well-known/apple-developer-merchantid-domain-association'),
        ];
    }

    /**
     * @dataProvider contentProvider
     *
     * @param string $content
     */
    public function testServesTheStoredFileByteForByte($content)
    {
        $this->service->shouldReceive('resolve')->once()->andReturn($content);

        $response = $this->action->getRouteResponse('GET', self::PATH . '?utm=1');

        $this->assertSame(200, $response['status']);
        $this->assertSame([
            'Content-Type: text/plain',
            'Cache-Control: public, max-age=3600',
            'Content-Length: ' . strlen($content),
            'X-Content-Type-Options: nosniff',
        ], $response['headers']);
        $this->assertSame($content, $response['body']);
    }

    public function testMatchesTheMethodCaseInsensitively()
    {
        $this->service->shouldReceive('resolve')->andReturn("ABCD\n");

        $this->assertSame(200, $this->action->getRouteResponse('get', self::PATH)['status']);
    }

    public function testAnswersHeadWithTheHeadersOnly()
    {
        $this->service->shouldReceive('resolve')->andReturn("ABCD\n");

        $response = $this->action->getRouteResponse('HEAD', self::PATH);

        $this->assertSame(200, $response['status']);
        $this->assertContains('Content-Length: 5', $response['headers']);
        $this->assertSame('', $response['body']);
    }

    public function testAnswersNotFoundWithoutCacheWhenNothingValidIsStored()
    {
        $this->service->shouldReceive('resolve')->andReturn(null);

        $this->assertSame(
            ['status' => 404, 'headers' => ['Cache-Control: no-store'], 'body' => ''],
            $this->action->getRouteResponse('GET', self::PATH)
        );
        $this->assertSame([], $this->logs);
    }

    public function testAnswersNotFoundAndLogsWhenTheServiceCannotBeBuilt()
    {
        $factory = \Mockery::mock('UnifiedApiPaymentServiceFactory');
        $factory->shouldReceive('createDomainAssociationService')->andThrow(new \RuntimeException('boom'));
        $this->useFactory($factory);

        $response = $this->action->getRouteResponse('GET', self::PATH);

        $this->assertSame(404, $response['status']);
        $this->assertSame(['Cache-Control: no-store'], $response['headers']);
        $this->assertErrorLogged();
        $this->assertStringContainsString('boom', $this->logs[0]['message']);
    }

    public function testServesAValidProductionFileStoredThroughTheModuleStorage()
    {
        $content = strtoupper(bin2hex(json_encode(['pspId' => DomainAssociationValidator::PROD_PSP_ID, 'version' => 1]))) . "\n";
        $rows = [];
        $configuration = \Mockery::mock('ConfigurationAdapter');
        $configuration->shouldReceive('getGlobalValue')->andReturnUsing(function ($key) use (&$rows) {
            return isset($rows[$key]) ? $rows[$key] : false;
        });
        $configuration->shouldReceive('updateValue')->andReturnUsing(function ($key, $value) use (&$rows) {
            $rows[$key] = $value;

            return true;
        });
        $storage_dependencies = \Mockery::mock('StorageDependencies');
        $storage_plugin = \Mockery::mock('StoragePlugin');
        $storage_dependencies->shouldReceive('getPlugin')->andReturn($storage_plugin);
        $storage_plugin->shouldReceive('getConfiguration')->andReturn($configuration);
        $storage = new UnifiedDomainAssociationStorage($storage_dependencies);
        $storage->writeContent($content);

        $http = \Mockery::mock('PayplugUnifiedCore\Contracts\IUnifiedApiHttpClient');
        $http->shouldNotReceive('get');
        $logger = \Mockery::mock('PayplugUnifiedCore\Contracts\ILogger');
        $factory = \Mockery::mock('UnifiedApiPaymentServiceFactory');
        $factory->shouldReceive('createDomainAssociationService')
            ->andReturn(new DomainAssociationService($http, $storage, $logger));
        $this->useFactory($factory);

        $response = $this->action->getRouteResponse('GET', self::PATH);

        $this->assertSame(200, $response['status']);
        $this->assertSame($content, $response['body']);
    }
}
