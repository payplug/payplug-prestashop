<?php

namespace PayPlug\tests\models\classes\Merchant;

/**
 * @group unit
 * @group merchant
 */
class getHostedFieldsIdentifierTest extends BaseMerchant
{
    public function testResolvesTheIdentifierForTheCurrentCurrency()
    {
        $currency = (object) ['iso_code' => 'USD'];
        $context = (object) ['currency' => $currency];
        $this->plugin->shouldReceive('getContext')->andReturn($context);
        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn(json_encode(['usd' => 'ident_usd', 'gbp' => 'ident_gbp']));

        $this->assertSame('ident_usd', $this->class->getHostedFieldsIdentifier());
    }

    public function testReturnsEmptyStringWhenNoneConfiguredForCurrency()
    {
        $currency = (object) ['iso_code' => 'USD'];
        $context = (object) ['currency' => $currency];
        $this->plugin->shouldReceive('getContext')->andReturn($context);
        $this->configuration_class->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn(json_encode(['gbp' => 'ident_gbp']));

        $this->assertSame('', $this->class->getHostedFieldsIdentifier());
    }
}
