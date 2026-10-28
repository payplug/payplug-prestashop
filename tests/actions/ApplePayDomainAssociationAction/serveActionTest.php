<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

/**
 * @group unit
 * @group action
 */
class serveActionTest extends BaseApplePayDomainAssociationAction
{
    const PATH = '/.well-known/apple-developer-merchantid-domain-association';

    public function testSendsNothingForAnotherPath()
    {
        $this->action->shouldNotReceive('discardOutputBuffers', 'sendStatus', 'sendHeader');
        $this->service->shouldNotReceive('resolve');
        $this->expectOutputString('');

        $this->assertFalse($this->action->serveAction('GET', '/.well-known/security.txt'));
    }

    public function testSendsNothingForAnUnhandledMethod()
    {
        $this->action->shouldNotReceive('discardOutputBuffers', 'sendStatus', 'sendHeader');
        $this->service->shouldNotReceive('resolve');
        $this->expectOutputString('');

        $this->assertFalse($this->action->serveAction('POST', self::PATH));
    }

    public function testSendsTheStoredFileByteForByte()
    {
        $content = "ABCD\r\n";
        $this->service->shouldReceive('resolve')->once()->andReturn($content);
        $this->action->shouldReceive('discardOutputBuffers')->once()->ordered();
        $this->action->shouldReceive('sendStatus')->once()->with(200)->ordered();
        $this->action->shouldReceive('sendHeader')->once()->with('Content-Type: text/plain')->ordered();
        $this->action->shouldReceive('sendHeader')->once()->with('Cache-Control: public, max-age=3600')->ordered();
        $this->action->shouldReceive('sendHeader')->once()->with('Content-Length: 6')->ordered();
        $this->action->shouldReceive('sendHeader')->once()->with('X-Content-Type-Options: nosniff')->ordered();
        $this->expectOutputString($content);

        $this->assertTrue($this->action->serveAction('GET', self::PATH));
    }

    public function testSendsTheHeadersOnlyForHead()
    {
        $this->service->shouldReceive('resolve')->once()->andReturn("ABCD\n");
        $this->action->shouldReceive('discardOutputBuffers')->once();
        $this->action->shouldReceive('sendStatus')->once()->with(200);
        $this->action->shouldReceive('sendHeader')->once()->with('Content-Length: 5');
        $this->action->shouldReceive('sendHeader')->times(3);
        $this->expectOutputString('');

        $this->assertTrue($this->action->serveAction('HEAD', self::PATH));
    }

    public function testSendsAnUncachedNotFoundWhenNothingValidIsStored()
    {
        $this->service->shouldReceive('resolve')->once()->andReturn(null);
        $this->action->shouldReceive('discardOutputBuffers')->once()->ordered();
        $this->action->shouldReceive('sendStatus')->once()->with(404)->ordered();
        $this->action->shouldReceive('sendHeader')->once()->with('Cache-Control: no-store')->ordered();
        $this->expectOutputString('');

        $this->assertTrue($this->action->serveAction('GET', self::PATH));
    }

    public function testSendsNothingAndLogsWhenTheResponseCannotBeBuilt()
    {
        $this->action->shouldReceive('getRouteResponse')->andThrow(new \LogicException('boom'));
        $this->action->shouldNotReceive('discardOutputBuffers', 'sendStatus', 'sendHeader');
        $this->expectOutputString('');

        $this->assertFalse($this->action->serveAction('GET', self::PATH));
        $this->assertErrorLogged();
        $this->assertStringContainsString('ApplePayDomainAssociationAction::serveAction', $this->logs[0]['message']);
        $this->assertStringContainsString('boom', $this->logs[0]['message']);
    }
}
