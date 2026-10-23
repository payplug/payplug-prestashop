<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class getInvalidSettingsTest extends BaseApmEligibility
{
    public function testReturnsEachEnabledMethodWhoseMinimumIsAboveItsMaximum()
    {
        $this->config_values['amounts'] = json_encode([
            'satispay' => ['min' => 'EUR:30', 'max' => 'EUR:4000'],
            'bizum' => ['min' => 'EUR:6000', 'max' => 'EUR:2000000'],
            'ideal' => ['min' => 'EUR:30', 'max' => 'EUR:2000000'],
        ]);
        $this->setCustomSettings([
            'satispay' => ['min' => ['EUR' => 5000]],
            'bizum' => ['max' => ['EUR' => 5500]],
            'ideal' => ['min' => ['EUR' => 1000], 'max' => ['EUR' => 9000]],
        ]);

        $this->assertSame(
            [
                'satispay' => ['min' => 5000, 'max' => 4000, 'currency' => 'EUR'],
                'bizum' => ['min' => 6000, 'max' => 5500, 'currency' => 'EUR'],
            ],
            $this->class->getInvalidSettings()
        );
        $this->assertSame([], $this->logs);
    }

    public function testChecksTheAmountsInTheBackOfficeCurrency()
    {
        $this->config_values['amounts'] = json_encode([
            'satispay' => ['min' => 'GBP:30', 'max' => 'GBP:4000'],
        ]);
        $this->setCustomSettings([
            'satispay' => ['min' => ['EUR' => 5000, 'GBP' => 4500]],
        ]);

        $this->assertSame(
            ['satispay' => ['min' => 4500, 'max' => 4000, 'currency' => 'GBP']],
            $this->class->getInvalidSettings()
        );
    }

    public function testIgnoresADisabledMethod()
    {
        $this->config_values['amounts'] = json_encode([
            'satispay' => ['min' => 'EUR:30', 'max' => 'EUR:4000'],
            'bizum' => ['min' => 'EUR:30', 'max' => 'EUR:4000'],
        ]);
        $this->config_values['payment_methods'] = json_encode(['satispay' => false]);
        $this->setCustomSettings([
            'satispay' => ['min' => ['EUR' => 5000]],
            'bizum' => ['min' => ['EUR' => 5000]],
        ]);

        $this->assertSame([], $this->class->getInvalidSettings());
    }

    public function testIgnoresAMethodWithoutAccountLimits()
    {
        $this->config_values['amounts'] = false;
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 5000], 'max' => ['EUR' => 4000]]]);

        $this->assertSame([], $this->class->getInvalidSettings());
    }

    public function testReturnsNothingWhenEverySettingFitsTheAccountLimits()
    {
        $this->setCustomSettings([
            'satispay' => ['min' => ['EUR' => 5000], 'max' => ['EUR' => 9000]],
            'scalapay' => ['countries' => ['IT']],
        ]);

        $this->assertSame([], $this->class->getInvalidSettings());
    }
}
