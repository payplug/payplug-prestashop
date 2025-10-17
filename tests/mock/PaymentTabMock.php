<?php

namespace PayPlug\tests\mock;

class PaymentTabMock
{
    public static function getStandard()
    {
        return self::getRaw();
    }

    public static function getInstallment()
    {
        $tab = self::getRaw();

        return array_merge(
            $tab,
            [
                'paymentTab' => [
                    'schedule' => [
                        0 => [
                            'date' => 'TODAY',
                            'amount' => 9280,
                        ],
                        1 => [
                            'date' => '2021-04-15',
                            'amount' => 9280,
                        ],
                        2 => [
                            'date' => '2021-05-15',
                            'amount' => 9280,
                        ],
                    ],
                ],
            ]
        );
    }

    public static function getRaw()
    {
        return [
            'currency' => 'EUR',
            'shipping' => [
                'title' => null,
                'first_name' => 'Lorem',
                'last_name' => 'Ipsum',
                'company_name' => 'Payplug',
                'email' => 'mock@payplug.com',
                'landline_phone_number' => '+33123456789',
                'mobile_phone_number' => '+33623456789',
                'address1' => '1 rue de l\'avenue',
                'address2' => null,
                'postcode' => '75000',
                'city' => 'Paris',
                'country' => 'FR',
                'language' => 'fr',
                'delivery_type' => 'BILLING',
            ],
            'billing' => [
                'title' => null,
                'first_name' => 'Lorem',
                'last_name' => 'Ipsum',
                'company_name' => 'Payplug',
                'email' => 'mock@payplug.com',
                'landline_phone_number' => '+33123456789',
                'mobile_phone_number' => '+33623456789',
                'address1' => '1 rue de l\'avenue',
                'address2' => null,
                'postcode' => '75000',
                'city' => 'Paris',
                'country' => 'FR',
                'language' => 'fr',
            ],
            'notification_url' => 'http://monsite.fr/fr/module/payplug/ipn',
            'force_3ds' => false,
            'hosted_payment' => [
                'return_url' => 'http://monsite.fr/fr/module/payplug/validation?ps=1&cartid=1',
                'cancel_url' => 'http://monsite.fr/fr/module/payplug/validation?ps=2&cartid=1',
            ],
            'metadata' => [
                'ID Client' => 1,
                'ID Cart' => 1,
                'Website' => 'http://monsite.fr',
            ],
            'allow_save_card' => true,
            'amount' => 3480,
        ];
    }
}
