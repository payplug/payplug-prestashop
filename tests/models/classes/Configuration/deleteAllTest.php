<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 *
 * @runTestsInSeparateProcesses
 */
class deleteAllTest extends BaseConfiguration
{
    public function testWhenNoConfigurationKeysFound()
    {
        $this->class->configurations = [];
        $this->assertFalse($this->class->deleteAll());
    }

    public function testWhenConfigurationCantBeDeleted()
    {
        $this->class->configurations = [
            'feature' => [],
        ];
        $this->class->shouldReceive([
            'delete' => false,
        ]);
        $this->assertFalse($this->class->deleteAll());
    }

    public function testWhenConfigurationIsDeleted()
    {
        $this->class->configurations = [
            'feature' => [],
        ];
        $this->class->shouldReceive([
            'delete' => true,
        ]);
        $this->assertTrue($this->class->deleteAll());
    }
}
