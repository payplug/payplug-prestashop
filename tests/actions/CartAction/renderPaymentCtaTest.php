<?php

namespace PayPlug\tests\actions\CartAction;

/**
 * @group unit
 * @group action
 * @group cart_action
 *
 * @runTestsInSeparateProcesses
 */
class renderPaymentCtaTest extends BaseCartAction
{
    private $payment_method;
    private $payment_method_class;

    public function setUp()
    {
        parent::setUp();

        $browser = \Mockery::mock('Browser');
        $browser
            ->shouldReceive(['getName' => 'browser']);

        $this->payment_method = \Mockery::mock('PaymentMethod');
        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');

        $this->plugin
            ->shouldReceive([
                'getBrowser' => $browser,
                'getPaymentMethodClass' => $this->payment_method_class,
            ]);

        $this->browser_validator
            ->shouldReceive([
                'isApplePayCompatible' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->action
            ->shouldReceive([
                'renderApplepayCheckout' => 'applepay_template',
            ]);
    }

    /**
     * @description Test when Apple Pay is not allowed in cart
     */
    public function testWhenApplePayCartIsNotAllowed()
    {
        $this->configureMocks(0, '{"applepay":true}', '{"checkout":true,"cart":false,"product":true}', 'cart');
        $this->payment_method->shouldReceive(['getCarriersList' => []]);
        $this->assertFalse($this->action->renderPaymentCTA());
    }

    /**
     * @description Test when Apple Pay is allowed for product page
     */
    public function testWhenApplePayProductIsAllowed()
    {
        $this->configureMocks(0, '{"applepay":true}', '{"checkout":false,"cart":false,"product":true}', 'product');
        $this->payment_method
            ->shouldReceive([
                'getCarriersList' => [
                    42,
                ],
            ]);

        $this->configurePluginMocks('applepay_template');

        $this->assertSame('applepay_template', $this->action->renderPaymentCTA());
    }

    /**
     * @description Test when Apple Pay is allowed in cart page
     */
    public function testWhenApplePayCartIsAllowed()
    {
        $this->configureMocks(0, '{"applepay":true}', '{"checkout":false,"cart":true,"product":false}', 'cart');
        $this->payment_method
            ->shouldReceive(['getCarriersList' => [42]]);

        $this->configurePluginMocks('applepay_template');

        $this->assertSame('applepay_template', $this->action->renderPaymentCTA());
    }

    /**
     * @description Test when Apple Pay feature is disabled
     */
    public function testWhenApplePayFeatureIsNotAllowed()
    {
        $this->configureMocks(0, '{"applepay":false}', '{"checkout":false,"cart":false,"product":false}', $this->controller);

        $this->assertFalse($this->action->renderPaymentCTA());
    }

    /**
     * @description Test when in sandbox mode
     */
    public function testWhenIsSandboxMode()
    {
        $this->configureMocks(1, '{"applepay":true}', '{"checkout":false,"cart":false,"product":false}', $this->controller);

        $this->assertFalse($this->action->renderPaymentCTA());
    }

    /**
     * @description this function is for calling necessary config mocks
     *
     * @param $sandbox_mode
     * @param $payment_methods
     * @param $applepay_display
     * @param $controller
     */
    private function configureMocks($sandbox_mode, $payment_methods, $applepay_display, $controller)
    {
        $this->configuration
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn($sandbox_mode);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn($payment_methods);
        $this->configuration
            ->shouldReceive('getValue')
            ->with('applepay_display')
            ->andReturn($applepay_display);

        $this->instance
            ->shouldReceive(['getController' => $controller]);
        $this->dispatcher
            ->shouldReceive(['getInstance' => $this->instance]);
        $this->payment_method_class
            ->shouldReceive('getPaymentMethod')
            ->andReturn($this->payment_method);
    }

    /**
     * @description this function is for calling necessary plugin mocks
     *
     * @param $template
     */
    private function configurePluginMocks($template)
    {
        $routes = \Mockery::mock('Routes');
        $routes
            ->shouldReceive(['getSourceUrl' => ['applepay' => 'source_url']]);

        $assign = \Mockery::mock('Assign');
        $assign
            ->shouldReceive(['assign' => true]);

        $media = \Mockery::mock('Media');
        $media
            ->shouldReceive(['addJsDef' => true]);

        $link = \Mockery::mock('Link');
        $link
            ->shouldReceive(['getModuleLink' => '']);
        $this->context->link = $link;

        $this->plugin
            ->shouldReceive([
                'getRoutes' => $routes,
                'getAssign' => $assign,
                'getMedia' => $media,
            ]);
        $this->configClass
            ->shouldReceive('fetchTemplate')->andReturn($template);
    }
}
