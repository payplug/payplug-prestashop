<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 */
class dispatchActionTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method
     */
    public function testWhenGivenMethodsIsntValidString($method)
    {
        $this->assertSame(
            [],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsntInExpectedPayment()
    {
        $method = 'unexpected_method';
        $this->assertSame(
            [],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenSelectMethodIsOneClick()
    {
        $method = 'one_click';
        $this->toolsAdapter->shouldReceive([
            'tool' => 42,
        ]);
        $this->cartAdapter->shouldReceive([
            'get' => CartMock::get(),
        ]);
        $this->context->shouldReceive('getPageLink')
            ->once()
            ->with('order', [
                'step' => '3',
                'embedded' => '1',
                'pc' => 42,
                'def' => 1,
                'modulename' => 'payplug',
            ])
            ->andReturn('https://shop.example/order?step=3&embedded=1&pc=42&def=1&modulename=payplug');

        $this->assertSame(
            [
                'return_url' => 'https://shop.example/order?step=3&embedded=1&pc=42&def=1&modulename=payplug',
            ],
            $this->action->dispatchAction($method)
        );
    }

    /**
     * @dataProvider embeddedReturnUrlProvider
     *
     * @param string $method
     * @param string $payment_methods
     * @param array $expected_args
     */
    public function testWhenModuleIsConfigureWithPopupDisplay($method, $payment_methods, $expected_args)
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn($payment_methods);
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('popup');
        $this->context->shouldReceive('getPageLink')
            ->once()
            ->with('order', $expected_args)
            ->andReturn('https://shop.example/order');

        $this->assertSame(
            [
                'return_url' => 'https://shop.example/order',
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function embeddedReturnUrlProvider()
    {
        $deferred = '{"standard":true, "deferred": true, "amex": true}';
        $not_deferred = '{"standard":true, "deferred": false, "amex": true}';
        $base = ['step' => '3', 'embedded' => '1', 'modulename' => 'payplug'];

        return [
            'standard, deferred' => ['standard', $deferred, $base + ['def' => '1']],
            'standard, not deferred' => ['standard', $not_deferred, $base],
            'installment, deferred' => ['installment', $deferred, $base + ['inst' => '1', 'def' => '1']],
            'amex never deferred' => ['amex', $deferred, $base + ['amex' => '1']],
        ];
    }

    public function testWhenNoPaymentTabIsReturnForCurrentPaymentMethod()
    {
        $method = 'standard';
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->payment_method->shouldReceive([
            'getPaymentTab' => [],
        ]);

        $this->assertSame(
            [],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenCurrentPaymentMethodForceTheResourceCreation()
    {
        $method = 'standard';
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->payment_method->force_resource = true;
        $this->payment_method->shouldReceive([
            'getPaymentTab' => [
                'amount' => 4242,
                'force_3ds' => false,
                'hosted_payment' => [],
                'metadata' => [],
                'allow_save_card' => false,
            ],
        ]);

        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);

        $this->action->shouldReceive([
            'createAction' => 'payment created',
            'retrieveAction' => 'payment retrieved',
        ]);

        $this->assertSame(
            'payment created',
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenNoResourceExistForCurrentCart()
    {
        $method = 'standard';
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->payment_method->force_resource = false;
        $this->payment_method->shouldReceive([
            'getPaymentTab' => [
                'amount' => 4242,
                'force_3ds' => false,
                'hosted_payment' => [],
                'metadata' => [],
                'allow_save_card' => false,
            ],
        ]);

        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);

        $this->action->shouldReceive([
            'createAction' => 'payment created',
            'retrieveAction' => 'payment retrieved',
        ]);

        $this->assertSame(
            'payment created',
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenResourceAlreadyExistsButIsNoValid()
    {
        $method = 'standard';
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->payment_method->force_resource = false;
        $this->payment_method->shouldReceive([
            'getPaymentTab' => [
                'amount' => 4242,
                'force_3ds' => false,
                'hosted_payment' => [],
                'metadata' => [],
                'allow_save_card' => false,
            ],
            'isValidResource' => false,
        ]);

        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);

        $this->action->shouldReceive([
            'createAction' => 'payment created',
            'retrieveAction' => 'payment retrieved',
        ]);

        $this->assertSame(
            'payment created',
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenResourceCreatedAndIsValid()
    {
        $method = 'standard';
        $this->configuration->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $this->payment_method->force_resource = false;
        $this->payment_method->shouldReceive([
            'getPaymentTab' => [
                'amount' => 4242,
                'force_3ds' => false,
                'hosted_payment' => [],
                'metadata' => [],
                'allow_save_card' => false,
            ],
            'isValidResource' => true,
        ]);

        $this->payment_repository->shouldReceive([
            'getBy' => [
                'id_payplug_payment' => 42,
                'resource_id' => 'pay_azerty1234',
                'is_live' => true,
                'method' => 'standard',
                'id_cart' => 42,
                'cart_hash' => 'cart-hash-azerty1234567',
                'schedules' => 'NULL',
                'date_upd' => '1970-01-01 00:00:00',
            ],
        ]);

        $this->action->shouldReceive([
            'createAction' => 'payment created',
            'retrieveAction' => 'payment retrieved',
        ]);

        $this->assertSame(
            'payment retrieved',
            $this->action->dispatchAction($method)
        );
    }
}
