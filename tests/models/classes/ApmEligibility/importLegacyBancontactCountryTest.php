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
        $this->assertContains(
            ['ApmEligibility::importLegacyBancontactCountry() - shop group 0 / shop 0: failed to write the merchant settings', 'error'],
            $this->logs
        );
    }

    public function testShopSettingIsWrittenAtTheShopScopeOnly()
    {
        $this->config_values['bancontact_country'] = '0';
        $this->setScopedValue('bancontact_country', '1', 1, 2);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
        $this->assertSame('{}', $this->config_values['apm_custom_settings']);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}']],
            $this->saved_scoped
        );
        $this->assertContains(
            ['ApmEligibility::importLegacyBancontactCountry() - shop group 1 / shop 2: merchant settings imported', 'info'],
            $this->logs
        );
    }

    public function testShopGroupSettingIsWrittenAtTheShopGroupScope()
    {
        $this->config_values['bancontact_country'] = '0';
        $this->setScopedValue('bancontact_country', '1', 3);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
        $this->assertSame(
            ['3/0' => ['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}']],
            $this->saved_scoped
        );
    }

    public function testShopEntryStartsFromTheInheritedSettings()
    {
        $this->setCustomSettings([
            'satispay' => ['countries' => ['IT']],
        ]);
        $this->config_values['bancontact_country'] = '0';
        $this->setScopedValue('bancontact_country', '1', 1, 2);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"bancontact":{"countries":["BE"]},"satispay":{"countries":["IT"]}}']],
            $this->saved_scoped
        );
    }

    public function testShopThatUntickedBelgiumOnlyKeepsEveryCountry()
    {
        $this->setCustomSettings([
            'satispay' => ['countries' => ['IT']],
        ]);
        $this->config_values['bancontact_country'] = '1';
        $this->setScopedValue('bancontact_country', '0', 1, 2);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame(
            ['apm_custom_settings' => '{"bancontact":{"countries":["BE"]},"satispay":{"countries":["IT"]}}'],
            $this->saved
        );
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"satispay":{"countries":["IT"]}}']],
            $this->saved_scoped
        );
    }

    public function testShopInheritingTheGlobalSettingIsNotWritten()
    {
        $this->config_values['bancontact_country'] = '1';
        $this->setScopedValue('bancontact_country', '1', 1, 2);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame(['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}'], $this->saved);
        $this->assertSame([], $this->saved_scoped);
    }

    public function testShopCountriesAlreadyStoredWin()
    {
        $this->setScopedValue('bancontact_country', '1', 1, 2);
        $this->setScopedValue('apm_custom_settings', '{"bancontact":{"countries":["FR"]}}', 1, 2);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
        $this->assertSame([], $this->saved_scoped);
    }

    public function testShopWithItsOwnSettingsKeepsTheInheritedBelgiumOnly()
    {
        $this->config_values['bancontact_country'] = '1';
        $this->setScopedValue('apm_custom_settings', '{"scalapay":{"min":{"EUR":1000}}}', 1, 2);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame(['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}'], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"bancontact":{"countries":["BE"]},"scalapay":{"min":{"EUR":1000}}}']],
            $this->saved_scoped
        );
    }

    public function testBothImportsInSequenceKeepBelgiumOnlyOnAShopWithItsOwnScalapayAmounts()
    {
        $this->config_values['bancontact_country'] = '1';
        $this->setScopedValue('scalapay_custom_min_amounts', 'EUR:1000', 1, 2);
        $this->setScopedValue('scalapay_custom_max_amounts', 'EUR:150000', 1, 2);

        $this->assertTrue($this->class->importLegacyScalapaySettings());
        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame(['apm_custom_settings' => '{"bancontact":{"countries":["BE"]}}'], $this->saved);
        $this->assertSame(
            '{"bancontact":{"countries":["BE"]},"scalapay":{"min":{"EUR":1000},"max":{"EUR":150000}}}',
            $this->scoped_values['apm_custom_settings']['1/2']
        );
        $this->assertSame(['result' => false, 'reason' => 'country'], $this->isEligibleInShop('bancontact', 4242, 'FR', 1, 2));
    }

    public function testShopRowStoredWithoutShopGroupReadsItsShopGroupSettings()
    {
        $this->shop_groups = [2 => 1];
        $this->setScopedValue('apm_custom_settings', '{"satispay":{"countries":["IT"]}}', 1);
        $this->setScopedValue('bancontact_country', '1', 0, 2);

        $this->assertTrue($this->class->importLegacyBancontactCountry());

        $this->assertSame([], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"bancontact":{"countries":["BE"]},"satispay":{"countries":["IT"]}}']],
            $this->saved_scoped
        );
        $this->assertContains(
            ['ApmEligibility::importLegacyBancontactCountry() - shop group 1 / shop 2: merchant settings imported', 'info'],
            $this->logs
        );
    }

    /**
     * @param string $name
     * @param int $cents
     * @param string $billing_iso
     * @param int $id_shop_group
     * @param int $id_shop
     *
     * @return array
     */
    private function isEligibleInShop($name, $cents, $billing_iso, $id_shop_group, $id_shop)
    {
        $global = $this->config_values['apm_custom_settings'];
        $this->config_values['apm_custom_settings'] = $this->scoped_values['apm_custom_settings'][$id_shop_group . '/' . $id_shop];
        $result = $this->class->isEligible($name, $cents, 'EUR', $billing_iso);
        $this->config_values['apm_custom_settings'] = $global;

        return $result;
    }
}
