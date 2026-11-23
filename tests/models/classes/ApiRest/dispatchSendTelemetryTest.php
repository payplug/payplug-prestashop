<?php

namespace PayPlug\tests\models\classes\ApiRest;

/**
 * @group unit
 * @group class
 * @group apirest_class
 */
class dispatchSendTelemetryTest extends BaseApiRest
{
    private $calls;
    private $stored;
    private $telemetry_action;

    public function setUp(): void
    {
        parent::setUp();

        $this->calls = [];
        $this->stored = [
            'payment_methods' => '{"standard":true,"scalapay":true}',
            'sandbox_mode' => '0',
            'scalapay_merchant_token' => '',
        ];
        $this->configuration_class->shouldReceive('getValue')->andReturnUsing(function ($key) {
            return array_key_exists($key, $this->stored) ? $this->stored[$key] : false;
        });

        $this->telemetry_action = \Mockery::mock('MerchantTelemetryAction');
        $this->telemetry_action->shouldReceive('sendAction')->with('save')->andReturnUsing(function () {
            $this->calls[] = 'telemetry';

            return true;
        })->byDefault();
        $this->api_service->shouldReceive('getAccount')->withNoArgs()->andReturnUsing(function () {
            $this->calls[] = 'account';

            return ['is_live' => true];
        })->byDefault();

        $this->plugin->shouldReceive([
            'getMerchantTelemetryAction' => $this->telemetry_action,
        ]);
    }

    public function testTheAccountIsRefreshedAfterTheTelemetryWhenScalapayHasNoToken()
    {
        $this->assertSame(['success' => true], $this->class->dispatch('send_telemetry'));
        $this->assertSame(['telemetry', 'account'], $this->calls);
    }

    public function testAMissingTokenRowIsTreatedAsNoToken()
    {
        unset($this->stored['scalapay_merchant_token']);

        $this->class->dispatch('send_telemetry');

        $this->assertSame(['telemetry', 'account'], $this->calls);
    }

    public function testTheAccountIsNotRefreshedInSandboxMode()
    {
        $this->stored['sandbox_mode'] = '1';

        $this->assertSame(['success' => true], $this->class->dispatch('send_telemetry'));
        $this->assertSame(['telemetry'], $this->calls);
    }

    public function disabledScalapayProvider()
    {
        yield 'off' => ['{"standard":true,"scalapay":false}'];

        yield 'absent' => ['{"standard":true}'];

        yield 'missing row' => [false];
    }

    /**
     * @dataProvider disabledScalapayProvider
     *
     * @param mixed $payment_methods
     */
    public function testTheAccountIsNotRefreshedWhenScalapayIsNotEnabled($payment_methods)
    {
        $this->stored['payment_methods'] = $payment_methods;

        $this->class->dispatch('send_telemetry');

        $this->assertSame(['telemetry'], $this->calls);
    }

    public function testTheAccountIsNotRefreshedWhenTheTokenIsAlreadyStored()
    {
        $this->stored['scalapay_merchant_token'] = 'tok_test_123';

        $this->class->dispatch('send_telemetry');

        $this->assertSame(['telemetry'], $this->calls);
    }

    public function testAFailedRefreshDoesNotChangeTheAnswerAndIsLogged()
    {
        $this->api_service->shouldReceive('getAccount')->andThrow(new \RuntimeException('timeout'));

        $this->assertSame(['success' => true], $this->class->dispatch('send_telemetry'));
        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::pattern('/ApiRest::refreshScalapayMerchantToken - RuntimeException: timeout/'), 'error')
            ->once();
    }

    public function testAnAccountRefreshReturningNoAccountDoesNotChangeTheAnswer()
    {
        $this->api_service->shouldReceive('getAccount')->withNoArgs()->andReturnUsing(function () {
            $this->calls[] = 'account';

            return false;
        });

        $this->assertSame(['success' => true], $this->class->dispatch('send_telemetry'));
        $this->assertSame(['telemetry', 'account'], $this->calls);
        $this->logger->shouldNotHaveReceived('addLog', [\Mockery::pattern('/refreshScalapayMerchantToken/'), \Mockery::any()]);
    }

    public function testTheAccountIsStillRefreshedWhenTheTelemetryFails()
    {
        $this->telemetry_action->shouldReceive('sendAction')->with('save')->andReturnUsing(function () {
            $this->calls[] = 'telemetry';

            return false;
        });

        $this->assertSame(['success' => true], $this->class->dispatch('send_telemetry'));
        $this->assertSame(['telemetry', 'account'], $this->calls);
    }
}
