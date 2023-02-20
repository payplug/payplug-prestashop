<?php

namespace PayPlug\tests\models\repositories\CountryRepository;

use PayPlug\src\models\repositories\CountryRepository;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group repository
 * @group country_repository
 *
 * @runTestsInSeparateProcesses
 */
class getIsoCodeByCountryTest extends TestCase
{
    protected $accountValidator;

    protected function setUp()
    {
        $this->repository = \Mockery::mock(CountryRepository::class)->makePartial();
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [0];
        yield [['key' => 'value']];
        yield [true];
        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_country
     */
    public function testWhenGivenIdCountryIsInvalidIntegerFormat($id_country)
    {
        $this->assertSame('', $this->repository->getIsoCodeByCountry($id_country));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $id_country = 42;
        $this
            ->repository
            ->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => [],
            ]);

        $this->assertSame('', $this->repository->getIsoCodeByCountry($id_country));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $id_country = 42;
        $this
            ->repository
            ->shouldReceive([
                'select' => $this->repository,
                'fields' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => 'FR',
            ]);

        $this->assertSame('FR', $this->repository->getIsoCodeByCountry($id_country));
    }
}
