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

use PHPUnit\Framework\TestCase;

/**
 * @group repository
 * @group oney
 * @group oney_repository
 */
final class IsValidOneyAmountTest extends TestCase
{
    protected $minAmount;
    protected $maxAmount;
    protected $amount;
    protected $limits;

    public function setUp()
    {
        $this->minAmount = 10000;
        $this->maxAmount = 300000;
        $this->amount = 37499;
        $this->limits = [
            'min' => $this->minAmount,
            'max' => $this->maxAmount,
        ];
    }

    public function testLimits()
    {
        $this->assertSame(
            ['min'=>10000,'max'=>300000],
            $this->limits
        );
    }

    public function testLimitsIsAnArray()
    {
        $this->assertTrue(
            is_array($this->limits)
        );
    }

    public function testLimitMin()
    {
        $this->assertSame(
            10000,
            $this->limits['min']
        );
    }

    public function testLimitMinIsInt()
    {
        $this->assertTrue(
            is_int($this->limits['min'])
        );
    }

    public function testLimitMax()
    {
        $this->assertSame(
            300000,
            $this->limits['max']
        );
    }

    public function testLimitMaxIsInt()
    {
        $this->assertTrue(
            is_int($this->limits['max'])
        );
    }

    public function testAmount()
    {
        $this->assertSame(
            37499,
            $this->amount
        );
    }

    public function testAmountIsInt()
    {
        $this->assertTrue(
            is_int($this->amount)
        );
    }

    public function testIsMinAmountValid()
    {
        $condition = $this->amount >= $this->minAmount;
        $this->assertTrue($condition);
    }

    public function testIsMaxAmountValid()
    {
        $condition = $this->amount <= $this->maxAmount;
        $this->assertTrue($condition);
    }
}
