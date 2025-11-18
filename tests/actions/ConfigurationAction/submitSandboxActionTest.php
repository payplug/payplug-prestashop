<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class submitSandboxActionTest extends BaseConfigurationAction
{
    public function invalidObjectFormatDataProvider()
    {
        yield [42];

        yield [['key' => 'value']];

        yield [true];

        yield [null];

        yield ['lorem ipsum'];
    }

    public function invaliPasswordFormatDataProvider()
    {
        yield [null];

        yield [['key' => 'value']];

        yield [true];

        yield [''];

        yield ['7bJ'];

        yield ['P2wc10BrYGtILSUuEHU6nZzxastNZmdwLHT12fLAAuy91CwQomiUBjB1NG3RR2hr5UO6KuaWk'];
    }

    /**
     * @description  test submitsandboxAction when datas is not valid
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $datas
     */
    public function testWhenGivenDataIsInvalidFormat($datas)
    {
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'An error has occurred',
                ],
            ],
            $this->action->submitSandboxAction($datas)
        );
    }

    /**
     * @description  test submitSandboxAction when password is not valid or empty
     * @dataProvider invaliPasswordFormatDataProvider
     *
     * @param mixed $password
     */
    public function testWhenPasswordIsEmptyORInvalid($password)
    {
        $datas = new \stdClass();
        $datas->payplug_password = $password;
        $datas->payplug_email = 'payplug.login@payplug.com';

        $account = \Mockery::mock('Account');
        $account->shouldReceive([
            'isPassword' => ['result' => false],
        ]);
        $this->dependencies->shouldReceive([
            'getValidators' => [
                'account' => $account,
            ],
        ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'logged.inactive.modal.error',
                ],
            ],
            $this->action->submitSandboxAction($datas)
        );
    }

    /**
     * @description test submitsandboxAction when login is false
     */
    public function testWhenSubmitLoginReturnFalse()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';

        $account = \Mockery::mock('Account');
        $account->shouldReceive([
            'isPassword' => [
                'result' => true,
            ],
        ]);
        $this->dependencies->shouldReceive([
            'getValidators' => [
                'account' => $account,
            ],
        ]);

        $this->api_service->shouldReceive([
            'login' => false,
        ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'logged.inactive.modal.error',
                ],
            ],
            $this->action->submitSandboxAction($datas)
        );
    }

    /**
     * description test submitSandboxAction when merchant is onboarded.
     */
    public function testWhenUserIsOnboarded()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';
        $datas->env = true;

        $account = \Mockery::mock('Account');
        $account->shouldReceive([
            'isPassword' => [
                'result' => true,
            ],
        ]);
        $this->dependencies->shouldReceive([
            'getValidators' => [
                'account' => $account,
            ],
        ]);

        $this->api_service->shouldReceive([
            'login' => true,
        ]);

        $this->action->shouldReceive([
            'checkPermissionAction' => [
                'success' => true,
                'data' => [],
            ],
        ]);
        $this->assertSame(
            [
                'success' => true,
                'data' => [
                    'still_inactive' => false,
                    'message' => '',
                ],
            ],
            $this->action->submitSandboxAction($datas)
        );
    }

    /**
     * description test submitSandboxAction when merchant is not onboarded.
     */
    public function testWhenUserIsntOnboarded()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';
        $datas->env = true;

        $account = \Mockery::mock('Account');
        $account->shouldReceive([
            'isPassword' => [
                'result' => true,
            ],
        ]);
        $this->dependencies->shouldReceive([
            'getValidators' => [
                'account' => $account,
            ],
        ]);

        $this->api_service->shouldReceive([
            'login' => true,
        ]);

        $this->action->shouldReceive([
            'checkPermissionAction' => [
                'success' => false,
                'data' => [],
            ],
        ]);
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'still_inactive' => true,
                    'message' => '',
                ],
            ],
            $this->action->submitSandboxAction($datas)
        );
    }
}
