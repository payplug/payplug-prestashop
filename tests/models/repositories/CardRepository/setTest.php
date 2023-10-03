<?php

namespace PayPlug\tests\models\repositories\CardRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
class setTest extends BaseCardRepository
{
    private $card;

    protected function setUp()
    {
        parent::setUp();
        $payment = PaymentMock::getOneClick();
        $this->card = $payment->card;
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
