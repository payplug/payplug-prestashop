<?php

namespace PayPlug\tests\models\repositories\CacheRepository;

/**
 * @group unit
 * @group repository
 * @group entity_repository
 * @group cache_repository
 */
class deleteAllExceptKeyPrefixTest extends BaseCacheRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $prefix
     */
    public function testWhenGivenPrefixIsInvalidStringFormat($prefix)
    {
        $this->repository->shouldNotReceive('delete');
        $this->assertFalse($this->repository->deleteAllExceptKeyPrefix($prefix));
    }

    public function testWhenNoEntityNameDefined()
    {
        $this->repository->entity_name = '';
        $this->repository->shouldNotReceive('delete');
        $this->assertFalse($this->repository->deleteAllExceptKeyPrefix('upc_token:'));
    }

    public function testWhenEntityObjectCantBeGot()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => null,
        ]);
        $this->repository->shouldNotReceive('delete');
        $this->assertFalse($this->repository->deleteAllExceptKeyPrefix('upc_token:'));
    }

    public function testWhenRowsCantBeDeleted()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);
        $this->assertFalse($this->repository->deleteAllExceptKeyPrefix('upc_token:'));
    }

    public function testWhenRowsAreDeleted()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'build' => true,
        ]);
        $this->repository->shouldNotReceive('truncate');
        $this->repository->shouldReceive('where')
            ->once()
            ->with('`cache_key` NOT LIKE "upc\\_token:%"')
            ->andReturn($this->repository);

        $this->assertTrue($this->repository->deleteAllExceptKeyPrefix('upc_token:'));
    }

    public function testLikeWildcardsInPrefixAreMatchedLiterally()
    {
        $this->repository->shouldReceive([
            'getEntityObject' => $this->entity,
            'delete' => $this->repository,
            'from' => $this->repository,
            'build' => true,
        ]);
        $this->repository->shouldReceive('where')
            ->once()
            ->with('`cache_key` NOT LIKE "a\\%b\\_c\\\\d%"')
            ->andReturn($this->repository);

        $this->assertTrue($this->repository->deleteAllExceptKeyPrefix('a%b_c\\d'));
    }
}
