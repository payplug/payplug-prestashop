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

        // Method Params
        $this->payplug = Mockery::mock('payplug');
        $this->payplug
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $cent) {
                if ($cent) {
                    return (int)$amount * 100;
                }
                return (float)$amount / 100;
            });

        $this->repo = new OneyRepository($this->payplug);

        $this->operation = reset($this->repo->methods);
        $this->resource = OneySimulationsMock::get()[$this->operation];
    }

    public function testLorem(){}
    public function atestWithInvalidMethod()
    {
        $method = 'wrong method';
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($method, $this->resource, $total_amount = false)
        );
    }

    public function atestWithInvalidResource()
    {
        $resource = 'wrong resource';
        $this->assertSame(
            false,
            $this->repo->formatOneyResource($this->operation, $resource, $total_amount = false)
        );
    }

    public function atestGetValidSplitValueFromMethod()
    {
    }

    public function formatOneyResource($operation, $resource, $total_amount = false)
    {
        $tools = $this->toolsSpecific;

        if (!in_array($operation, $this->methods)) {
            return false;
        }

        $type = explode('_', $operation);

        if (!is_array($resource)) {
            return false;
        }
        $resource['split'] = (int)str_replace('x', '', $type[0]);
        $resource['title'] = sprintf($this->l('Payment in %sx'), $resource['split']);

        // format price
        $total_cost = $this->payplug->convertAmount($resource['total_cost'], true);
        $resource['total_cost'] = [
            'amount' => $total_cost,
            'value' => $tools->tool('displayPrice', $total_cost),
        ];
        $down_payment_amount = $this->payplug->convertAmount($resource['down_payment_amount'], true);
        $resource['down_payment_amount'] = [
            'amount' => $down_payment_amount,
            'value' => $tools->tool('displayPrice', $down_payment_amount),
        ];
        foreach ($resource['installments'] as &$installment) {
            $amount = $this->payplug->convertAmount($installment['amount'], true);
            $installment['amount'] = $amount;
            $installment['value'] = $tools->tool('displayPrice', $amount);
        }

        $total_amount = $this->payplug->convertAmount($total_amount, true);
        $total_amount += $total_cost;
        $resource['total_amount'] = [
            'amount' => $total_amount,
            'value' => $tools->tool('displayPrice', $total_amount),
        ];
        return $resource;
    }
}
