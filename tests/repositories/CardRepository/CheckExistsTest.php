<?php

namespace PayPlug\tests\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckExistsTest extends BaseCardRepository
{
    private $paymentId;
    private $companyId;

    public function setUp()
    {
        parent::setUp();

        $this->paymentId = 'pay_id';
        $this->companyId = 42;
    }

    public function invalidDataProvider()
    {
        // invalid string $paymentId
        yield [false, 42];
        yield [null, 42];
        yield [42, 42];
        yield [['key' => 'value'], 42];

        // invalid int $companyId
        yield ['pay_id', false];
        yield ['pay_id', null];
        yield ['pay_id', 'wrong parameter'];
        yield ['pay_id', ['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $month
     * @param $year
     * @param mixed $paymentId
     * @param mixed $companyId
     */
    public function testWithInvalidParams($paymentId, $companyId)
    {
        $this->assertFalse($this->repo->checkExists($paymentId, $companyId));
    }

    public function testWhenDataBaseThrowingException()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
            ])
        ;

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500)
        ;

        $this->assertFalse($this->repo->checkExists($this->paymentId, $this->companyId));
    }

    public function testWhenDataBaseReturnFalse()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false,
            ])
        ;

        $this->assertFalse($this->repo->checkExists($this->paymentId, $this->companyId));
    }

    public function testWhenACardExists()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => true,
            ])
        ;

        $this->assertTrue($this->repo->checkExists($this->paymentId, $this->companyId));
    }
}
