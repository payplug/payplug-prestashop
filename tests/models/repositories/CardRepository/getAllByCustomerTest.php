<?php

namespace PayPlug\tests\models\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
class getAllByCustomerTest extends BaseCardRepository
{
    protected function setUp()
    {
        parent::setUp();
        $this->repository
            ->shouldReceive('escape')
            ->andReturnUsing(function ($key) { return $key; });
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_customer
     */
    public function testWhenGivenIdCustomerIsInvalidIntegerFormat($id_customer)
    {
        $id_company = 4242;
        $is_sandbox = false;
        $this->assertSame(
            [],
            $this->repository->getAllByCustomer($id_customer, $id_company, $is_sandbox)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_company
     */
    public function testWhenGivenIdCompanyIsInvalidIntegerFormat($id_company)
    {
        $id_customer = 4242;
        $is_sandbox = false;
        $this->assertSame(
            [],
            $this->repository->getAllByCustomer($id_customer, $id_company, $is_sandbox)
        );
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_sandbox
     */
    public function testWhenGivenIsSandboxIsInvalidBooleanFormat($is_sandbox)
    {
        $id_customer = 4242;
        $id_company = 4242;
        $this->assertSame(
            [],
            $this->repository->getAllByCustomer($id_customer, $id_company, $is_sandbox)
        );
    }

    public function testWhenNoCardIsReturnForGivenCustomer()
    {
        $id_customer = 4242;
        $id_company = 4242;
        $is_sandbox = true;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getAllByCustomer($id_customer, $id_company, $is_sandbox)
        );
    }

    public function testWhenCardIsReturnForGivenCustomer()
    {
        $id_customer = 4242;
        $id_company = 4242;
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
            'build' => [$card],
        ]);

        $this->assertSame(
            [$card],
            $this->repository->getAllByCustomer($id_customer, $id_company, $is_sandbox)
        );
    }
}
