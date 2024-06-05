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
class isCachedPaymentTest extends TestCase
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

    public function invalidHashFormatDataProvider()
    {
        yield ['1234567890azertyuiop']; // shorter
        yield ['1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop12345678990']; // longer
        yield ['1234567890AZERTYUIOP1234567890AZERTYUIOP1234567890AZERTYUIOP1234']; // wrong characters
        yield ['1234567890azertyuio?1234567890azertyuiop1234567890azertyuiop123!']; // wrong characters - !? tested
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $hash
     */
    public function testWithInvalidHashFormat($hash)
    {
        $stored_hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $hash must be a non empty string',
        ], $this->validator->isCachedPayment($hash, $stored_hash));
    }

    /**
     * @dataProvider invalidHashFormatDataProvider
     *
     * @param mixed $hash
     */
    public function testWhenGivenHashIsInvalidHashFormat($hash)
    {
        $stored_hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid hash format given, $hash given is not valid',
        ], $this->validator->isCachedPayment($hash, $stored_hash));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $stored_hash
     */
    public function testWithInvalidStoredHashFormat($stored_hash)
    {
        $hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument given, $stored_hash must be a non empty string',
        ], $this->validator->isCachedPayment($hash, $stored_hash));
    }

    /**
     * @dataProvider invalidHashFormatDataProvider
     *
     * @param mixed $stored_hash
     */
    public function testWhenGivenStoreHashIsInvalidHashFormat($stored_hash)
    {
        $hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid hash format given, $stored_hash given is not valid',
        ], $this->validator->isCachedPayment($hash, $stored_hash));
    }

    public function testWhenHashAndStoreHashIsDifferent()
    {
        $hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
        $stored_hash = 'azertyuiop1234567890azertyuiop1234567890azertyuiop1234567890azer';
        $this->assertSame([
            'result' => false,
            'message' => 'The given hash does not match with the stored one',
        ], $this->validator->isCachedPayment($hash, $stored_hash));
    }

    public function testWhenHashAndStoreHashIsTheSame()
    {
        $hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
        $stored_hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
        $this->assertSame([
            'result' => true,
            'message' => 'The given hash match with the stored one',
        ], $this->validator->isCachedPayment($hash, $stored_hash));
    }
}
