<?php

namespace PayPlug\tests\repositories\OneyRepository;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @dontrunTestsInSeparateProcesses
 */
final class IsValidOneyAmountTest extends BaseOneyRepository
{
    protected $limits;

    public function setUp()
    {
        parent::setUp();

        $this->limits = [
            'min' => 10000,
            'max' => 30000,
        ];

        $this->repo
            ->shouldReceive([
                'getOneyPriceLimit' => $this->limits,
            ])
        ;
    }

    public function testWithTooLowAmount()
    {
        $amount = 99;

        $this->dependencies->amountCurrencyClass = \Mockery::mock('alias:PayPlug\classes\AmountCurrencyClass');
        $this->dependencies->amountCurrencyClass
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $to_cents = false) {
                if ($to_cents) {
                    return (float) ($amount / 100);
                }
                $amount = (float) ($amount * 1000);
                $amount = (float) ($amount / 10);

                return (int) ($this->tools->tool('ps_round', $amount));
            })
        ;

        $this->amount_helper
            ->shouldReceive([
                'convertAmount' => $amount,
            ]);

        $this->validators['payment']->shouldReceive([
                'isAmount' => [
                    'result' => false,
                    'message' => '',
                ],
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => $this->validators,
            ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.isValidOneyAmount.unvalid',
            ],
            $this->repo->isValidOneyAmount($amount)
        );
    }

    public function testWithTooHightAmount()
    {
        $amount = 3001;

        $this->dependencies->amountCurrencyClass = \Mockery::mock('alias:PayPlug\classes\AmountCurrencyClass');
        $this->dependencies->amountCurrencyClass
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $to_cents = false) {
                if ($to_cents) {
                    return (float) ($amount / 100);
                }
                $amount = (float) ($amount * 1000);
                $amount = (float) ($amount / 10);

                return (int) ($this->tools->tool('ps_round', $amount));
            })
        ;

        $this->amount_helper
            ->shouldReceive([
                'convertAmount' => $amount,
            ]);

        $this->validators['payment']->shouldReceive([
                'isAmount' => [
                    'result' => false,
                    'message' => '',
                ],
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => $this->validators,
            ]);

        $this->assertSame(
            [
                'result' => false,
                'error' => 'oney.isValidOneyAmount.unvalid',
            ],
            $this->repo->isValidOneyAmount($amount)
        );
    }

    /**
     * @group testmee
     */
    public function testWithValidAmount()
    {
        $amount = 150;

        $this->dependencies->amountCurrencyClass = \Mockery::mock('alias:PayPlug\classes\AmountCurrencyClass');
        $this->dependencies->amountCurrencyClass
            ->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $to_cents = false) {
                if ($to_cents) {
                    return (float) ($amount / 100);
                }
                $amount = (float) ($amount * 1000);
                $amount = (float) ($amount / 10);

                return (int) ($this->tools->tool('ps_round', $amount));
            })
        ;

        $this->amount_helper
            ->shouldReceive([
                'convertAmount' => $amount,
            ]);

        $this->validators['payment']->shouldReceive([
                'isAmount' => [
                    'result' => true,
                    'code' => '',
                ],
            ]);

        $this->dependencies
            ->shouldReceive([
                'getPlugin' => $this->plugin,
                'getValidators' => $this->validators,
            ]);

        $this->assertSame(
            [
                'result' => true,
                'error' => false,
            ],
            $this->repo->isValidOneyAmount($amount)
        );
    }
}
