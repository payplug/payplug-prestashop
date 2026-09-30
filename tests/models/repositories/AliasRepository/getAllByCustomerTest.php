<?php

namespace PayPlug\tests\models\repositories\AliasRepository;

/**
 * @group unit
 * @group repository
 * @group alias_repository
 */
class getAllByCustomerTest extends BaseAliasRepository
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_customer
     */
    public function testWhenGivenIdCustomerIsInvalidIntegerFormat($id_customer)
    {
        $this->assertSame([], $this->repository->getAllByCustomer($id_customer));
    }

    public function testWhenNoAliasIsFoundForGivenCustomer()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame([], $this->repository->getAllByCustomer(7));
    }

    public function testReturnsEveryAliasOfTheCustomer()
    {
        $alias = [
            'id_payplug_alias' => '1',
            'id_customer' => '7',
            'alias_id' => 'alias_abc',
            'currency' => 'usd',
            'identifier' => 'ident_usd',
            'brand' => 'visa',
            'last4' => '0001',
            'exp_month' => '12',
            'exp_year' => '2029',
        ];
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'build' => [$alias],
        ]);
        $this->repository->shouldReceive('where')->once()->with('`id_customer` = 7')->andReturn($this->repository);

        $this->assertSame([$alias], $this->repository->getAllByCustomer(7));
    }
}
