<?php

namespace PayPlug\Tests\utilities\helpers\LinkHelper;

use PayPlug\src\utilities\helpers\LinkHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group link_helper
 */
class getPaymentLinkUrlsTest extends TestCase
{
    private $link;

    public function setUp(): void
    {
        $this->link = \Mockery::mock('Link');
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testUrlsAreBuiltWithModuleLinks()
    {
        $this->link->shouldReceive('getModuleLink')
            ->once()
            ->with('payplug', 'validation', ['ps' => 1, 'cartid' => 42], true)
            ->andReturn('RETURN_URL');
        $this->link->shouldReceive('getModuleLink')
            ->once()
            ->with('payplug', 'validation', ['ps' => 2, 'cartid' => 42], true)
            ->andReturn('CANCEL_URL');
        $this->link->shouldReceive('getModuleLink')
            ->once()
            ->with('payplug', 'ipn', [], true)
            ->andReturn('NOTIFICATION_URL');

        $this->assertSame(
            [
                'return' => 'RETURN_URL',
                'cancel' => 'CANCEL_URL',
                'notification' => 'NOTIFICATION_URL',
            ],
            LinkHelper::getPaymentLinkUrls($this->link, 'payplug', 42)
        );
    }

    public function testCartIdIsCastToInt()
    {
        $this->mockModuleLinks();

        $urls = LinkHelper::getPaymentLinkUrls($this->link, 'payplug', '42');

        $this->assertSame('https://shop.test/module/payplug/validation?ps=1&cartid=42', $urls['return']);
        $this->assertSame('https://shop.test/module/payplug/validation?ps=2&cartid=42', $urls['cancel']);
    }

    public function testUrlsTargetExistingFrontControllers()
    {
        $this->mockModuleLinks();

        $urls = LinkHelper::getPaymentLinkUrls($this->link, 'payplug', 42);

        $this->assertSame('https://shop.test/module/payplug/ipn', $urls['notification']);
        foreach ($urls as $url) {
            $this->assertStringNotContainsString('module/payplug/notification', $url);
            $this->assertStringNotContainsString('module/payplug/cancel', $url);
        }
    }

    /**
     * @dataProvider missingCartIdProvider
     *
     * @param mixed $cart_id
     */
    public function testThrowsWithoutCartId($cart_id)
    {
        $this->link->shouldNotReceive('getModuleLink');

        $this->expectException(\InvalidArgumentException::class);

        LinkHelper::getPaymentLinkUrls($this->link, 'payplug', $cart_id);
    }

    public function missingCartIdProvider()
    {
        return [
            'null' => [null],
            'zero' => [0],
            'empty string' => [''],
            'negative' => [-1],
        ];
    }

    private function mockModuleLinks()
    {
        $this->link->shouldReceive('getModuleLink')
            ->andReturnUsing(function ($module, $controller, array $params = [], $ssl = null) {
                $query = $params ? '?' . http_build_query($params) : '';

                return 'https://shop.test/module/' . $module . '/' . $controller . $query;
            });
    }
}
