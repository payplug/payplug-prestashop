<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\mock\OneySimulationsMock;
use PayPlug\src\repositories\OneyRepository;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class FormatOneyResourceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // Default setup
    protected $cache;
    protected $config;
    protected $logger;
    protected $myLogPhp;

    // Method setup
    protected $translate;

    // Method Params
    protected $payplug;
    protected $repo;
    protected $tab;

    protected $operation;
    protected $resource;

    public function setUp()
    {
        // Default setup for Oney Repository using
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->logger = MockHelper::createMockFactory('Payplug\src\repositories\LoggerRepository');
        $this->config = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->myLogPhp = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        // Method setup
        $this->tools = MockHelper::createToolsMock('Payplug\src\specific\ToolsSpecific');
        $this->translate = MockHelper::createTranslateMock('Payplug\src\specific\TranslationSpecific');

        // Method Params
        $this->payplug = Mockery::mock('payplug');
        $this->payplug
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $cent) {
                if ($cent) {
                    return round($amount / 100, 2);
                }
                return (int)$amount * 100;
            });

        $this->repo = new OneyRepository($this->payplug);

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
            'amount'=> (float)3.5,
            'value'=> '3,50 €'
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
            'amount' => (float)83.92,
            'value' => '83,92 €'
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
                'amount' => (float)80.42,
                'value' => '80,42 €'
            ],
            $response['installments'][0]
        );
        $this->assertSame(
            [
                'date' => '2021-03-19T01:00:00.000Z',
                'amount' => (float)80.41,
                'value' => '80,41 €'
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
            'amount' => (float)3.5,
            'value' => '3,50 €'
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
            'amount' => (float)4.5,
            'value' => '4,50 €'
        ];
        $this->assertSame(
            $expected_value,
            $response['total_amount']
        );
    }
}
