<?php

/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\tests\repositories\OneyRepository;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class IsValidOneyAmountTest extends BaseOneyRepository
{
    protected $limits;

    public function setUp()
    {
        parent::setUp();

        $this->limits = [
            'min' => 100,
            'max' => 3000,
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
                'error' => 'The total amount of your order should be between 100,00 € and 3,000,00 € to pay with Oney.',
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
                'error' => 'The total amount of your order should be between 100,00 € and 3,000,00 € to pay with Oney.',
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
