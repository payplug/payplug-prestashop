<?php

namespace PayPlug\tests\models\repositories\CardRepository;

use PayPlug\src\models\repositories\CardRepository;
use PayPlug\tests\models\repositories\BaseRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
class getTest extends BaseRepository
{
    protected function setUp()
    {
        $this->repository = \Mockery::mock(CardRepository::class)->makePartial();
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_payplug_card
     */
    public function testWhenGivenIdPayplugCardIsInvalidObjectFormat($id_payplug_card)
    {
        $this->assertSame(
            [],
            $this->repository->get($id_payplug_card)
        );
    }

    public function testWhenTheCardCannotBeFind()
    {
        $id_payplug_card = 4242;

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->get($id_payplug_card)
        );
    }

    public function testWhenTheCardIsFound()
    {
        $id_payplug_card = 4242;
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
            $card,
            $this->repository->get($id_payplug_card)
        );
    }
}
