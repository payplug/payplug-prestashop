<?php

namespace PayPlug\tests\utilities\validators\LoggerValidator;

use PayPlug\src\utilities\validators\cardValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group card_validator
 *
 * @runTestsInSeparateProcesses
 */
class isExpiredTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new cardValidator();
    }

    public function invalidFormatDataProvider()
    {
        yield ['string'];
        yield [['key' => 'value']];
        yield [false];
        yield [null];
    }

    /**
     * @dataProvider invalidFormatDataProvider
     *
     * @param mixed $month
     */
    public function testWithInvalidMonthFormat($month)
    {
        $year = 2050;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $month must be a non null integer',
        ], $this->validator->isExpired($month, $year));
    }

    /**
     * @dataProvider invalidFormatDataProvider
     *
     * @param mixed $year
     */
    public function testWithInvalidYearFormat($year)
    {
        $month = 1;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $year must be a non null integer',
        ], $this->validator->isExpired($month, $year));
    }

    public function testWhenMonthIsNotAValidDigit()
    {
        $month = 123;
        $year = 2050;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument format for $month given',
        ], $this->validator->isExpired($month, $year));
    }

    public function testWhenYearIsNotAValidDigit()
    {
        $month = 12;
        $year = 20500;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument format for $year given',
        ], $this->validator->isExpired($month, $year));
    }

    public function testWhenGivenDateIsInvalid()
    {
        $month = 42;
        $year = 2050;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid date given through $month and/or $year',
        ], $this->validator->isExpired($month, $year));
    }

    public function testWhenGivenDateIsExpired()
    {
        $month = 1;
        $year = 2010;
        $this->assertSame([
            'result' => false,
            'message' => 'This card is expired',
        ], $this->validator->isExpired($month, $year));
    }

    public function testWhenGivenDateIsNotExpired()
    {
        $month = 1;
        $year = 2030;
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isExpired($month, $year));
    }
}
