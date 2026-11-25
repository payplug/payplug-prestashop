<?php

namespace PayPlug\tests\application\adapter\CurrencyAdapter;

/**
 * Each test runs in its own process: the \Currency alias mock must not leak into the rest of the suite.
 *
 * @group unit
 * @group adapter
 * @group currency_adapter
 */
class findAllTest extends BaseCurrencyAdapter
{
    private $calls = [];

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testSameArgumentsAreFetchedOnce()
    {
        $this->mockCurrency();

        $first = $this->adapter->findAll(false, true, false);
        $second = $this->adapter->findAll(false, true, false);

        $this->assertSame([[false, true, false]], $this->calls);
        $this->assertSame($first, $second);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testEachDistinctSetOfArgumentsIsFetchedOnce()
    {
        $this->mockCurrency();

        $sets = [
            [true, false, true],
            [false, false, true],
            [true, true, true],
            [true, false, false],
        ];
        $results = [];
        foreach ($sets as $set) {
            $results[] = $this->adapter->findAll($set[0], $set[1], $set[2]);
        }
        foreach ($sets as $index => $set) {
            $this->assertSame($results[$index], $this->adapter->findAll($set[0], $set[1], $set[2]));
        }

        $this->assertSame($sets, $this->calls);
        $this->assertSame([1, 2, 3, 4], array_column(array_column($results, 0), 'id_currency'));
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testArgumentsOfDifferentTypesAreDistinctKeys()
    {
        $this->mockCurrency();

        $this->adapter->findAll(true);
        $this->adapter->findAll(1);
        $this->adapter->findAll('1');
        $this->adapter->findAll(false);
        $this->adapter->findAll(0);
        $this->adapter->findAll(null);

        $this->assertSame([
            [true, false, true],
            [1, false, true],
            ['1', false, true],
            [false, false, true],
            [0, false, true],
            [null, false, true],
        ], $this->calls);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDefaultArgumentsShareTheCacheOfTheirExplicitValues()
    {
        $this->mockCurrency();

        $default = $this->adapter->findAll();
        $explicit = $this->adapter->findAll(true, false, true);

        $this->assertSame([[true, false, true]], $this->calls);
        $this->assertSame($default, $explicit);
    }

    private function mockCurrency()
    {
        $calls = &$this->calls;
        $currency = \Mockery::mock('alias:\Currency');
        $currency->shouldReceive('findAll')
            ->andReturnUsing(function ($active, $groupBy, $currentShopOnly) use (&$calls) {
                $calls[] = [$active, $groupBy, $currentShopOnly];

                return [['id_currency' => count($calls), 'iso_code' => 'EUR']];
            });
    }
}
