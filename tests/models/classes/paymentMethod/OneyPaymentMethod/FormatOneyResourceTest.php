<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 *
 * @runTestsInSeparateProcesses
 */
final class FormatOneyResourceTest extends BaseOneyPaymentMethod
{
    protected $repo;
    protected $tab;

    protected $operation;
    protected $resource;
    protected $context;

    public function setUp()
    {
        parent::setUp();

        $this->context = \Mockery::mock('Context');
        $this->context->currency = CurrencyMock::get();
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $operation
     */
    public function testWhenGivenOperationIsNotValidBooleanFormat($operation)
    {
        $resource = ['resource'];
        $total_amount = false;

        $this->class->shouldReceive([
            'getOperations' => [],
        ]);

        $this->assertSame(
            false,
            $this->class->formatOneyResource($operation, $resource, $total_amount)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $resource
     */
    public function testWhenGivenResourceIsNotValidArrayFormat($resource)
    {
        $operation = 'x3_with_fees';
        $total_amount = false;

        $this->class->shouldReceive([
            'getOperations' => ['x3_with_fees'],
        ]);

        $this->assertSame(
            false,
            $this->class->formatOneyResource($operation, $resource, $total_amount)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $total_amount
     */
    public function testWhenGivenTotalAmountIsNotValidIntegerFormat($total_amount)
    {
        $operation = 'x3_with_fees';
        $resource = ['resource'];

        $this->class->shouldReceive([
            'getOperations' => ['x3_with_fees'],
        ]);

        $this->assertSame(
            false,
            $this->class->formatOneyResource($operation, $resource, $total_amount)
        );
    }

    public function testWhenResourceFormatted()
    {
        $operation = 'x3_with_fees';
        $resource = [
            'nominal_annual_percentage_rate' => 15, 00,
            'effective_annual_percentage_rate' => 15, 00,
            'total_cost' => 15, 00,
            'down_payment_amount' => 15, 00,
            'installments' => [
                [
                    'amount' => 15, 00,
                    'value' => 15, 00,
                ],
                [
                    'amount' => 15, 00,
                    'value' => 15, 00,
                ],
            ],
        ];
        $total_amount = 1500;

        $this->class->shouldReceive([
            'getOperations' => ['x3_with_fees'],
            'formatPrice' => 15,
        ]);

        $this->helpers['amount']->shouldReceive([
            'convertAmount' => 1500,
        ]);

        $expected = [
            'nominal_annual_percentage_rate' => '15.00',
            0 => 0,
            'effective_annual_percentage_rate' => '15.00',
            1 => 0,
            'total_cost' => [
                'amount' => '1,500.00',
                'value' => 15,
            ],
            2 => 0,
            'down_payment_amount' => [
                'amount' => '1,500.00',
                'value' => 15,
            ],
            3 => 0,
            'installments' => [
                0 => [
                    'amount' => '1,500.00',
                    0 => 0,
                    'value' => 15,
                    1 => 0,
                ],
                1 => [
                    'amount' => '1,500.00',
                    0 => 0,
                    'value' => 15,
                    1 => 0,
                ],
            ],
            'split' => 3,
            'title' => 'Payment in 3x',
            'total_amount' => [
                'amount' => '3,000.00',
                'value' => 15,
            ],
        ];

        $this->assertSame(
            $expected,
            $this->class->formatOneyResource($operation, $resource, $total_amount)
        );
    }
}
