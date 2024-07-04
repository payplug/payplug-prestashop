<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group format_oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class FormatOneyResourceTest extends BaseOneyRepository
{
    protected $repo;
    protected $tab;

    protected $operation;
    protected $resource;

    public function setUp()
    {
        parent::setUp();

        $this->repo
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive([
                'getMethods' => [
                    'x3_with_fees',
                ],
            ])
        ;

        $this->configuration
            ->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('FR');

        $this->operation = 'x3_with_fees';
        $this->resource = OneySimulationsMock::get()[$this->operation];
    }

    /**
     * @description test formatOneyResource
     * with invalid method
     */
    public function testWithInvalidMethod()
    {
        $method = 'wrong method';
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($method, $this->resource, $total_amount = false)
        );
    }

    /**
     * @description test formatOneyResource
     * with invalid resource
     */
    public function testWithInvalidResource()
    {
        $resource = 'wrong resource';
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($this->operation, $resource, $total_amount = false)
        );
    }

    /**
     * @description test formatOneyResource
     * when the split is valid
     */
    public function testGetValidSplit()
    {
        $this->amount_helper->shouldReceive([
            'convertAmount' => 3.50,
        ]);

        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);
        $expected_value = 3;
        $this->assertSame(
            $expected_value,
            $response['split']
        );
    }

    /**
     * @description test formatOneyResource returns
     * valid title
     */
    public function testGetValidTitle()
    {
        $this->amount_helper->shouldReceive([
            'convertAmount' => 3.50,
        ]);

        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);
        $expected_value = 'Payment in 3x';
        $this->assertSame(
            $expected_value,
            $response['title']
        );
    }

    /**
     * @description test formatOneyResource
     * returns valid total cost
     */
    public function testGetValidTotalCost()
    {
        $this->amount_helper->shouldReceive([
            'convertAmount' => 3.50,
        ]);

        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);
        $expected_value = [
            'amount' => number_format(3.5, 2),
            'value' => '3,50 €',
        ];
        $this->assertSame(
            $expected_value,
            $response['total_cost']
        );
    }

    /**
     * @description test formatOneyResource
     * returns valid down payment amount
     */
    public function testGetValidDownPaymentAmount()
    {
        $this->amount_helper->shouldReceive([
            'convertAmount' => 83.92,
        ]);

        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);
        $expected_value = [
            'amount' => number_format(83.92, 2),
            'value' => '83,92 €',
        ];
        $this->assertSame(
            $expected_value,
            $response['down_payment_amount']
        );
    }

    /**
     * @description test formatOneyResource
     * returns valid installment
     */
    public function testGetValidInstallments()
    {
        $this->amount_helper->shouldReceive([
            'convertAmount' => 80.42,
        ]);

        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);

        // check installments count
        $this->assertSame(
            2,
            count($response['installments'])
        );

        $this->assertSame(
            [
                'date' => '2021-02-19T01:00:00.000Z',
                'amount' => number_format(80.42, 2),
                'value' => '80,42 €',
            ],
            $response['installments'][0]
        );

        $this->assertSame(
            [
                'date' => '2021-03-19T01:00:00.000Z',
                'amount' => number_format(80.42, 2),
                'value' => '80,42 €',
            ],
            $response['installments'][1]
        );
    }

    /**
     * @description  test formatOneyResource
     * returns invalid amount
     */
    public function testWithInvalidAmount()
    {
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($this->operation, $this->resource, 'wrong params')
        );
    }

    /**
     * @description test formatOneyResource
     * returns invalid total amount
     */
    public function testGetValidTotalAmountWithEmptyValue()
    {
        $this->amount_helper->shouldReceive([
            'convertAmount' => 1.75,
        ]);

        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);
        $expected_value = [
            'amount' => number_format(3.5, 2),
            'value' => '3,50 €',
        ];
        $this->assertSame(
            $expected_value,
            $response['total_amount']
        );
    }

    /**
     * @description test formatOneyResource
     * returns valid total amount
     */
    public function testGetValidTotalAmount()
    {
        $this->amount_helper->shouldReceive([
            'convertAmount' => 2.25,
        ]);

        $response = $this->repo->formatOneyResource($this->operation, $this->resource, 100);
        $expected_value = [
            'amount' => number_format(4.5, 2),
            'value' => '4,50 €',
        ];
        $this->assertSame(
            $expected_value,
            $response['total_amount']
        );
    }
}
