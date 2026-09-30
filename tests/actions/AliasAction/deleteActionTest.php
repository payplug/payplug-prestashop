<?php

namespace PayPlug\tests\actions\AliasAction;

/**
 * @group unit
 * @group action
 * @group alias_action
 */
class deleteActionTest extends BaseAliasAction
{
    /**
     * @dataProvider invalidArgumentsProvider
     *
     * @param mixed $id_customer
     * @param mixed $id_payplug_alias
     */
    public function testReturnsFalseForInvalidArguments($id_customer, $id_payplug_alias)
    {
        $this->alias_repository->shouldReceive('getEntity')->never();

        $this->assertFalse($this->action->deleteAction($id_customer, $id_payplug_alias));
    }

    public function invalidArgumentsProvider()
    {
        return [
            'zero customer' => [0, 5],
            'string customer' => ['7', 5],
            'zero alias' => [7, 0],
            'string alias' => [7, '5'],
        ];
    }

    public function testReturnsFalseWhenTheAliasDoesNotExist()
    {
        $this->alias_repository->shouldReceive('getEntity')->once()->with(5)->andReturn([]);
        $this->alias_repository->shouldReceive('deleteEntity')->never();

        $this->assertFalse($this->action->deleteAction(7, 5));
    }

    public function testCustomerCannotDeleteTheAliasOfAnotherCustomer()
    {
        $this->alias_repository->shouldReceive('getEntity')->once()->with(5)->andReturn($this->aliasRow([
            'id_payplug_alias' => '5',
            'id_customer' => '99',
        ]));
        $this->alias_repository->shouldReceive('deleteEntity')->never();

        $this->assertFalse($this->action->deleteAction(7, 5));
    }

    public function testDeletesTheOwnedAliasLocally()
    {
        $this->alias_repository->shouldReceive('getEntity')->once()->with(5)->andReturn($this->aliasRow([
            'id_payplug_alias' => '5',
        ]));
        $this->alias_repository->shouldReceive('deleteEntity')->once()->with(5)->andReturn(true);

        $this->assertTrue($this->action->deleteAction(7, 5));
    }
}
