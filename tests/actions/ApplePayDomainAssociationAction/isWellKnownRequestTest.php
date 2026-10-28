<?php

namespace PayPlug\tests\actions\ApplePayDomainAssociationAction;

use PayPlug\src\actions\ApplePayDomainAssociationAction;

/**
 * @group unit
 * @group action
 */
class isWellKnownRequestTest extends BaseApplePayDomainAssociationAction
{
    public function requestUriProvider()
    {
        yield 'exact path' => ['/.well-known/apple-developer-merchantid-domain-association', true];

        yield 'with a query string' => ['/.well-known/apple-developer-merchantid-domain-association?x=1', true];

        yield 'with a fragment' => ['/.well-known/apple-developer-merchantid-domain-association#f', true];

        yield 'absolute form' => ['https://shop.test/.well-known/apple-developer-merchantid-domain-association', true];

        yield 'trailing slash' => ['/.well-known/apple-developer-merchantid-domain-association/', false];

        yield 'extension' => ['/.well-known/apple-developer-merchantid-domain-association.txt', false];

        yield 'sub-folder' => ['/shop/.well-known/apple-developer-merchantid-domain-association', false];

        yield 'upper case' => ['/.WELL-KNOWN/APPLE-DEVELOPER-MERCHANTID-DOMAIN-ASSOCIATION', false];

        yield 'other well-known file' => ['/.well-known/security.txt', false];

        yield 'empty' => ['', false];

        yield 'double slash' => ['//', false];

        yield 'unparseable' => ['http:///', false];

        yield 'not a string' => [null, false];
    }

    /**
     * @dataProvider requestUriProvider
     *
     * @param mixed $request_uri
     * @param bool $expected
     */
    public function testMatchesOnlyTheExactApplePath($request_uri, $expected)
    {
        $this->assertSame($expected, $this->action->isWellKnownRequest($request_uri));
    }

    /**
     * @dataProvider requestUriProvider
     *
     * @param mixed $request_uri
     * @param bool $expected
     */
    public function testMatchesWithoutBuildingTheAction($request_uri, $expected)
    {
        $this->assertSame($expected, ApplePayDomainAssociationAction::isWellKnownRequest($request_uri));
    }

    public function testIsCallableWithoutAnInstance()
    {
        $method = new \ReflectionMethod(ApplePayDomainAssociationAction::class, 'isWellKnownRequest');

        $this->assertTrue($method->isStatic());
    }
}
