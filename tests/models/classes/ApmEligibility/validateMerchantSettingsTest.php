<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class validateMerchantSettingsTest extends BaseApmEligibility
{
    public function setUp(): void
    {
        parent::setUp();

        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn([
                21 => ['iso_code' => 'BE', 'name' => 'Belgique'],
                8 => ['iso_code' => 'FR', 'name' => 'France'],
                12 => ['iso_code' => 'LU', 'name' => 'Luxembourg'],
            ]);
        $this->country_adapter->shouldReceive([
            'getByIso' => false,
        ]);
    }

    public function testNarrowedRangeInsideApiBoundsIsAccepted()
    {
        $this->assertSame(
            [
                'result' => true,
                'code' => 'ok',
                'settings' => ['min' => ['EUR' => 1000], 'max' => ['EUR' => 50000]],
            ],
            $this->class->validateMerchantSettings('satispay', ['min' => 1000, 'max' => 50000], 1)
        );
    }

    public function testMinBelowApiBoundIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'amount_out_of_range'],
            $this->class->validateMerchantSettings('bizum', ['min' => 49], 1)
        );
    }

    public function testMaxAboveApiBoundIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'amount_out_of_range'],
            $this->class->validateMerchantSettings('satispay', ['max' => 2000001], 1)
        );
    }

    public function testMaxEqualToMinIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'max_not_above_min'],
            $this->class->validateMerchantSettings('satispay', ['min' => 5000, 'max' => 5000], 1)
        );
    }

    public function testPartialPayloadIsComparedWithTheStoredBound()
    {
        $this->setCustomSettings(['satispay' => ['max' => ['EUR' => 3000]]]);

        $this->assertSame(
            ['result' => false, 'code' => 'max_not_above_min'],
            $this->class->validateMerchantSettings('satispay', ['min' => 4000], 1)
        );
    }

    public function testMinEqualToTheApiMaxIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'max_not_above_min'],
            $this->class->validateMerchantSettings('satispay', ['min' => 2000000], 1)
        );
    }

    public function invalidAmountProvider()
    {
        yield [-100];

        yield ['abc'];

        yield [12.5];

        yield [true];

        yield [['EUR' => 100]];
    }

    /**
     * @dataProvider invalidAmountProvider
     *
     * @param mixed $amount
     */
    public function testInvalidAmountIsRefused($amount)
    {
        $this->assertSame(
            ['result' => false, 'code' => 'amount_invalid'],
            $this->class->validateMerchantSettings('satispay', ['min' => $amount], 1)
        );
    }

    public function testBoundsEqualToTheApiOnesAreNotStoredAsOverrides()
    {
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('satispay', ['min' => 30, 'max' => 2000000], 1)
        );
    }

    public function testResetRemovesTheStoredBound()
    {
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 1000], 'max' => ['EUR' => 9000]]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['max' => ['EUR' => 9000]]],
            $this->class->validateMerchantSettings('satispay', ['min' => null], 1)
        );
    }

    public function testCountrySubsetOfTheApiListIsAccepted()
    {
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['IT', 'FR']]],
            $this->class->validateMerchantSettings('scalapay', ['countries' => ['it', 'FR']], 1)
        );
    }

    public function testCountryOutsideTheApiListIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'country_not_allowed'],
            $this->class->validateMerchantSettings('satispay', ['countries' => ['IT', 'FR']], 1)
        );
    }

    public function invalidCountriesProvider()
    {
        yield ['IT'];

        yield [['ITA']];

        yield [[42]];

        yield [[['IT']]];
    }

    /**
     * @dataProvider invalidCountriesProvider
     *
     * @param mixed $countries
     */
    public function testMalformedCountriesAreRefused($countries)
    {
        $this->assertSame(
            ['result' => false, 'code' => 'country_not_allowed'],
            $this->class->validateMerchantSettings('scalapay', ['countries' => $countries], 1)
        );
    }

    public function testEmptyCountrySelectionIsAcceptedAndKeptDistinctFromTheDefault()
    {
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => []]],
            $this->class->validateMerchantSettings('satispay', ['countries' => []], 1)
        );
    }

    public function testSelectingEveryApiCountryStoresNoOverride()
    {
        $this->setCustomSettings(['satispay' => ['countries' => []]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('satispay', ['countries' => ['IT']], 1)
        );
    }

    public function testWithoutApiRestrictionTheShopCountriesAreSelectable()
    {
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['BE']]],
            $this->class->validateMerchantSettings('bancontact', ['countries' => ['BE']], 1)
        );
        $this->assertSame(
            ['result' => false, 'code' => 'country_not_allowed'],
            $this->class->validateMerchantSettings('bancontact', ['countries' => ['NL']], 1)
        );
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('bancontact', ['countries' => ['LU', 'FR', 'BE']], 1)
        );
    }

    public function testApmMissingFromTheCacheIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'no_config'],
            $this->class->validateMerchantSettings('wero', ['min' => 100], 1)
        );
    }

    public function testNonPproMethodIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'unknown_method'],
            $this->class->validateMerchantSettings('standard', ['min' => 100], 1)
        );
    }

    public function testScalapayNarrowedRangeIsStoredInTheGenericSettings()
    {
        $this->assertSame(
            [
                'result' => true,
                'code' => 'ok',
                'settings' => ['min' => ['EUR' => 1000], 'max' => ['EUR' => 150000], 'countries' => ['IT']],
            ],
            $this->class->validateMerchantSettings('scalapay', ['min' => 1000, 'max' => 150000, 'countries' => ['IT']], 1)
        );
    }

    public function testScalapayMaxEqualToMinIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'max_not_above_min'],
            $this->class->validateMerchantSettings('scalapay', ['min' => 1000, 'max' => 1000], 1)
        );
    }

    public function testScalapayAmountOutsideTheApiRangeIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'amount_out_of_range'],
            $this->class->validateMerchantSettings('scalapay', ['min' => 499], 1)
        );
        $this->assertSame(
            ['result' => false, 'code' => 'amount_out_of_range'],
            $this->class->validateMerchantSettings('scalapay', ['max' => 200001], 1)
        );
    }

    public function testStoredSettingsOfTheOtherFieldsAreKept()
    {
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 1000], 'countries' => []]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['min' => ['EUR' => 1000], 'max' => ['EUR' => 9000], 'countries' => []]],
            $this->class->validateMerchantSettings('satispay', ['max' => 9000], 1)
        );
    }

    public function testSaveWritesEveryApmAtOnceAndDropsEmptyEntries()
    {
        $this->setCustomSettings(['ideal' => ['min' => ['EUR' => 1000]], 'mybank' => ['countries' => []]]);

        $this->assertTrue($this->class->saveMerchantSettings([
            'satispay' => ['countries' => []],
            'ideal' => [],
        ]));

        $this->assertSame(
            '{"mybank":{"countries":[]},"satispay":{"countries":[]}}',
            $this->saved['apm_custom_settings']
        );
    }
}
