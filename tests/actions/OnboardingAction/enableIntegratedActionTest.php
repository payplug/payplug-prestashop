<?php

namespace PayPlug\tests\actions\OnboardingAction;

/**
 * @group unit
 * @group action
 * @group onboarding_action
 *
 * @runTestsInSeparateProcesses
 */
class enableIntegratedActionTest extends BaseOnboardingAction
{
    public function invalidJSONFormatDataProvider()
    {
        yield [''];
        yield ['{"feature": \'value\'}'];
        yield ['{"feature": "value", }'];
        yield ['{{}}'];
    }

    /**
     * @dataProvider invalidJSONFormatDataProvider
     *
     * @param mixed $json
     */
    public function testWhenGetOnboardingStateGettedIsNotValidArray($json)
    {
        $this->configurationClass->shouldReceive([
            'getValue' => $json,
        ]);

        $this->assertSame(
            false,
            $this->action->enableIntegratedAction()
        );
    }

    public function testWhenGetOnboardingStateContainedEmbeddedMode()
    {
        $this->configurationClass->shouldReceive([
            'getValue' => '{"embedded_mode": "redirect"}',
        ]);

        $this->assertSame(
            true,
            $this->action->enableIntegratedAction()
        );
    }

    public function testWhenOnboardingStatesConfigurationCannotBeSetted()
    {
        $this->configurationClass
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'onboarding_states':
                        return '{}';
                    case 'embedded_mode':
                        return 'popup';
                    default:
                        break;
                }
            });

        $this->configurationClass
            ->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                switch ($key) {
                    case 'onboarding_states':
                        return false;
                    case 'embedded_mode':
                        return true;
                    default:
                        break;
                }
            });

        $this->assertSame(
            false,
            $this->action->enableIntegratedAction()
        );
    }

    public function testWhenEmbeddedModeConfigurationCannotBeSetted()
    {
        $this->configurationClass
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'onboarding_states':
                        return '{}';
                    case 'embedded_mode':
                        return 'popup';
                }
            });

        $this->configurationClass
            ->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                switch ($key) {
                    case 'onboarding_states':
                        return true;
                    case 'embedded_mode':
                        return false;
                }
            });

        $this->assertSame(
            false,
            $this->action->enableIntegratedAction()
        );
    }

    public function testWhenConfigurationsIsSetted()
    {
        $this->configurationClass
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'onboarding_states':
                        return '{}';
                    case 'embedded_mode':
                        return 'popup';
                }
            });

        $this->configurationClass
            ->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                switch ($key) {
                    case 'onboarding_states':
                        return true;
                    case 'embedded_mode':
                        return true;
                }
            });

        $this->assertSame(
            true,
            $this->action->enableIntegratedAction()
        );
    }
}
