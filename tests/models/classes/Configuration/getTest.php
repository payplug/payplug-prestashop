<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @runTestsInSeparateProcesses
 */
class getTest extends BaseConfiguration
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
