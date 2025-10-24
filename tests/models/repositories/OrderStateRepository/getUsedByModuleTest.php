<?php

namespace PayPlug\tests\models\repositories\OrderStateRepository;

/**
 * @group unit
 * @group repository
 * @group order_state_repository
 */
class getUsedByModuleTest extends BaseOrderStateRepository
{
    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $module_name
     */
    public function testWhenGivenModuleNameIsInvalidIntegerFormat($module_name)
    {
        $this->assertSame(
            [],
            $this->repository->getUsedByModule($module_name)
        );
    }

    public function testWhenNoOrderFoundForGivenModuleName()
    {
        $module_name = 'payplug';

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'leftJoin' => $this->repository,
            'where' => $this->repository,
            'build' => [],
        ]);

        $this->assertSame(
            [],
            $this->repository->getUsedByModule($module_name)
        );
    }

    public function testWhenOrderAreFoundForGivenModuleName()
    {
        $module_name = 'payplug';
        $orders = [
            [
                'id_order' => 1,
                'id_lang' => 1,
                'id_customer' => 3,
                'id_cart' => 42,
                'id_currency' => 1,
                'id_address_delivery' => 14,
                'id_address_invoice' => 14,
                'current_state' => 2,
                'id_order_state' => 56,
            ],
        ];

        $this->repository->shouldReceive([
            'select' => $this->repository,
            'fields' => $this->repository,
            'from' => $this->repository,
            'leftJoin' => $this->repository,
            'where' => $this->repository,
            'build' => $orders,
        ]);

        $this->assertSame(
            $orders,
            $this->repository->getUsedByModule($module_name)
        );
    }
}
