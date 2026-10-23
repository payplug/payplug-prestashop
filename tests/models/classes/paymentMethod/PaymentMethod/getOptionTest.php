<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class getOptionTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $configuration
     */
    public function testWhenGivenConfigurationIsntValidArray($configuration)
    {
        $this->assertSame([], $this->class->getOption($configuration));
    }

    public function testWhenPaymentMethodHasNoName()
    {
        $configuration = [];
        $this->class->set('name', '');
        $this->assertSame([], $this->class->getOption($configuration));
    }

    public function testWhenDefaultConfigurationIsGotAsTrue()
    {
        $configuration = [];
        $this->class->set('name', 'standard');

        $this->configuration->shouldReceive('getDefault')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');
        $this->assertTrue($this->class->getOption($configuration)['checked']);
    }

    public function testWhenDefaultConfigurationIsGotAsFalse()
    {
        $configuration = [];
        $this->class->set('name', 'standard');
        $this->configuration->shouldReceive('getDefault')
            ->with('payment_methods')
            ->andReturn('{"standard":false}');
        $this->assertFalse($this->class->getOption($configuration)['checked']);
    }

    public function testPproGetsMinMaxFieldsBoundedByTheApi()
    {
        $this->class->set('name', 'satispay');
        $this->stubCountries('{"satispay":["IT"]}');

        $this->assertSame(
            [
                [
                    'name' => 'thresholds',
                    'image_url' => 'modules/payplug/views/img/oney/payplug-thresholds.jpg',
                    'title' => 'paymentmethods.apm.thresholds.title',
                    'descriptions' => [
                        'description' => 'paymentmethods.apm.thresholds.description',
                        'decimal' => true,
                        'strict_range' => true,
                        'min_amount' => [
                            'name' => 'satispay_min_amounts',
                            'label' => 'paymentmethods.apm.thresholds.min',
                            'value' => 0.3,
                            'placeholder' => 0.3,
                            'default' => 0.3,
                        ],
                        'inter' => 'paymentmethods.apm.thresholds.inter',
                        'max_amount' => [
                            'name' => 'satispay_max_amounts',
                            'label' => 'paymentmethods.apm.thresholds.max',
                            'value' => 20000.0,
                            'placeholder' => 20000.0,
                            'default' => 20000.0,
                        ],
                        'error' => [
                            'text' => 'paymentmethods.apm.thresholds.error.text',
                            'maxtext' => 'paymentmethods.apm.thresholds.error.max.text',
                            'mintext' => 'paymentmethods.apm.thresholds.error.min.text',
                        ],
                    ],
                    'switch' => false,
                ],
            ],
            $this->class->getOption([])['advanced_options']
        );
    }

    public function testPproMinMaxFieldsShowTheMerchantRange()
    {
        $this->class->set('name', 'satispay');
        $this->stubCountries('{}');
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"satispay":{"min":{"EUR":1000},"max":{"EUR":50000}}}');

        $descriptions = $this->class->getOption([])['advanced_options'][0]['descriptions'];

        $this->assertSame(10.0, $descriptions['min_amount']['value']);
        $this->assertSame(0.3, $descriptions['min_amount']['default']);
        $this->assertSame(500.0, $descriptions['max_amount']['value']);
        $this->assertSame(20000.0, $descriptions['max_amount']['default']);
    }

    public function testPproCountriesDefaultToEveryApiCountryActiveInTheShop()
    {
        $this->class->set('name', 'scalapay');
        $this->stubCountries('{"scalapay":["DE","FR","IT"]}');
        $this->stubShopCountries([
            8 => ['iso_code' => 'FR', 'name' => 'France'],
            10 => ['iso_code' => 'IT', 'name' => 'Italie'],
            17 => ['iso_code' => 'GB', 'name' => 'Royaume-Uni'],
        ]);

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame('scalapay_countries', $countries['name']);
        $this->assertSame(
            [['value' => 'FR', 'label' => 'France'], ['value' => 'IT', 'label' => 'Italie']],
            $countries['options']
        );
        $this->assertSame(['FR', 'IT'], $countries['value']);
        $this->assertSame(['FR', 'IT'], $countries['default']);
        $this->assertSame('paymentmethods.apm.countries.title', $countries['title']);
        $this->assertSame('paymentmethods.apm.countries.warning', $countries['warning']);
        $this->assertSame('paymentmethods.apm.countries.search', $countries['labels']['search']);
    }

    public function testPproCountriesShowAnEmptySelection()
    {
        $this->class->set('name', 'satispay');
        $this->stubCountries('{"satispay":["IT"]}');
        $this->stubShopCountries([10 => ['iso_code' => 'IT', 'name' => 'Italie']]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"satispay":{"countries":[]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame([], $countries['value']);
        $this->assertSame(['IT'], $countries['default']);
    }

    public function testPproStoredCountryInactiveInTheShopIsLeftOutOfTheSelection()
    {
        $this->class->set('name', 'scalapay');
        $this->stubCountries('{"scalapay":["DE","FR","IT"]}');
        $this->stubShopCountries([
            8 => ['iso_code' => 'FR', 'name' => 'France'],
            10 => ['iso_code' => 'IT', 'name' => 'Italie'],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"scalapay":{"countries":["DE","IT"]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame(['IT'], $countries['value']);
        $this->assertSame(['FR', 'IT'], $countries['default']);
    }

    public function testPproStoredBelgiumInactiveInTheShopLeavesAnEmptyBancontactSelection()
    {
        $this->class->set('name', 'bancontact');
        $this->stubCountries('{}');
        $this->stubShopCountries([
            8 => ['iso_code' => 'FR', 'name' => 'France'],
            12 => ['iso_code' => 'LU', 'name' => 'Luxembourg'],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"bancontact":{"countries":["BE"]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame([], $countries['value']);
        $this->assertSame(['FR', 'LU'], $countries['default']);
    }

    public function testPproCountriesExplainWhichCountriesAreOffered()
    {
        $this->class->set('name', 'satispay');
        $this->stubCountryMessages();
        $this->stubCountries('{"satispay":["IT"]}');
        $this->stubShopCountries([10 => ['iso_code' => 'IT', 'name' => 'Italie']]);

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame('help Satispay', $countries['help']);
        $this->assertArrayNotHasKey('empty', $countries);
        $this->assertArrayNotHasKey('removed', $countries);
    }

    public function testPproWithoutAnyApiCountryActiveInTheShopGetsAnEmptyCountryField()
    {
        $this->class->set('name', 'ideal');
        $this->stubCountryMessages();
        $this->stubCountries('{"ideal":["NL","BE"]}');
        $this->stubShopCountries([8 => ['iso_code' => 'FR', 'name' => 'France']]);
        $this->stubCountryNames(['NL' => [13, 'Pays-Bas'], 'BE' => [3, 'Belgique']]);

        $option = $this->class->getOption([]);
        $countries = $option['countries'];

        $this->assertSame('ideal_min_amounts', $option['advanced_options'][0]['descriptions']['min_amount']['name']);
        $this->assertSame('ideal_countries', $countries['name']);
        $this->assertSame([], $countries['options']);
        $this->assertSame([], $countries['value']);
        $this->assertSame([], $countries['default']);
        $this->assertSame('help iDEAL', $countries['help']);
        $this->assertSame('empty iDEAL / Pays-Bas, Belgique', $countries['empty']);
        $this->assertSame('paymentmethods.apm.countries.search', $countries['labels']['search']);
        $this->assertSame('paymentmethods.apm.countries.warning', $countries['warning']);
    }

    public function testPproWithoutAnyApiCountryActiveInTheShopNamesUnknownCountriesByIsoCode()
    {
        $this->class->set('name', 'ideal');
        $this->stubCountryMessages();
        $this->stubCountries('{"ideal":["NL"]}');
        $this->stubShopCountries([8 => ['iso_code' => 'FR', 'name' => 'France']]);

        $this->assertSame('empty iDEAL / NL', $this->class->getOption([])['countries']['empty']);
    }

    public function testPproWithoutAnyApiCountryActiveInTheShopDoesNotAnnounceTheRemovalOfTheStoredCountries()
    {
        $this->class->set('name', 'ideal');
        $this->stubCountryMessages();
        $this->stubCountries('{"ideal":["NL"]}');
        $this->stubShopCountries([8 => ['iso_code' => 'FR', 'name' => 'France']]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"ideal":{"countries":["NL"]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame([], $countries['value']);
        $this->assertArrayHasKey('empty', $countries);
        $this->assertArrayNotHasKey('removed', $countries);
    }

    public function testPproWithoutApiCountryRestrictionNorShopCountryGetsAnEmptyCountryFieldWithoutMessage()
    {
        $this->class->set('name', 'bancontact');
        $this->stubCountryMessages();
        $this->stubCountries('{}');
        $this->stubShopCountries([]);

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame([], $countries['options']);
        $this->assertSame([], $countries['value']);
        $this->assertSame('help Bancontact', $countries['help']);
        $this->assertArrayNotHasKey('empty', $countries);
    }

    public function testPproWithAnEmptyApiCountryListGetsAnEmptyCountryFieldWithoutMessage()
    {
        $this->class->set('name', 'ideal');
        $this->stubCountryMessages();
        $this->stubCountries('{"ideal":[]}');
        $this->stubShopCountries([8 => ['iso_code' => 'FR', 'name' => 'France']]);

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame([], $countries['options']);
        $this->assertArrayNotHasKey('empty', $countries);
    }

    public function testPproStoredCountriesInactiveInTheShopAreAnnouncedAsRemoved()
    {
        $this->class->set('name', 'scalapay');
        $this->stubCountryMessages();
        $this->stubCountries('{"scalapay":["DE","FR","IT","AT"]}');
        $this->stubShopCountries([
            8 => ['iso_code' => 'FR', 'name' => 'France'],
            10 => ['iso_code' => 'IT', 'name' => 'Italie'],
        ]);
        $this->stubCountryNames(['AT' => [2, 'Autriche'], 'DE' => [1, 'Allemagne']]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"scalapay":{"countries":["AT","IT","DE"]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame(['IT'], $countries['value']);
        $this->assertSame('removed Autriche, Allemagne', $countries['removed']);
    }

    public function testPproStoredCountriesNoLongerAllowedByTheApiAreAnnouncedAsRemoved()
    {
        $this->class->set('name', 'scalapay');
        $this->stubCountryMessages();
        $this->stubCountries('{"scalapay":["FR","IT"]}');
        $this->stubShopCountries([
            8 => ['iso_code' => 'FR', 'name' => 'France'],
            10 => ['iso_code' => 'IT', 'name' => 'Italie'],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"scalapay":{"countries":["ES","IT","DE"]}}');
        $this->stubCountryNames(['ES' => [6, 'Espagne']]);

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame(['IT'], $countries['value']);
        $this->assertSame('removed Espagne, DE', $countries['removed']);
    }

    public function testPproStoredBelgiumInactiveInTheShopIsAnnouncedAsRemovedFromTheBancontactSelection()
    {
        $this->class->set('name', 'bancontact');
        $this->stubCountryMessages();
        $this->stubCountries('{}');
        $this->stubShopCountries([
            8 => ['iso_code' => 'FR', 'name' => 'France'],
            10 => ['iso_code' => 'IT', 'name' => 'Italie'],
            12 => ['iso_code' => 'LU', 'name' => 'Luxembourg'],
        ]);
        $this->stubCountryNames(['BE' => [3, 'Belgique']]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"bancontact":{"countries":["BE"]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame([], $countries['value']);
        $this->assertSame(['FR', 'IT', 'LU'], $countries['default']);
        $this->assertSame('removed Belgique', $countries['removed']);
        $this->assertArrayNotHasKey('empty', $countries);
    }

    public function testPproMalformedStoredCountriesAreNotAnnouncedAsRemoved()
    {
        $this->class->set('name', 'bancontact');
        $this->stubCountryMessages();
        $this->stubCountries('{}');
        $this->stubShopCountries([8 => ['iso_code' => 'FR', 'name' => 'France']]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"bancontact":{"countries":[42,"FR"]}}');

        $this->assertArrayNotHasKey('removed', $this->class->getOption([])['countries']);
    }

    public function testPproStoredSelectionInsideTheSelectableCountriesAnnouncesNoRemoval()
    {
        $this->class->set('name', 'scalapay');
        $this->stubCountryMessages();
        $this->stubCountries('{"scalapay":["FR","IT"]}');
        $this->stubShopCountries([
            8 => ['iso_code' => 'FR', 'name' => 'France'],
            10 => ['iso_code' => 'IT', 'name' => 'Italie'],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"scalapay":{"countries":[]}}');

        $this->assertArrayNotHasKey('removed', $this->class->getOption([])['countries']);
    }

    public function testPproWithoutApiCountryRestrictionListsTheShopCountries()
    {
        $this->class->set('name', 'bancontact');
        $this->stubCountries('{}');
        $this->stubShopCountries([
            21 => ['iso_code' => 'BE', 'name' => 'Belgique'],
            12 => ['iso_code' => 'LU', 'name' => 'Luxembourg'],
        ]);
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"bancontact":{"countries":["BE"]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame(
            [['value' => 'BE', 'label' => 'Belgique'], ['value' => 'LU', 'label' => 'Luxembourg']],
            $countries['options']
        );
        $this->assertSame(['BE'], $countries['value']);
    }

    public function testPproWithoutApiLimitsGetsNoRestrictionField()
    {
        $this->class->set('name', 'wero');
        $this->stubCountries('{}');

        $option = $this->class->getOption([]);

        $this->assertArrayNotHasKey('advanced_options', $option);
        $this->assertArrayNotHasKey('countries', $option);
    }

    public function testCardMethodGetsNoRestrictionField()
    {
        $this->class->set('name', 'standard');

        $option = $this->class->getOption([]);

        $this->assertArrayNotHasKey('advanced_options', $option);
        $this->assertArrayNotHasKey('countries', $option);
    }

    private function stubCountryMessages()
    {
        $translation = $this->translation->getPaymentMethodsTranslations();
        $translation['apm']['countries']['help'] = 'help %s';
        $translation['apm']['countries']['empty'] = 'empty %1$s / %2$s';
        $translation['apm']['countries']['removed'] = 'removed %s';
        $this->class->set('translation', $translation);
    }

    /**
     * @param array $names [ISO code => [country id, name]]
     */
    private function stubCountryNames($names)
    {
        foreach ($names as $iso_code => $country) {
            $this->country_adapter->shouldReceive('getByIso')
                ->with($iso_code)
                ->andReturn($country[0]);
            $this->country_adapter->shouldReceive('getNameById')
                ->with(1, $country[0])
                ->andReturn($country[1]);
        }
    }

    /**
     * @param string $countries
     */
    private function stubCountries($countries)
    {
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn($countries);
    }

    /**
     * @param array $countries
     */
    private function stubShopCountries($countries)
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->with(1, true)
            ->andReturn($countries);
    }
}
