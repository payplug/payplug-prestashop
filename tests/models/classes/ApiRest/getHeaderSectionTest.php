<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;

/**
 * @group unit
 * @group class
 * @group apirest_class
 */
class getHeaderSectionTest extends BaseApiRest
{
    public function setUp()
    {
        parent::setUp();

        $this->configuration_class->shouldReceive('getDefault')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'enable':
                        return 1;

                    default:
                        return $key;
                }
            })
        ;

        $this->dependencies->version = 'x.xx.xx';
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenConfigurationIsInvalidArrayFormat($current_configuration)
    {
        $this->assertSame(
            [],
            $this->class->getHeaderSection($current_configuration)
        );
    }

    public function testWhenUserIsntLoggedAndModuleIsntEnable()
    {
        $current_configuration = [
            'logged' => false,
            'enable' => false,
        ];

        $expected = [
            'title' => 'payplug.getHeaderTranslations.headerTitle',
            'descriptions' => [
                'live' => [
                    'description' => 'payplug.getHeaderTranslations.headerText',
                    'plugin_version' => 'x.xx.xx',
                ],
                'sandbox' => [
                    'description' => 'payplug.getHeaderTranslations.headerText',
                    'plugin_version' => 'x.xx.xx',
                ],
            ],
            'options' => [
                'type' => 'select',
                'name' => 'payplug_enable',
                'disabled' => true,
                'options' => [
                    [
                        'value' => 1,
                        'label' => 'payplug.getHeaderTranslations.headerVisible',
                        'checked' => false,
                    ],
                    [
                        'value' => 0,
                        'label' => 'payplug.getHeaderTranslations.headerHidden',
                        'checked' => true,
                    ],
                ],
            ],
        ];
        $this->assertSame(
            $expected,
            $this->class->getHeaderSection($current_configuration)
        );
    }

    public function testWhenUserIsLoggedAndModuleEnable()
    {
        $current_configuration = [
            'logged' => true,
            'enable' => true,
        ];

        $expected = [
            'title' => 'payplug.getHeaderTranslations.headerTitle',
            'descriptions' => [
                'live' => [
                    'description' => 'payplug.getHeaderTranslations.headerText',
                    'plugin_version' => 'x.xx.xx',
                ],
                'sandbox' => [
                    'description' => 'payplug.getHeaderTranslations.headerText',
                    'plugin_version' => 'x.xx.xx',
                ],
            ],
            'options' => [
                'type' => 'select',
                'name' => 'payplug_enable',
                'disabled' => false,
                'options' => [
                    [
                        'value' => 1,
                        'label' => 'payplug.getHeaderTranslations.headerVisible',
                        'checked' => true,
                    ],
                    [
                        'value' => 0,
                        'label' => 'payplug.getHeaderTranslations.headerHidden',
                        'checked' => false,
                    ],
                ],
            ],
        ];
        $this->assertSame(
            $expected,
            $this->class->getHeaderSection($current_configuration)
        );
    }
}
