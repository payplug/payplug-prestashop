<?php

namespace PayPlug\tests\actions\CardAction;

/**
 * @group unit
 * @group action
 * @group card_action
 *
 * @runTestsInSeparateProcesses
 */
class renderListTest extends BaseCardAction
{
    public function setUp()
    {
        parent::setUp();
        $this->configuration_class->shouldReceive('getValue')
            ->with('sandbox_mode')
            ->andReturn(42);
        $this->configuration_class->shouldReceive('getValue')
            ->with('company_id')
            ->andReturn(42);
    }

    /**
     * @dataProvider invalidBoolFormatDataProvider
     *
     * @param mixed $active_only
     */
    public function testWhenGivenArgumentIsInvalidBooleanFormat($active_only)
    {
        $this->assertSame(
            [],
            $this->action->renderList($active_only)
        );
    }

    public function testWhenNoCardsFound()
    {
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => [],
        ]);

        $this->assertSame(
            [],
            $this->action->renderList()
        );
    }

    public function testWhenCardsFoundAndExpiredAndOnlyActivedAreExpected()
    {
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => [
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
        $this->card_validator->shouldReceive([
            'isValidExpiration' => [
                'result' => false,
            ],
        ]);

        $this->assertSame(
            [],
            $this->action->renderList(true)
        );
    }

    public function testWhenCardsFoundAndExpired()
    {
        $card = [
            'id_payplug_card' => 1,
            'id_customer' => 42,
            'id_company' => 4242,
            'is_sandbox' => false,
            'id_card' => 'card_azerty12345',
            'last4' => '4242',
            'exp_month' => '12',
            'exp_year' => '2024',
            'brand' => 'CB',
            'country' => 'GB',
            'metadata' => 'N;',
        ];
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => [$card],
        ]);
        $this->card_validator->shouldReceive([
            'isValidExpiration' => [
                'result' => false,
            ],
        ]);

        $this->assertSame(
            [],
            $this->action->renderList()
        );
    }

    public function testWhenCardsFoundAndNotExpired()
    {
        $card = [
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
        ];
        $this->card_repository->shouldReceive([
            'getAllByCustomer' => [$card],
        ]);
        $this->card_validator->shouldReceive([
            'isValidExpiration' => [
                'result' => true,
            ],
        ]);

        $card['expiry_date'] = '12 / 30';
        unset($card['is_sandbox'], $card['id_card']);

        $this->assertSame(
            [$card],
            $this->action->renderList()
        );
    }
}
