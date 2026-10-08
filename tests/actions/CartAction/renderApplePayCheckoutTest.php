<?php

namespace PayPlug\tests\actions\CartAction;

/**
 * @group unit
 * @group action
 * @group cart_action
 */
class renderApplePayCheckoutTest extends BaseCartAction
{
    public $payment_method_class;
    public $payment_method;
    public $routes;
    public $assign;
    public $media;
    public $link;

    public function setUp(): void
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

    public function testWhenNoAvailableCarriersFound()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => 42],
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
        $this->cartAdapter->shouldReceive('checkQuantities')
            ->once()
            ->with($this->context->cart)
            ->andReturn(true);
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
        $this->mockNoRequestedAttribute();
        $this->product_adapter->shouldReceive('getDefaultAttribute')
            ->with(42)
            ->andReturn(0);
        $this->product_adapter->shouldReceive('isOrderableRegardingStock')
            ->once()
            ->with(42, 0)
            ->andReturn(true);
        $this->configClass->shouldReceive([
            'fetchTemplate' => 'applepay_template',
        ]);

        // Assert that the method returns the expected template
        $this->assertSame('applepay_template', $this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckout when the product can't be ordered regarding its stock.
     */
    public function testWhenProductIsNotOrderableRegardingStock()
    {
        $this->mockProductPage();
        $this->mockNoRequestedAttribute();
        $this->product_adapter->shouldReceive('getDefaultAttribute')
            ->with(42)
            ->andReturn(7);
        $this->product_adapter->shouldReceive('isOrderableRegardingStock')
            ->once()
            ->with(42, 7)
            ->andReturn(false);
        $this->configClass->shouldNotReceive('fetchTemplate');

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckout still defines the JS vars when the product can't be ordered regarding its stock,
     * as the CTA can be displayed later by the AJAX refresh of the product page (another combination selected).
     */
    public function testWhenProductIsNotOrderableRegardingStockJsDefIsAdded()
    {
        $this->mockProductPage();
        $this->mockNoRequestedAttribute();
        $this->product_adapter->shouldReceive('getDefaultAttribute')
            ->with(42)
            ->andReturn(7);
        $this->product_adapter->shouldReceive('isOrderableRegardingStock')
            ->once()
            ->with(42, 7)
            ->andReturn(false);
        $this->media->shouldReceive('addJsDef')
            ->once()
            ->with(\Mockery::on(function ($js_def) {
                return isset($js_def['applePayPaymentRequestAjaxURL'], $js_def['applePayMerchantSessionAjaxURL'], $js_def['applePayPaymentAjaxURL'])
                    && 1 === $js_def['applePayIdCart'];
            }))
            ->andReturn(true);
        $this->configClass->shouldNotReceive('fetchTemplate');

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckout when the product has no compatible carriers.
     */
    public function testWhenProductHasNoCompatibleCarriers()
    {
        $this->mockProductPage(false);
        $this->product_adapter->shouldNotReceive('isOrderableRegardingStock');
        $this->media->shouldNotReceive('addJsDef');
        $this->configClass->shouldNotReceive('fetchTemplate');

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckout uses the combination given in the hook params.
     */
    public function testWhenProductAttributeIsGivenInHookParams()
    {
        $this->mockProductPage();
        $this->product_adapter->shouldNotReceive('getDefaultAttribute');
        $this->product_adapter->shouldNotReceive('getIdProductAttributeByIdAttributes');
        $this->product_adapter->shouldReceive('isOrderableRegardingStock')
            ->once()
            ->with(42, 13)
            ->andReturn(false);

        $params = [
            'product' => new \ArrayObject([
                'id_product' => 42,
                'id_product_attribute' => '13',
            ]),
        ];
        $this->assertFalse($this->action->renderApplePayCheckout($params));
    }

    /**
     * test renderApplePayCheckout resolves the combination from the requested attribute groups.
     */
    public function testWhenProductAttributeIsResolvedFromRequestedGroups()
    {
        $this->mockProductPage();
        $group = [1 => 2, 3 => 4];
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'group')
            ->andReturn($group);
        $this->product_adapter->shouldReceive('getIdProductAttributeByIdAttributes')
            ->once()
            ->with(42, $group)
            ->andReturn(21);
        $this->product_adapter->shouldNotReceive('getDefaultAttribute');
        $this->product_adapter->shouldReceive('isOrderableRegardingStock')
            ->once()
            ->with(42, 21)
            ->andReturn(false);

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckout uses the combination given in the request when there is no group.
     */
    public function testWhenProductAttributeIsGivenInRequest()
    {
        $this->mockProductPage();
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'group')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'id_product_attribute')
            ->andReturn('9');
        $this->product_adapter->shouldNotReceive('getDefaultAttribute');
        $this->product_adapter->shouldNotReceive('getIdProductAttributeByIdAttributes');
        $this->product_adapter->shouldReceive('isOrderableRegardingStock')
            ->once()
            ->with(42, 9)
            ->andReturn(false);

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    /**
     * test renderApplePayCheckout when one of the cart products can't be ordered regarding its stock.
     */
    public function testWhenCartProductsAreNotOrderableRegardingStock()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => 42],
        ]);
        $this->instance->shouldReceive([
            'getController' => 'cart',
        ]);
        $this->payment_method->shouldReceive([
            'getCarriersList' => [42],
        ]);
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->cartAdapter->shouldReceive('checkQuantities')
            ->once()
            ->with($this->context->cart)
            ->andReturn(false);
        $this->mockCheckoutAssets();
        // the JS vars are still defined as the CTA can be displayed later by the AJAX refresh of the cart
        $this->media->shouldReceive('addJsDef')
            ->once()
            ->andReturn(true);
        $this->configClass->shouldNotReceive('fetchTemplate');

        $this->assertFalse($this->action->renderApplePayCheckout());
    }

    /**
     * @description Mock a logged customer on the product page of product 42
     *
     * @param bool $has_compatible_carriers
     */
    private function mockProductPage($has_compatible_carriers = true)
    {
        $this->configuration->shouldReceive('getValue')
            ->with('PS_GUEST_CHECKOUT_ENABLED')
            ->andReturn(false);
        $this->customer_adapter->shouldReceive([
            'get' => (object) ['id' => 42],
        ]);
        $this->instance->shouldReceive([
            'getController' => 'product',
        ]);
        $this->payment_method->shouldReceive([
            'getCarriersList' => [42],
        ]);
        $this->payment_method_class->shouldReceive([
            'getPaymentMethod' => $this->payment_method,
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'id_product')
            ->andReturn('42');
        $this->payment_method->shouldReceive('hasCompatibleCarriersForProduct')
            ->with(42)
            ->andReturn($has_compatible_carriers);
        $this->mockCheckoutAssets();
    }

    /**
     * @description Mock the assets needed by the applepay CTA (js url, smarty and js vars)
     */
    private function mockCheckoutAssets()
    {
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
        ])->byDefault();
        $this->link->shouldReceive([
            'getModuleLink' => 'module_link',
        ]);
        $this->context->link = $this->link;
        $this->context->language = (object) ['iso_code' => 'fr'];
    }

    /**
     * @description Mock a request without any requested combination
     */
    private function mockNoRequestedAttribute()
    {
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'group')
            ->andReturn(false);
        $this->tools_adapter->shouldReceive('tool')
            ->with('getValue', 'id_product_attribute')
            ->andReturn(false);
    }
}
