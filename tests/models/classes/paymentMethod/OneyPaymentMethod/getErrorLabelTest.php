<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getErrorLabelTest extends BaseOneyPaymentMethod
{
    public function errorMessageDataProvider()
    {
        yield ['invalid_addresses', 'payplug.getPaymentOptions.invalidAddresses'];
        yield ['invalid_amount_bottom', 'payplug.getPaymentOptions.invalidAmount'];
        yield ['invalid_amount_top', 'payplug.getPaymentOptions.invalidAmount'];
        yield ['invalid_carrier', 'payplug.getPaymentOptions.invalidCarrier'];
        yield ['invalid_cart', 'payplug.getPaymentOptions.invalidCart'];
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
        if (in_array($error, ['invalid_amount_bottom', 'invalid_amount_top'])) {
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
        $this->assertSame(
            $message,
            $this->classe->getErrorLabel($error)
        );
    }
}
