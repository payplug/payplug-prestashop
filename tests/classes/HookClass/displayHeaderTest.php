<?php

namespace PayPlug\tests\classes\HookClass;

use PayPlug\classes\HookClass;
use PayPlug\src\utilities\helpers\LanguageHelper;

/**
 * @group unit
 * @group class
 * @group hook_class
 */
class displayHeaderTest extends BaseHookClass
{
    private $context;
    private $js_defs = [];
    private $widget_action;

    public function setUp(): void
    {
        parent::setUp();
        $this->js_defs = [];

        $this->config_class->shouldReceive('isAllowed')->andReturn(true)->byDefault();

        $this->configuration_values = [
            'payment_methods' => '{"oney":false,"applepay":false,"scalapay":true}',
            'embedded_mode' => 'redirect',
            'sandbox_mode' => '0',
        ];

        $media = \Mockery::mock('MediaAdapter');
        $media->shouldReceive('addJsDef')->andReturnUsing(function ($defs) {
            $this->js_defs = array_merge($this->js_defs, $defs);
        });

        $tools = \Mockery::mock('ToolsAdapter');
        $tools->shouldReceive('tool')->andReturn(false);

        $link = \Mockery::mock('Link');
        $link->shouldReceive('getModuleLink')->andReturn('https://shop.test/module/payplug/ajax');
        $this->context = new \stdClass();
        $this->context->link = $link;
        $context_adapter = \Mockery::mock('ContextAdapter');
        $context_adapter->shouldReceive('get')->andReturn($this->context);

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog')->andReturn(true);

        $this->plugin->shouldReceive([
            'getContext' => $context_adapter,
            'getLogger' => $logger,
            'getMedia' => $media,
            'getTools' => $tools,
        ]);
        $this->dependencies->shouldReceive('loadAdapterPresta')->andReturn(null);

        $this->widget_action = \Mockery::mock('ScalapayWidgetAction');
        $this->module->shouldReceive('getService')
            ->with('payplug.action.scalapay_widget')
            ->andReturn($this->widget_action)
            ->byDefault();

        $this->hook = new HookClass($this->dependencies);
    }

    public function testTheScalapayScriptIsAddedToTheHeader()
    {
        $script = '<script type="module" src="https://cdn.scalapay.com/widget/v5/js/scalapay-widget.js"></script>';
        $this->widget_action->shouldReceive('renderScriptTag')->once()->andReturn($script);

        $this->assertSame($script, $this->hook->renderHeader([]));
        $this->assertArrayHasKey('payplug_ajax_url', $this->js_defs);
        $this->assertSame('https://secure.payplug.com', $this->js_defs['PAYPLUG_DOMAIN']);
    }

    public function testDisplayHeaderItselfReturnsNoScript()
    {
        $this->module->shouldNotReceive('getService')->with('payplug.action.scalapay_widget');

        $this->assertNull($this->hook->displayHeader([]));
    }

    public function testNothingIsAddedWhenNoWidgetCanBeShown()
    {
        $this->widget_action->shouldReceive('renderScriptTag')->once()->andReturn('');

        $this->assertSame('', $this->hook->renderHeader([]));
    }

    public function testAScriptRenderingErrorDoesNotBreakTheHeader()
    {
        $this->widget_action->shouldReceive('renderScriptTag')->andThrow(new \RuntimeException('broken'));

        $this->assertSame('', $this->hook->renderHeader([]));
        $this->assertArrayHasKey('payplug_ajax_url', $this->js_defs);
    }

    public function testTheWidgetActionIsNotBuiltWhenScalapayIsOff()
    {
        $this->configuration_values['payment_methods'] = '{"oney":false,"applepay":false,"scalapay":false}';
        $this->module->shouldNotReceive('getService')->with('payplug.action.scalapay_widget');

        $this->assertSame('', $this->hook->renderHeader([]));
    }

    public function otherPageProvider()
    {
        yield ['index'];

        yield ['category'];

        yield ['order-confirmation'];
    }

    /**
     * @dataProvider otherPageProvider
     *
     * @param string $controller
     */
    public function testTheWidgetActionIsNotBuiltOnPagesWithoutWidget($controller)
    {
        $this->controller = $controller;
        $this->module->shouldNotReceive('getService')->with('payplug.action.scalapay_widget');

        $this->assertSame('', $this->hook->renderHeader([]));
    }

    public function widgetPageProvider()
    {
        yield ['product'];

        yield ['cart'];

        yield ['order'];
    }

    /**
     * @dataProvider widgetPageProvider
     *
     * @param string $controller
     */
    public function testTheScriptIsRequestedOnPagesWithAWidget($controller)
    {
        $this->controller = $controller;
        $this->widget_action->shouldReceive('renderScriptTag')->once()->andReturn('<script>');

        $this->assertSame('<script>', $this->hook->renderHeader([]));
    }

    public function testNothingIsRenderedWhenTheModuleIsNotAllowed()
    {
        $this->config_class->shouldReceive('isAllowed')->andReturn(false);
        $this->module->shouldNotReceive('getService')->with('payplug.action.scalapay_widget');

        $this->assertFalse($this->hook->displayHeader([]));
        $this->assertSame('', $this->hook->renderHeader([]));
    }

    /**
     * @description shop language and Oney widget language provider
     *
     * @return \Generator
     */
    public function oneyWidgetLanguageProvider()
    {
        yield 'not accepted by Oney' => ['en-us', 'EN-EU'];

        yield 'accepted by Oney' => ['fr-fr', 'FR-FR'];

        yield 'accepted by Oney, other country' => ['nl-be', 'NL-BE'];

        yield 'empty' => ['', 'EN-EU'];
    }

    /**
     * @dataProvider oneyWidgetLanguageProvider
     *
     * @param string $language_code
     * @param string $expected
     */
    public function testTheOneyWidgetGetsALanguageAcceptedByOney($language_code, $expected)
    {
        $this->enableOney();
        $this->context->language = new \stdClass();
        $this->context->language->iso_code = substr($language_code, 0, 2);
        $this->context->language->language_code = $language_code;

        $this->hook->displayHeader([]);

        $this->assertSame($expected, $this->js_defs['payplug_oney_language']);
    }

    public function testTheOneyWidgetFallsBackToEnglishWithoutALanguageCode()
    {
        $this->enableOney();
        $this->context->language = new \stdClass();
        $this->context->language->iso_code = 'fr';

        $this->hook->displayHeader([]);

        $this->assertSame('EN-EU', $this->js_defs['payplug_oney_language']);
    }

    public function testTheOneyWidgetFallsBackToEnglishWithoutALanguage()
    {
        $this->enableOney();

        $this->hook->displayHeader([]);

        $this->assertSame('EN-EU', $this->js_defs['payplug_oney_language']);
    }

    public function testTheLanguageHelperIsNotBuiltWhenOneyIsOff()
    {
        $this->module->shouldNotReceive('getService')->with('payplug.utilities.helper.language');

        $this->hook->displayHeader([]);

        $this->assertArrayNotHasKey('payplug_oney_language', $this->js_defs);
    }

    private function enableOney()
    {
        $this->configuration_values['payment_methods'] = '{"oney":true,"applepay":false,"scalapay":false}';

        $business_codes = \Mockery::mock('BusinessCodes');
        $business_codes->shouldReceive('toList')->andReturn(['3x']);
        $oney_account_data = \Mockery::mock('OneyAccountData');
        $oney_account_data->shouldReceive([
            'getMerchantGuid' => 'merchant-guid',
            'getBusinessCodes' => $business_codes,
            'showLegalNotices' => true,
        ]);
        $oney_payment_method = \Mockery::mock('OneyPaymentMethod');
        $oney_payment_method->shouldReceive('getOneyAccountData')->andReturn($oney_account_data);
        $payment_method_class = \Mockery::mock('PaymentMethodClass');
        $payment_method_class->shouldReceive('getPaymentMethod')->with('oney')->andReturn($oney_payment_method);
        $routes = \Mockery::mock('Routes');
        $routes->shouldReceive('getSourceUrl')->andReturn(['oney' => 'https://oney.test/loader.min.js']);
        $this->plugin->shouldReceive([
            'getPaymentMethodClass' => $payment_method_class,
            'getRoutes' => $routes,
        ]);

        $language_helper = (new \ReflectionClass(LanguageHelper::class))->newInstanceWithoutConstructor();
        $this->module->shouldReceive('getService')
            ->with('payplug.utilities.helper.language')
            ->andReturn($language_helper);
    }
}
