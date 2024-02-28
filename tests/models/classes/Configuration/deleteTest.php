<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @runTestsInSeparateProcesses
 */
class deleteTest extends BaseConfiguration
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key
     */
    public function testWhenGivenKeyIsInvalidStringFormat($key)
    {
        $this->assertFalse(
            $this->classe->delete($key)
        );
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $value = 'value';
        $this->classe->configurations = [
            'feature' => [],
        ];
        $this->assertFalse($this->classe->delete($key, $value));
    }

    public function testWhenConfigurationCantBeDeleted()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'deleteByName' => false,
        ]);
        $this->assertFalse($this->classe->delete($key));
    }

    public function testWhenConfigurationIsDeleted()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'deleteByName' => true,
        ]);
        $this->classe->shouldReceive([
            'getName' => 'enable',
        ]);
        $this->assertTrue($this->classe->delete($key));
    }
}
