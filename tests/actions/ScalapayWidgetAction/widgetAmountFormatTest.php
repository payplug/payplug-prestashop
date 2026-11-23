<?php

namespace PayPlug\tests\actions\ScalapayWidgetAction;

/**
 * @group unit
 * @group action
 * @group scalapay_widget_action
 */
class widgetAmountFormatTest extends BaseScalapayWidgetAction
{
    public function amountProvider()
    {
        yield 'two decimals' => [99.9, '99.90'];

        yield 'whole amount' => [150, '150.00'];

        yield 'rounded up' => [99.899, '99.90'];

        yield 'rounded down' => [12.344, '12.34'];

        yield 'half rounded up' => [10.005, '10.01'];

        yield 'cents only' => [0.05, '0.05'];

        yield 'one cent' => [0.01, '0.01'];

        yield 'large amount without thousands separator' => [1234567.89, '1234567.89'];

        yield 'numeric string' => ['49.5', '49.50'];
    }

    /**
     * @dataProvider amountProvider
     *
     * @param mixed $total
     * @param string $expected
     */
    public function testTheAmountIsAPlainDecimalWithADotAndTwoDecimals($total, $expected)
    {
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn($total);

        $this->action->renderCheckout();

        $this->assertSame($expected, $this->assigned['scalapay_amount']);
    }

    public function testTheAmountIgnoresACommaDecimalLocale()
    {
        $previous = setlocale(LC_ALL, '0');
        setlocale(LC_ALL, 'fr_FR.UTF-8', 'fr_FR.utf8', 'fr_FR', 'de_DE.UTF-8', 'de_DE.utf8', 'de_DE');
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn(1999.95);

        try {
            $this->action->renderCheckout();
        } finally {
            setlocale(LC_ALL, $previous);
        }

        $this->assertSame('1999.95', $this->assigned['scalapay_amount']);
    }

    public function missingAmountProvider()
    {
        yield 'zero total' => [0.0, 0];

        yield 'negative total' => [-5.0, -500];

        yield 'not converted' => [150.0, 0];
    }

    /**
     * @dataProvider missingAmountProvider
     *
     * @param float $total
     * @param int $cents
     */
    public function testNoAmountIsAssignedWhenItCannotBeDetermined($total, $cents)
    {
        $this->context->cart->shouldReceive('getOrderTotal')->with(true)->andReturn($total);
        $this->amount_helper->shouldReceive('convertAmount')->andReturn($cents);

        $this->assertSame('<scalapay/widget.tpl>', $this->action->renderCheckout());
        $this->assertSame('', $this->assigned['scalapay_amount']);
    }
}
