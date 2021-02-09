<?php

namespace PayPlugMock;

class PayPlugCacheMock
{
    public static function getMock()
    {
        $payplug_cache = new \stdClass();
        $payplug_cache->id = 1234;
        $payplug_cache->cache_key = 'Payplug::OneySimulations_17976_GP_x3_with_fees_x4_with_fees_live';
        $payplug_cache->cache_value = '{"result":true,"simulations":{}}';
        $payplug_cache->date_add = '2020-06-24 12:09:04';
        $payplug_cache->date_upd = '2020-06-24 12:09:04';
        return $payplug_cache;
    }
}
