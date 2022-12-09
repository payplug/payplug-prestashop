<?php

namespace PayPlug\tests\utilities\validators\LoggerValidator;

use PayPlug\src\utilities\validators\loggerValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group logger_validator
 *
 * @runTestsInSeparateProcesses
 */
class isContentTest extends TestCase
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
     * @param mixed $content
     */
    public function testWithInvalidFormat($content)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $message must be a non empty string',
        ], $this->validator->isContent($content));
    }

    public function testWithValidProcess()
    {
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isContent('Some logger'));
    }
}
