<?php

namespace PayPlug\tests\models\repositories\AliasRepository;

/**
 * @group unit
 * @group repository
 * @group alias_repository
 */
class saveIfAbsentTest extends BaseAliasRepository
{
    /**
     * @dataProvider invalidArgumentsProvider
     *
     * @param mixed $id_customer
     * @param mixed $alias_id
     * @param mixed $currency
     * @param mixed $identifier
     */
    public function testReturnsFalseWithoutWritingWhenARequiredArgumentIsInvalid($id_customer, $alias_id, $currency, $identifier)
    {
        $this->repository->shouldReceive('getBy')->never();
        $this->repository->shouldReceive('createEntity')->never();

        $this->assertFalse($this->repository->saveIfAbsent($id_customer, $alias_id, $currency, $identifier, 'visa'));
    }

    public function invalidArgumentsProvider()
    {
        return [
            'zero customer' => [0, 'alias_abc', 'usd', 'ident_usd'],
            'empty alias id' => [7, '', 'usd', 'ident_usd'],
            'empty currency' => [7, 'alias_abc', '', 'ident_usd'],
            'empty identifier' => [7, 'alias_abc', 'usd', ''],
        ];
    }

    public function testDoesNothingWhenTheAliasAlreadyExists()
    {
        $this->repository->shouldReceive('getBy')->once()->with('alias_id', 'alias_abc')->andReturn([
            'id_payplug_alias' => '1',
            'alias_id' => 'alias_abc',
        ]);
        $this->repository->shouldReceive('createEntity')->never();

        $this->assertTrue($this->repository->saveIfAbsent(7, 'alias_abc', 'usd', 'ident_usd', 'visa', '0001', '12', '2029'));
    }

    public function testCreatesTheAliasWithEveryKnownField()
    {
        $this->repository->shouldReceive('getBy')->once()->with('alias_id', 'alias_abc')->andReturn([]);
        $this->repository->shouldReceive('createEntity')
            ->once()
            ->withArgs(function ($fields) {
                return 7 === $fields['id_customer']
                    && 'alias_abc' === $fields['alias_id']
                    && 'usd' === $fields['currency']
                    && 'ident_usd' === $fields['identifier']
                    && 'visa' === $fields['brand']
                    && '0001' === $fields['last4']
                    && '12' === $fields['exp_month']
                    && '2029' === $fields['exp_year']
                    && 1 === preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $fields['date_add']);
            })
            ->andReturn(3);

        $this->assertTrue($this->repository->saveIfAbsent(7, 'alias_abc', 'usd', 'ident_usd', 'visa', '0001', '12', '2029'));
    }

    public function testOmitsTheUnknownCardDetails()
    {
        $this->repository->shouldReceive('getBy')->once()->with('alias_id', 'alias_abc')->andReturn([]);
        $this->repository->shouldReceive('createEntity')
            ->once()
            ->withArgs(function ($fields) {
                return !array_key_exists('last4', $fields)
                    && !array_key_exists('exp_month', $fields)
                    && !array_key_exists('exp_year', $fields);
            })
            ->andReturn(3);

        $this->assertTrue($this->repository->saveIfAbsent(7, 'alias_abc', 'usd', 'ident_usd', 'visa', null, null, null));
    }

    public function testReturnsFalseWhenTheInsertFails()
    {
        $this->repository->shouldReceive('getBy')->once()->with('alias_id', 'alias_abc')->andReturn([]);
        $this->repository->shouldReceive('createEntity')->once()->andReturn(0);

        $this->assertFalse($this->repository->saveIfAbsent(7, 'alias_abc', 'usd', 'ident_usd', 'visa'));
    }
}
