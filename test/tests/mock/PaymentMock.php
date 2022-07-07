<?php

namespace PayPlugMock;

use Payplug\Resource\Payment;
use Payplug\Payplug;

class PaymentMock
{
    public static function getStandardPayment()
    {
        $payment = \Payplug\Resource\Payment::fromAttributes([
            'id' => 'pay_7BMOc4IlKpypGc3zUmvr5C',
            'failure' => [],
            'amount_refunded' => '0',
            'card' => [
                'last4' => '1800',
                'country' => 'FR',
                'exp_year' => 2024,
                'exp_month' => 9,
                'brand' => 'Mastercard'
            ],
            'installment_plan_id' => '',
            'billing' => [
                'city' => 'Paris',
                'postcode' => '75008',
                'first_name' => 'hadrien',
                'title' => '',
                'state' => '',
                'mobile_phone_number' => '',
                'last_name' => 'de laforce',
                'landline_phone_number' => '+33123456789',
                'address1' => '1 rue de la boetie',
                'company_name' => 'payplug',
                'address2' => '',
                'email' => 'hdelaforce@payplug.com',
                'country' => 'FR',
                'language' => 'fr',
            ],
            'shipping' => [
                'city' => 'Paris',
                'postcode' => '75008',
                'first_name' => 'hadrien',
                'title' => '',
                'state' => '',
                'mobile_phone_number' => '',
                'last_name' => 'de laforce',
                'landline_phone_number' => '+33123456789',
                'address1' => '1 rue de la boetie',
                'delivery_type' => 'BILLING',
                'company_name' => 'payplug',
                'address2' => '',
                'email' => 'hdelaforce@payplug.com',
                'country' => 'FR',
                'language' => 'fr',
            ],
            'is_refunded' => '',
            'description' => '',
            'created_at' => '1592401755',
            'save_card' => '',
            'authorization' => [],
            'amount' => '150840',
            'notification' => [
                'url' => 'http://monsite.fr/fr/module/payplug/ipn',
                'response_code' => '',
            ],
            'object' => 'payment',
            'is_paid' => '',
            'hosted_payment' => [
                'return_url' => 'http://monsite.fr/fr/module/payplug/validation?ps=1&cartid=8',
                'paid_at' => '',
                'cancel_url' => 'http://monsite.fr/fr/module/payplug/validation?ps=2&cartid=8',
                'sent_by' => '',
                'payment_url' => 'https://secure-qa.payplug.com/pay/test/7BMOc4IlKpypGc3zUmvr5C',
            ],
            'paid_at' => '',
            'is_live' => '',
            'is_3ds' => '',
            'metadata' => [
                'Website' => 'http://presta16.local',
                'Cart' => '8',
                'Client' => '2',
            ],
            'currency' => 'EUR',
        ]);

        return $payment;
    }
}
