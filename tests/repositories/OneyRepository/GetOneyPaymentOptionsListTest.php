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

use PayPlug\src\repositories\OneyRepository;
use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\OneySimulationsMock;
use PayPlug\tests\mock\MockHelper;
use PHPUnit\Framework\TestCase;
use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group dev
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyPaymentOptionsListTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    // Default setup
    protected $cache;
    protected $logger;
    protected $config;
    protected $myLogPhp;

    // Method setup
    protected $cart;
    protected $carrier;
    protected $context;
    protected $oneyEntity;
    protected $tools;
    protected $translate;
    protected $validate;

    protected $list;

    public function setUp()
    {
        // Default setup for Oney Repository using
        $this->cache = MockHelper::createMockFactory('Payplug\src\repositories\CacheRepository');
        $this->logger = MockHelper::createMockFactory('Payplug\src\repositories\LoggerRepository');
        $this->config = MockHelper::createMockFactory('Payplug\src\specific\ConfigurationSpecific');
        $this->myLogPhp = MockHelper::createMockFactory('Payplug\classes\MyLogPHP');

        // Method setup
        $this->cart = MockHelper::createContextMock('Payplug\src\specific\CartSpecific');
        $this->carrier = MockHelper::createContextMock('Payplug\src\specific\CarrierSpecific');
        $this->tools = MockHelper::createToolsMock('Payplug\src\specific\ToolsSpecific');
        $this->translate = MockHelper::createTranslateMock('Payplug\src\specific\TranslationSpecific');
        $this->validate = MockHelper::createValidateMock('Payplug\src\specific\ValidateSpecific');

        $this->carrier->shouldReceive([
            'get' => CarrierMock::get(),
            'getDefaultDelay' => 0,
            'getDefaultDeliveryType' => 'storepickup'
        ]);

        $this->config->shouldReceive('get')
            ->with('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            ->andReturn('FR');

        $this->context = MockHelper::createContextMock('Payplug\src\specific\ContextSpecific');

        // Method Params
        $this->payplug = Mockery::mock('payplug');
        $this->payplug
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $cent = false) {
                if ($cent) {
                    return round($amount / 100, 2);
                }
                return (int)$amount * 100;
            });

        $this->repo = \Mockery::mock(OneyRepository::class)->makePartial();
        $this->repo->setFactories();
        $this->repo->setPayplug($this->payplug);

        $this->repo
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive([
                'getOneySimulations' => [
                    'result' => true,
                    'simulations' => OneySimulationsMock::get()
                ],
                'getMethods' => [
                    'x3_with_fees',
                ]
            ]);

        $this->list = [
            'x3_with_fees' => [
                'installments' => [
                    [
                        'date' => '2021-02-19T01:00:00.000Z',
                        'amount' => (float)80.42,
                        'value' => '80,42 €',
                    ],
                    [
                        'date' => '2021-03-19T01:00:00.000Z',
                        'amount' => (float)80.41,
                        'value' => '80,41 €',
                    ]
                ],
                'total_cost' => [
                    'amount' => (float)3.5,
                    'value' => '3,50 €',
                ],
                'nominal_annual_percentage_rate' => (float)17.76,
                'effective_annual_percentage_rate' => (float)19.27,
                'down_payment_amount' => [
                    'amount' => (float)83.92,
                    'value' => '83,92 €',
                ],
                'split' => 3,
                'title' => 'Payment in 3x',
                'total_amount' => [
                    'amount' => (float)15003.5,
                    'value' => '15,003,50 €',
                ]
            ],
            'x4_with_fees' => false,
        ];
    }

    public function testGetList()
    {
        $amount = 15000;
        $country = 'FR';
        $response = $this->repo->getOneyPaymentOptionsList($amount, $country);

        $this->assertSame(
            $this->list,
            $response
        );
    }

    public function testGetListWithWrongAmount()
    {
        $country = 'FR';
        $this->assertSame(
            [],
            $this->repo->getOneyPaymentOptionsList(0, $country)
        );
        $this->assertSame(
            [],
            $this->repo->getOneyPaymentOptionsList('wrong params', $country)
        );
    }

    public function testGetListWithNullCountry()
    {
        $amount = 15000;
        $response = $this->repo->getOneyPaymentOptionsList($amount, false);

        $this->assertSame(
            $this->list,
            $response
        );
    }

    public function getOneyPaymentOptionsList($amount, $country = false)
    {
        // get Oney resource
        $payment_list = [];
        $amount = $this->payplug->convertAmount($amount);

        if (!$country) {
            $iso_code_list = $this->configurationSpecific->get('PAYPLUG_ONEY_ALLOWED_COUNTRIES');
            $iso_list = explode(',', $iso_code_list);
            $country = reset($iso_list);
        }

        $country = $this->toolsSpecific->tool('strtoupper', $country);

        $oney_sims = $this->getOneySimulations($amount, $country, $this->methods);

        if (!$oney_sims['result']) {
            return $payment_list;
        }

        foreach ($oney_sims['simulations'] as $method => $oney_sim) {
            if (isset($oney_sim['installments']) && $oney_sim['installments']) {
                $payment_list[$method] = $this->formatOneyResource($method, $oney_sim, $amount);
            }
        }

        return $payment_list;
    }
}
