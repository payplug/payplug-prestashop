<?php

namespace PayPlug\tests\actions\AliasAction;

/**
 * @group unit
 * @group action
 * @group alias_action
 */
class renderListTest extends BaseAliasAction
{
    public function testReturnsAnEmptyListForAGuest()
    {
        $this->customer = (object) ['id' => 7, 'is_guest' => 1];
        $this->alias_repository->shouldReceive('getAllByCustomer')->never();

        $this->assertSame([], $this->action->renderList());
    }

    public function testReturnsAnEmptyListWhenTheCustomerHasNoAlias()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([]);

        $this->assertSame([], $this->action->renderList());
    }

    public function testExcludesExpiredAliasesAndFlagsUsability()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([
            $this->aliasRow(),
            $this->aliasRow(['id_payplug_alias' => '2', 'alias_id' => 'alias_old', 'exp_month' => '01', 'exp_year' => '2020']),
            $this->aliasRow(['id_payplug_alias' => '3', 'alias_id' => 'alias_gbp', 'currency' => 'gbp', 'identifier' => 'ident_gbp_old']),
        ]);
        $this->prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('usd')->andReturn('ident_usd');
        $this->prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('gbp')->andReturn('ident_gbp_new');

        $this->assertSame([
            [
                'id_payplug_alias' => 1,
                'currency' => 'usd',
                'brand' => 'visa',
                'last4' => '0001',
                'expiry_date' => '12 / 99',
                'usable' => true,
            ],
            [
                'id_payplug_alias' => 3,
                'currency' => 'gbp',
                'brand' => 'visa',
                'last4' => '0001',
                'expiry_date' => '12 / 99',
                'usable' => false,
            ],
        ], $this->action->renderList());
    }

    public function testFormatsTheCardDetailsAsStringsWithoutTheUnifiedApiAliasId()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([
            $this->aliasRow(['last4' => '4242', 'exp_month' => '3', 'exp_year' => '2031']),
        ]);
        $this->prestashop_adapter->shouldReceive('getHostedFieldsIdentifier')->with('usd')->andReturn('');

        $result = $this->action->renderList();

        $this->assertCount(1, $result);
        $this->assertArrayNotHasKey('alias_id', $result[0]);
        $this->assertSame('4242', $result[0]['last4']);
        $this->assertSame('03 / 31', $result[0]['expiry_date']);
        $this->assertFalse($result[0]['usable']);
    }
}
