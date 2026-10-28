<?php

namespace PayPlug\tests\models\classes\Configuration;

/**
 * @group unit
 * @group class
 * @group configuration_class
 */
class getScopedValueTest extends BaseConfiguration
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $key
     */
    public function testWhenGivenKeyIsInvalidStringFormat($key)
    {
        $this->assertFalse($this->class->getScopedValue($key, 1, 2));
    }

    public function testModuleKeyIsReadForTheScope()
    {
        $this->configuration->shouldReceive('get')
            ->once()
            ->with('PAYPLUG_ENABLE', 1, 2)
            ->andReturn('0');
        $this->assertSame('0', $this->class->getScopedValue('enable', 1, 2));
    }

    public function testNativeKeyIsReadForTheScope()
    {
        $this->configuration->shouldReceive('get')
            ->once()
            ->with('PS_SHOP_NAME', 3, 0)
            ->andReturn('Group shop');
        $this->assertSame('Group shop', $this->class->getScopedValue('PS_SHOP_NAME', 3, 0));
    }

    public function testGlobalScopeIsReadByDefault()
    {
        $this->configuration->shouldReceive('get')
            ->once()
            ->with('PAYPLUG_ENABLE', 0, 0)
            ->andReturn('1');
        $this->assertSame('1', $this->class->getScopedValue('enable'));
    }
}
