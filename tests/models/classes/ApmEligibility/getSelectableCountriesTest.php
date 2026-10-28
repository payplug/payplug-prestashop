<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class getSelectableCountriesTest extends BaseApmEligibility
{
    public function testApiCountriesAreLimitedToTheActiveShopCountries()
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->once()
            ->with(1, true)
            ->andReturn([
                8 => ['iso_code' => 'FR', 'name' => 'France'],
                21 => ['iso_code' => 'be', 'name' => 'Belgique'],
                17 => ['iso_code' => 'GB', 'name' => 'Royaume-Uni'],
                10 => ['iso_code' => 'IT', 'name' => 'Italie'],
            ]);
        $this->country_adapter->shouldNotReceive('getByIso');

        $this->assertSame(
            ['FR' => 'France', 'BE' => 'Belgique', 'IT' => 'Italie'],
            $this->class->getSelectableCountries('scalapay', 1)
        );
    }

    public function testLowercaseShopIsoCodeMatchesTheUppercaseApiCountry()
    {
        $this->config_values['countries'] = json_encode(['satispay' => ['IT']]);
        $this->country_adapter->shouldReceive('getCountries')
            ->with(1, true)
            ->andReturn([
                10 => ['iso_code' => 'it', 'name' => 'Italie'],
                8 => ['iso_code' => 'fr', 'name' => 'France'],
            ]);

        $this->assertSame(['IT' => 'Italie'], $this->class->getSelectableCountries('satispay', 1));
    }

    public function testShopCountriesWithoutIsoCodeOrNameAreSkipped()
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->with(1, true)
            ->andReturn([
                21 => ['name' => 'Belgique'],
                8 => ['iso_code' => 'FR'],
                99 => [],
                10 => ['iso_code' => 'IT', 'name' => 'Italie'],
            ]);

        $this->assertSame(['IT' => 'Italie'], $this->class->getSelectableCountries('scalapay', 1));
    }

    public function testNoApiCountryActiveInTheShopLeavesNothingSelectable()
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->with(1, true)
            ->andReturn([
                21 => ['iso_code' => 'BE', 'name' => 'Belgique'],
                8 => ['iso_code' => 'FR', 'name' => 'France'],
            ]);

        $this->assertSame([], $this->class->getSelectableCountries('ideal', 1));
    }

    public function testWhenTheShopCountriesCannotBeReadNoApiCountryIsSelectable()
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn(false);

        $this->assertSame([], $this->class->getSelectableCountries('scalapay', 1));
    }

    public function testWithoutApiRestrictionTheActiveShopCountriesAreListedByName()
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->with(1, true)
            ->andReturn([
                21 => ['iso_code' => 'BE', 'name' => 'Belgique'],
                8 => ['iso_code' => 'FR', 'name' => 'France'],
                99 => ['name' => 'No iso'],
            ]);

        $this->assertSame(
            ['BE' => 'Belgique', 'FR' => 'France'],
            $this->class->getSelectableCountries('bancontact', 1)
        );
    }

    public function testWhenTheShopCountriesCannotBeReadNothingIsSelectable()
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn(false);

        $this->assertSame([], $this->class->getSelectableCountries('bancontact', 1));
    }

    public function testNonPproMethodHasNoSelectableCountry()
    {
        $this->assertSame([], $this->class->getSelectableCountries('standard', 1));
    }

    public function testBackOfficeCurrencyIsTheFirstCurrencyOfTheApiBounds()
    {
        $this->config_values['amounts'] = json_encode(['satispay' => ['min' => 'GBP:30;EUR:30']]);

        $this->assertSame('GBP', $this->class->getBackOfficeCurrency('satispay'));
        $this->assertSame('EUR', $this->class->getBackOfficeCurrency('wero'));
    }
}
