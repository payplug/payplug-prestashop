<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 *
 * @runTestsInSeparateProcesses
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
        $this->assertSame('', $this->class->getName($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame('', $this->class->getName($key));
    }

    public function testWhenNameConfigurationIsReturned()
    {
        $key = 'enable';
        $this->assertSame(
            'PAYPLUG_ENABLE',
            $this->class->getName($key)
        );
    }
}
