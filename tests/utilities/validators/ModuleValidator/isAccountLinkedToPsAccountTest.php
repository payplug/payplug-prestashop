<?php

namespace PayPlug\tests\utilities\validators\ModuleValidator;

use PayPlug\src\utilities\validators\moduleValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group logger_validator
 */
class isAccountLinkedToPsAccountTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new moduleValidator();
    }

    public function invalidFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [false];

        yield ['string'];
    }

    /**
     * @dataProvider invalidFormatDataProvider
     *
     * @param mixed $module
     */
    public function testWithInvalidFormat($module)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid parameters given, $module must be an non null object',
        ], $this->validator->isAccountLinkedToPsAccount($module));
    }

    public function testWhenGetServiceThrowingException()
    {
        $module = \Mockery::mock();
        $module->shouldReceive('getService')
            ->andThrow('Exception', 'getService() method throw exception', 500);

        $this->assertSame([
            'result' => false,
            'message' => 'getService() method throw exception',
        ], $this->validator->isAccountLinkedToPsAccount($module));
    }

    public function testWhenGetPsAccountsServiceThrowingException()
    {
        $facade = \Mockery::mock();
        $facade->shouldReceive('getPsAccountsService')
            ->andThrow('Exception', 'getPsAccountsService() method throw exception', 500);

        $module = \Mockery::mock();
        $module->shouldReceive('getService')
            ->andReturn($facade);

        $this->assertSame([
            'result' => false,
            'message' => 'getPsAccountsService() method throw exception',
        ], $this->validator->isAccountLinkedToPsAccount($module));
    }

    public function testWhenIsAccountLinkedThrowingException()
    {
        $service = \Mockery::mock();
        $service->shouldReceive('isAccountLinked')
            ->andThrow('Exception', 'isAccountLinked() method throw exception', 500);

        $facade = \Mockery::mock();
        $facade->shouldReceive('getPsAccountsService')
            ->andReturn($service);

        $module = \Mockery::mock();
        $module->shouldReceive('getService')
            ->andReturn($facade);

        $this->assertSame([
            'result' => false,
            'message' => 'isAccountLinked() method throw exception',
        ], $this->validator->isAccountLinkedToPsAccount($module));
    }

    public function testWhenIsAccountLinkedReturnFalse()
    {
        $service = \Mockery::mock();
        $service->shouldReceive('isAccountLinked')
            ->andReturn(false);

        $facade = \Mockery::mock();
        $facade->shouldReceive('getPsAccountsService')
            ->andReturn($service);

        $module = \Mockery::mock();
        $module->shouldReceive('getService')
            ->andReturn($facade);

        $this->assertSame([
            'result' => false,
            'message' => '',
        ], $this->validator->isAccountLinkedToPsAccount($module));
    }

    public function testWhenIsAccountLinkedReturnTrue()
    {
        $service = \Mockery::mock();
        $service->shouldReceive('isAccountLinked')
            ->andReturn(true);

        $facade = \Mockery::mock();
        $facade->shouldReceive('getPsAccountsService')
            ->andReturn($service);

        $module = \Mockery::mock();
        $module->shouldReceive('getService')
            ->andReturn($facade);

        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isAccountLinkedToPsAccount($module));
    }
}
