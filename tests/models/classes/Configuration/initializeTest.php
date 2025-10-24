<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_classe
 */
class initializeTest extends BaseConfiguration
{
    public function testWhenNoConfigurationKeysFound()
    {
        $this->class->configurations = [];
        $this->assertFalse($this->class->initialize());
    }

    public function testWhenConfigurationCantBeDeleted()
    {
        $this->class->configurations = [
            'feature' => [
                'setConf' => true,
                'defaultValue' => 'lorem',
            ],
        ];
        $this->class->shouldReceive([
            'set' => false,
        ]);
        $this->assertFalse($this->class->initialize());
    }

    public function testWhenConfigurationIsSetted()
    {
        $this->class->configurations = [
            'feature' => [
                'setConf' => true,
                'defaultValue' => 'lorem',
            ],
        ];
        $this->class->shouldReceive([
            'set' => true,
        ]);
        $this->assertTrue($this->class->initialize());
    }
}
