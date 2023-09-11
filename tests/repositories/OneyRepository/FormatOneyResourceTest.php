<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\OneySimulationsMock;

/**
 * @group unit
 * @group repository
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

    public function testWithInvalidMethod()
    {
        $method = 'wrong method';
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($method, $this->resource, $total_amount = false)
        );
    }

    public function testWithInvalidResource()
    {
        $resource = 'wrong resource';
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($this->operation, $resource, $total_amount = false)
        );
    }

    public function testGetValidSplit()
    {
        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);
        $expected_value = 3;
        $this->assertSame(
            $expected_value,
            $response['split']
        );
    }

    public function testGetValidTitle()
    {
        $response = $this->repo->formatOneyResource($this->operation, $this->resource, $total_amount = false);
        $expected_value = 'Payment in 3x';
        $this->assertSame(
            $expected_value,
            $response['title']
        );
    }

    public function testGetValidTotalCost()
    {
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

    public function testGetValidDownPaymentAmount()
    {
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

    public function testGetValidInstallments()
    {
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
                'amount' => number_format(80.41, 2),
                'value' => '80,41 €',
            ],
            $response['installments'][1]
        );
    }

    public function testWithInvalidAmount()
    {
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($this->operation, $this->resource, 'wrong params')
        );
    }

    public function testGetValidTotalAmountWithEmptyValue()
    {
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

    public function testGetValidTotalAmount()
    {
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
