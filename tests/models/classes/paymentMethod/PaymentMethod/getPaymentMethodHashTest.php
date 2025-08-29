<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentMethodHashTest extends BasePaymentMethod
{
    public $payment_tab;

    public function setUp()
    {
        parent::setUp();
        $this->payment_tab = [
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
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_tab
     */
    public function testWhenGivenPaymentTabIsntValidArray($payment_tab)
    {
        $this->assertSame('', $this->class->getPaymentMethodHash($payment_tab));
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_live
     */
    public function testWhenGivenPaymentTabIsntValidBoolean($is_live)
    {
        $this->assertSame('', $this->class->getPaymentMethodHash($this->payment_tab, $is_live));
    }

    public function testWhenGivenPaymentTabIsntEmpty()
    {
        $this->assertSame('', $this->class->getPaymentMethodHash([]));
    }

    public function testWhenHashIsReturned()
    {
        $expected_hash = hash('sha256', json_encode($this->payment_tab) . 'live');
        $this->assertSame(
            $expected_hash,
            $this->class->getPaymentMethodHash($this->payment_tab)
        );
    }
}
