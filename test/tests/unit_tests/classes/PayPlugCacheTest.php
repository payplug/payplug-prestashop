<?php

//namespace Tests;
use PHPUnit\Framework\TestCase;


/**
 * @group unit
 * @group ci
 * @group recommended
 */
class PayPlugCacheTest extends TestCase
{
    /**
     * @test
     */
    public function cacheValue()
    {
        // définition de la fixture dans la fonction
        $payplug_cache = \PayPlugMock\PayPlugCacheMock::getMock();
        $payplug_cache->cache_value = 'test';

        $this->assertEquals('test', $payplug_cache->cache_value);
    }

    /**
     * @test
     */
    public function checkMock()
    {
        // définition de la fixture via un mock
        $payplug_cache = \PayPlugMock\PayPlugCacheMock::getMock();
        $this->assertEquals(1234, $payplug_cache->id, 'wrong payplug cache id');
    }

}
