<?php

namespace PayPlug\tests\actions\PaymentAction;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class retrieveActionTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $stored_resource
     */
    public function testWhenGivenStoredResourceIsntValidArray($stored_resource)
    {
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $this->assertSame(
            [],
            $this->action->retrieveAction($stored_resource, $payment_tab)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_tab
     */
    public function testWhenGivenPaymentTabIsntValidArray($payment_tab)
    {
        $stored_resource = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this->assertSame(
            [],
            $this->action->retrieveAction($stored_resource, $payment_tab)
        );
    }

    public function testWhenTheStoredHashAndGeneratedOneDoesNotCorrespond()
    {
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $stored_resource = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];

        $this->payment_method->shouldReceive([
            'getPaymentMethodHash' => 'not the same hash',
        ]);
        $this->action->shouldReceive([
            'createAction' => 'create action',
        ]);

        $this->assertSame(
            'create action',
            $this->action->retrieveAction($stored_resource, $payment_tab)
        );
    }

    public function testWhenTheStoredHashAndGeneratedOneCorrespond()
    {
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $stored_resource = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];

        $return_url = [
            'result' => 'new_card',
            'embedded' => false,
            'redirect' => true,
            'return_url' => 'return_url',
            'resource_stored' => [],
        ];
        $this->payment_method->shouldReceive([
            'getPaymentMethodHash' => 'cart-hash-azerty1234567',
            'getReturnUrl' => $return_url,
        ]);
        $this->assertSame(
            $return_url,
            $this->action->retrieveAction($stored_resource, $payment_tab)
        );
    }
}
