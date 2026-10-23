<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class importLegacyScalapaySettingsTest extends BaseApmEligibility
{
    public function testLegacyAmountsAreCopiedToTheGenericSettingsAndKept()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->config_values['scalapay_custom_max_amounts'] = 'EUR:150000';

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(
            ['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000},"max":{"EUR":150000}}}'],
            $this->saved
        );
        $this->assertSame('EUR:1000', $this->config_values['scalapay_custom_min_amounts']);
        $this->assertSame('EUR:150000', $this->config_values['scalapay_custom_max_amounts']);
    }

    public function testNothingIsWrittenWithoutLegacyAmounts()
    {
        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame([], $this->saved);
    }

    public function testImportIsIdempotent()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';

        $this->assertTrue($this->class->importLegacyScalapaySettings());
        $first = $this->config_values['apm_custom_settings'];
        $this->saved = [];

        $this->assertTrue($this->class->importLegacyScalapaySettings());
        $this->assertSame([], $this->saved);
        $this->assertSame($first, $this->config_values['apm_custom_settings']);
    }

    public function testGenericSettingsAlreadyStoredWin()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->config_values['scalapay_custom_max_amounts'] = 'EUR:150000';
        $this->setCustomSettings([
            'satispay' => ['countries' => ['IT']],
            'scalapay' => ['min' => ['EUR' => 3000], 'countries' => ['FR']],
        ]);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(
            '{"satispay":{"countries":["IT"]},"scalapay":{"min":{"EUR":3000},"countries":["FR"],"max":{"EUR":150000}}}',
            $this->saved['apm_custom_settings']
        );
    }

    public function testLegacyAmountsEqualToTheApiBoundsAreNotStoredAsOverrides()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:500';
        $this->config_values['scalapay_custom_max_amounts'] = 'EUR:200000';

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame([], $this->saved);
    }

    public function testLegacyAmountsOutsideTheApiBoundsAreClampedWhenRead()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:100';
        $this->config_values['scalapay_custom_max_amounts'] = 'EUR:900000';

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(
            ['min' => 500, 'max' => 200000, 'countries' => ['AT', 'BE', 'DE', 'ES', 'FI', 'FR', 'IT', 'NL', 'PT'], 'valid' => true],
            $this->class->getEffectiveLimits('scalapay', 'EUR')
        );
    }

    public function malformedLegacyAmountProvider()
    {
        yield [false];

        yield ['EUR:'];

        yield ['1000'];

        yield ['EUR:-5'];

        yield ['EUR:10.5'];

        yield ['abc'];
    }

    /**
     * @dataProvider malformedLegacyAmountProvider
     *
     * @param mixed $amount
     */
    public function testMalformedLegacyAmountsAreIgnored($amount)
    {
        $this->config_values['scalapay_custom_min_amounts'] = $amount;
        $this->config_values['scalapay_custom_max_amounts'] = $amount;

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame([], $this->saved);
    }

    public function testWriteFailureIsReported()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->set_result = false;

        $this->assertFalse($this->class->importLegacyScalapaySettings());
        $this->assertContains(
            ['ApmEligibility::importLegacyScalapaySettings() - shop group 0 / shop 0: failed to write the merchant settings', 'error'],
            $this->logs
        );
    }

    public function testShopAmountsAreWrittenAtTheShopScopeOnly()
    {
        $this->setScopedValue('scalapay_custom_min_amounts', 'EUR:1000', 1, 2);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame([], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000}}}']],
            $this->saved_scoped
        );
        $this->assertContains(
            ['ApmEligibility::importLegacyScalapaySettings() - shop group 1 / shop 2: merchant settings imported', 'info'],
            $this->logs
        );
    }

    public function testShopGroupAmountsAreWrittenAtTheShopGroupScope()
    {
        $this->setScopedValue('scalapay_custom_max_amounts', 'EUR:150000', 3);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame([], $this->saved);
        $this->assertSame(
            ['3/0' => ['apm_custom_settings' => '{"scalapay":{"max":{"EUR":150000}}}']],
            $this->saved_scoped
        );
    }

    public function testBoundsStoredAtDifferentScopesAreMergedInTheShopEntry()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->setScopedValue('scalapay_custom_max_amounts', 'EUR:150000', 1, 2);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000}}}'], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000},"max":{"EUR":150000}}}']],
            $this->saved_scoped
        );
    }

    public function testShopRowsReadBeforeTheGlobalOnesStillInheritTheGlobalBound()
    {
        $this->setScopedValue('scalapay_custom_min_amounts', 'EUR:1000', 1, 2);
        $this->config_values['scalapay_custom_max_amounts'] = 'EUR:150000';

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(['apm_custom_settings' => '{"scalapay":{"max":{"EUR":150000}}}'], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000},"max":{"EUR":150000}}}']],
            $this->saved_scoped
        );
    }

    public function testShopInheritingTheGlobalAmountsIsNotWritten()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->setScopedValue('scalapay_custom_min_amounts', 'EUR:1000', 1, 2);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000}}}'], $this->saved);
        $this->assertSame([], $this->saved_scoped);
    }

    public function testShopSettingsAlreadyStoredWin()
    {
        $this->setScopedValue('scalapay_custom_min_amounts', 'EUR:1000', 1, 2);
        $this->setScopedValue('apm_custom_settings', '{"scalapay":{"min":{"EUR":3000}}}', 1, 2);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame([], $this->saved);
        $this->assertSame([], $this->saved_scoped);
    }

    public function testShopAmountEqualToTheShopApiBoundIsNotStored()
    {
        $this->setScopedValue('amounts', '{"scalapay":{"min":"EUR:800","max":"EUR:200000"}}', 1, 2);
        $this->setScopedValue('scalapay_custom_min_amounts', 'EUR:800', 1, 2);
        $this->setScopedValue('scalapay_custom_max_amounts', 'EUR:200000', 1, 2);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame([], $this->saved);
        $this->assertSame([], $this->saved_scoped);
    }

    public function testShopWithItsOwnSettingsGetsTheInheritedAmounts()
    {
        $this->config_values['scalapay_custom_min_amounts'] = 'EUR:1000';
        $this->setScopedValue('apm_custom_settings', '{"satispay":{"countries":["IT"]}}', 1, 2);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000}}}'], $this->saved);
        $this->assertSame(
            ['1/2' => ['apm_custom_settings' => '{"satispay":{"countries":["IT"]},"scalapay":{"min":{"EUR":1000}}}']],
            $this->saved_scoped
        );
    }

    public function testDeletedShopKeepsTheShopGroupItWasStoredWith()
    {
        $this->setScopedValue('scalapay_custom_min_amounts', 'EUR:1000', 3, 9);

        $this->assertTrue($this->class->importLegacyScalapaySettings());

        $this->assertSame(
            ['3/9' => ['apm_custom_settings' => '{"scalapay":{"min":{"EUR":1000}}}']],
            $this->saved_scoped
        );
    }
}
