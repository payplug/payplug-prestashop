<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @dontrunTestsInSeparateProcesses
 */
class isOneyEmailTest extends TestCase
{
    protected $validator;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
    }

    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
    }

    /**
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
            $this->validator->isOneyEmail($email)
        );
    }

    public function testWhenGivenEmailIsTooLongAndContainForbiddenChar()
    {
        $email = '';
        for ($i = 0; $i < 100; ++$i) {
            $email .= 'a';
        }
        $email .= '+email@test.com';

        $this->assertSame([
            'result' => false,
            'code' => 'length-char',
            'message' => 'Invalid email lenght given, Oney email is limited to 100 char and "+" usage is forbidden',
        ], $this->validator->isOneyEmail($email));
    }

    public function testWhenGivenEmailContainForbiddenChar()
    {
        $email = 'email+email@test.com';

        $this->assertSame([
            'result' => false,
            'code' => 'char',
            'message' => 'Invalid character found in given email, "+" usage is forbidden',
        ], $this->validator->isOneyEmail($email));
    }

    public function testWhenGivenEmailIsTooLong()
    {
        $email = '';
        for ($i = 0; $i < 100; ++$i) {
            $email .= 'a';
        }
        $email .= '@test.com';

        $this->assertSame([
            'result' => false,
            'code' => 'length',
            'message' => 'Invalid email lenght given, Oney email is limited to 100 char',
        ], $this->validator->isOneyEmail($email));
    }

    public function testWhenGivenEmailIsValid()
    {
        $email = 'email@test.com';
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isOneyEmail($email));
    }
}
