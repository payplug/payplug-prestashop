<?php

namespace PayPlug\tests\utilities\validators\ModuleValidator;

use PayPlug\src\utilities\validators\moduleValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group module_validator
 *
 * @runTestsInSeparateProcesses
 */
class isAllowedTest extends TestCase
{
    protected $moduleValidator;
    protected $features = [];

    public function setUp()
    {
        $this->moduleValidator = new moduleValidator();
    }

    public function invalidDataProvider()
    {
        yield [42, true];

        yield ['lorem Ipsum', true];

        yield [null, true];

        yield [['key' => 'value'], true];

        yield [true, 42];

        yield [true, 'lorem Ipsum'];

        yield [true, null];

        yield [true, ['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $enable
     * @param mixed $shown
     */
    public function testWithInvalidArgumentsFormat($enable, $shown)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameters given, $enable and $shown must be a boolean',
            ],
            $this->moduleValidator->isAllowed($enable, $shown)
        );
    }

    public function testWhenModuleIsDisabledAndHidden()
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'The module is not enable and is setted to be hidden',
            ],
            $this->moduleValidator->isAllowed(false, false)
        );
    }

    public function testWhenModuleIsDisabledButShown()
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'The module is not enable',
            ],
            $this->moduleValidator->isAllowed(false, true)
        );
    }

    public function testWhenModuleIsEnabledButHidden()
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'The module is setted to be hidden',
            ],
            $this->moduleValidator->isAllowed(true, false)
        );
    }

    public function testWhenModuleIsEnabledAndShown()
    {
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->moduleValidator->isAllowed(true, true)
        );
    }
}
