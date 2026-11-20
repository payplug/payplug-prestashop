<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

use PayPlug\src\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class parseAmountSelectorsTest extends BaseScalapayWidgetAction
{
    public function testCommaSeparatedSelectorsAreSplitAndTrimmed()
    {
        $this->assertSame(
            ['#price', '.product .price'],
            ScalapayWidgetAction::parseAmountSelectors(' #price , .product .price ,')
        );
    }

    public function testChildCombinatorsAreAccepted()
    {
        $this->assertSame(['.prices > .price'], ScalapayWidgetAction::parseAmountSelectors('.prices > .price'));
    }

    public function testAnEmptyValueGivesNoSelector()
    {
        $this->assertSame([], ScalapayWidgetAction::parseAmountSelectors('  '));
    }

    public function invalidValueProvider()
    {
        yield 'quote' => ['#price, .a"b'];

        yield 'html' => ['<script>'];

        yield 'too long' => [str_repeat('a', 201)];

        yield 'too many' => [implode(',', array_fill(0, 11, '.a'))];

        yield 'not a string' => [['#price']];

        yield 'null' => [null];
    }

    /**
     * @dataProvider invalidValueProvider
     *
     * @param mixed $value
     */
    public function testAnInvalidValueIsRefused($value)
    {
        $this->assertNull(ScalapayWidgetAction::parseAmountSelectors($value));
    }

    public function testTenSelectorsAreAccepted()
    {
        $this->assertCount(10, ScalapayWidgetAction::parseAmountSelectors(implode(',', array_fill(0, 10, '.a'))));
    }
}
