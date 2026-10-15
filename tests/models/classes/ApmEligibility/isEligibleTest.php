<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class isEligibleTest extends BaseApmEligibility
{
    public function testEligibleWhenInsideBoundsAndCountryAllowed()
    {
        $this->assertSame(
            ['result' => true, 'reason' => 'ok'],
            $this->class->isEligible('satispay', 4242, 'EUR', 'IT')
        );
    }

    public function boundaryProvider()
    {
        yield [29, false, 'amount_low'];

        yield [30, true, 'ok'];

        yield [2000000, true, 'ok'];

        yield [2000001, false, 'amount_high'];
    }

    /**
     * @dataProvider boundaryProvider
     *
     * @param int $cents
     * @param bool $result
     * @param string $reason
     */
    public function testBoundsAreInclusive($cents, $result, $reason)
    {
        $this->assertSame(
            ['result' => $result, 'reason' => $reason],
            $this->class->isEligible('satispay', $cents, 'EUR', 'IT')
        );
    }

    public function testSatispayLimitedToItalyIsRefusedForAFrenchBillingAddress()
    {
        $this->assertSame(
            ['result' => false, 'reason' => 'country'],
            $this->class->isEligible('satispay', 4242, 'EUR', 'FR')
        );
    }

    public function testBillingCountryIsComparedCaseInsensitively()
    {
        $this->assertTrue($this->class->isEligible('satispay', 4242, 'EUR', 'it')['result']);
    }

    public function testMerchantCountrySubsetIsApplied()
    {
        $this->setCustomSettings(['scalapay' => ['countries' => ['FR']]]);

        $this->assertSame(
            ['result' => false, 'reason' => 'country'],
            $this->class->isEligible('scalapay', 4242, 'EUR', 'IT')
        );
        $this->assertTrue($this->class->isEligible('scalapay', 4242, 'EUR', 'FR')['result']);
    }

    public function testEmptyMerchantCountryListHidesTheApmForEveryone()
    {
        $this->setCustomSettings(['bancontact' => ['countries' => []]]);

        $this->assertSame(
            ['result' => false, 'reason' => 'country'],
            $this->class->isEligible('bancontact', 4242, 'EUR', 'BE')
        );
    }

    public function testMerchantAmountRangeIsApplied()
    {
        $this->setCustomSettings(['ideal' => ['max' => ['EUR' => 10000]]]);

        $this->assertSame(
            ['result' => false, 'reason' => 'amount_high'],
            $this->class->isEligible('ideal', 10001, 'EUR', 'NL')
        );
    }

    public function testNoCountryRestrictionAcceptsAnyBillingCountry()
    {
        $this->assertTrue($this->class->isEligible('bancontact', 4242, 'EUR', 'FR')['result']);
    }

    public function testNoBillingAddressYetIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'reason' => 'no_address'],
            $this->class->isEligible('bancontact', 4242, 'EUR', '')
        );
    }

    public function testApmMissingFromTheCacheIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'reason' => 'no_config'],
            $this->class->isEligible('wero', 4242, 'EUR', 'BE')
        );
    }

    public function testEmptyCacheRefusesEveryPproApm()
    {
        $this->config_values['amounts'] = '{}';
        $this->config_values['countries'] = '{}';

        foreach (['satispay', 'bancontact', 'ideal', 'mybank', 'wero', 'bizum', 'scalapay'] as $name) {
            $this->assertSame(
                ['result' => false, 'reason' => 'no_config'],
                $this->class->isEligible($name, 4242, 'EUR', 'IT'),
                $name
            );
        }
    }

    public function testCurrencyMissingFromTheCacheIsRefused()
    {
        $this->assertSame(
            ['result' => false, 'reason' => 'currency'],
            $this->class->isEligible('satispay', 4242, 'USD', 'IT')
        );
    }

    public function testInvalidEffectiveLimitsAreRefused()
    {
        $this->config_values['amounts'] = json_encode([
            'satispay' => ['min' => 'EUR:30', 'max' => 'EUR:4000'],
        ]);
        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 5000]]]);

        $this->assertSame(
            ['result' => false, 'reason' => 'invalid_settings'],
            $this->class->isEligible('satispay', 4500, 'EUR', 'IT')
        );
    }

    public function testNonPproMethodIsNotHandled()
    {
        $this->assertSame(
            ['result' => true, 'reason' => 'ok'],
            $this->class->isEligible('standard', 1, 'USD', '')
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $cents
     */
    public function testInvalidAmountIsRefused($cents)
    {
        $this->assertSame(
            ['result' => false, 'reason' => 'amount_invalid'],
            $this->class->isEligible('satispay', $cents, 'EUR', 'IT')
        );
    }
}
