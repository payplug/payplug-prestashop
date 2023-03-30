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

    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
        yield [null];
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

    public function testWhenGivenMethodsDoesNotExistsInAvailablePayment()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [],
            ]);

        $method = 'standard';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $method not found from getAvailableOptions',
            ],
            $this->action->dispatchAction($method)
        );
    }

    public function testWhenGivenMethodsIsNotInAvailablePayment()
    {
        $this->toolsAdapter
            ->shouldReceive([
                'tool' => 42,
            ]);
        $this->cartAdapter
            ->shouldReceive([
                'get' => CartMock::get(),
            ]);
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'standard' => false,
                ],
            ]);

        $method = 'standard';
        $this->assertSame(
            [
                'result' => false,
                'message' => 'Given $method is not available',
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
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'applepay' => true,
                ],
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
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'bancontact' => true,
                ],
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
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'one_click' => true,
                    'deferred' => true,
                ],
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
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'oney' => true,
                ],
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
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'deferred' => true,
                    'embedded' => 'popup',
                    'standard' => true,
                ],
            ]);

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
        $this->configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'amex' => true,
                    'deferred' => false,
                    'embedded' => 'redirect',
                ],
            ]);

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
