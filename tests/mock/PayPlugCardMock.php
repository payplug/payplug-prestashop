<?php

namespace PayPlug\tests\mock;

class PayPlugCardMock
{
    public static function get()
    {
        return [
            'id_payplug_card' => 1,
            'id_customer' => 1,
            'id_company' => 123456,
            'is_sandbox' => 1,
            'id_card' => 'card_4242LoremIpsumSit42442',
            'last4' => 4242,
            'exp_month' => 6,
            'exp_year' => 2030,
            'brand' => 'Visa',
            'country' => 'FR',
            'metadata' => 'N;',
        ];
    }

    public static function getExpired()
    {
        $card = self::get();
        $card['exp_year'] = 2020;

        return $card;
    }
}
