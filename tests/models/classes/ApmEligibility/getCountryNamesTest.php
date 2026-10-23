<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class getCountryNamesTest extends BaseApmEligibility
{
    public function testCountriesAreNamedInTheGivenLanguageInTheGivenOrder()
    {
        $this->country_adapter->shouldReceive('getByIso')
            ->with('NL')
            ->andReturn(13);
        $this->country_adapter->shouldReceive('getByIso')
            ->with('BE')
            ->andReturn(3);
        $this->country_adapter->shouldReceive('getNameById')
            ->with(2, 13)
            ->andReturn('Pays-Bas');
        $this->country_adapter->shouldReceive('getNameById')
            ->with(2, 3)
            ->andReturn('Belgique');

        $this->assertSame(['Pays-Bas', 'Belgique'], $this->class->getCountryNames(['NL', 'BE'], 2));
    }

    public function testCountryUnknownToTheShopIsNamedByItsIsoCode()
    {
        $this->country_adapter->shouldReceive('getByIso')
            ->with('XK')
            ->andReturn(false);
        $this->country_adapter->shouldNotReceive('getNameById');

        $this->assertSame(['XK'], $this->class->getCountryNames(['XK'], 1));
    }

    public function testCountryWithoutNameInTheLanguageIsNamedByItsIsoCode()
    {
        $this->country_adapter->shouldReceive('getByIso')
            ->with('NL')
            ->andReturn(13);
        $this->country_adapter->shouldReceive('getNameById')
            ->with(1, 13)
            ->andReturn(false);

        $this->assertSame(['NL'], $this->class->getCountryNames(['NL'], 1));
    }

    public function testInvalidIsoCodesAreSkipped()
    {
        $this->country_adapter->shouldNotReceive('getByIso');

        $this->assertSame([], $this->class->getCountryNames([42, '', null, ['NL']], 1));
        $this->assertSame([], $this->class->getCountryNames('NL', 1));
    }
}
