<?php

namespace PayPlug\tests\actions\CardAction;

/**
 * @group unit
 * @group action
 * @group card_action
 */
class uninstallActionTest extends BaseCardAction
{
    public function testWhenNoCardsFound()
    {
        $this->card_repository->shouldReceive([
            'getAll' => [],
        ]);

        $this->assertTrue(
            $this->action->uninstallAction()
        );
    }

    public function testWhenCardsCantBeDeleted()
    {
        $this->card_repository->shouldReceive([
            'getAll' => [
                [
                    'id_payplug_card' => 1,
                    'id_customer' => 42,
                    'id_company' => 4242,
                    'is_sandbox' => false,
                    'id_card' => 'card_azerty12345',
                    'last4' => '4242',
                    'exp_month' => '12',
                    'exp_year' => '2030',
                    'brand' => 'CB',
                    'country' => 'GB',
                    'metadata' => 'N;',
                ],
            ],
        ]);
        $this->action->shouldReceive([
            'deleteAction' => false,
        ]);
        $this->assertFalse(
            $this->action->uninstallAction()
        );
    }

    public function testWhenCardsCanIsDeleted()
    {
        $this->card_repository->shouldReceive([
            'getAll' => [
                [
                    'id_payplug_card' => 1,
                    'id_customer' => 42,
                    'id_company' => 4242,
                    'is_sandbox' => false,
                    'id_card' => 'card_azerty12345',
                    'last4' => '4242',
                    'exp_month' => '12',
                    'exp_year' => '2030',
                    'brand' => 'CB',
                    'country' => 'GB',
                    'metadata' => 'N;',
                ],
            ],
        ]);
        $this->action->shouldReceive([
            'deleteAction' => true,
        ]);
        $this->assertTrue(
            $this->action->uninstallAction()
        );
    }
}
