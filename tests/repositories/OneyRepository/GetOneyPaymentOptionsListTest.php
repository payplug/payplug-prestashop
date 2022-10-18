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

use PayPlug\tests\mock\CarrierMock;
use PayPlug\tests\mock\OneySimulationsMock;
use PayPlug\tests\mock\MockHelper;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyPaymentOptionsListTest extends BaseOneyRepository
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

        $this->config->shouldReceive('get')
            ->with('PAYPLUG_ONEY_FEES')
            ->andReturn(true);

        $this->context = MockHelper::createContextMock('Payplug\src\application\adapter\ContextAdapter');

        $this->repo
            ->shouldAllowMockingProtectedMethods();

        $this->list = OneySimulationsMock::getFormated();
    }

    public function validListDataProvider()
    {
        yield [15000, null];
        yield [15000, false];
        yield [15000, ''];
        yield [15000, 'FR'];
    }

    /**
     * @dataProvider validListDataProvider
     * @param $amount
     * @param $country
     * @group mytestlist
     */
    public function testGetList($amount, $country)
    {
        $this->repo
            ->shouldReceive([
                'getOneySimulations' => [
                    'result' => true,
                    'simulations' => OneySimulationsMock::get()
                ]
            ]);

        $this->assertSame(
            $this->repo->getOneyPaymentOptionsList($amount, $country),
            $this->list
        );
    }

    public function invalidListDataProvider()
    {
        yield [0, 'FR'];
        yield [false, 'FR'];
        yield [null, 'FR'];
        yield ['wrong params', 'FR'];
    }

    /**
     * @dataProvider invalidListDataProvider
     */
    public function testGetListWithWrongAmount($amount, $country)
    {
        $this->assertSame(
            [],
            $this->repo->getOneyPaymentOptionsList($amount, $country)
        );
    }

    public function testGetListWithoutSimulation()
    {
        $this->repo
            ->shouldReceive([
                'getOneySimulations' => [
                    'result' => false,
                    'error' => 'There is an error',
                    'simulations' => []
                ]
            ]);

        $this->assertSame(
            [],
            $this->repo->getOneyPaymentOptionsList(15000, 'FR')
        );
    }
}
