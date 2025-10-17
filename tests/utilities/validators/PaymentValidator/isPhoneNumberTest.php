<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class isPhoneNumberTest extends TestCase
{
    protected $validator;

    public function setUp()
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

    public function invalidPhoneFormatDataProvider()
    {
        yield ['42424']; // to short

        yield ['01|23|45|67|89']; // invalid char |

        yield ['lorem ipsum']; // invalid char alpha

        yield ['01234567890123456']; // to long
    }

    public function validPhoneFormatDataProvider()
    {
        yield ['+33 1 234 567 890'];

        yield ['(+33) 1 234 567 890'];

        yield ['0123456789'];

        yield ['01-23-45-67-89'];

        yield ['01.23.45.67.89'];

        yield ['01 23 45 67 89'];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $phone
     */
    public function testWhenGivenPhoneWithInvalidStringFormat($phone)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $phone must be a non empty string',
        ], $this->validator->isPhoneNumber($phone));
    }

    /**
     * @dataProvider invalidPhoneFormatDataProvider
     *
     * @param mixed $phone
     */
    public function testWhenGivenPhoneWithInvalidPhoneFormat($phone)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $phone must be a valid phone number format (E.164)',
        ], $this->validator->isPhoneNumber($phone));
    }

    /**
     * @dataProvider validPhoneFormatDataProvider
     *
     * @param mixed $phone
     */
    public function testWhenGivenPhoneWithValidPhoneFormat($phone)
    {
        $this->assertSame([
            'result' => true,
            'message' => '',
        ], $this->validator->isPhoneNumber($phone));
    }
}
