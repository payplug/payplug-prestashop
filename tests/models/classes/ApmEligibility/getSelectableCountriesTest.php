<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class getSelectableCountriesTest extends BaseApmEligibility
{
    public function testApiCountriesAreNamedFromTheShop()
    {
        $this->country_adapter->shouldReceive('getByIso')
            ->andReturnUsing(function ($iso) {
                return 'FR' == $iso ? 8 : false;
            });
        $this->country_adapter->shouldReceive('getNameById')
            ->with(1, 8)
            ->andReturn('France');
        $this->country_adapter->shouldNotReceive('getCountries');

        $this->assertSame(
            ['AT' => 'AT', 'BE' => 'BE', 'DE' => 'DE', 'ES' => 'ES', 'FI' => 'FI', 'FR' => 'France', 'IT' => 'IT', 'NL' => 'NL', 'PT' => 'PT'],
            $this->class->getSelectableCountries('scalapay', 1)
        );
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
