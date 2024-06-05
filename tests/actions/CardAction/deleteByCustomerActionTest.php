<?php

namespace PayPlug\tests\actions\CardAction;

/**
 * @group unit
 * @group action
 * @group card_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class deleteByCustomerActionTest extends BaseCardAction
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $customer_id
     */
    public function testWhenGivenCustomerIdIsInvalidIntegerFormat($customer_id)
    {
        $this->assertFalse($this->action->deleteByCustomerAction($customer_id));
    }

    public function testWhenNoCardsFound()
    {
        $customer_id = 42;

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(42);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_id')
            ->andReturn(42);

        $this->card_repository
            ->shouldReceive([
                'getAllByCustomer' => [],
            ]);

        $this->assertFalse($this->action->deleteByCustomerAction($customer_id));
    }

    public function testWhenRetrievedCardCantBeDeleted()
    {
        $customer_id = 42;

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(42);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_id')
            ->andReturn(42);

        $this->card_repository
            ->shouldReceive([
                'getAllByCustomer' => [
                    [
                        'id_payplug_card' => 'card_azery1',
                    ],
                    [
                        'id_payplug_card' => 'card_azery2',
                    ],
                ],
            ]);

        $this->action
            ->shouldReceive([
                'deleteAction' => false,
            ]);

        $this->assertFalse($this->action->deleteByCustomerAction($customer_id));
    }

    public function testWhenRetrievedCardIsDeleted()
    {
        $customer_id = 42;

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(42);

        $this->configuration_class
            ->shouldReceive('getValue')
            ->with('company_id')
            ->andReturn(42);

        $this->card_repository
            ->shouldReceive([
                'getAllByCustomer' => [
                    [
                        'id_payplug_card' => 'card_azery1',
                    ],
                    [
                        'id_payplug_card' => 'card_azery2',
                    ],
                ],
            ]);

        $this->action
            ->shouldReceive([
                'deleteAction' => true,
            ]);

        $this->assertTrue($this->action->deleteByCustomerAction($customer_id));
    }
}
