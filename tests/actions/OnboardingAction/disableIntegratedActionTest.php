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
    public function testWhenGetOnboardingStateGettedIsntValidArray($json)
    {
        $this->configurationClass->shouldReceive([
            'getValue' => $json,
        ]);

        $this->assertSame(
            [
                'success' => false,
                'message' => '$onboarding_states does not exist',
            ],
            $this->action->disableIntegratedAction()
        );
    }

    public function testWhenIntegratedPaymentisNotSet()
    {
        $this->configurationClass->shouldReceive([
                                                     'getValue' => '{}',
                                                 ]);

        $this->assertSame(
            [
                'success' => true,
                'message' => 'integrated payment has not been forced',
            ],
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
            ->shouldReceive('getDefault')
            ->with('onboarding_states')
            ->andReturn('{}');

        $this->configurationClass
            ->shouldReceive([
                'set' => false,
            ]);

        $this->assertSame(
            [
                'success' => false,
                'message' => 'Something wrong happened! We could not force rollback the integrated payment',
            ],
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
            ->shouldReceive('getDefault')
            ->with('onboarding_states')
            ->andReturn('{}');

        $this->configurationClass
            ->shouldReceive([
                'set' => true,
            ]);

        $this->assertSame(
            [
                'success' => true,
                'message' => 'embedded_mode is different from integrated',
            ],
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
            ->shouldReceive('getDefault')
            ->with('onboarding_states')
            ->andReturn('{}');

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
            [
                'success' => false,
                'message' => 'Something went wrong! Integrated payment has not been rollback',
            ],
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
            ->shouldReceive('getDefault')
            ->with('onboarding_states')
            ->andReturn('{}');

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
            [
                'success' => true,
                'message' => 'Integrated payment has been successfully rollback',
            ],
            $this->action->disableIntegratedAction()
        );
    }
}
