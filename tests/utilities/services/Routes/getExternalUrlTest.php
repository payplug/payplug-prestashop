<?php

namespace PayPlug\tests\utilities\services\Routes;

use PayPlug\src\utilities\services\Routes;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group service
 * @group routes_service
 *
 * @runTestsInSeparateProcesses
 */
class getExternalUrlTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        $this->service = new Routes();
    }

    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [null];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $iso_code
     */
    public function testWhenGivenIsoCodeIsInvalidStringFormat($iso_code)
    {
        $this->assertSame([], $this->service->getExternalUrl($iso_code));
    }

    public function testWhenGivenIsoCodeIsEmpty()
    {
        $routes = $this->service->getExternalUrl();
        $this->assertTrue(strpos($routes['activation'], 'hc/fr/articles') > 0);
    }

    public function testWhenGivenIsoCodeMustBeFormated()
    {
        $routes = $this->service->getExternalUrl('en');
        $this->assertTrue(strpos($routes['activation'], 'hc/en-gb/articles') > 0);
    }

    public function testWhenReturnIsntEmptyArray()
    {
        $routes = $this->service->getExternalUrl('fr');
        $this->assertTrue(is_array($routes) && !empty($routes));
    }
}
