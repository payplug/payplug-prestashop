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
final class GetOneyPriceLimitTest extends BaseOneyRepository
{
    private $currencyMock;
    private $amounts;

    public function setUp()
    {
        parent::setUp();

        $this->currencyMock = CurrencyMock::get();

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
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            $this->amounts,
            $this->repo->getOneyPriceLimit(false, $this->currencyMock)
        );
    }

    public function testIsValidCustomOneyMax()
    {
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;
        $custom_amount = $this->repo->getOneyPriceLimit(true, $this->currencyMock);
        $amount = $this->repo->getOneyPriceLimit(false, $this->currencyMock);
        $oney_custom_max_amount = $custom_amount['max'];
        $oney_max_amount = $amount['max'] / 100;

        $this->assertGreaterThanOrEqual($oney_custom_max_amount, $oney_max_amount);
    }

    public function testIsValidCustomOneyMin()
    {
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;
        $custom_amount = $this->repo->getOneyPriceLimit(true, $this->currencyMock);
        $amount = $this->repo->getOneyPriceLimit(false, $this->currencyMock);
        $oney_custom_min_amount = $custom_amount['min'];
        $oney_min_amount = $amount['min'] / 100;
        $this->assertGreaterThanOrEqual($oney_min_amount, $oney_custom_min_amount);
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
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            $this->amounts,
            $this->repo->getOneyPriceLimit(false, $data)
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
            $this->repo->getOneyPriceLimit(null)
        );
    }
}
