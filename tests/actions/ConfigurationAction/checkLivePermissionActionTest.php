<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 *
 * @runTestsInSeparateProcesses
 */
class checkLivePermissionActionTest
{
    public function invalidObjectFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [true];
        yield [null];
        yield ['lorem ipsum'];
    }

    /**
     * @description  test checkLivePermissionAction when datas is not valid
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
            $this->action->checkLivePermissionAction($datas)
        );
    }

    /**
     * description test checkLivePermissionAction when merchant is onboarded
     */
    public function testWhenUserIsOnboarded()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';
        $datas->env = true;

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
            $this->action->checkLivePermissionAction($datas)
        );
    }

    /**
     * description test checkLivePermissionAction when merchant is not onboarded
     */
    public function testWhenUserIsNotOnboarded()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_login';
        $datas->payplug_email = 'payplug.login@payplug.com';
        $datas->payplug_password = 'password';
        $datas->env = true;

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
            $this->action->checkLivePermissionAction($datas)
        );
    }
}
