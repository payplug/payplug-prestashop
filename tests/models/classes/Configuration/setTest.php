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
        $value = 'value';
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $value = 'value';
        $this->classe->configuration = [
            'feature' => [],
        ];
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenConfigurationKeyDoesNotReturnType()
    {
        $key = 'config_key';
        $value = 'value';
        $this->classe->configuration = [
            'feature' => [
                'name' => 'FEATURE',
                'defaultValue' => 0,
                'setConf' => 1,
            ],
        ];
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenGivenValueIsNotAIntegerAsExpected()
    {
        $key = 'standard';
        $value = 'value';
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenGivenValueIsNotAStringAsExpected()
    {
        $key = 'company_iso';
        $value = 42;
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenConfigurationCanNotBeUpdated()
    {
        $key = 'standard';
        $value = 1;
        $this->configuration->shouldReceive([
            'updateValue' => false,
        ]);
        $this->assertFalse($this->classe->set($key, $value));
    }

    public function testWhenConfigurationIsUpdated()
    {
        $key = 'standard';
        $value = 1;
        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);
        $this->classe->shouldReceive([
            'getName' => 'standard',
        ]);
        $this->assertTrue($this->classe->set($key, $value));
    }
}
