<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @runTestsInSeparateProcesses
 */
class initializeTest extends BaseConfiguration
{
    public function testWhenNoConfigurationKeysFound()
    {
        $this->classe->configurations = [];
        $this->assertFalse($this->classe->initialize());
    }

    public function testWhenConfigurationCantBeDeleted()
    {
        $this->classe->configurations = [
            'feature' => [
                'setConf' => true,
                'defaultValue' => 'lorem',
            ],
        ];
        $this->classe
            ->shouldReceive([
                'set' => false,
            ]);
        $this->assertFalse($this->classe->initialize());
    }

    public function testWhenConfigurationIsSetted()
    {
        $this->classe->configurations = [
            'feature' => [
                'setConf' => true,
                'defaultValue' => 'lorem',
            ],
        ];
        $this->classe
            ->shouldReceive([
                'set' => true,
            ]);
        $this->assertTrue($this->classe->initialize());
    }
}
