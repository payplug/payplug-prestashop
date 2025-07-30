<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;

/**
 * @group unit
 * @group class
 * @group apirest_classe
 */
class dispatchTest extends BaseApiRest
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $action
     */
    public function testWhenGivenActionWithInvalidStringFormat($action)
    {
        $this->assertSame(
            [],
            $this->class->dispatch($action)
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
        $this->configuration_action->shouldReceive([
            'loginAction' => $response,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 'file_get_contents',
        ]);

        $action = 'login';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
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
        $this->configuration_action->shouldReceive([
            'logoutAction' => $response,
        ]);
        $action = 'logout';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
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
        $this->configuration_action->shouldReceive([
            'saveAction' => $response,
        ]);

        $this->tools_adapter->shouldReceive([
            'tool' => 'file_get_contents',
        ]);

        $action = 'save';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
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
        $this->configuration_action->shouldReceive([
            'renderConfiguration' => $response,
        ]);
        $action = 'init';
        $this->assertSame(
            $response,
            $this->class->dispatch($action)
        );
    }
}
