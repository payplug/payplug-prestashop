<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class getAmountSelectorsTest extends BaseScalapayWidgetAction
{
    public function themeDefaultProvider()
    {
        yield 'classic product' => ['classic', 'product', ['.current-price-value']];

        yield 'classic cart' => ['classic', 'cart', ['.cart-summary-totals .cart-total .value']];

        yield 'classic checkout' => ['classic', 'checkout', ['.cart-summary-totals .cart-total .value']];

        yield 'hummingbird product' => ['hummingbird', 'product', ['.product__price']];

        yield 'hummingbird cart' => ['hummingbird', 'cart', []];

        yield 'hummingbird checkout' => ['hummingbird', 'checkout', []];

        yield 'unknown theme' => ['my_theme', 'product', []];
    }

    /**
     * @dataProvider themeDefaultProvider
     *
     * @param string $theme
     * @param string $page
     * @param array $expected
     */
    public function testTheSelectorsAreTheThemeDefaults($theme, $page, $expected)
    {
        $this->context->shop->theme_name = $theme;

        $this->assertSame($expected, $this->action->getAmountSelectors($page));
    }

    public function testTheParentThemeDefaultIsUsedForAChildTheme()
    {
        $theme = \Mockery::mock('Theme');
        $theme->shouldReceive('get')->with('parent')->andReturn('hummingbird');
        $this->context->shop->theme_name = 'my_child_theme';
        $this->context->shop->theme = $theme;

        $this->assertSame(['.product__price'], $this->action->getAmountSelectors('product'));
    }

    public function pageProvider()
    {
        yield ['cart'];

        yield ['checkout'];
    }

    /**
     * @dataProvider pageProvider
     *
     * @param string $page
     */
    public function testAHummingbirdChildThemeHasNoCartTotalSelector($page)
    {
        $theme = \Mockery::mock('Theme');
        $theme->shouldReceive('get')->with('parent')->andReturn('hummingbird');
        $this->context->shop->theme_name = 'my_child_theme';
        $this->context->shop->theme = $theme;

        $this->assertSame([], $this->action->getAmountSelectors($page));
    }

    /**
     * @dataProvider pageProvider
     *
     * @param string $page
     */
    public function testTheClassicCartTotalSelectorTargetsTheLineWithTaxes($page)
    {
        $this->assertSame(['.cart-summary-totals .cart-total .value'], $this->action->getAmountSelectors($page));
    }

    public function testAThemeWithAnUnknownParentHasNoSelector()
    {
        $theme = \Mockery::mock('Theme');
        $theme->shouldReceive('get')->with('parent')->andReturn('other_theme');
        $this->context->shop->theme_name = 'my_child_theme';
        $this->context->shop->theme = $theme;

        $this->assertSame([], $this->action->getAmountSelectors('product'));
    }

    public function testALeftoverStoredSelectorValueIsIgnored()
    {
        $this->stored['scalapay_amount_selectors'] = '{"product":["#custom"]}';

        $this->assertSame(['.current-price-value'], $this->action->getAmountSelectors('product'));
    }

    public function testAnUnknownPageHasNoSelector()
    {
        $this->assertSame([], $this->action->getAmountSelectors('home'));
    }
}
