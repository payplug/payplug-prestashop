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
        $this->assertFalse(
            $this->classe->delete($key)
        );
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $value = 'value';
        $this->classe->configuration = [
            'feature' => [],
        ];
        $this->assertFalse($this->classe->delete($key, $value));
    }

    public function testWhenConfigurationCanNotBeDeleted()
    {
        $key = 'standard';
        $this->configuration->shouldReceive([
            'deleteByName' => false,
        ]);
        $this->assertFalse($this->classe->delete($key));
    }

    public function testWhenConfigurationIsDeleted()
    {
        $key = 'standard';
        $this->configuration->shouldReceive([
            'deleteByName' => true,
        ]);
        $this->classe->shouldReceive([
            'getName' => 'standard',
        ]);
        $this->assertTrue($this->classe->delete($key));
    }
}
