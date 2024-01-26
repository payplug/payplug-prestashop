<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @runTestsInSeparateProcesses
 */
class setTest extends BaseConfiguration
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key
     */
    public function testWhenGivenKeyIsInvalidStringFormat($key)
    {
        $value = 'value';
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $value = 'value';
        $this->classe->configurations = [
            'feature' => [],
        ];
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenConfigurationKeyDoesNotReturnType()
    {
        $key = 'config_key';
        $value = 'value';
        $this->classe->configurations = [
            'feature' => [
                'name' => 'FEATURE',
                'defaultValue' => 0,
                'setConf' => 1,
            ],
        ];
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenGivenValueIsntAIntegerAsExpected()
    {
        $key = 'standard';
        $value = 'value';
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenGivenValueIsntAStringAsExpected()
    {
        $key = 'company_iso';
        $value = 42;
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenConfigurationCantBeUpdated()
    {
        $key = 'enable';
        $value = 1;
        $this->configuration->shouldReceive([
            'updateValue' => false,
        ]);
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenConfigurationIsUpdated()
    {
        $key = 'enable';
        $value = 1;
        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);
        $this->classe->shouldReceive([
            'getName' => 'enable',
        ]);
        $this->assertTrue($this->classe->set($key, $value));
    }
}
