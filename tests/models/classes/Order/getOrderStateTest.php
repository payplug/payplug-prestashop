<?php

namespace PayPlug\tests\models\classes\Order;

/**
 * @group unit
 * @group class
 * @group order_classe
 *
 * @runTestsInSeparateProcesses
 */
class getOrderStatesTest extends BaseOrder
{
    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $is_live
     */
    public function testWhenGivenIsLiveIsInvalidBoolFormat($is_live)
    {
        $this->assertSame([], $this->class->getOrderStates($is_live));
    }

    public function testWhenOrderStateAreReturn()
    {
        $configuration_class = \Mockery::mock('ConfigurationClass');
        $configuration_class->shouldReceive('getValue')
            ->andReturnUsing(function ($str) {
                return $str;
            });
        $this->plugin->shouldReceive([
            'getConfigurationClass' => $configuration_class,
        ]);
        $this->assertSame(
            [
                'auth' => 'order_state_auth',
                'cancelled' => 'order_state_cancelled',
                'error' => 'order_state_error',
                'expired' => 'order_state_exp',
                'oney_pg' => 'order_state_oney_pg',
                'outofstock_paid' => 'PS_OS_OUTOFSTOCK_PAID',
                'outofstock_unpaid' => 'PS_OS_OUTOFSTOCK_UNPAID',
                'paid' => 'order_state_paid',
                'pending' => 'order_state_pending',
                'refund' => 'order_state_refund',
            ],
            $this->class->getOrderStates()
        );
    }
}
