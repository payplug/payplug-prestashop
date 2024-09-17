<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 *
 * @runTestsInSeparateProcesses
 */
class getDefaultTest extends BaseConfiguration
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key
     */
    public function testWhenGivenKeyIsInvalidStringFormat($key)
    {
        $this->assertSame(false, $this->class->getDefault($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame(false, $this->class->getDefault($key));
    }

    public function testWhenDefaultConfigurationIsReturned()
    {
        $key = 'enable';
        $this->assertSame(
            0,
            (int) $this->class->getDefault($key)
        );
    }
}
