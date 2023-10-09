<?php

namespace PayPlug\tests\repositories\OneyRepository;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
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
        $this->repo->isValidOneyAmount($amount);

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

        $this->assertSame(
            [
                'result' => true,
                'error' => false,
            ],
            $this->repo->isValidOneyAmount($amount)
        );
    }
}
