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

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\src\entities\OneyEntity;
use PayPlug\src\repositories\OneyRepository;
use PayPlug\src\specific\AddressSpecific;
use PayPlug\src\specific\ContextSpecific;
use PayPlug\src\specific\CountrySpecific;
use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\OneySimulationsMock;
use PayPlug\tests\mock\MockHelper;
use PayPlug\tests\repositories\BaseTest;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyPaymentOptionsListTest extends BaseTest
{
    protected $list;

    public function setUp()
    {
        parent::setUp();

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
        $this->oneyEntity = \Mockery::mock(OneyEntity::class);
        $this->oneyEntity
            ->shouldReceive([
                'getOperations' => ['x3_with_fees'],
                'setOperations' => true
            ]);

        $this->payplug
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $cent = false) {
                if ($cent) {
                    return round($amount / 100, 2);
                }
                return (int)$amount * 100;
            });

        $this->repo = \Mockery::mock(OneyRepository::class, [
            $this->cache,
            $this->logger,
            new AddressSpecific(),
            $this->cart,
            $this->carrier,
            $this->config,
            new ContextSpecific(),
            new CountrySpecific(),
            $this->tools,
            $this->validate,
            $this->oneyEntity,
            $this->myLogPhp,
            $this->payplug
        ])->makePartial();

        $this->repo
            ->shouldAllowMockingProtectedMethods()
            ->shouldReceive([
                'getOneySimulations' => [
                    'result' => true,
                    'simulations' => OneySimulationsMock::get()
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
}
