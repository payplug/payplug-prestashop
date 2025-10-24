<?php

namespace PayPlug\tests\utilities\validators\LoggerValidator;

use PayPlug\src\utilities\validators\cardValidator;
use PayPlug\tests\FormatDataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group card_validator
 */
class isValidExpirationTest extends TestCase
{
    use FormatDataProvider;
    protected $validator;

    public function setUp()
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
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $month
     */
    public function testWithInvalidMonthFormat($month)
    {
        $year = 2050;
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $month must be a string',
        ], $this->validator->isValidExpiration($month, $year));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $year
     */
    public function testWithInvalidYearFormat($year)
    {
        $month = '1';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $year must be a string',
        ], $this->validator->isValidExpiration($month, $year));
    }

    public function testWhenMonthIsntAValidDigit()
    {
        $month = '123';
        $year = '2050';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument format for $month given',
        ], $this->validator->isValidExpiration($month, $year));
    }

    public function testWhenYearIsntAValidDigit()
    {
        $month = '12';
        $year = '20500';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument format for $year given',
        ], $this->validator->isValidExpiration($month, $year));
    }

    public function testWhenGivenDateIsInvalid()
    {
        $month = '42';
        $year = '2050';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid date given through $month and/or $year',
        ], $this->validator->isValidExpiration($month, $year));
    }

    public function testWhenGivenDateIsExpired()
    {
        $month = '1';
        $year = '2010';
        $this->assertSame([
            'result' => false,
            'message' => 'This card is expired',
        ], $this->validator->isValidExpiration($month, $year));
    }

    public function testWhenGivenDateIsntExpired()
    {
        $month = '1';
        $year = '2030';
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isValidExpiration($month, $year));
    }
}
