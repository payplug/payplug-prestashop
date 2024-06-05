<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getPaymentMethodHashTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_tab
     */
    public function testWhenGivenPaymentTabIsntValidObject($payment_tab)
    {
        $this->assertSame('', $this->classe->getPaymentMethodHash($payment_tab));
    }

    public function testWhenGivenPaymentTabIsntEmpty()
    {
        $this->assertSame('', $this->classe->getPaymentMethodHash([]));
    }

    public function testWhenHashIsReturned()
    {
        $payment_tab = [
            'is_live' => true,
            'amount' => 42042,
            'hosted_payment' => [
                'payment_url' => 'payment_url',
                'return_url' => 'return_url',
                'cancel_url' => 'cancel_url',
            ],
            'notification' => [
                'url' => 'url',
                'response_code' => null,
            ],
            'metadata' => [
                'ID Client' => 4,
                'ID Cart' => 17,
                'Website' => 'website',
            ],
            'billing' => [
                'title' => null,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => '1 rue de paris',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75000',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'jdoe@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+336123456789',
                'language' => 'fr',
            ],
            'shipping' => [
                'title' => null,
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => '1 rue de paris',
                'address2' => null,
                'company_name' => 'Cedric PayPlug',
                'postcode' => '75000',
                'city' => 'Paris',
                'state' => null,
                'country' => 'FR',
                'email' => 'jdoe@payplug.com',
                'mobile_phone_number' => null,
                'landline_phone_number' => '+336123456789',
                'language' => 'fr',
                'delivery_type' => 'BILLING',
            ],
        ];
        $expected_hash = hash('sha256', json_encode($payment_tab));
        $this->assertSame(
            $expected_hash,
            $this->classe->getPaymentMethodHash($payment_tab)
        );
    }
}
