<?php

namespace PayPlug\tests\application\adapter\ContextAdapter;

use PayPlug\src\application\adapter\ContextAdapter;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group adapter
 * @group context_adapter
 */
class getPageLinkTest extends TestCase
{
    private $adapter;
    private $link;

    public function setUp(): void
    {
        $this->link = \Mockery::mock('Link');

        $context = new \stdClass();
        $context->link = $this->link;
        $context->language = new \stdClass();
        $context->language->id = '2';

        $this->adapter = \Mockery::mock(ContextAdapter::class)->makePartial();
        $this->adapter->shouldReceive('get')->andReturn($context);
    }

    public function tearDown(): void
    {
        \Mockery::close();
    }

    public function testBuildsAnHttpsLinkInTheContextLanguage()
    {
        $args = ['step' => 3, 'has_error' => 1, 'modulename' => 'payplug'];
        $url = 'https://shop.example/fr/commande?step=3&has_error=1&modulename=payplug';
        $calls = $this->captureLinkCalls($url);

        $this->assertSame($url, $this->adapter->getPageLink('order', $args));
        // assertSame: Mockery's with() compares scalars loosely ('2' == 2, 1 == true).
        $this->assertSame([['order', true, 2, $args]], $calls->getArrayCopy());
    }

    public function testDefaultsToNoArguments()
    {
        $calls = $this->captureLinkCalls('https://shop.example/fr/');

        $this->assertSame('https://shop.example/fr/', $this->adapter->getPageLink('index'));
        $this->assertSame([['index', true, 2, []]], $calls->getArrayCopy());
    }

    private function captureLinkCalls($url)
    {
        $calls = new \ArrayObject();
        $this->link->shouldReceive('getPageLink')
            ->once()
            ->andReturnUsing(function () use ($calls, $url) {
                $calls->append(func_get_args());

                return $url;
            });

        return $calls;
    }
}
