<?php

namespace PayPlug\tests\actions\OnboardingAction;

/**
 * @group unit
 * @group action
 * @group onboarding_action
 *
 * @runTestsInSeparateProcesses
 */
class disableIntegratedActionTest extends BaseOnboardingAction
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
            $this->action->disableIntegratedAction()
        );
    }

    public function testWhenOnboardingStatesConfigurationCannotBeSetted()
    {
        $this->configurationClass
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'onboarding_states':
                        return '{"embedded_mode": "redirect"}';
                    case 'embedded_mode':
                        return 'integrated';
                    default:
                        break;
                }
            });

        $this->configurationClass
            ->shouldReceive([
                'set' => false,
            ]);

        $this->assertSame(
            false,
            $this->action->disableIntegratedAction()
        );
    }

    public function testWhenConfigurationHasBeenChanged()
    {
        $this->configurationClass
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'onboarding_states':
                        return '{"embedded_mode": "redirect"}';
                    case 'embedded_mode':
                        return 'redirect';
                    default:
                        break;
                }
            });

        $this->configurationClass
            ->shouldReceive([
                'set' => true,
            ]);

        $this->assertSame(
            true,
            $this->action->disableIntegratedAction()
        );
    }

    public function testWhenEmbeddedModeConfigurationCannotBeSetted()
    {
        $this->configurationClass
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'onboarding_states':
                        return '{"embedded_mode": "redirect"}';
                    case 'embedded_mode':
                        return 'integrated';
                    default:
                        break;
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
                    default:
                        break;
                }
            });

        $this->assertSame(
            false,
            $this->action->disableIntegratedAction()
        );
    }

    public function testWhenConfigurationsIsSetted()
    {
        $this->configurationClass
            ->shouldReceive('getValue')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'onboarding_states':
                        return '{"embedded_mode": "redirect"}';
                    case 'embedded_mode':
                        return 'integrated';
                    default:
                        break;
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
                    default:
                        break;
                }
            });

        $this->assertSame(
            true,
            $this->action->disableIntegratedAction()
        );
    }
}
