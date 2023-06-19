<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 */
class getValueTest extends BaseConfiguration
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
        $this->assertSame(false, $this->classe->getValue($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame(false, $this->classe->getValue($key));
    }

    public function testWhenConfigurationCanNotBeReturn()
    {
        $key = 'standard';
        $this->configuration->shouldReceive([
            'get' => false,
        ]);
        $this->assertSame(false, $this->classe->getValue($key));
    }

    public function testWhenConfigurationIsReturn()
    {
        $key = 'standard';
        $this->configuration->shouldReceive([
            'get' => 1,
        ]);
        $this->classe->shouldReceive([
            'getName' => 'standard',
        ]);
        $this->assertSame(1, $this->classe->getValue($key));
    }
}
