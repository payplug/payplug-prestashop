<?php

namespace PayPlug\tests\utilities\validators\ModuleValidator;

use PayPlug\src\utilities\validators\moduleValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group module_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class canBeShownTest extends TestCase
{
    protected $moduleValidator;

    protected function setUp()
    {
        $this->moduleValidator = new moduleValidator();
    }

    public function invalidDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [null];
        yield [''];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $configuration
     */
    public function testWithInvalidConfigurationFormat($configuration)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid parameters given, $configuration and $showed must be a boolean',
            ],
            $this->moduleValidator->canBeShown($configuration)
        );
    }

    public function testWithConfigurationReturningFalse()
    {
        $configuration = false;
        $this->assertSame(
            [
                'result' => false,
                'message' => 'The module is setted to be hide',
            ],
            $this->moduleValidator->canBeShown($configuration)
        );
    }

    public function testWithConfigurationReturningTrue()
    {
        $configuration = true;
        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->moduleValidator->canBeShown($configuration)
        );
    }
}
