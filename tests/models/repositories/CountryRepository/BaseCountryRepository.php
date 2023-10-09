<?php

namespace PayPlug\tests\models\repositories\CountryRepository;

use PayPlug\src\models\repositories\CountryRepository;
use PayPlug\tests\models\repositories\BaseRepository;

class BaseCountryRepository extends BaseRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository = \Mockery::mock(CountryRepository::class, ['prefix', $this->dependencies])->makePartial();
    }
}
