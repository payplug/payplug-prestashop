<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getErrorLabelTest extends BaseOneyPaymentMethod
{
    public function errorMessageDataProvider()
    {
        yield ['address', 'payplug.getPaymentOptions.invalidAddresses'];

        yield ['amount', 'payplug.getPaymentOptions.invalidAmount'];

        yield ['invalid_carrier', 'payplug.getPaymentOptions.invalidCarrier'];

        yield ['product_quantity', 'payplug.getPaymentOptions.invalidCart'];

        yield ['default', 'payplug.getPaymentOptions.errorOccurred'];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $error
     */
    public function testWhenGivenDataHasInvalidFormat($error)
    {
        $this->assertSame(
            'payplug.getPaymentOptions.errorOccurred',
            $this->class->getErrorLabel($error)
        );
    }

    /**
     * @dataProvider errorMessageDataProvider
     *
     * @param mixed $error
     * @param mixed $message
     */
    public function testReturnedMessageForGivenError($error, $message)
    {
        if ('amount' === $error) {
            $oney = \Mockery::mock('Oney');
            $oney
                ->shouldReceive([
                    'getOneyPriceLimit' => [
                        'min' => 100,
                        'max' => 3000,
                    ],
                ]);
            $this->plugin->shouldReceive([
                'getOney' => $oney,
            ]);
        }
        $this->validate_adapter->shouldReceive([
            'validate' => false,
        ]);
        $this->configuration_adapter->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');
        $this->currency_adapter->shouldReceive([
            'get' => 1,
        ]);

        $this->assertSame(
            $message,
            $this->class->getErrorLabel($error)
        );
    }

    public function testAmountLimitsAreFormattedInTheShopDefaultCurrency()
    {
        $currency = new \stdClass();
        $currency->iso_code = 'jpy';
        $this->configuration->shouldReceive('getValue')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('42');
        $this->currency_adapter->shouldReceive('get')
            ->with(42)
            ->andReturn($currency);
        $this->validate_adapter->shouldReceive('validate')->andReturn(true);
        $this->tools_adapter->shouldReceive('tool')
            ->with('strtoupper', 'jpy')
            ->andReturn('JPY');
        $this->class->shouldReceive('getOneyPriceLimit')
            ->with(true)
            ->andReturn(['min' => 10000, 'max' => 300000]);
        $this->amountHelper->shouldReceive('formatOneyAmount')
            ->andReturn(['result' => 0.0, 'message' => '']);

        $this->class->getErrorLabel('amount');

        $this->amountHelper->shouldHaveReceived('formatOneyAmount')->with(10000, 'JPY')->once();
        $this->amountHelper->shouldHaveReceived('formatOneyAmount')->with(300000, 'JPY')->once();
        $this->addToAssertionCount(1);
    }
}
