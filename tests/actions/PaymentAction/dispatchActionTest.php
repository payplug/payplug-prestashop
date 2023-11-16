<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class dispatchActionTest extends BasePaymentAction
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method
     */
    public function testWhenGivenMethodsIsNotValidString($method)
    {
        $this->assertSame(
            [],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsNotInExpectedPayment()
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
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);
        $this->assertSame(
            [
                'return_url' => 'index.php?controller=order&step=3&embedded=1&pc=42&def=1&modulename=payplug',
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenModuleIsConfigureWithPopupDisplay()
    {
        $method = 'standard';
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('popup');

        $this->assertSame(
            [
                'return_url' => 'index.php?controller=order&step=3&embedded=1&def=1&modulename=' . $this->dependencies->name,
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenNoPaymentTabIsReturnForCurrentPaymentMethod()
    {
        $method = 'standard';
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $payment_method
            ->shouldReceive([
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->force_resource = true;

        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $payment_method
            ->shouldReceive([
                'getPaymentTab' => [
                    'amount' => 4242,
                    'force_3ds' => false,
                    'hosted_payment' => [],
                    'metadata' => [],
                    'allow_save_card' => false,
                ],
            ]);

        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
            ]);

        $this->action
            ->shouldReceive([
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->force_resource = false;

        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $payment_method
            ->shouldReceive([
                'getPaymentTab' => [
                    'amount' => 4242,
                    'force_3ds' => false,
                    'hosted_payment' => [],
                    'metadata' => [],
                    'allow_save_card' => false,
                ],
            ]);

        $this->payment_repository
            ->shouldReceive([
                'getByCart' => [],
            ]);

        $this->action
            ->shouldReceive([
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->force_resource = false;

        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $payment_method
            ->shouldReceive([
                'getPaymentTab' => [
                    'amount' => 4242,
                    'force_3ds' => false,
                    'hosted_payment' => [],
                    'metadata' => [],
                    'allow_save_card' => false,
                ],
                'isValidResource' => false,
            ]);

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

        $this->action
            ->shouldReceive([
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
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');

        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method->force_resource = false;

        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $payment_method
            ->shouldReceive([
                'getPaymentTab' => [
                    'amount' => 4242,
                    'force_3ds' => false,
                    'hosted_payment' => [],
                    'metadata' => [],
                    'allow_save_card' => false,
                ],
                'isValidResource' => true,
            ]);

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

        $this->action
            ->shouldReceive([
                'createAction' => 'payment created',
                'retrieveAction' => 'payment retrieved',
            ]);

        $this->assertSame(
            'payment retrieved',
            $this->action->dispatchAction($method)
        );
    }
}
