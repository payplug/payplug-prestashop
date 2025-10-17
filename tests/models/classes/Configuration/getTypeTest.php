<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 *
 * @runTestsInSeparateProcesses
 */
class getTypeTest extends BaseConfiguration
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key
     */
    public function testWhenGivenKeyIsInvalidStringFormat($key)
    {
        $this->assertSame('', $this->class->getType($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame('', $this->class->getType($key));
    }

    public function testWhenDefaultConfigurationIsReturned()
    {
        $key = 'enable';
        $this->assertSame(
            'integer',
            $this->class->getType($key)
        );
    }
}
