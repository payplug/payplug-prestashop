<?php

namespace PayPlug\tests\utilities\validators\PaymentValidator;

use Generator;
use PayPlug\src\utilities\validators\paymentValidator;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group validator
 * @group payment_validator
 *
 * @runTestsInSeparateProcesses
 */
class isPendingPaymentTest extends TestCase
{
    protected $validator;
    private $query;

    protected function setUp()
    {
        $this->validator = new paymentValidator();
        $this->query = MockHelper::createQueryMock('PayPlug\src\repositories\QueryRepository');
    }

    /**
     * @description bad format argument provider
     *
     * @return Generator
     */
    public function invalidArgumentFormatProvider()
    {
        yield ['a string'];
        yield [null];
        yield [0];
        yield [['key' => 'value']];
    }

    /**
     * @dataProvider invalidArgumentFormatProvider
     * @description test if payment is pending with bad argument format
     *
     * @param $id_cart
     */
    public function testIsPendingPaymentWithInvalidIdCart($id_cart)
    {
        $this->assertSame([
                              'result' => false,
                              'message' => 'Invalid argument given, $id_cart must be a non empty integer.',
                          ], $this->validator->isPendingPayment($id_cart));
    }

    /**
     * @param $id_cart
     */
    public function testPendingPayment()
    {
        $id_cart = 1234;
        $this->query
            ->shouldReceive([
                                'select' => $this->query,
                                'fields' => $this->query,
                                'from' => $this->query,
                                'where' => $this->query,
                                'build' => false,
                            ])
        ;

        $this->assertSame(
            [
                'result' => false,
                'message' => 'this payment is not in a pending',
            ],
            $this->validator->isPendingPayment($id_cart)
        );
    }
}
