<?php

namespace PayPlug\tests\models\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
class removeTest extends BaseCardRepository
{
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
