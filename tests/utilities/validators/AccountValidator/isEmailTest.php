<?php

namespace PayPlug\tests\utilities\validators\AccountValidator;

use PayPlug\src\utilities\validators\accountValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group account_validator
 *
 * @runTestsInSeparateProcesses
 */
class isEmailTest extends TestCase
{
    protected $validator;

    public function setUp()
    {
        $this->validator = new AccountValidator();
    }

    /**
     * @description  non string type email provider
     *
     * @return \Generator
     */
    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
        yield [null];
    }

    /**
     * @description  non valid email format provider
     *
     * @return \Generator
     */
    public function invalidEmailFormatDataProvider()
    {
        yield ['@test.com'];
        yield ['email@test'];
        yield ['emailtest.com'];
    }

    /**
     * @description test with invalid string format
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $email
     */
    public function testWhenGivenEmailWithInvalidStringFormat($email)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument given, $email must be a non empty string',
            ],
            $this->validator->isEmail($email)
        );
    }

    /**
     * @description  test with invalid email format
     * @dataProvider invalidEmailFormatDataProvider
     *
     * @param mixed $email
     */
    public function testWhenGivenEmailWithInvalidEmailFormat($email)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid email format given, $email given is not valid',
            ],
            $this->validator->isEmail($email)
        );
    }
}
