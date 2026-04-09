<?php

namespace PayPlug\Tests\utilities\helpers\LinkHelper;

use PayPlug\src\utilities\helpers\LinkHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group link_helper
 */
class getOAuthCallbackUrlTest extends TestCase
{
    public function tearDown()
    {
        \Mockery::close();
    }

    /**
     * @description On PS < 9.0.0 (bootstrap defines 1.7.0.0), getOAuthCallbackUrl
     *              must return the legacy admin link without any side effects.
     */
    public function testReturnsAdminLinkForPrePS9()
    {
        $expectedUrl = 'https://shop.example.com/admin/index.php?controller=AdminPayplug&token=abc123';

        $context = new \stdClass();
        $context->link = \Mockery::mock();
        $context->link->shouldReceive('getAdminLink')
            ->with('AdminPayplug')
            ->once()
            ->andReturn($expectedUrl);

        $result = LinkHelper::getOAuthCallbackUrl($context);

        $this->assertSame($expectedUrl, $result);
    }

    /**
     * @description On PS < 9.0.0, getModuleLink must never be called.
     */
    public function testDoesNotCallGetModuleLinkForPrePS9()
    {
        $context = new \stdClass();
        $context->link = \Mockery::mock();
        $context->link->shouldReceive('getAdminLink')
            ->with('AdminPayplug')
            ->andReturn('https://shop.example.com/admin/index.php?controller=AdminPayplug&token=xyz');
        $context->link->shouldNotReceive('getModuleLink');

        LinkHelper::getOAuthCallbackUrl($context);
    }

    /**
     * @description The returned URL for PS < 9 must preserve the token parameter
     *              (tokens are stable on 1.7/8.x, so no stripping needed).
     */
    public function testPreservesTokenInPrePS9Url()
    {
        $urlWithToken = 'https://shop.example.com/admin/index.php?controller=AdminPayplug&token=stable_hash';

        $context = new \stdClass();
        $context->link = \Mockery::mock();
        $context->link->shouldReceive('getAdminLink')
            ->with('AdminPayplug')
            ->andReturn($urlWithToken);

        $result = LinkHelper::getOAuthCallbackUrl($context);

        $this->assertContains('token=stable_hash', $result);
    }
}
