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
class removeTest extends BaseRepository
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
            false,
            $this->repository->remove($id_payplug_card)
        );
    }

    public function testWhenTheCardCannotBeDelete()
    {
        $id_payplug_card = 4242;

        $this->repository->shouldReceive([
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame(
            false,
            $this->repository->remove($id_payplug_card)
        );
    }

    public function testWhenTheCardIsDeleted()
    {
        $id_payplug_card = 4242;

        $this->repository->shouldReceive([
            'delete' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => true,
        ]);

        $this->assertSame(
            true,
            $this->repository->remove($id_payplug_card)
        );
    }
}
