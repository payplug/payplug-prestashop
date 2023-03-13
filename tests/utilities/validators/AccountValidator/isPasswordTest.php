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
class isPasswordTest extends TestCase
{
    protected $validator;

    protected function setUp()
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
     * @description test with invalid string format
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $password
     */
    public function testWhenGivenPasswordWithInvalidStringFormat($password)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument given, $password must be a non empty string',
            ],
            $this->validator->isPassword($password)
        );
    }

    public function testWhenGivenPasswordIsTooShort()
    {
        $password = '';
        for ($i = 0; $i < 3; ++$i) {
            $password .= 'a';
        }

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid $password given, it is to short',
            ],
            $this->validator->isPassword($password)
        );
    }

    public function testWhenGivenPasswordIsTooLong()
    {
        $password = '';
        for ($i = 0; $i < 100; ++$i) {
            $password .= 'a';
        }

        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid $password given, it is to long',
            ],
            $this->validator->isPassword($password)
        );
    }

    public function testWhenGivenPasswordIsValid()
    {
        $password = '';
        for ($i = 0; $i < 16; ++$i) {
            $password .= 'a';
        }

        $this->assertSame(
            [
                'result' => true,
                'message' => '',
            ],
            $this->validator->isPassword($password)
        );
    }
}
