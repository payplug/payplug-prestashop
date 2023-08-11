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
    public function setUp()
    {
        parent::setUp();

        $paymentClass = \Mockery::mock('PaymentClass');
        $paymentClass
            ->shouldReceive('preparePayment')
            ->andReturnUsing(function ($payment_options) {
                return $payment_options;
            });
        $this->dependencies->paymentClass = $paymentClass;
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method
     */
    public function testWhenGivenMethodsIsNotValidString($method)
    {
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $method must be a string.',
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsNotInExpectedPayment()
    {
        $method = 'unexpected_method';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Invalid argument, $method given is not expected.',
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsApplePay()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);

        $method = 'applepay';
        $paymentClass = \Mockery::mock('PaymentClass');
        $paymentClass
            ->shouldReceive('preparePayment')
            ->andReturnUsing(function ($payment_options) {
                return $payment_options;
            });
        $this->dependencies->paymentClass = $paymentClass;

        $this->assertSame(
            [
                'is_applepay' => true,
                'payment_context' => [
                    'apple_pay' => [
                        'domain_name' => 'my-mock.com',
                        'application_data' => 'eyJhcHBsZV9wYXlfZG9tYWluIjoibXktbW9jay5jb20ifQ==',
                    ],
                ],
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsBancontact()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);

        $method = 'bancontact';

        $this->assertSame(
            [
                'is_bancontact' => true,
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsOneClick()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);

        $method = 'one_click';

        $this->assertSame(
            [
                'result' => true,
                'return_url' => 'index.php?controller=order&step=3&embedded=1&pc=42&def=1&modulename=payplug',
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsOney()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);

        $method = 'oney';

        $this->assertSame(
            [
                'is_oney' => 42,
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsEmbedded()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);

        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('popup');

        $method = 'standard';

        $this->assertSame(
            [
                'result' => true,
                'return_url' => 'index.php?controller=order&step=3&embedded=1&def=1&modulename=payplug',
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsAmex()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('embedded_mode')
            ->andReturn('redirect');
        $method = 'amex';

        $this->assertSame(
            [
                'is_installment' => false,
                'is_amex' => true,
                'is_deferred' => false,
            ],
            $this->action->dispatchAction($method)
        );
    }
}
