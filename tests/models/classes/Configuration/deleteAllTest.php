<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @runTestsInSeparateProcesses
 */
class deleteAllTest extends BaseConfiguration
{
    public function testWhenNoConfigurationKeysFound()
    {
        $this->classe->configurations = [];
        $this->assertFalse($this->classe->deleteAll());
    }

    public function testWhenConfigurationCantBeDeleted()
    {
        $this->classe->configurations = [
            'feature' => [],
        ];
        $this->classe
            ->shouldReceive([
                'delete' => false,
            ]);
        $this->assertFalse($this->classe->deleteAll());
    }

    public function testWhenConfigurationIsDeleted()
    {
        $this->classe->configurations = [
            'feature' => [],
        ];
        $this->classe
            ->shouldReceive([
                'delete' => true,
            ]);
        $this->assertTrue($this->classe->deleteAll());
    }
}
