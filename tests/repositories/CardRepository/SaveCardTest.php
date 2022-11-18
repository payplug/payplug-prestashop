<?php

namespace PayPlug\tests\repositories\CardRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class SaveCardTest extends BaseCardRepository
{
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->payment = PaymentMock::getOneClick();
        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_SANDBOX_MODE')
            ->andReturn(false)
        ;
        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_COMPANY_ID')
            ->andReturn(4242)
        ;
    }

    public function invalidDataProvider()
    {
        yield [false];
        yield [null];
        yield [42];
        yield ['wrong parameter'];
        yield [['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $month
     * @param $year
     * @param mixed $payment
     */
    public function testWithInvalidParams($payment)
    {
        $this->assertFalse($this->repo->saveCard($payment));
    }

    public function testWhenCardAlreadyExists()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => true,
            ])
        ;

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenDataBaseThrowingException()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => false,
            ])
        ;

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
            ])
        ;

        $this->query
            ->shouldReceive('build')
            ->andThrow('Exception', 'Build method throw exception', 500)
        ;

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenDataBaseReturnFalse()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => false,
            ])
        ;

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => false,
            ])
        ;

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenCardIsSaved()
    {
        $this->repo
            ->shouldReceive([
                'checkExists' => false,
            ])
        ;

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => true,
            ])
        ;

        $this->assertTrue($this->repo->saveCard($this->payment));
    }
}
