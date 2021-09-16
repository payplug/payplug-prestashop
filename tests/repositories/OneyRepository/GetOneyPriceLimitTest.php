<?php

/**
 * 2013 - 2021 PayPlug SAS.
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

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class GetOneyPriceLimitTest extends BaseOneyRepository
{
    private $currencyMock;
    private $amounts;

    public function setUp()
    {
        parent::setUp();

        $this->currencyMock = CurrencyMock::get();

        $this->config->shouldReceive('get')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'PS_CURRENCY_DEFAULT':
                        return 1;

                        break;

                    case 'PAYPLUG_ONEY_MIN_AMOUNTS':
                        return 'EUR:10000';

                    case 'PAYPLUG_ONEY_MAX_AMOUNTS':
                        return 'EUR:300000';

                        break;
                }
            })
        ;

        $this->country->shouldReceive('getByIso')
            ->andReturn(1)
        ;

        $this->amounts = [
            'min' => 10000,
            'max' => 300000,
        ];
    }

    public function testWithCurrencyObject()
    {
        $this->currency->shouldReceive('getCurrency')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            $this->amounts,
            $this->repo->getOneyPriceLimit($this->currencyMock)
        );
    }

    public function validDataProvider()
    {
        yield ['wrong_parameter'];
        yield [''];
        yield [1];
        yield [null];
        yield [false];
    }

    /**
     * @dataProvider validDataProvider
     *
     * @param mixed $data
     */
    public function testWithValidDataProvider($data)
    {
        $this->currency->shouldReceive('getCurrency')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            $this->amounts,
            $this->repo->getOneyPriceLimit($data)
        );
    }

    public function testWithNoCurrencyFound()
    {
        $this->currency->shouldReceive('getCurrency')
            ->andReturn(false)
        ;

        $this->assertSame(
            [
                'min' => false,
                'max' => false,
            ],
            $this->repo->getOneyPriceLimit(null)
        );
    }
}
