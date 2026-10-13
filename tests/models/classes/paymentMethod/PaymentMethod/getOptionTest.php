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

    public function testPproCountriesDefaultToEveryApiCountry()
    {
        $this->class->set('name', 'scalapay');
        $this->stubCountries('{"scalapay":["FR","IT"]}');
        $this->country_adapter->shouldReceive('getByIso')
            ->andReturnUsing(function ($iso) {
                return 'FR' == $iso ? 8 : 10;
            });
        $this->country_adapter->shouldReceive('getNameById')
            ->andReturnUsing(function ($id_lang, $id_country) {
                return 8 == $id_country ? 'France' : 'Italie';
            });

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
        $this->configuration->shouldReceive('getValue')
            ->with('apm_custom_settings')
            ->andReturn('{"satispay":{"countries":[]}}');

        $countries = $this->class->getOption([])['countries'];

        $this->assertSame([], $countries['value']);
        $this->assertSame(['IT'], $countries['default']);
    }

    public function testPproWithoutApiCountryRestrictionListsTheShopCountries()
    {
        $this->class->set('name', 'bancontact');
        $this->stubCountries('{}');
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn([
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

    /**
     * @param string $countries
     */
    private function stubCountries($countries)
    {
        $this->configuration->shouldReceive('getValue')
            ->with('countries')
            ->andReturn($countries);
    }
}
