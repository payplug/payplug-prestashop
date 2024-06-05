<?php

namespace PayPlug\tests\utilities\validators\LoggerValidator;

use PayPlug\src\utilities\validators\loggerValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group logger_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class isAllowedProcessTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new loggerValidator();
    }

    public function invalidFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
    }

    /**
     * @dataProvider invalidFormatDataProvider
     *
     * @param mixed $process
     */
    public function testWithInvalidFormat($process)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $process must be a non empty string',
        ], $this->validator->isAllowedProcess($process));
    }

    public function testWithInvalidProcess()
    {
        $process = 'forbidden';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $process is not allowed',
        ], $this->validator->isAllowedProcess($process));
    }

    public function testWithValidProcess()
    {
        $process = 'payment';
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isAllowedProcess($process));
    }
}
