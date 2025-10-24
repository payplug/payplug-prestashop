<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_classe
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
            $this->class->delete($key)
        );
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $value = 'value';
        $this->class->configurations = [
            'feature' => [],
        ];
        $this->assertFalse($this->class->delete($key, $value));
    }

    public function testWhenConfigurationCantBeDeleted()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'deleteByName' => false,
        ]);
        $this->assertFalse($this->class->delete($key));
    }

    public function testWhenConfigurationIsDeleted()
    {
        $key = 'enable';
        $this->configuration->shouldReceive([
            'deleteByName' => true,
        ]);
        $this->class->shouldReceive([
            'getName' => 'enable',
        ]);
        $this->assertTrue($this->class->delete($key));
    }
}
