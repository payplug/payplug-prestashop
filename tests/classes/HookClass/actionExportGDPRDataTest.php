<?php

namespace PayPlug\tests\classes\HookClass;

/**
 * @group unit
 * @group class
 * @group hook_class
 */
class actionExportGDPRDataTest extends BaseHookClass
{
    public function testWhenCustomerOnlyHasCards()
    {
        $this->config_class->shouldReceive('gdprCardExport')->with(42)->andReturn([
            ['#' => 1, 'brand' => 'visa', 'last4' => '4242'],
            ['#' => 2, 'brand' => 'mastercard', 'last4' => '4444'],
        ]);
        $this->alias_action->shouldReceive('gdprExportAction')->with(42)->andReturn([]);

        $this->assertSame([
            ['#' => 1, 'brand' => 'visa', 'last4' => '4242'],
            ['#' => 2, 'brand' => 'mastercard', 'last4' => '4444'],
        ], \json_decode($this->hook->actionExportGDPRData(['id' => '42']), true));
    }

    public function testWhenCustomerOnlyHasAliases()
    {
        $this->config_class->shouldReceive('gdprCardExport')->with(42)->andReturn([]);
        $this->alias_action->shouldReceive('gdprExportAction')->with(42)->andReturn([
            ['brand' => 'visa', 'last4' => '0001', 'currency' => 'USD'],
        ]);

        $this->assertSame([
            ['brand' => 'visa', 'last4' => '0001', 'currency' => 'USD', '#' => 1],
        ], \json_decode($this->hook->actionExportGDPRData(['id' => 42]), true));
    }

    public function testWhenCustomerHasCardsAndAliasesNumberingIsContinuous()
    {
        $this->config_class->shouldReceive('gdprCardExport')->with(42)->andReturn([
            ['#' => 1, 'last4' => '4242'],
            ['#' => 2, 'last4' => '4444'],
        ]);
        $this->alias_action->shouldReceive('gdprExportAction')->with(42)->andReturn([
            ['last4' => '0001'],
            ['last4' => '0002'],
        ]);

        $result = \json_decode($this->hook->actionExportGDPRData(['id' => 42]), true);

        $this->assertSame([1, 2, 3, 4], array_column($result, '#'));
        $this->assertSame(['4242', '4444', '0001', '0002'], array_column($result, 'last4'));
    }

    public function testWhenCustomerHasNothingToExport()
    {
        $this->config_class->shouldReceive('gdprCardExport')->with(42)->andReturn([]);
        $this->alias_action->shouldReceive('gdprExportAction')->with(42)->andReturn([]);

        $this->assertSame(
            \json_encode('hook.actionExportGDPRData.unableToExport'),
            $this->hook->actionExportGDPRData(['id' => 42])
        );
    }
}
