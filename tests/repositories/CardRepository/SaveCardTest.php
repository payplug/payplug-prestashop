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
    public function atestWithInvalidParams($payment)
    {
        $this->assertFalse($this->repo->saveCard($payment));
    }

    public function atestWhenCardAlreadyExists()
    {
        $this->repositories['card']->shouldReceive([
            'exists' => true,
        ]);

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenDataBaseReturnFalse()
    {
        $this->repositories['card']->shouldReceive([
            'exists' => false,
            'set' => false,
        ]);

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function atestWhenCardIsSaved()
    {
        $this->repositories['card']->shouldReceive([
            'exists' => false,
            'set' => true,
        ]);

        $this->assertTrue($this->repo->saveCard($this->payment));
    }
}
