<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
final class formatOverseaCountryIsoTest extends BaseOneyPaymentMethod
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $country
     */
    public function testWhenGivenISOIsNotValidStringFormat($country)
    {
        $this->assertSame(
            '',
            $this->class->formatOverseaCountryIso($country)
        );
    }

    public function testWhenGivenISOIsOversea()
    {
        $country = 'GP';
        $this->assertSame(
            'FR',
            $this->class->formatOverseaCountryIso($country)
        );
    }

    public function testWhenGivenISOIsNotOversea()
    {
        $country = 'DE';
        $this->assertSame(
            $country,
            $this->class->formatOverseaCountryIso($country)
        );
    }
}
