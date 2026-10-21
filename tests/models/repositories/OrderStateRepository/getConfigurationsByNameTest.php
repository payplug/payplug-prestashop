<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_repository
 */
class getConfigurationsByNameTest extends BaseOrderStateRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $name
     */
    public function testWhenGivenNameIsInvalidStringFormat($name)
    {
        $this->assertSame([], $this->repository->getConfigurationsByName($name));
    }

    public function testWhenFailedRetrievingInDatabase()
    {
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'where' => $this->repository,
            'build' => false,
        ]);

        $this->assertSame([], $this->repository->getConfigurationsByName('PAYPLUG_ORDER_STATE_PARTIAL_REFUND'));
    }

    public function testWhenSucceedRetrievingInDatabase()
    {
        $rows = [
            ['id_shop_group' => null, 'id_shop' => null, 'value' => '31'],
            ['id_shop_group' => '1', 'id_shop' => '2', 'value' => '32'],
        ];
        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'build' => $rows,
        ]);
        $this->repository->shouldReceive('where')
            ->once()
            ->with('c.name = \'PAYPLUG_ORDER_STATE_PARTIAL_REFUND\'')
            ->andReturn($this->repository);

        $this->assertSame($rows, $this->repository->getConfigurationsByName('PAYPLUG_ORDER_STATE_PARTIAL_REFUND'));
    }
}
