<?php

namespace PayPlug\tests\mock;

use Payplug\Resource\Refund;

class RefundMock
{
    public static function get($parameters = [])
    {
        $attributes = [
            'id' => 're_12345azerty',
            'payment_id' => 'pay_12345azerty',
            'object' => 'refund',
            'is_live' => true,
            'amount' => 4242,
            'currency' => 'EUR',
            'created_at' => 1434012358,
            'metadata' => [
                'customer_id' => 42,
                'reason' => 'The delivery was delayed',
            ],
        ];

        if (!empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $attributes[$key] = $value;
            }
        }

        return Refund::fromAttributes($attributes);
    }
}
