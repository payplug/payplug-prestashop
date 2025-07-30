<?php

namespace PayPlug\tests\utilities\validators\LockValidator;

use PayPlug\src\utilities\validators\lockValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group lock_validator
 */
class isExpiredTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new lockValidator();
    }

    /**
     * @description invalid string format data provider
     *
     * @return Generator
     */
    public function invalidStringFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [false];

        yield [''];
    }

    /**
     * @description invalid date time format data provider
     *
     * @return Generator
     */
    public function invalidDateTimeFormatDataProvider()
    {
        yield ['1970-01-01'];

        yield ['00:00:00 1970-01-01'];

        yield ['01-01-1970 00:00:00'];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $date
     */
    public function testWithInvalidDateFormat($date)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $date must be a non empty string',
        ], $this->validator->isExpired($date));
    }

    /**
     * @dataProvider invalidDateTimeFormatDataProvider
     *
     * @param mixed $date
     */
    public function testWithInvalidDateTimeFormat($date)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $date must be a date in format Y-m-d H:i:s',
        ], $this->validator->isExpired($date));
    }

    /**
     * @description asserting lock is not expired
     */
    public function testWhenGivenLockDateIsntExpired()
    {
        $date = date('Y-m-d H:i:s', strtotime('-1 minutes'));
        $this->assertSame([
            'result' => false,
            'message' => 'Lock is not expired',
        ], $this->validator->isExpired($date));
    }

    /**
     * @description asserting lock is expired
     */
    public function testWhenGivenLockDateIsExpired()
    {
        $date = date('Y-m-d H:i:s', strtotime('-3 minutes'));
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isExpired($date));
    }
}
