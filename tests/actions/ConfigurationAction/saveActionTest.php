<?php

namespace PayPlug\tests\actions\ConfigurationAction;

/**
 * @group unit
 * @group action
 * @group configuration_action
 * @group configuration_action_save
 *
 * @runTestsInSeparateProcesses
 */
class saveActionTest extends BaseConfigurationAction
{
    public $configurationClass;

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
            $this->action->saveAction($datas)
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
            $this->action->saveAction($datas)
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
            $this->action->saveAction($datas)
        );
    }

    public function testWhenConfigurationCannotBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->enable_standard = 1;

        $this->configuration->shouldReceive([
            'updateValue' => false,
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => true,
            ])
        ;
        $this->dependencies->apiClass = \Mockery::mock();
        $this->dependencies->apiClass->shouldReceive([
            'getAccount' => [
                'payment_methods' => [
                    'sofort' => [
                        'enabled' => 1,
                        'allowed_countries' => [
                            '0' => 'AT',
                            '1' => 'BE',
                            '2' => 'DE',
                            '3' => 'ES',
                            '4' => 'IT',
                            '5' => 'NL',
                        ],
                        'min_amounts' => [
                            'EUR' => 100,
                        ],
                        'max_amounts' => [
                            'EUR' => 2000000,
                        ],
                    ],
                ],
            ],
        ]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'message' => 'An error has occurred while register amounts',
                ],
            ],
            $this->action->saveAction($datas)
        );
    }

    public function testWhenConfigurationCanBeUpdate()
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->payplug_standard = 1;

        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => true,
            ])
        ;
        $this->dependencies->apiClass = \Mockery::mock();
        $this->dependencies->apiClass->shouldReceive([
            'getAccount' => [
                'payment_methods' => [
                    'sofort' => [
                        'enabled' => 1,
                        'allowed_countries' => [
                            '0' => 'AT',
                            '1' => 'BE',
                            '2' => 'DE',
                            '3' => 'ES',
                            '4' => 'IT',
                            '5' => 'NL',
                        ],
                        'min_amounts' => [
                            'EUR' => 100,
                        ],
                        'max_amounts' => [
                            'EUR' => 2000000,
                        ],
                    ],
                ],
            ],
        ]);

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);

        $this->assertSame(
            true,
            $this->action->saveAction($datas)['success']
        );
    }
}
