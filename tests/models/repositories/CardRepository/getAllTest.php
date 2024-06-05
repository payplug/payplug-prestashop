<?php

namespace PayPlug\tests\models\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class getAllTest extends BaseCardRepository
{
    public function testWhenNoCardsFind()
    {
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getAll()
        );
    }

    public function testWhenCardsAreFound()
    {
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
            'build' => [$card],
        ]);

        $this->assertSame(
            [$card],
            $this->repository->getAll()
        );
    }
}
