<?php

use PayPlug\src\utilities\validators\paymentValidator;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class canBeRefundTest extends TestCase
{
    private $paymentValidator;

    protected function setUp()
    {
        $this->paymentValidator = new paymentValidator();
    }

    /**
     * @description invalid data provider
     *
     * @return Generator
     */
    public function invalidDataProvider()
    {
        //$pay_id
        yield [1, ['amount' => 5, 'metadata' => ''], 2, 1, 'invalid argument, $pay_id must be a string.'];
        yield [['key' => 'value'], ['amount' => 5, 'metadata' => ''], 2, 1, 'invalid argument, $pay_id must be a string.'];
        // $data provide
        yield [[], [], 2, 1, '$data must not be empty.'];
        yield [[], 'a string', 2, 1, '$data must not be empty.'];
        yield [[], null, 2, 1, '$data must not be empty.'];
        yield [[], false, 2, 1, '$data must not be empty.'];
        yield ['pay_1355', false, 2, 1, '$data must be a non empty array.'];
        yield ['pay_1355', 1, 2, 1, '$data must be a non empty array.'];
        yield ['pay_1355', null, 2, 1, '$data must be a non empty array.'];
        yield ['pay_1355', [], 2, 1, '$data must be a non empty array.'];
        //$truly_refundable_amount provider
        yield [[], ['amount' => 5, 'metadata' => ''], true, 1, 'invalid argument, $truly_refundable_amount must be a numeric type.'];
        yield [[], ['amount' => 5, 'metadata' => ''], 'amount', 1, 'invalid argument, $truly_refundable_amount must be a numeric type.'];
        yield [[], ['amount' => 5, 'metadata' => ''], null, 1, 'invalid argument, $truly_refundable_amount must be a numeric type.'];
        yield [[], ['amount' => 5, 'metadata' => ''], ['key' => 'value'], 1, 'invalid argument, $truly_refundable_amount must be a numeric type.'];
        //$amount_total provider
        yield [[], ['amount' => 5, 'metadata' => ''], 2, true, 'invalid argument, $total_amount must be a numeric type.'];
        yield [[], ['amount' => 5, 'metadata' => ''], 2, ['key' => 'value'], 'invalid argument, $total_amount must be a numeric type.'];
        yield [[], ['amount' => 5, 'metadata' => ''], 2, null, 'invalid argument, $total_amount must be a numeric type.'];
        yield [[], ['amount' => 5, 'metadata' => ''], 2, 'a string', 'invalid argument, $total_amount must be a numeric type.'];
    }

    /**
     * @description  valid data provider
     *
     * @return Generator
     */
    public function validDataProvider()
    {
        yield ['pay_888', ['amount' => 5, 'metadata' => ''], 4, 3];
        yield ['pay_1355', ['amount' => 5, 'metadata' => ''], 1, 2];
        yield ['pay_1355', ['amount' => 5, 'metadata' => ''], '', ''];
    }

    /**
     * @description  invalid amounts data provider
     *
     * @return Generator
     */
    public function invalidAmountsDataProvider()
    {
        yield ['', ['amount' => 5, 'metadata' => ''], 3, 4, '$truly_refundable_amount must be lower than $total_amount.'];
    }

    /**
     * @description  test refund with invalid data format
     * @dataProvider invalidDataProvider
     *
     * @param mixed $pay_id
     * @param mixed $data
     * @param mixed $truly_refundable_amount
     * @param mixed $total_amount
     * @param mixed $error_message
     */
    public function testWithInvalidData($pay_id, $data, $truly_refundable_amount, $total_amount, $error_message)
    {
        $this->assertSame(
            ['result' => false,
                'message' => $error_message, ],
            $this->paymentValidator->canBeRefund($pay_id, $data, $truly_refundable_amount, $total_amount)
        );
    }

    /**
     * @description  test refund with invalid amounts data
     * @dataProvider invalidAmountsDataProvider
     *
     * @param mixed $pay_id
     * @param mixed $data
     * @param mixed $truly_refundable_amount
     * @param mixed $total_amount
     * @param mixed $error_message
     */
    public function testWithInvalidAmountsData($pay_id, $data, $truly_refundable_amount, $total_amount, $error_message)
    {
        $this->assertSame(
            ['result' => false,
                'message' => $error_message, ],
            $this->paymentValidator->canBeRefund($pay_id, $data, $truly_refundable_amount, $total_amount)
        );
    }

    /**
     * @description  test refund with valid data
     * @dataProvider validDataProvider
     *
     * @param mixed $pay_id
     * @param mixed $data
     * @param mixed $truly_refundable_amount
     * @param mixed $total_amount
     */
    public function testWithValidData($pay_id, $data, $truly_refundable_amount, $total_amount)
    {
        $this->assertSame(
            ['result' => true,
                              'message' => 'payment can be refund.', ],
            $this->paymentValidator->canBeRefund($pay_id, $data, $truly_refundable_amount, $total_amount)
        );
    }
}
