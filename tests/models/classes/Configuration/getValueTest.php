<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @runTestsInSeparateProcesses
 */
class getValueTest extends BaseConfiguration
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key
     */
    public function testWhenGivenKeyIsInvalidStringFormat($key)
    {
        $this->assertSame(false, $this->classe->getValue($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame(false, $this->classe->getValue($key));
    }

    public function testWhenConfigurationCanNotBeReturn()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'get' => false,
        ]);
        $this->assertSame(false, $this->classe->getValue($key));
    }

    public function testWhenConfigurationIsReturn()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'get' => 1,
        ]);
        $this->classe->shouldReceive([
            'getName' => 'enable',
        ]);
        $this->assertSame(1, $this->classe->getValue($key));
    }
}
