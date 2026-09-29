<?php

namespace PayPlug\tests\models\repositories\AliasRepository;

/**
 * @group unit
 * @group repository
 * @group alias_repository
 */
class findUsableTest extends BaseAliasRepository
{
    /**
     * @dataProvider invalidArgumentsProvider
     *
     * @param mixed $alias_id
     * @param mixed $id_customer
     * @param mixed $currency
     * @param mixed $identifier
     */
    public function testReturnsNullWithoutQueryingWhenAnArgumentIsInvalid($alias_id, $id_customer, $currency, $identifier)
    {
        $this->repository->shouldReceive('select')->never();

        $this->assertNull($this->repository->findUsable($alias_id, $id_customer, $currency, $identifier));
    }

    public function invalidArgumentsProvider()
    {
        return [
            'empty alias id' => ['', 7, 'usd', 'ident_usd'],
            'non string alias id' => [null, 7, 'usd', 'ident_usd'],
            'zero customer' => ['alias_abc', 0, 'usd', 'ident_usd'],
            'non int customer' => ['alias_abc', '7', 'usd', 'ident_usd'],
            'empty currency' => ['alias_abc', 7, '', 'ident_usd'],
            'empty identifier' => ['alias_abc', 7, 'usd', ''],
        ];
    }

    public function testFiltersOnAliasCustomerCurrencyAndIdentifier()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
        ]);
        $this->repository->shouldReceive('where')->once()->with('`alias_id` = "alias_abc"')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`id_customer` = 7')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`currency` = "usd"')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`identifier` = "ident_usd"')->andReturn($this->repository);
        $this->repository->shouldReceive('build')->once()->with('unique_row')->andReturn([]);

        $this->assertNull($this->repository->findUsable('alias_abc', 7, 'usd', 'ident_usd'));
    }

    /**
     * @dataProvider mismatchingScopeProvider
     *
     * @param int $id_customer
     * @param string $currency
     * @param string $identifier
     */
    public function testReturnsNullForAnotherCustomerCurrencyOrIdentifier($id_customer, $currency, $identifier)
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertNull($this->repository->findUsable('alias_abc', $id_customer, $currency, $identifier));
    }

    public function mismatchingScopeProvider()
    {
        return [
            'other customer' => [99, 'usd', 'ident_usd'],
            'other currency' => [7, 'gbp', 'ident_usd'],
            'other identifier' => [7, 'usd', 'ident_other'],
        ];
    }

    public function testReturnsTheRowWhenFound()
    {
        $row = [
            'id_payplug_alias' => '1',
            'id_customer' => '7',
            'alias_id' => 'alias_abc',
            'currency' => 'usd',
            'identifier' => 'ident_usd',
            'brand' => 'visa',
        ];
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $row,
        ]);

        $this->assertSame($row, $this->repository->findUsable('alias_abc', 7, 'usd', 'ident_usd'));
    }
}
