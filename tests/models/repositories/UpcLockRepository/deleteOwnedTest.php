<?php

namespace PayPlug\tests\models\repositories\UpcLockRepository;

/**
 * @group unit
 * @group repository
 * @group upc_lock_repository
 */
class deleteOwnedTest extends BaseUpcLockRepository
{
    public function testReturnsFalseWhenLockKeyIsInvalid()
    {
        $this->assertFalse($this->repository->deleteOwned('', time() + 30));
        $this->assertFalse($this->repository->deleteOwned(null, time() + 30));
    }

    public function testReturnsFalseWhenExpectedExpiresAtIsInvalid()
    {
        $this->assertFalse($this->repository->deleteOwned('cart_1', 0));
        $this->assertFalse($this->repository->deleteOwned('cart_1', 'not-an-int'));
    }

    public function testReturnsFalseWhenEntityObjectCannotBeResolved()
    {
        $this->repository->shouldReceive('getEntityObject')->andReturn(null);

        $this->assertFalse($this->repository->deleteOwned('cart_1', time() + 30));
    }

    /**
     * Confirms the DELETE is built with BOTH conditions (lock_key AND the exact expires_at this
     * caller owns) - the atomicity fix under test: a lock_key match alone (the previous
     * getBy()+deleteBy() behavior) would delete a new owner's row if the key had since been
     * stolen; requiring both at the SQL level means that can no longer happen.
     */
    public function testBuildsADeleteRestrictedToBothLockKeyAndExpectedExpiresAt()
    {
        $this->repository->shouldReceive('getEntityObject')->andReturn($this->entity);
        $this->repository->shouldReceive('delete')->once()->andReturn($this->repository);
        $this->repository->shouldReceive('from')->once()->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('lock_key = "cart_1"')->andReturn($this->repository);
        $this->repository->shouldReceive('where')->once()->with('expires_at = 12345')->andReturn($this->repository);
        $this->repository->shouldReceive('build')->once()->andReturn(true);

        $this->assertTrue($this->repository->deleteOwned('cart_1', 12345));
    }

    public function testReturnsFalseWhenTheDeleteQueryFails()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertFalse($this->repository->deleteOwned('cart_1', time() + 30));
    }
}
