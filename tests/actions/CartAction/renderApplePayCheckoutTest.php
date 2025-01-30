<?php

namespace PayPlug\tests\actions\CartAction;

/**
 * @group unit
 * @group action
 * @group cart_action
 *
 * @runTestsInSeparateProcesses
 */
class renderApplePayCheckoutTest extends BaseCartAction
{
    public $payment_method_class;
    public $payment_method;
    public $routes;
    public $assign;
    public $media;
    public $link;

    public function setUp()
    {
        parent::setUp();
        $browser = \Mockery::mock('Browser');
        $browser->shouldReceive([
            'getName' => 'browser',
        ]);
        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->routes = \Mockery::mock('Routes');
        $this->assign = \Mockery::mock('Assign');
        $this->media = \Mockery::mock('Media');
        $this->link = \Mockery::mock('Link');
        $this->plugin->shouldReceive([
            'getAssign' => $this->assign,
            'getBrowser' => $browser,
            'getMedia' => $this->media,
            'getPaymentMethodClass' => $this->payment_method_class,
            'getRoutes' => $this->routes,
        ]);
    }

    public function testWhenConfigurationDoesNotAllowGuestOrder()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => false],
        ]);

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    public function testWhenApplePayCartWhenBrowserIsNotSafari()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => 42],
        ]);
        $this->browser_validator->shouldReceive([
            'isApplePayCompatible' => [
                'result' => false,
                'message' => 'This browser is not applepay compatible.',
            ],
        ]);
        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    public function testWhenNoAvailableCarriersFound()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => 42],
        ]);
        $this->browser_validator->shouldReceive([
            'isApplePayCompatible' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->payment_method->shouldReceive([
            'getCarriersList' => [],
        ]);
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
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
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => 42],
        ]);
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
        $carrier_list = [
            42,
        ];
        $this->payment_method->shouldReceive([
            'getCarriersList' => $carrier_list,
        ]);
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->routes->shouldReceive([
            'getSourceUrl' => [
                'applepay' => 'source_url',
            ],
        ]);
        $this->assign->shouldReceive([
            'assign' => true,
        ]);
        $this->media->shouldReceive([
            'addJsDef' => true,
        ]);
        $this->link->shouldReceive([
            'getModuleLink' => '',
        ]);
        $this->context->link = $this->link;
        $this->configClass->shouldReceive([
            'fetchTemplate' => 'applepay_template',
        ]);
        $this->assertSame('applepay_template', $this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckoutTest when is product checkout.
     */
    public function testWhenTemplateIsReturnForProduct()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => 42],
        ]);
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

        $carrier_list = [42];
        $this->payment_method->shouldReceive([
            'getCarriersList' => $carrier_list,
            'hasCompatibleCarriersForProduct' => $carrier_list,
        ]);
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->routes->shouldReceive([
            'getSourceUrl' => [
                'applepay' => 'source_url',
            ],
        ]);
        $this->assign->shouldReceive([
            'assign' => true,
        ]);
        $this->media->shouldReceive([
            'addJsDef' => true,
        ]);
        $this->link->shouldReceive([
            'getModuleLink' => '',
        ]);
        $this->context->link = $this->link;
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'id_product')
            ->andReturn('42');
        $this->configClass->shouldReceive([
            'fetchTemplate' => 'applepay_template',
        ]);

        // Assert that the method returns the expected template
        $this->assertSame('applepay_template', $this->action->renderApplePayCheckout());
    }
}
