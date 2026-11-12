<?php

namespace PayPlug\tests\classes\HookClass;

/**
 * @group unit
 * @group class
 * @group hook_class
 */
class actionDeleteGDPRCustomerTest extends BaseHookClass
{
    public function testWhenCardsAndAliasesAreDeleted()
    {
        $this->card_action->shouldReceive('deleteByCustomerAction')->once()->with(42)->andReturn(true);
        $this->alias_action->shouldReceive('deleteByCustomerAction')->once()->with(42)->andReturn(true);

        $this->assertSame(\json_encode(true), $this->hook->actionDeleteGDPRCustomer(['id' => '42']));
    }

    public function testWhenCardDeletionFails()
    {
        $this->card_action->shouldReceive('deleteByCustomerAction')->with(42)->andReturn(false);
        $this->alias_action->shouldReceive('deleteByCustomerAction')->with(42)->andReturn(true);

        $this->assertSame(
            \json_encode('hook.actionDeleteGDPRCustomer.unableDelete'),
            $this->hook->actionDeleteGDPRCustomer(['id' => 42])
        );
    }

    public function testWhenAliasDeletionFails()
    {
        $this->card_action->shouldReceive('deleteByCustomerAction')->with(42)->andReturn(true);
        $this->alias_action->shouldReceive('deleteByCustomerAction')->with(42)->andReturn(false);

        $this->assertSame(
            \json_encode('hook.actionDeleteGDPRCustomer.unableDelete'),
            $this->hook->actionDeleteGDPRCustomer(['id' => 42])
        );
    }

    public function testWhenCustomerOnlyHasAliases()
    {
        // CardAction::deleteByCustomerAction() returns true when the customer has no saved card.
        $this->card_action->shouldReceive('deleteByCustomerAction')->with(42)->andReturn(true);
        $this->alias_action->shouldReceive('deleteByCustomerAction')->once()->with(42)->andReturn(true);

        $this->assertSame(\json_encode(true), $this->hook->actionDeleteGDPRCustomer(['id' => 42]));
    }
}
