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
                10 => ['iso_code' => 'IT', 'name' => 'Italie'],
                12 => ['iso_code' => 'LU', 'name' => 'Luxembourg'],
            ])
            ->byDefault();
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

    public function testMaxOfZeroIsRefusedWhenTheApiSetsNoBound()
    {
        $this->config_values['amounts'] = json_encode(['satispay' => []]);

        $this->assertSame(
            ['result' => false, 'code' => 'amount_invalid'],
            $this->class->validateMerchantSettings('satispay', ['max' => 0], 1)
        );
    }

    public function testMinOfZeroIsAcceptedWhenTheApiSetsNoBound()
    {
        $this->config_values['amounts'] = json_encode(['satispay' => []]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['min' => ['EUR' => 0]]],
            $this->class->validateMerchantSettings('satispay', ['min' => 0], 1)
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

    public function testApiCountryInactiveInTheShopIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'country_not_allowed'],
            $this->class->validateMerchantSettings('scalapay', ['countries' => ['IT', 'DE']], 1)
        );
    }

    public function testSelectingEverySelectableCountryWithoutStoredRestrictionStoresNoOverride()
    {
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('scalapay', ['countries' => ['BE', 'FR', 'IT']], 1)
        );
    }

    public function testSelectingEverySelectableCountryWithoutStoredRestrictionKeepsTheOtherSettings()
    {
        $this->setCustomSettings(['scalapay' => ['min' => ['EUR' => 1000]]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['min' => ['EUR' => 1000]]],
            $this->class->validateMerchantSettings('scalapay', ['countries' => ['IT', 'FR', 'BE']], 1)
        );
    }

    public function testSelectingEverySelectableCountryAfterAnEmptyStoredSelectionStoresTheList()
    {
        $this->setCustomSettings(['scalapay' => ['countries' => []]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['BE', 'FR', 'IT']]],
            $this->class->validateMerchantSettings('scalapay', ['countries' => ['BE', 'FR', 'IT']], 1)
        );
    }

    public function testSelectingEverySelectableCountryWithAStoredRestrictionStoresTheListWhenSomeApiCountriesAreInactiveInTheShop()
    {
        $this->setCustomSettings(['scalapay' => ['countries' => ['IT']]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['IT', 'BE', 'FR']]],
            $this->class->validateMerchantSettings('scalapay', ['countries' => ['IT', 'BE', 'FR']], 1)
        );
    }

    public function testSelectingEveryApiCountryActiveInTheShopStoresNoOverride()
    {
        $this->config_values['countries'] = json_encode(['satispay' => ['FR', 'IT']]);
        $this->setCustomSettings(['satispay' => ['countries' => ['IT']]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('satispay', ['countries' => ['IT', 'FR']], 1)
        );
    }

    public function testRestrictionIsKeptWhenAnApiCountryIsInactiveInTheShop()
    {
        $this->config_values['countries'] = json_encode(['satispay' => ['FR', 'IT']]);
        $this->setCustomSettings(['satispay' => ['countries' => ['IT']]]);
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn([10 => ['iso_code' => 'IT', 'name' => 'Italie']]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['IT']]],
            $this->class->validateMerchantSettings('satispay', ['countries' => ['IT']], 1)
        );
    }

    public function testRestrictionStrictlyInsideTheSelectableCountriesIsKept()
    {
        $this->config_values['countries'] = json_encode(['satispay' => ['FR', 'IT']]);
        $this->setCustomSettings(['satispay' => ['countries' => ['IT']]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['IT']]],
            $this->class->validateMerchantSettings('satispay', ['countries' => ['IT']], 1)
        );
    }

    public function testLowercaseShopIsoCodeMatchesTheApiCountry()
    {
        $this->config_values['countries'] = json_encode(['satispay' => ['FR', 'IT']]);
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn([
                8 => ['iso_code' => 'fr', 'name' => 'France'],
                10 => ['iso_code' => 'it', 'name' => 'Italie'],
            ]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['IT']]],
            $this->class->validateMerchantSettings('satispay', ['countries' => ['it']], 1)
        );
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('satispay', ['countries' => ['it', 'FR']], 1)
        );
    }

    public function testStoredCountryNoLongerActiveInTheShopIsReplacedBySubmittedSelection()
    {
        $this->setCustomSettings(['scalapay' => ['countries' => ['DE', 'IT']]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['IT']]],
            $this->class->validateMerchantSettings('scalapay', ['countries' => ['IT']], 1)
        );
    }

    public function testWithoutAnyApiCountryActiveInTheShopACountryIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'code' => 'country_not_allowed'],
            $this->class->validateMerchantSettings('ideal', ['countries' => ['NL']], 1)
        );
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['min' => ['EUR' => 1000]]],
            $this->class->validateMerchantSettings('ideal', ['min' => 1000], 1)
        );
    }

    public function testWithoutAnyApiCountryActiveInTheShopAnEmptySelectionStoresNoCountry()
    {
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('ideal', ['countries' => []], 1)
        );
        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['min' => ['EUR' => 1000]]],
            $this->class->validateMerchantSettings('ideal', ['min' => 1000, 'countries' => []], 1)
        );
    }

    public function storedCountriesProvider()
    {
        yield [['NL']];

        yield [[]];
    }

    /**
     * @dataProvider storedCountriesProvider
     *
     * @param array $stored
     */
    public function testWithoutAnyApiCountryActiveInTheShopAnEmptySelectionKeepsTheStoredCountries($stored)
    {
        $this->setCustomSettings(['ideal' => ['min' => ['EUR' => 1000], 'countries' => $stored]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['min' => ['EUR' => 1000], 'countries' => $stored]],
            $this->class->validateMerchantSettings('ideal', ['countries' => []], 1)
        );
    }

    public function testWhenTheShopCountriesCannotBeReadAnEmptySelectionKeepsTheStoredCountries()
    {
        $this->setCustomSettings(['bancontact' => ['countries' => ['BE']]]);
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn(false);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['BE']]],
            $this->class->validateMerchantSettings('bancontact', ['countries' => []], 1)
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

    public function testEmptyCountrySelectionKeepsAStoredEmptySelection()
    {
        $this->setCustomSettings(['satispay' => ['countries' => []]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => []]],
            $this->class->validateMerchantSettings('satispay', ['countries' => []], 1)
        );
    }

    public function testEmptySelectionReplacesAStoredBancontactCountryInactiveInTheShop()
    {
        $this->setCustomSettings(['bancontact' => ['countries' => ['BE']]]);
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn([
                8 => ['iso_code' => 'FR', 'name' => 'France'],
                10 => ['iso_code' => 'IT', 'name' => 'Italie'],
                12 => ['iso_code' => 'LU', 'name' => 'Luxembourg'],
            ]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => ['countries' => []]],
            $this->class->validateMerchantSettings('bancontact', ['countries' => []], 1)
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
            $this->class->validateMerchantSettings('bancontact', ['countries' => ['LU', 'IT', 'FR', 'BE']], 1)
        );
    }

    public function testWithoutApiRestrictionSelectingEveryActiveShopCountryRemovesTheRestriction()
    {
        $this->setCustomSettings(['bancontact' => ['countries' => ['BE']]]);

        $this->assertSame(
            ['result' => true, 'code' => 'ok', 'settings' => []],
            $this->class->validateMerchantSettings('bancontact', ['countries' => ['BE', 'FR', 'IT', 'LU']], 1)
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
