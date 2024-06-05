<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group classes
 * @group configuration_classes
 *
 * @dontrunTestsInSeparateProcesses
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
        $this->assertSame('', $this->classe->getType($key));
    }

    public function testWhenGivenKeyDoesNotExistsInAllowedConfiguration()
    {
        $key = 'config_key';
        $this->assertSame('', $this->classe->getType($key));
    }

    public function testWhenDefaultConfigurationIsReturned()
    {
        $key = 'enable';
        $this->assertSame(
            'integer',
            $this->classe->getType($key)
        );
    }
}
