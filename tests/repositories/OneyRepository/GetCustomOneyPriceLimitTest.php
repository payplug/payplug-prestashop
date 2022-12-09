<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetCustomOneyPriceLimitTest extends BaseOneyRepository
{
    private $currencyMock;
    private $amounts;
    private $badAmounts;

    public function setUp()
    {
        parent::setUp();

        $this->currencyMock = CurrencyMock::get();

        $this->config->shouldReceive('get')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'PS_CURRENCY_DEFAULT':
                        return 1;

                    case 'PAYPLUG_ONEY_MIN_AMOUNTS':
                        return 'EUR:10000';

                    case 'PAYPLUG_ONEY_MAX_AMOUNTS':
                        return 'EUR:300000';

                    case 'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS':
                        return 'EUR:100';

                    case 'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS':
                        return 'EUR:3000';

                    case 'PS_SHOP_NAME':
                        return 'Payplug';

                    case 'PAYPLUG_ONEY_ALLOWED_COUNTRIES':
                        return '';

                    default:
                        return true;
                }
            })
        ;

        $this->country->shouldReceive('getByIso')
            ->andReturn(1)
        ;

        $this->amounts = [
            'min' => 100,
            'max' => 3000,
        ];
        $this->badAmounts = [
            'min' => 10,
            'max' => 300,
        ];
    }

    public function testCustomLimitWithCurrencyObject()
    {
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            $this->amounts,
            $this->repo->getOneyPriceLimit(true, $this->currencyMock)
        );
    }

    public function testCustomLimitWithNotValidLimits()
    {
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;

        $this->assertNotSame(
            $this->badAmounts,
            $this->repo->getOneyPriceLimit(true, $this->currencyMock)
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
    public function testCustomLimitWithValidDataProvider($data)
    {
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            $this->amounts,
            $this->repo->getOneyPriceLimit(true, $data)
        );
    }

    public function testWithNoCurrencyFound()
    {
        $this->currency->shouldReceive('get')
            ->andReturn(false)
        ;

        $this->assertSame(
            [
                'min' => false,
                'max' => false,
            ],
            $this->repo->getOneyPriceLimit(true, null)
        );
    }
}
