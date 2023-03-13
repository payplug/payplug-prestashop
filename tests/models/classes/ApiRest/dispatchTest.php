<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 *
 * @runTestsInSeparateProcesses
 */
class dispatchTest extends BaseApiRest
{
    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
        yield [null];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $action
     */
    public function testWhenGivenActionWithInvalidStringFormat($action)
    {
        $this->assertSame(
            [],
            $this->classe->dispatch($action)
        );
    }

    public function testWhenGivenActionIsLogin()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action
            ->shouldReceive([
                'loginAction' => $response,
            ]);

        $this->tools
            ->shouldReceive([
               'tool' => 'file_get_contents',
            ]);

        $action = 'login';
        $this->assertSame(
            $response,
            $this->classe->dispatch($action)
        );
    }

    public function testWhenGivenActionIsLogout()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action
            ->shouldReceive([
                'logoutAction' => $response,
            ]);
        $action = 'logout';
        $this->assertSame(
            $response,
            $this->classe->dispatch($action)
        );
    }

    public function testWhenGivenActionIsSave()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action
            ->shouldReceive([
                'saveAction' => $response,
            ]);

        $this->tools
            ->shouldReceive([
               'tool' => 'file_get_contents',
            ]);

        $action = 'save';
        $this->assertSame(
            $response,
            $this->classe->dispatch($action)
        );
    }

    public function testWhenGivenActionIsInit()
    {
        $response = [
            'success' => true,
            'data' => [
                'payplug_wooc_settings' => [],
                'settings' => [],
                'header' => [],
                'login' => [],
                'logged' => [],
                'payment_methods' => [],
                'payment_paylater' => [],
                'status' => [],
                'footer' => [],
            ],
        ];
        $this->configuration_action
            ->shouldReceive([
                'renderConfiguration' => $response,
            ]);
        $action = 'init';
        $this->assertSame(
            $response,
            $this->classe->dispatch($action)
        );
    }
}
