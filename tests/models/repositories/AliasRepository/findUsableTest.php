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
     * @param mixed $id_payplug_alias
     * @param mixed $id_customer
     * @param mixed $currency
     * @param mixed $identifier
     */
    public function testReturnsNullWithoutQueryingWhenAnArgumentIsInvalid($id_payplug_alias, $id_customer, $currency, $identifier)
    {
        $this->repository->shouldReceive('select')->never();

        $this->assertNull($this->repository->findUsable($id_payplug_alias, $id_customer, $currency, $identifier));
    }

    public function invalidArgumentsProvider()
    {
        return [
            'zero alias id' => [0, 7, 'usd', 'ident_usd'],
            'negative alias id' => [-1, 7, 'usd', 'ident_usd'],
            'non int alias id' => ['1', 7, 'usd', 'ident_usd'],
            'null alias id' => [null, 7, 'usd', 'ident_usd'],
            'zero customer' => [1, 0, 'usd', 'ident_usd'],
            'non int customer' => [1, '7', 'usd', 'ident_usd'],
            'empty currency' => [1, 7, '', 'ident_usd'],
            'empty identifier' => [1, 7, 'usd', ''],
        ];
    }

    public function testFiltersOnAliasIdCustomerCurrencyAndIdentifier()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
        ]);
        $this->repository->shouldReceive('where')->once()->with('`id_payplug_alias` = 1')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`id_customer` = 7')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`currency` = "usd"')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`identifier` = "ident_usd"')->andReturn($this->repository);
        $this->repository->shouldReceive('build')->once()->with('unique_row')->andReturn([]);

        $this->assertNull($this->repository->findUsable(1, 7, 'usd', 'ident_usd'));
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
        ]);
        // The query is scoped on the given values, not on the stored alias: a row of another
        // customer, currency or identifier can't match, so the query finds nothing.
        $this->repository->shouldReceive('where')->once()->with('`id_payplug_alias` = 1')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`id_customer` = ' . $id_customer)->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`currency` = "' . $currency . '"')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('`identifier` = "' . $identifier . '"')->andReturn($this->repository);
        $this->repository->shouldReceive('build')->once()->with('unique_row')->andReturn([]);

        $this->assertNull($this->repository->findUsable(1, $id_customer, $currency, $identifier));
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

        $this->assertSame($row, $this->repository->findUsable(1, 7, 'usd', 'ident_usd'));
    }
}
