<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 *
 * @runTestsInSeparateProcesses
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
        $this->assertSame([], $this->class->get($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame([], $this->class->get($key));
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
            $this->class->get($key)
        );
    }
}
