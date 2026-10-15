<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class getEffectiveLimitsTest extends BaseApmEligibility
{
    public function testApiLimitsAreReadFromTheCacheForTheCurrency()
    {
        $this->assertSame(
            [
                'configured' => true,
                'currency_supported' => true,
                'min' => 30,
                'max' => 2000000,
                'countries' => ['IT'],
            ],
            $this->class->getApiLimits('satispay', 'EUR')
        );
    }

    public function testApiLimitsOfAnApmMissingFromTheCacheAreNotConfigured()
    {
        $limits = $this->class->getApiLimits('wero', 'EUR');

        $this->assertFalse($limits['configured']);
        $this->assertNull($limits['countries']);
    }

    public function testApiLimitsWhenTheCacheIsMalformed()
    {
        $this->config_values['amounts'] = 'not json';
        $this->config_values['countries'] = false;

        $this->assertFalse($this->class->getApiLimits('satispay', 'EUR')['configured']);
    }

    public function testApiLimitsWhenTheCurrencyIsNotInTheCache()
    {
        $limits = $this->class->getApiLimits('satispay', 'USD');

        $this->assertTrue($limits['configured']);
        $this->assertFalse($limits['currency_supported']);
    }

    public function testApiLimitsWhenTheApmHasNoCountryRestriction()
    {
        $this->assertNull($this->class->getApiLimits('bancontact', 'EUR')['countries']);
    }

    public function testApiLimitsFallBackOnTheDefaultBoundWhenTheApmHasNone()
    {
        $this->config_values['amounts'] = json_encode([
            'default' => ['min' => 'EUR:99', 'max' => 'EUR:2000000'],
            'satispay' => ['max' => 'EUR:5000'],
        ]);

        $limits = $this->class->getApiLimits('satispay', 'EUR');

        $this->assertSame(99, $limits['min']);
        $this->assertSame(5000, $limits['max']);
    }

    public function testNoMerchantSettingsMeansApiValues()
    {
        $this->assertSame(
            ['min' => 30, 'max' => 2000000, 'countries' => ['IT'], 'valid' => true],
            $this->class->getEffectiveLimits('satispay', 'EUR')
        );
    }

    public function testMerchantNarrowsBothBounds()
    {
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 1000], 'max' => ['EUR' => 50000]]]);

        $limits = $this->class->getEffectiveLimits('satispay', 'EUR');

        $this->assertSame(1000, $limits['min']);
        $this->assertSame(50000, $limits['max']);
    }

    public function testMerchantCannotWidenTheBounds()
    {
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 10], 'max' => ['EUR' => 9000000]]]);

        $limits = $this->class->getEffectiveLimits('satispay', 'EUR');

        $this->assertSame(30, $limits['min']);
        $this->assertSame(2000000, $limits['max']);
    }

    public function testMerchantBoundOfAnotherCurrencyIsIgnored()
    {
        $this->setCustomSettings(['satispay' => ['min' => ['GBP' => 1000]]]);

        $this->assertSame(30, $this->class->getEffectiveLimits('satispay', 'EUR')['min']);
    }

    public function testCountriesAreTheIntersectionOfMerchantAndApiLists()
    {
        $this->setCustomSettings(['scalapay' => ['countries' => ['FR', 'IT', 'GB']]]);

        $this->assertSame(['FR', 'IT'], $this->class->getEffectiveLimits('scalapay', 'EUR')['countries']);
    }

    public function testMerchantCountriesApplyWhenTheApiHasNoRestriction()
    {
        $this->setCustomSettings(['bancontact' => ['countries' => ['BE', 'LU']]]);

        $this->assertSame(['BE', 'LU'], $this->class->getEffectiveLimits('bancontact', 'EUR')['countries']);
    }

    public function testAnEmptyMerchantCountryListIsKeptEmpty()
    {
        $this->setCustomSettings(['satispay' => ['countries' => []]]);

        $limits = $this->class->getEffectiveLimits('satispay', 'EUR');

        $this->assertSame([], $limits['countries']);
        $this->assertTrue($limits['valid']);
    }

    public function testMinAboveMaxAfterClampingIsInvalid()
    {
        $this->config_values['amounts'] = json_encode([
            'satispay' => ['min' => 'EUR:30', 'max' => 'EUR:4000'],
        ]);
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 5000]]]);

        $this->assertFalse($this->class->getEffectiveLimits('satispay', 'EUR')['valid']);
    }

    public function testMalformedMerchantSettingsAreIgnored()
    {
        $this->config_values['apm_custom_settings'] = '{"satispay":"oops"';

        $this->assertSame(
            ['min' => 30, 'max' => 2000000, 'countries' => ['IT'], 'valid' => true],
            $this->class->getEffectiveLimits('satispay', 'EUR')
        );
    }

    public function testMalformedMerchantValuesAreIgnored()
    {
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 'abc'], 'max' => 'EUR:50', 'countries' => 'IT']]);

        $this->assertSame(
            ['min' => 30, 'max' => 2000000, 'countries' => ['IT'], 'valid' => true],
            $this->class->getEffectiveLimits('satispay', 'EUR')
        );
    }

    public function testScalapayBoundsComeFromTheGenericSettingsOnly()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->config_values['scalapay_custom_max_amounts'] = 'EUR:150000';
        $this->setCustomSettings(['scalapay' => ['min' => ['EUR' => 3000], 'countries' => ['IT']]]);

        $this->assertSame(
            ['min' => 3000, 'max' => 200000, 'countries' => ['IT'], 'valid' => true],
            $this->class->getEffectiveLimits('scalapay', 'EUR')
        );
    }
}
