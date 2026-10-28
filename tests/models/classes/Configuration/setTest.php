<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
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
        $this->assertFalse($this->class->set($key, $value));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $value = 'value';
        $this->class->configurations = [
            'feature' => [],
        ];
        $this->assertFalse($this->class->set($key, $value));
    }

    public function testWhenConfigurationKeyDoesNotReturnType()
    {
        $key = 'config_key';
        $value = 'value';
        $this->class->configurations = [
            'feature' => [
                'name' => 'FEATURE',
                'defaultValue' => 0,
                'setConf' => 1,
            ],
        ];
        $this->assertFalse($this->class->set($key, $value));
    }

    public function testWhenGivenValueIsntAIntegerAsExpected()
    {
        $key = 'standard';
        $value = 'value';
        $this->assertFalse($this->class->set($key, $value));
    }

    public function testWhenGivenValueIsntAStringAsExpected()
    {
        $key = 'company_iso';
        $value = 42;
        $this->assertFalse($this->class->set($key, $value));
    }

    public function testWhenConfigurationCantBeUpdated()
    {
        $key = 'enable';
        $value = 1;
        $this->configuration->shouldReceive([
            'updateValue' => false,
        ]);
        $this->assertFalse($this->class->set($key, $value));
    }

    public function testWhenConfigurationIsUpdated()
    {
        $key = 'enable';
        $value = '1';
        $this->configuration->shouldReceive([
            'updateValue' => true,
        ]);
        $this->class->shouldReceive([
            'getName' => 'enable',
        ]);
        $this->assertTrue($this->class->set($key, $value));
    }

    public function testCurrentContextIsWrittenWithoutScope()
    {
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ENABLE', '1', null, null)
            ->andReturn(true);
        $this->assertTrue($this->class->set('enable', '1'));
    }

    public function testScopeIsPassedToTheAdapter()
    {
        $this->configuration->shouldReceive('updateValue')
            ->once()
            ->with('PAYPLUG_ENABLE', '1', 1, 2)
            ->andReturn(true);
        $this->assertTrue($this->class->set('enable', '1', 1, 2));
    }
}
