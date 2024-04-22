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
            'is_live' => true,
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
        $default_parameters = [
            'authorized_amount' => 424242,
            'authorization' => [
                'authorized_amount' => 424242,
                'authorized_at' => strtotime('-2 days'),
                'expires_at' => strtotime('+2 days'),
            ],
        ];

        foreach ($default_parameters as $key => $value) {
            if (!isset($parameters[$key])) {
                $parameters[$key] = $value;
            }
        }

        $resource = self::getDefault($parameters);
        unset($resource['amount']);

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

        return InstallmentPlan::fromAttributes($resource);
    }

    public static function getInstallmentSchedule()
    {
        $defaultConfiguration = [
            'installment_plan_id' => 'inst_1gDmrsoMdIAJfV2MxzkBPX',
        ];

        $resource = self::getDefault($defaultConfiguration);

        return Payment::fromAttributes($resource);
    }

    public static function getOneClick()
    {
        return Payment::fromAttributes(self::$payment_parameters['oneclick']);
    }

    /**
     * @description  get Satispay resource
     *
     * @param array $parameters
     *
     * @return Payment
     */
    public static function getSatispay($parameters = [])
    {
        $resource = self::getDefault($parameters);
        $resource['payment_method'] = [
            'type' => 'Satispay',
        ];

        return Payment::fromAttributes($resource);
    }

    public static function getOney($parameters = [])
    {
        $resource = self::getDefault($parameters);
        unset($resource['amount']);
        $resource['authorized_amount'] = 20880;
        $resource['payment_method'] = [
            'type' => 'oney_x3_with_fees',
            'is_pending' => false,
        ];
        $resource['payment_context'] = [
            'cart' => [
                [
                    'merchant_item_id' => '3',
                    'name' => 'Affiche encadrée The best is yet to come - Dimension : 40x60cm',
                    'price' => 3480,
                    'quantity' => 6,
                    'total_amount' => 20880,
                    'brand' => 'Graphic Corner',
                    'delivery_label' => 'Click and collect',
                    'expected_delivery_date' => '2022-11-23',
                    'delivery_type' => 'storepickup',
                ],
            ],
        ];
        $resource['authorization'] = [
            'authorized_amount' => 20880,
            'authorized_at' => '',
            'expires_at' => '',
        ];

        return Payment::fromAttributes($resource);
    }
}
