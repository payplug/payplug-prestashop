<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class getAmountSelectorsTest extends BaseScalapayWidgetAction
{
    public function testStoredSelectorsAreTrimmed()
    {
        $this->stored['scalapay_amount_selectors'] = '{"product":[".a"," .b "]}';

        $this->assertSame(['.a', '.b'], $this->action->getAmountSelectors('product'));
        $this->assertSame([], $this->logs);
    }

    public function themeDefaultProvider()
    {
        yield 'classic product' => ['classic', 'product', ['.current-price-value']];

        yield 'classic cart' => ['classic', 'cart', ['.cart-summary-totals .cart-total .value']];

        yield 'classic checkout' => ['classic', 'checkout', ['.cart-summary-totals .cart-total .value']];

        yield 'hummingbird product' => ['hummingbird', 'product', ['.product__price']];

        yield 'hummingbird cart' => ['hummingbird', 'cart', ['.cart-summary__total .cart-summary__line:first-child .cart-summary__value']];

        yield 'hummingbird checkout' => ['hummingbird', 'checkout', ['.cart-summary__total .cart-summary__line:first-child .cart-summary__value']];

        yield 'unknown theme' => ['my_theme', 'product', []];
    }

    /**
     * @dataProvider themeDefaultProvider
     *
     * @param string $theme
     * @param string $page
     * @param array $expected
     */
    public function testThemeDefaultIsUsedWhenNothingIsStoredForThePage($theme, $page, $expected)
    {
        $this->context->shop->theme_name = $theme;
        $this->stored['scalapay_amount_selectors'] = '{"cart":[".mine"]}';
        if ('cart' === $page) {
            $this->stored['scalapay_amount_selectors'] = '{"product":[".mine"]}';
        }

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

    public function missingStoredValueProvider()
    {
        yield 'missing row' => [false];

        yield 'empty object' => ['{}'];

        yield 'malformed json' => ['{"product":'];

        yield 'empty list' => ['{"product":[]}'];

        yield 'not a list' => ['{"product":".a"}'];
    }

    /**
     * @dataProvider missingStoredValueProvider
     *
     * @param mixed $stored
     */
    public function testThemeDefaultIsUsedWhenTheStoredValueHasNoSelectorForThePage($stored)
    {
        $this->stored['scalapay_amount_selectors'] = $stored;

        $this->assertSame(['.current-price-value'], $this->action->getAmountSelectors('product'));
    }

    public function invalidSelectorProvider()
    {
        yield 'opening angle bracket' => ['.a<b'];

        yield 'double quote' => ['.a"b'];

        yield 'single quote' => ['.a\'b'];

        yield 'backtick' => ['.a`b'];

        yield 'backslash' => ['.a\\b'];

        yield 'control character' => [".a\x07b"];

        yield 'too long' => [str_repeat('a', 201)];

        yield 'not a string' => [42];
    }

    /**
     * @dataProvider invalidSelectorProvider
     *
     * @param mixed $selector
     */
    public function testAnInvalidStoredSelectorFallsBackToTheThemeDefaultAndIsLogged($selector)
    {
        $this->stored['scalapay_amount_selectors'] = json_encode(['product' => ['.ok', $selector]]);

        $this->assertSame(['.current-price-value'], $this->action->getAmountSelectors('product'));
        $this->assertCount(1, $this->logs);
        $this->assertSame('warning', $this->logs[0]['level']);
    }

    public function testMoreThanTenStoredSelectorsFallBackToTheThemeDefault()
    {
        $selectors = [];
        for ($i = 0; $i < 11; ++$i) {
            $selectors[] = '.s' . $i;
        }
        $this->stored['scalapay_amount_selectors'] = json_encode(['product' => $selectors]);

        $this->assertSame(['.current-price-value'], $this->action->getAmountSelectors('product'));
    }

    public function testChildCombinatorsAreAccepted()
    {
        $this->stored['scalapay_amount_selectors'] = '{"product":[".product-prices > .current-price"]}';

        $this->assertSame(['.product-prices > .current-price'], $this->action->getAmountSelectors('product'));
        $this->assertSame([], $this->logs);
    }

    public function testTwoHundredCharactersAreAccepted()
    {
        $selector = str_repeat('a', 200);
        $this->stored['scalapay_amount_selectors'] = json_encode(['product' => [$selector]]);

        $this->assertSame([$selector], $this->action->getAmountSelectors('product'));
    }

    public function testAnUnknownPageHasNoSelector()
    {
        $this->assertSame([], $this->action->getAmountSelectors('home'));
    }
}
