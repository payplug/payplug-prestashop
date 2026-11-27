<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class MapPaymentMethodsLimitsTest extends BaseApi
{
    /**
     * `payment_methods` of a GET /account response, ids removed.
     *
     * @return array
     */
    public function getAccountPaymentMethods()
    {
        return [
            'apple_pay' => ['enabled' => false],
            'american_express' => ['enabled' => false],
            'bancontact' => [
                'enabled' => false,
                'allowed_countries' => ['ALL'],
                'min_amounts' => ['EUR' => 30],
                'max_amounts' => ['EUR' => 2000000],
            ],
            'ideal' => [
                'enabled' => false,
                'allowed_countries' => ['NL'],
                'min_amounts' => ['EUR' => 30],
                'max_amounts' => ['EUR' => 2000000],
            ],
            'satispay' => [
                'enabled' => false,
                'allowed_countries' => ['AT', 'BE', 'CY', 'DE', 'EE', 'ES', 'FI', 'FR', 'GR', 'HR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PT', 'SI', 'SK'],
                'min_amounts' => ['EUR' => 30],
                'max_amounts' => ['EUR' => 2000000],
            ],
            'bizum' => [
                'enabled' => false,
                'allowed_countries' => ['ES'],
                'min_amounts' => ['EUR' => 50],
                'max_amounts' => ['EUR' => 2000000],
            ],
            'scalapay' => [
                'enabled' => false,
                'allowed_countries' => ['AT', 'BE', 'DE', 'ES', 'FI', 'FR', 'IT', 'NL', 'PT'],
                'min_amounts' => ['EUR' => 500],
                'max_amounts' => ['EUR' => 200000],
            ],
        ];
    }

    public function testAccountPayloadIsMappedToAmountsAndCountries()
    {
        $mapped = $this->service->mapPaymentMethodsLimits($this->getAccountPaymentMethods(), [], []);

        $this->assertSame(['min' => 'EUR:50', 'max' => 'EUR:2000000'], $mapped['amounts']['bizum']);
        $this->assertSame(['min' => 'EUR:500', 'max' => 'EUR:200000'], $mapped['amounts']['scalapay']);
        $this->assertSame(['ES'], $mapped['countries']['bizum']);
        $this->assertSame(['NL'], $mapped['countries']['ideal']);
        $this->assertFalse(isset($mapped['amounts']['apple_pay']));
    }

    public function testWhenAllowedCountriesIsAllRemovesPreviousRestriction()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            $this->getAccountPaymentMethods(),
            [],
            ['bancontact' => ['BE']]
        );

        $this->assertFalse(isset($mapped['countries']['bancontact']));
    }

    public function testWhenAllowedCountriesIsEmptyRemovesPreviousRestriction()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['allowed_countries' => []]],
            [],
            ['satispay' => ['IT']]
        );

        $this->assertFalse(isset($mapped['countries']['satispay']));
    }

    public function testWhenAllowedCountriesListedStoresUpperCaseUniqueList()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['allowed_countries' => ['it', 'IT', ' fr']]],
            [],
            []
        );

        $this->assertSame(['IT', 'FR'], $mapped['countries']['satispay']);
    }

    public function testWhenAllowedCountriesKeyAbsentKeepsPreviousValue()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['min_amounts' => ['EUR' => 30]]],
            [],
            ['satispay' => ['IT']]
        );

        $this->assertSame(['IT'], $mapped['countries']['satispay']);
    }

    public function testWhenAllowedCountriesOnlyHoldsInvalidCodesKeepsPreviousValue()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['allowed_countries' => ['ITALY', 42, null]]],
            [],
            ['satispay' => ['IT']]
        );

        $this->assertSame(['IT'], $mapped['countries']['satispay']);
    }

    public function testWhenAllowedCountriesIsNotAListKeepsPreviousValue()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['allowed_countries' => 'IT']],
            [],
            ['satispay' => ['IT']]
        );

        $this->assertSame(['IT'], $mapped['countries']['satispay']);
    }

    public function testWhenEurIsMissingStoresTheReturnedCurrencyWithoutNotice()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['min_amounts' => ['GBP' => 400], 'max_amounts' => []]],
            ['satispay' => ['min' => 'EUR:30', 'max' => 'EUR:2000000']],
            []
        );

        $this->assertSame(['min' => 'GBP:400', 'max' => 'EUR:2000000'], $mapped['amounts']['satispay']);
    }

    public function testWhenSeveralCurrenciesStoresAllOfThemInApiOrder()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['min_amounts' => ['EUR' => 500, 'GBP' => 400], 'max_amounts' => ['EUR' => 9000, 'GBP' => 8000]]],
            [],
            []
        );

        $this->assertSame(['min' => 'EUR:500;GBP:400', 'max' => 'EUR:9000;GBP:8000'], $mapped['amounts']['satispay']);
    }

    public function testWhenBoundValueIsNotNumericItIsSkipped()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => ['min_amounts' => ['EUR' => 'abc', 'GBP' => 400]]],
            [],
            []
        );

        $this->assertSame(['min' => 'GBP:400'], $mapped['amounts']['satispay']);
    }

    public function testOtherPaymentMethodsAndDefaultAreKeptUntouched()
    {
        $previous_amounts = [
            'default' => ['min' => 'EUR:99', 'max' => 'EUR:2000000'],
            'oney_x3_with_fees' => ['min' => 'EUR:10000', 'max' => 'EUR:300000'],
        ];

        $mapped = $this->service->mapPaymentMethodsLimits(['ideal' => ['min_amounts' => ['EUR' => 30]]], $previous_amounts, null);

        $this->assertSame($previous_amounts['default'], $mapped['amounts']['default']);
        $this->assertSame($previous_amounts['oney_x3_with_fees'], $mapped['amounts']['oney_x3_with_fees']);
        $this->assertSame([], $mapped['countries']);
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_methods
     */
    public function testWhenPaymentMethodsIsNotAnArrayReturnsPreviousUntouched($payment_methods)
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            $payment_methods,
            ['satispay' => ['min' => 'EUR:30']],
            ['satispay' => ['IT']]
        );

        $this->assertSame(
            ['amounts' => ['satispay' => ['min' => 'EUR:30']], 'countries' => ['satispay' => ['IT']]],
            $mapped
        );
    }

    public function testWhenAnEntryIsMalformedItIsSkipped()
    {
        $mapped = $this->service->mapPaymentMethodsLimits(
            ['satispay' => 'enabled', 'ideal' => ['allowed_countries' => ['NL']]],
            ['satispay' => ['min' => 'EUR:30']],
            []
        );

        $this->assertSame(['min' => 'EUR:30'], $mapped['amounts']['satispay']);
        $this->assertSame(['NL'], $mapped['countries']['ideal']);
    }
}
