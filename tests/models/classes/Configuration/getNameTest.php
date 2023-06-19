<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 */
class getNameTest extends BaseConfiguration
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
        $this->assertSame('', $this->classe->getName($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame('', $this->classe->getName($key));
    }

    public function testWhenNameConfigurationIsReturned()
    {
        $key = 'standard';
        $this->assertSame(
            'PAYPLUG_STANDARD',
            $this->classe->getName($key)
        );
    }
}
