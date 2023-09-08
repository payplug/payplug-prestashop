<?php

namespace PayPlug\tests\repositories\CacheRepository;

/**
 * @group unit
 * @group old_repository
 * @group cache
 * @group cache_repository
 *
 * @runTestsInSeparateProcesses
 */
final class SetCacheKeyTest extends BaseCacheRepository
{
    public function invalidDataProvider()
    {
        yield [15000, 'FR', 'not a array', 'Operations is not a valid array'];
        yield ['not numeric', 'FR', ['operation'], 'Amount is not a valid int'];
        yield [15000, false, ['operation'], 'Country is not a valid string'];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param mixed $amount
     * @param mixed $country
     * @param mixed $operations
     * @param mixed $errorMsg
     */
    public function testWithInvalidDataProvider($amount, $country, $operations, $errorMsg)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => $errorMsg,
            ],
            $this->repo->setCacheKey($amount, $country, $operations)
        );
    }

    public function testWithValidData()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(false);
        $this->assertSame(
            [
                'result' => 'Payplug::OneySimulations_15000_FR_operation_live',
                'message' => 'success',
            ],
            $this->repo->setCacheKey(15000, 'FR', ['operation'])
        );
    }
}
