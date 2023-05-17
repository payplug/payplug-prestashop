<?php

namespace PayPlug\tests\models\repositories\PaymentRepository;

use PayPlug\src\models\repositories\PaymentRepository;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group repository
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
class removeTest extends TestCase
{
    protected function setUp()
    {
        $this->repository = \Mockery::mock(PaymentRepository::class)->makePartial();
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
     * @param mixed $id_cart
     */
    public function testWhenGivenIdCountryIsInvalidIntegerFormat($id_cart)
    {
        $this->assertFalse($this->repository->remove($id_cart));
    }

    public function testWhenNoResultIsGivenByTheQuery()
    {
        $id_cart = 42;
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => false,
            ]);

        $this->assertFalse($this->repository->remove($id_cart));
    }

    public function testWhenExpectedResultIsGivenByTheQuery()
    {
        $id_cart = 42;
        $this
            ->repository
            ->shouldReceive([
                'delete' => $this->repository,
                'from' => $this->repository,
                'where' => $this->repository,
                'build' => true,
            ]);

        $this->assertTrue($this->repository->remove($id_cart));
    }
}
