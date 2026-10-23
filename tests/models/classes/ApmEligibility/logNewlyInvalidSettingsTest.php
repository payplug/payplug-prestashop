<?php

namespace PayPlug\tests\models\classes\ApmEligibility;

/**
 * @group unit
 * @group class
 * @group apm_eligibility_class
 */
class logNewlyInvalidSettingsTest extends BaseApmEligibility
{
    public function setUp(): void
    {
        parent::setUp();

        $this->setCustomSettings(['satispay' => ['min' => ['EUR' => 5000]]]);
    }

    public function testLogsOnceAMethodThatBecomesInvalid()
    {
        $previous = $this->class->getInvalidSettings();
        $this->setAccountLimits('EUR:4000');

        $this->assertSame(['satispay'], $this->class->logNewlyInvalidSettings($previous));
        $this->assertSame(
            [
                [
                    'ApmEligibility: satispay min amount 5000 is above max amount 4000 (EUR cents) with the new account limits, method hidden at checkout',
                    'warning',
                ],
            ],
            $this->logs
        );
    }

    public function testLogsNothingForAMethodAlreadyInvalid()
    {
        $this->setAccountLimits('EUR:4500');
        $previous = $this->class->getInvalidSettings();
        $this->setAccountLimits('EUR:4000');

        $this->assertSame([], $this->class->logNewlyInvalidSettings($previous));
        $this->assertSame([], $this->logs);
    }

    public function testLogsNothingForAMethodThatBecomesValid()
    {
        $this->setAccountLimits('EUR:4000');
        $previous = $this->class->getInvalidSettings();
        $this->setAccountLimits('EUR:2000000');

        $this->assertSame([], $this->class->logNewlyInvalidSettings($previous));
        $this->assertSame([], $this->logs);
    }

    public function testLogsNothingForADisabledMethod()
    {
        $previous = $this->class->getInvalidSettings();
        $this->config_values['payment_methods'] = json_encode(['satispay' => false]);
        $this->setAccountLimits('EUR:4000');

        $this->assertSame([], $this->class->logNewlyInvalidSettings($previous));
        $this->assertSame([], $this->logs);
    }

    public function testLogsAMethodWithoutPreviousAccountLimitsThatIsInvalid()
    {
        $this->config_values['amounts'] = false;
        $previous = $this->class->getInvalidSettings();
        $this->setAccountLimits('EUR:4000');

        $this->assertSame(['satispay'], $this->class->logNewlyInvalidSettings($previous));
        $this->assertCount(1, $this->logs);
    }

    /**
     * @param string $max
     */
    private function setAccountLimits($max)
    {
        $this->config_values['amounts'] = json_encode([
            'satispay' => ['min' => 'EUR:30', 'max' => $max],
        ]);
    }
}
