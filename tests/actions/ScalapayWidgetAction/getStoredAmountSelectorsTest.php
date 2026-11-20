<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class getStoredAmountSelectorsTest extends BaseScalapayWidgetAction
{
    public function testTheStoredSelectorsOfThePageAreReturned()
    {
        $this->stored['scalapay_amount_selectors'] = '{"product":[" #price ",".price"],"cart":[".total"]}';

        $this->assertSame(['#price', '.price'], $this->action->getStoredAmountSelectors('product'));
    }

    public function nothingStoredProvider()
    {
        yield 'missing row' => [false];

        yield 'empty object' => ['{}'];

        yield 'other page only' => ['{"cart":[".total"]}'];

        yield 'malformed json' => ['{"product":'];
    }

    /**
     * @dataProvider nothingStoredProvider
     *
     * @param mixed $stored
     */
    public function testTheThemeDefaultIsNotReturnedWhenNothingIsStored($stored)
    {
        $this->stored['scalapay_amount_selectors'] = $stored;

        $this->assertSame([], $this->action->getStoredAmountSelectors('product'));
        $this->assertSame([], $this->logs);
    }

    public function testInvalidStoredSelectorsAreNotReturnedAndAreLogged()
    {
        $this->stored['scalapay_amount_selectors'] = '{"product":[".a\"b"]}';

        $this->assertSame([], $this->action->getStoredAmountSelectors('product'));
        $this->assertCount(1, $this->logs);
        $this->assertSame('warning', $this->logs[0]['level']);
    }

    public function testAnUnknownPageHasNoStoredSelector()
    {
        $this->stored['scalapay_amount_selectors'] = '{"home":[".price"]}';

        $this->assertSame([], $this->action->getStoredAmountSelectors('home'));
    }
}
