<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 */
class getTypeTest extends BaseConfiguration
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
        $this->assertSame('', $this->classe->getType($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame('', $this->classe->getType($key));
    }

    public function testWhenDefaultConfigurationIsReturned()
    {
        $key = 'standard';
        $this->assertSame(
            'integer',
            $this->classe->getType($key)
        );
    }
}
