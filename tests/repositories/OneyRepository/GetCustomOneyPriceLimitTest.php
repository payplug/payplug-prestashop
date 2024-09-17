<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\CurrencyMock;

/**
 * @group unit
 * @group old_repository
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

        $this->configuration->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'amounts':
                        return '{"default":{"min":"EUR:99","max":"EUR:2000000"},"oney_x3_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_with_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x3_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"oney_x4_without_fees":{"min":"EUR:10000","max":"EUR:300000"},"bancontact":{"min":"EUR:99","max":"EUR:2000000"},"ideal":{"min":"EUR:99","max":"EUR:2000000"},"mybank":{"min":"EUR:99","max":"EUR:2000000"},"satispay":{"min":"EUR:99","max":"EUR:2000000"},"sofort":{"min":"EUR:100","max":"EUR:500000"}}';
                    case 'countries':
                        return '{"oney_x3_with_fees":["MQ","YT","NC","PF","GP","GF","RE","FR","MF","BL"],"oney_x4_with_fees":["MQ","YT","NC","PF","GP","GF","RE","FR","MF","BL"],"oney_x3_without_fees":["MQ","YT","NC","PF","GP","GF","RE","FR","MF","BL"],"oney_x4_without_fees":["MQ","YT","NC","PF","GP","GF","RE","FR","MF","BL"],"ideal":["NL"],"mybank":["IT"],"satispay":["AT","BE","CY","DE","EE","ES","FI","FR","GR","HR","HU","IE","IT","LT","LU","LV","MT","NL","PT","SI","SK"],"sofort":["AT","BE","DE","ES","IT","NL"]}';
                    case 'oney_custom_max_amounts':
                        return 'EUR:300000';
                    case 'oney_custom_min_amounts':
                        return 'EUR:10000';
                    default:
                        return $this->configuration->getDefault($key);
                }
            });

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $this->configuration,
        ]);
        $this->dependencies->shouldReceive([
            'getPlugin' => $this->plugin,
        ]);
    }

    public function testCustomLimitWithCurrencyObject()
    {
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            [
                'min' => 10000,
                'max' => 300000,
            ],
            $this->repo->getOneyPriceLimit(true, $this->currencyMock)
        );
    }

    public function atestCustomLimitWithNotValidLimits()
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
    public function atestCustomLimitWithValidDataProvider($data)
    {
        $this->currency->shouldReceive('get')
            ->andReturn($this->currencyMock)
        ;

        $this->assertSame(
            $this->amounts,
            $this->repo->getOneyPriceLimit(true, $data)
        );
    }

    public function atestWithNoCurrencyFound()
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
