<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group oney_payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
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
            $this->classe->getErrorLabel($error)
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
        $this->validate_adapter
            ->shouldReceive([
                                'validate' => false,
                            ]);
        $this->configuration_adapter
            ->shouldReceive('get')
            ->with('PS_CURRENCY_DEFAULT')
            ->andReturn('EUR');
        $this->currency_adapter->shouldReceive([
                                                   'get' => 1,
                                               ]);

        $this->assertSame(
            $message,
            $this->classe->getErrorLabel($error)
        );
    }
}
