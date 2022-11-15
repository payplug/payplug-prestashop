<?php

namespace PayPlug\tests\mock;

use Payplug\Resource\InstallmentPlan;
use Payplug\Resource\Payment;

class PaymentMock
{
    public static $payment_parameters = [
        'oneclick' => [
            'is_paid' => true,
            'paid_at' => 1614949567,
            'is_3ds' => false,
            'card' => [
                'last4' => '0001',
                'country' => 'FR',
                'exp_year' => 2030,
                'exp_month' => 9,
                'brand' => 'CB',
                'id' => 'card_3EOJHyQXNCG8gZ452cUA0y',
                'metadata' => null,
            ],
            'hosted_payment' => [
                'paid_at' => 1614949567,
            ],
            'refundable_after' => 1614949567,
            'refundable_until' => 1630501567,
            'metadata' => [
                'ID Client' => 4,
                'ID Cart' => 17,
                'Website' => 'http://localhost',
            ],
        ],
    ];

    public static function getStandard($parameters = [])
    {
        $resource = self::getDefault($parameters);

        return Payment::fromAttributes($resource);
    }

    public static function getDefault($parameters)
    {
        $defaultConfiguration = [
            'id' => 'pay_5ktNvd3BNCp6GPcqIZvY9j',
            'object' => 'payment',
            'is_live' => true,
            'amount' => 31320,
            'amount_refunded' => 0,
            'currency' => 'EUR',
            'created_at' => 1614939982,
            'description' => null,
            'is_paid' => false,
            'paid_at' => null,
            'is_refunded' => false,
            'is_3ds' => null,
            'save_card' => false,
            'card' => [
                'last4' => null,
                'country' => null,
                'exp_year' => null,
                'exp_month' => null,
                'brand' => null,
                'id' => null,
                'metadata' => null,
            ],
            'hosted_payment' => [
                'payment_url' => 'https://secure-qa.payplug.com/pay/5ktNvd3BNCp6GPcqIZvY9j',
                'return_url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/validation?ps=1&cartid=17',
                'cancel_url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/validation?ps=2&cartid=17',
                'paid_at' => null,
                'sent_by' => null,
            ],
            'notification' => [
                'url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/ipn',
                'response_code' => null,
            ],
            'metadata' => [
                'ID Client' => 4,
                'ID Cart' => 17,
                'Website' => 'http://localhost',
            ],
            'failure' => null,
            'installment_plan_id' => null,
            'authorization' => null,
            'refundable_after' => null,
            'refundable_until' => null,
            'billing' => [
                'title' => null,
                'first_name' => 'Cedric',
                'last_name' => 'PayPlug',
                'address1' => '110 avenue de France',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75013',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'ctouma@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+33667899297',
                'language' => 'fr',
            ],
            'shipping' => [
                'title' => null,
                'first_name' => 'Cedric',
                'last_name' => 'PayPlug',
                'address1' => '110 avenue de France',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75013',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'ctouma@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+33667899297',
                'language' => 'fr',
                'delivery_type' => 'BILLING',
            ],
        ];

        if (!empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $defaultConfiguration[$key] = $value;
            }
        }

        return $defaultConfiguration;
    }

    public static function getDeferred($parameters = [])
    {
        $defaultDeferred = [
            'object' => 'installment_plan',
            'id' => 'inst_1gDmrsoMdIAJfV2MxzkBPX',
            'is_active' => true,
            'is_fully_paid' => false,
            'metadata' => [
                'ID Cart' => 17,
                'Website' => 'http://localhost',
                'ID Client' => 4,
            ],
            'currency' => 'EUR',
            'failure' => null,
            'created_at' => 1614940725,
            'hosted_payment' => [
                'return_url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/validation?ps=1&cartid=17',
                'cancel_url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/validation?ps=2&cartid=17',
                'payment_url' => 'https://secure-qa.payplug.com/pay/3nfaejGO3m9dyHFIwfsUTR',
            ],
            'notification' => [
                'url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/ipn',
            ],
            'authorization' => [
                'authorized_amount' => 16658,
                'authorized_at' => 1668699788,
                'expires_at' => 1669248000,
            ],
            'is_live' => true,
            'billing' => [
                'title' => null,
                'first_name' => 'Cedric',
                'last_name' => 'PayPlug',
                'address1' => '110 avenue de France',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75013',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'ctouma@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+33667899297',
                'language' => 'fr',
            ],
            'shipping' => [
                'title' => null,
                'first_name' => 'Cedric',
                'last_name' => 'PayPlug',
                'address1' => '110 avenue de France',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75013',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'ctouma@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+33667899297',
                'language' => 'fr',
                'delivery_type' => 'BILLING',
            ],
        ];
        if (!empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $defaultDeferred[$key] = $value;
            }
        }

        $resource = self::getDefault($defaultDeferred);

        return Payment::fromAttributes($resource);
    }

    public static function getInstallment($parameters = [])
    {
        $defaultInstallment = [
            'object' => 'installment_plan',
            'id' => 'inst_1gDmrsoMdIAJfV2MxzkBPX',
            'is_active' => true,
            'is_fully_paid' => false,
            'metadata' => [
                'ID Cart' => 17,
                'Website' => 'http://localhost',
                'ID Client' => 4,
            ],
            'currency' => 'EUR',
            'failure' => null,
            'created_at' => 1614940725,
            'hosted_payment' => [
                'return_url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/validation?ps=1&cartid=17',
                'cancel_url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/validation?ps=2&cartid=17',
                'payment_url' => 'https://secure-qa.payplug.com/pay/3nfaejGO3m9dyHFIwfsUTR',
            ],
            'notification' => [
                'url' => 'http://localhost/prestashop_1.7.6.9/fr/module/payplug/ipn',
            ],
            'schedule' => [
                0 => [
                    'date' => '2021-03-05',
                    'amount' => 10440,
                    'payment_ids' => [
                        0 => 'pay_3nfaejGO3m9dyHFIwfsUTR',
                    ],
                ],
                1 => [
                    'date' => '2021-04-04',
                    'amount' => 10440,
                    'payment_ids' => [],
                ],
                2 => ['date' => '2021-05-04',
                    'amount' => 10440,
                    'payment_ids' => [],
                ],
            ],
            'is_live' => true,
            'billing' => [
                'title' => null,
                'first_name' => 'Cedric',
                'last_name' => 'PayPlug',
                'address1' => '110 avenue de France',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75013',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'ctouma@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+33667899297',
                'language' => 'fr',
            ],
            'shipping' => [
                'title' => null,
                'first_name' => 'Cedric',
                'last_name' => 'PayPlug',
                'address1' => '110 avenue de France',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75013',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'ctouma@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+33667899297',
                'language' => 'fr',
                'delivery_type' => 'BILLING',
            ],
        ];
        if (!empty($parameters)) {
            foreach ($parameters as $key => $value) {
                $defaultInstallment[$key] = $value;
            }
        }

        $resource = self::getDefault($defaultInstallment);

        return InstallmentPlan::fromAttributes(self::$payment_parameters['oneclick']);
    }

    public static function getOneClick()
    {
        return Payment::fromAttributes(self::$payment_parameters['oneclick']);
    }
}
