<?php

namespace PayPlug\tests\actions\CartAction;

/**
 * @group unit
 * @group action
 * @group renderer_action
 *
 * @runTestsInSeparateProcesses
 */
class renderApplePayCheckoutTest extends BaseCartAction
{
    private $payment_method_class;

    public function setUp()
    {
        parent::setUp();
        $browser = \Mockery::mock('Browser');
        $browser
            ->shouldReceive([
            'getName' => 'browser',
        ]);
        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->plugin
            ->shouldReceive([
            'getBrowser' => $browser,
            'getPaymentMethodClass' => $this->payment_method_class,
        ]);
    }

    public function testWhenApplePayCartWhenBrowserIsNotSafari()
    {
        $this->browser_validator
            ->shouldReceive([
            'isApplePayCompatible' => [
                'result' => false,
                'message' => 'This browser is not applepay compatible.',
            ],
        ]);

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    public function testWhenNoAvailableCarriersFound()
    {
        $this->browser_validator->shouldReceive([
            'isApplePayCompatible' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $payment_method = \Mockery::mock('PaymentMethod');
        $payment_method
            ->shouldReceive([
                'getCarriersList' => [],
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);
        $controller = $this->instance->shouldReceive([
                                                         'getController' => 'cart',
                                                     ]);
        $this->dispatcher->shouldReceive([
                                             'getInstance' => $controller,
                                         ]);
        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    public function testWhenTemplateIsReturn()
    {
        $this->browser_validator->shouldReceive([
            'isApplePayCompatible' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $controller = $this->instance->shouldReceive([
                                                         'getController' => 'cart',
                                                     ]);
        $this->dispatcher->shouldReceive([
                                             'getInstance' => $controller,
                                         ]);
        $payment_method = \Mockery::mock('PaymentMethod');
        $carrier_list = [
            42,
        ];
        $payment_method
            ->shouldReceive([
                'getCarriersList' => $carrier_list,
            ]);
        $this->payment_method_class
            ->shouldReceive([
                'getPaymentMethod' => $payment_method,
            ]);

        $routes = \Mockery::mock('Routes');
        $routes
            ->shouldReceive([
                'getSourceUrl' => [
                    'applepay' => 'source_url',
                ],
            ]);
        $assign = \Mockery::mock('Assign');
        $assign
            ->shouldReceive([
                'assign' => true,
            ]);
        $media = \Mockery::mock('Media');
        $media
            ->shouldReceive([
                'addJsDef' => true,
            ]);
        $link = \Mockery::mock('Link');
        $link
            ->shouldReceive([
                'getModuleLink' => '',
            ]);
        $this->context->link = $link;

        $this->plugin
            ->shouldReceive([
                'getRoutes' => $routes,
                'getAssign' => $assign,
                'getMedia' => $media,
            ]);

        $this->configClass
            ->shouldReceive([
                'fetchTemplate' => 'applepay_template',
            ]);

        $this->assertSame('applepay_template', $this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckoutTest when is product checkout.
     */
    public function testWhenTemplateIsReturnForProduct()
    {
        // Mock the browser compatibility check
        $this->browser_validator->shouldReceive([
            'isApplePayCompatible' => [
                'result' => true,
                'message' => '',
            ],
        ]);

        // Mock the controller to return 'product'
        $controller = $this->instance->shouldReceive([
                                                         'getController' => 'product',
                                                     ]);
        $this->dispatcher->shouldReceive([
                                             'getInstance' => $controller,
                                         ]);

        $payment_method = \Mockery::mock('PaymentMethod');
        $carrier_list = [42];
        $payment_method
            ->shouldReceive([
                                'getCarriersList' => $carrier_list,
                            ]);
        $this->payment_method_class
            ->shouldReceive([
                                'getPaymentMethod' => $payment_method,
                            ]);

        $routes = \Mockery::mock('Routes');
        $routes
            ->shouldReceive([
                                'getSourceUrl' => [
                                    'applepay' => 'source_url',
                                ],
                            ]);
        $assign = \Mockery::mock('Assign');
        $assign->shouldReceive([
                                   'assign' => true,
                               ]);
        $media = \Mockery::mock('Media');
        $media->shouldReceive([
                                  'addJsDef' => true,
                              ]);
        $link = \Mockery::mock('Link');
        $link->shouldReceive([
                                 'getModuleLink' => '',
                             ]);
        $this->context->link = $link;

        $this->plugin
            ->shouldReceive([
                                'getRoutes' => $routes,
                                'getAssign' => $assign,
                                'getMedia' => $media,
                            ]);
        $this->configClass
            ->shouldReceive([
                                'fetchTemplate' => 'applepay_template',
                            ]);

        // Assert that the method returns the expected template
        $this->assertSame('applepay_template', $this->action->renderApplePayCheckout());
    }
}
