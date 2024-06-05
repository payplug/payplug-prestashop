<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class createActionTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method
     */
    public function testWhenGivenMethodIsntValidString($method)
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
            $this->action->createAction($method, $payment_tab)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_tab
     */
    public function testWhenGivenPaymentTabIsntValidArray($payment_tab)
    {
        $method = 'standard';
        $this->assertSame(
            [],
            $this->action->createAction($method, $payment_tab)
        );
    }

    public function testWhenAResourceAlreadyExistsAndCantBeRemoved()
    {
        $method = 'standard';
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];

        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [
                    'id_payplug_payment' => 42,
                    'resource_id' => 'pay_azerty1234',
                    'method' => 'standard',
                    'id_cart' => 42,
                    'cart_hash' => 'cart-hash-azerty1234567',
                    'schedules' => 'NULL',
                    'date_upd' => '1970-01-01 00:00:00',
                ],
            ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->cancellable = true;
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $this->action
            ->shouldReceive([
                'removeAction' => false,
            ]);

        $this->assertSame(
            [],
            $this->action->createAction($method, $payment_tab)
        );
    }

    public function testWhenResourceCantBeSaved()
    {
        $method = 'standard';
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];

        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
            ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->cancellable = true;
        $payment_method
            ->shouldReceive([
                'saveResource' => [
                    'result' => false,
                ],
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $this->assertSame(
            [],
            $this->action->createAction($method, $payment_tab)
        );
    }

    public function testWhenResourceCantBeSavedInDataBase()
    {
        $method = 'standard';
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];

        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
                'createPayment' => false,
            ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->cancellable = true;
        $payment_method
            ->shouldReceive([
                'saveResource' => [
                    'result' => false,
                ],
                'getPaymentMethodHash' => 'cart-hash-azerty1234567',
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $this->assertSame(
            [],
            $this->action->createAction($method, $payment_tab)
        );
    }

    public function testWhenSelectedMethodIsApplePay()
    {
        $method = 'applepay';
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
                'createPayment' => $resource,
            ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->cancellable = true;
        $payment_method
            ->shouldReceive([
                'saveResource' => $resource,
                'getPaymentMethodHash' => 'cart-hash-azerty1234567',
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $this->assertSame(
            $resource,
            $this->action->createAction($method, $payment_tab)
        );
    }

    public function testWhenTheReturnUrlIsReturned()
    {
        $method = 'standard';
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];

        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
                'createPayment' => $resource,
            ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->cancellable = true;
        $return_url = [
            'result' => 'new_card',
            'embedded' => false,
            'redirect' => true,
            'return_url' => 'return_url',
            'resource_stored' => [],
        ];
        $payment_method
            ->shouldReceive([
                'saveResource' => $resource,
                'getPaymentMethodHash' => 'cart-hash-azerty1234567',
                'getReturnUrl' => $return_url,
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $this->assertSame(
            $return_url,
            $this->action->createAction($method, $payment_tab)
        );
    }
}
