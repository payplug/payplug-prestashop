<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
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
        $this->assertSame(false, $this->class->getValue($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'native_config_key';
        $this->configuration->shouldReceive([
            'get' => 'native_config_value',
        ]);
        $this->assertSame('native_config_value', $this->class->getValue($key));
    }

    public function testWhenConfigurationCantBeReturned()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'get' => false,
        ]);
        $this->assertSame(false, $this->class->getValue($key));
    }

    public function testWhenConfigurationIsReturn()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'get' => 1,
        ]);
        $this->class->shouldReceive([
            'getName' => 'enable',
        ]);
        $this->assertSame(1, $this->class->getValue($key));
    }
}
