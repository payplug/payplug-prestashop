<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 * @group dev
 *
 * @runTestsInSeparateProcesses
 */
class loginActionTest extends BaseConfigurationAction
{
    public function invalidObjectFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [true];
        yield ['lorem ipsum'];
    }

    /**
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
            $this->action->loginAction($datas)
        );
    }

    public function testWhenGivenActionIsEmpty()
    {
        $datas = new \stdClass();
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'An error has occurred',
                ],
            ],
            $this->action->loginAction($datas)
        );
    }

    public function testWhenGivenActionIsInvalid()
    {
        $datas = new \stdClass();
        $datas->action = 'test';
        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'An error has occurred',
                ],
            ],
            $this->action->loginAction($datas)
        );
    }

    public function testWhenEmailIsEmpty()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = '';

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'The email you entered is invalid.',
                ],
            ],
            $this->action->loginAction($datas)
        );
    }

    public function testWhenPasswordIsEmpty()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = '';

        $account = \Mockery::mock('Account');
        $account
            ->shouldReceive([
                'isPassword' => false,
            ]);
        $this->dependencies
            ->shouldReceive([
                'getValidators' => [
                    'account' => $account,
                ],
            ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'The password you entered is invalid.',
                ],
            ],
            $this->action->loginAction($datas)
        );
    }

    public function testWhenPasswordIsNotValidPassword()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';

        $account = \Mockery::mock('Account');
        $account
            ->shouldReceive([
                'isPassword' => [
                    'result' => false,
                ],
            ]);
        $this->dependencies
            ->shouldReceive([
                'getValidators' => [
                    'account' => $account,
                ],
            ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'The password you entered is invalid.',
                ],
            ],
            $this->action->loginAction($datas)
        );
    }

    public function testWhenLoginReturnFalse()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';

        $account = \Mockery::mock('Account');
        $account
            ->shouldReceive([
                'isPassword' => [
                    'result' => true,
                ],
            ]);
        $this->dependencies
            ->shouldReceive([
                'getValidators' => [
                    'account' => $account,
                ],
            ]);

        $this->dependencies->apiClass = \Mockery::mock();
        $this->dependencies->apiClass->shouldReceive([
            'login' => false,
        ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'The email and/or password was not correct.',
                ],
            ],
            $this->action->loginAction($datas)
        );
    }

    public function testWhenLoginReturnTrue()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';

        $account = \Mockery::mock('Account');
        $account
            ->shouldReceive([
                'isPassword' => [
                    'result' => true,
                ],
            ]);
        $this->dependencies
            ->shouldReceive([
                'getValidators' => [
                    'account' => $account,
                ],
            ]);

        $this->dependencies->apiClass = \Mockery::mock();
        $this->dependencies->apiClass->shouldReceive([
            'login' => true,
        ]);

        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);

        $this->assertSame(
            true,
            $this->action->loginAction($datas)['success']
        );
    }
}
