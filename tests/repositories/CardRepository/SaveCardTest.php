<?php

namespace PayPlug\tests\repositories\CardRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group old_repository
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

        $this->dependencies->configClass
            ->shouldReceive([
                'getValue' => 1722,
            ]);

        $this->plugin = \Mockery::mock('Plugin');
        $this->plugin
            ->shouldReceive([
                'getConfigurationClass' => $this->dependencies->configClass,
            ]);
        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
            ]);
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
        $this->card_repository->shouldReceive([
            'exists' => true,
        ]);

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenDataBaseReturnFalse()
    {
        $this->card_repository->shouldReceive([
            'exists' => false,
            'set' => false,
        ]);

        $this->assertFalse($this->repo->saveCard($this->payment));
    }

    public function testWhenCardIsSaved()
    {
        $this->card_repository->shouldReceive([
            'exists' => false,
            'set' => true,
        ]);

        $this->assertTrue($this->repo->saveCard($this->payment));
    }
}
