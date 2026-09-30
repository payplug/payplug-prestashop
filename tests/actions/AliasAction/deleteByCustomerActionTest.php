<?php

namespace PayPlug\tests\actions\AliasAction;

/**
 * @group unit
 * @group action
 * @group alias_action
 */
class deleteByCustomerActionTest extends BaseAliasAction
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_customer
     */
    public function testReturnsFalseForAnInvalidCustomerId($id_customer)
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->never();

        $this->assertFalse($this->action->deleteByCustomerAction($id_customer));
    }

    public function invalidIntegerFormatDataProvider()
    {
        return [[0], [null], ['7'], [true]];
    }

    public function testReturnsTrueWhenTheCustomerHasNoAlias()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([]);
        $this->alias_repository->shouldReceive('deleteBy')->never();

        $this->assertTrue($this->action->deleteByCustomerAction(7));
    }

    public function testDeletesEveryAliasOfTheCustomer()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([
            $this->aliasRow(),
            $this->aliasRow(['id_payplug_alias' => '2', 'alias_id' => 'alias_def']),
        ]);
        $this->alias_repository->shouldReceive('deleteBy')->once()->with('id_customer', 7)->andReturn(true);

        $this->assertTrue($this->action->deleteByCustomerAction(7));
    }

    public function testReturnsFalseWhenTheDeletionFails()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([$this->aliasRow()]);
        $this->alias_repository->shouldReceive('deleteBy')->once()->with('id_customer', 7)->andReturn(false);

        $this->assertFalse($this->action->deleteByCustomerAction(7));
    }
}
