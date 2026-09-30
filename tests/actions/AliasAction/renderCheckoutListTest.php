<?php

namespace PayPlug\tests\actions\AliasAction;

/**
 * @group unit
 * @group action
 * @group alias_action
 */
class renderCheckoutListTest extends BaseAliasAction
{
    public function testReturnsAnEmptyListForAGuest()
    {
        $this->customer = (object) ['id' => 7, 'is_guest' => 1];
        $this->alias_repository->shouldReceive('getAllByCustomer')->never();

        $this->assertSame([], $this->action->renderCheckoutList('USD', 'ident_usd'));
    }

    public function testReturnsAnEmptyListForAnAnonymousVisitor()
    {
        $this->customer = (object) ['id' => 0];
        $this->alias_repository->shouldReceive('getAllByCustomer')->never();

        $this->assertSame([], $this->action->renderCheckoutList('USD', 'ident_usd'));
    }

    public function testReturnsAnEmptyListWithoutIdentifier()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->never();

        $this->assertSame([], $this->action->renderCheckoutList('USD', ''));
    }

    public function testKeepsOnlyUnexpiredAliasesOfTheCartCurrencyAndIdentifier()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([
            $this->aliasRow(),
            $this->aliasRow(['id_payplug_alias' => '2', 'alias_id' => 'alias_gbp', 'currency' => 'gbp']),
            $this->aliasRow(['id_payplug_alias' => '3', 'alias_id' => 'alias_other_ident', 'identifier' => 'ident_old']),
            $this->aliasRow(['id_payplug_alias' => '4', 'alias_id' => 'alias_expired', 'exp_year' => '2020']),
        ]);

        $result = $this->action->renderCheckoutList('USD', 'ident_usd');

        $this->assertCount(1, $result);
        $this->assertSame(1, $result[0]['id_payplug_alias']);
        $this->assertTrue($result[0]['usable']);
    }
}
