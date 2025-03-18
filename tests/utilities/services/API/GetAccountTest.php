<?php

namespace PayPlug\tests\utilities\services\API;

/**
 * @group unit
 * @group service
 * @group api_service
 *
 * @runTestsInSeparateProcesses
 */
class GetAccountTest extends BaseApi
{
    public $api_key;
    public $sandbox;
    public $treat_account;

    public function setUp()
    {
        parent::setUp();
        $this->api_key = 'live_api_key';
        $this->sandbox = true;
        $this->treat_account = true;
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $api_key
     */
    public function testWhenGivenApiKeyIsntValidStringFormat($api_key)
    {
        $this->assertSame(
            [],
            $this->service->getAccount($api_key, $this->sandbox, $this->treat_account)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $sandbox
     */
    public function testWhenGivenSandboxIsntValidBooleanFormat($sandbox)
    {
        $this->assertSame(
            [],
            $this->service->getAccount($this->api_key, $sandbox, $this->treat_account)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $treat_account
     */
    public function testWhenGivenTreatAccountIsntValidBooleanFormat($treat_account)
    {
        $this->assertSame(
            [],
            $this->service->getAccount($this->api_key, $this->sandbox, $treat_account)
        );
    }

    public function testWhenAPiCantBeInitialize()
    {
        $this->service->shouldReceive([
            'initialize' => null,
        ]);
        $this->assertSame(
            [],
            $this->service->getAccount($this->api_key, $this->sandbox, $this->treat_account)
        );
    }

    public function testWhenExceptionIsThrownThenUserLogout()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);
        $this->authentication
            ->shouldReceive('getAccount')
            ->andThrow(new \Exception('An error occured during the process', 401));

        $configuration_action = \Mockery::mock('getConfigurationAction');
        $configuration_action->shouldReceive([
            'logoutAction' => true,
        ]);
        $this->plugin->shouldReceive([
            'getConfigurationAction' => $configuration_action,
        ]);

        $this->assertSame(
            [],
            $this->service->getAccount($this->api_key, $this->sandbox, $this->treat_account)
        );
    }

    public function testWhenNoTreatmentAsked()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $expected = [
            'key' => 'value',
        ];
        $this->authentication->shouldReceive([
            'getAccount' => [
                'httpResponse' => $expected,
            ],
        ]);

        $this->assertSame(
            $expected,
            $this->service->getAccount($this->api_key, $this->sandbox, false)
        );
    }

    public function testWhenAccountRequestCantBeTreated()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $expected = [
            'key' => 'value',
        ];
        $this->authentication->shouldReceive([
            'getAccount' => [
                'httpResponse' => $expected,
            ],
        ]);

        $this->service->shouldReceive([
            'treatAccountResponse' => [],
        ]);

        $this->assertSame(
            [],
            $this->service->getAccount($this->api_key, $this->sandbox, $this->treat_account)
        );
    }

    public function testWhenAccountRequestIsTreated()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $expected = [
            'key' => 'value',
        ];
        $this->authentication->shouldReceive([
            'getAccount' => [
                'httpResponse' => $expected,
            ],
        ]);

        $this->service->shouldReceive([
            'treatAccountResponse' => $expected,
        ]);

        $this->assertSame(
            $expected,
            $this->service->getAccount($this->api_key, $this->sandbox, $this->treat_account)
        );
    }
}
