<?php

namespace PayPlug\tests\models\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
class existsTest extends BaseCardRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $payment_id
     */
    public function testWhenGivenPaymentIdIsInvalidStringFormat($payment_id)
    {
        $company_id = 42;
        $is_sandbox = true;
        $this->assertSame(
            false,
            $this->repository->exists($payment_id, $company_id, $is_sandbox)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $company_id
     */
    public function testWhenGivenCompanyIdIsInvalidIntegerFormat($company_id)
    {
        $payment_id = 'pay_azertyui';
        $is_sandbox = true;
        $this->assertSame(
            false,
            $this->repository->exists($payment_id, $company_id, $is_sandbox)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_sandbox
     */
    public function testWhenGivenIsSandboxIsInvalidBoolFormat($is_sandbox)
    {
        $payment_id = 'pay_azertyui';
        $company_id = 42;
        $this->assertSame(
            false,
            $this->repository->exists($payment_id, $company_id, $is_sandbox)
        );
    }

    public function testWhenTheCardDoesNotExist()
    {
        $payment_id = 'pay_azertyui';
        $company_id = 42;
        $is_sandbox = true;
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            false,
            $this->repository->exists($payment_id, $company_id, $is_sandbox)
        );
    }

    public function testWhenTheCardExists()
    {
        $payment_id = 'pay_azertyui';
        $company_id = 42;
        $is_sandbox = true;
        $card = [
            'id_payplug_card' => '2',
            'id_customer' => '3',
            'id_company' => '156786',
            'is_sandbox' => '1',
            'id_card' => 'card_5aQdhfj6H7cDi4VuzSbcNj',
            'last4' => '4242',
            'exp_month' => '12',
            'exp_year' => '2023',
            'brand' => 'Visa',
            'country' => 'GB',
            'metadata' => 'N;',
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => $card,
        ]);

        $this->assertSame(
            true,
            $this->repository->exists($payment_id, $company_id, $is_sandbox)
        );
    }
}
