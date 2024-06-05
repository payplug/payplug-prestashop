<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getTest extends BaseConfiguration
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key
     */
    public function testWhenGivenKeyIsInvalidStringFormat($key)
    {
        $this->assertSame([], $this->classe->get($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame([], $this->classe->get($key));
    }

    public function testWhenConfigurationIsReturned()
    {
        $key = 'enable';
        $this->assertSame(
            [
                'type' => 'integer',
                'name' => 'ENABLE',
                'defaultValue' => 0,
                'setConf' => 1,
            ],
            $this->classe->get($key)
        );
    }
}
