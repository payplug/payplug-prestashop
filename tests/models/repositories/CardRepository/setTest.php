<?php

namespace PayPlug\tests\models\repositories\CardRepository;

use PayPlug\src\models\repositories\CardRepository;
use PayPlug\tests\mock\PaymentMock;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
class setTest extends TestCase
{
    protected function setUp()
    {
        $payment = PaymentMock::getOneClick();
        $this->card = $payment->card;
        $this->repository = \Mockery::mock(CardRepository::class)->makePartial();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($key) { return $key; });
    }

    public function invalidObjectFormatDataProvider()
    {
        yield ['lorem Ipsum'];
        yield [true];
        yield [42];
        yield [['key' => 'value']];
        yield [null];
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [0];
        yield [['key' => 'value']];
        yield [true];
        yield ['lorem ipsum'];
    }

    public function invalidBoolFormatDataProvider()
    {
        yield ['lorem Ipsum'];
        yield [42];
        yield [['key' => 'value']];
        yield [null];
    }

    /**
     * @dataProvider invalidObjectFormatDataProvider
     *
     * @param mixed $card
     */
    public function testWhenGivenCardIsInvalidObjectFormat($card)
    {
        $customer_id = 42;
        $company_id = 4242;
        $is_sandbox = false;
        $this->assertSame(
            false,
            $this->repository->set($card, $customer_id, $company_id, $is_sandbox)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $customer_id
     */
    public function testWhenGivenCustomerIdIsInvalidIntegerFormat($customer_id)
    {
        $company_id = 4242;
        $is_sandbox = false;
        $this->assertSame(
            false,
            $this->repository->set($this->card, $customer_id, $company_id, $is_sandbox)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $company_id
     */
    public function testWhenGivenCompanyIdIsInvalidIntegerFormat($company_id)
    {
        $customer_id = 4242;
        $is_sandbox = false;
        $this->assertSame(
            false,
            $this->repository->set($this->card, $customer_id, $company_id, $is_sandbox)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_sandbox
     */
    public function testWhenGivenIsSandboxIsInvalidBooleanFormat($is_sandbox)
    {
        $customer_id = 4242;
        $company_id = 4242;
        $this->assertSame(
            false,
            $this->repository->set($this->card, $customer_id, $company_id, $is_sandbox)
        );
    }

    public function testWhenGivenCardIsIncomplete()
    {
        $card = new \stdClass();
        $customer_id = 4242;
        $company_id = 4242;
        $is_sandbox = true;

        $this->assertSame(
            false,
            $this->repository->set($card, $customer_id, $company_id, $is_sandbox)
        );
    }

    public function testWhenTheCardCannotBeRegister()
    {
        $customer_id = 4242;
        $company_id = 4242;
        $is_sandbox = true;

        $this->repository->shouldReceive([
            'insert' => $this->repository,
            'into' => $this->repository,
            'fields' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            false,
            $this->repository->set($this->card, $customer_id, $company_id, $is_sandbox)
        );
    }

    public function testWhenTheCardIsRegistered()
    {
        $customer_id = 4242;
        $company_id = 4242;
        $is_sandbox = true;

        $this->repository->shouldReceive([
            'insert' => $this->repository,
            'into' => $this->repository,
            'fields' => $this->repository,
            'build' => true,
        ]);

        $this->assertSame(
            true,
            $this->repository->set($this->card, $customer_id, $company_id, $is_sandbox)
        );
    }
}
