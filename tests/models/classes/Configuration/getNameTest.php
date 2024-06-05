<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @dontrunTestsInSeparateProcesses
 */
class getNameTest extends BaseConfiguration
{
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
        $key = 'enable';
        $this->assertSame(
            'PAYPLUG_ENABLE',
            $this->classe->getName($key)
        );
    }
}
