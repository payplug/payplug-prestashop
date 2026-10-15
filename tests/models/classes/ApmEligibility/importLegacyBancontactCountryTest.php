<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class importLegacyBancontactCountryTest extends BaseApmEligibility
{
    public function testBelgiumOnlyIsCarriedOverAndTheLegacyValueKept()
    {
        $this->config_values['bancontact_country'] = '1';

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame(['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}'], $this->saved);
        $this->assertSame('1', $this->config_values['bancontact_country']);
    }

    public function disabledLegacyValueProvider()
    {
        yield [false];

        yield ['0'];

        yield [''];

        yield [0];
    }

    /**
     * @dataProvider disabledLegacyValueProvider
     *
     * @param mixed $value
     */
    public function testNothingIsWrittenWhenBelgiumOnlyWasNotTicked($value)
    {
        $this->config_values['bancontact_country'] = $value;

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
    }

    public function testCountriesAlreadyStoredWin()
    {
        $this->config_values['bancontact_country'] = '1';
        $this->setCustomSettings([
            'bancontact' => ['countries' => ['FR', 'LU']],
        ]);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
    }

    public function testAnEmptyStoredCountryListIsKept()
    {
        $this->config_values['bancontact_country'] = '1';
        $this->setCustomSettings([
            'bancontact' => ['countries' => []],
        ]);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
    }

    public function testOtherStoredSettingsAreKept()
    {
        $this->config_values['bancontact_country'] = '1';
        $this->setCustomSettings([
            'bancontact' => ['min' => ['EUR' => 1000]],
            'satispay' => ['countries' => ['IT']],
        ]);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame(
            '{"bancontact":{"min":{"EUR":1000},"countries":["BE"]},"satispay":{"countries":["IT"]}}',
            $this->saved['apm_custom_settings']
        );
    }

    public function testImportIsIdempotent()
    {
        $this->config_values['bancontact_country'] = '1';

        $this->assertTrue($this->class->importLegacyBancontactCountry());
        $first = $this->config_values['apm_custom_settings'];
        $this->saved = [];

        $this->assertTrue($this->class->importLegacyBancontactCountry());
        $this->assertSame([], $this->saved);
        $this->assertSame($first, $this->config_values['apm_custom_settings']);
    }

    public function testBelgiumOnlyIsEnforcedWhenTheApiAllowsEveryCountry()
    {
        $this->config_values['bancontact_country'] = '1';

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertNull($this->class->getApiLimits('bancontact', 'EUR')['countries']);
        $this->assertSame(['BE'], $this->class->getEffectiveLimits('bancontact', 'EUR')['countries']);
        $this->assertSame(['result' => true, 'reason' => 'ok'], $this->class->isEligible('bancontact', 4242, 'EUR', 'BE'));
        $this->assertSame(['result' => false, 'reason' => 'country'], $this->class->isEligible('bancontact', 4242, 'EUR', 'FR'));
    }

    public function testWriteFailureIsReported()
    {
        $this->config_values['bancontact_country'] = '1';
        $this->set_result = false;

        $this->assertFalse($this->class->importLegacyBancontactCountry());
    }
}
