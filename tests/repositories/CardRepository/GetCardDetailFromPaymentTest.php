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

namespace PayPlug\tests\repositories\CardRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 *
 * @internal
 * @coversNothing
 */
final class GetCardDetailFromPaymentTest extends BaseCardRepository
{
    private $payment;

    public function setUp()
    {
        parent::setUp();

        $this->payment = PaymentMock::getOneClick();
    }

    public function invalidDataProvider()
    {
        // invalid int $customerId
        yield [42];
        yield [null];
        yield [false];
        yield ['I am a string!'];
        yield [['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $customerId
     * @param $payplugCardId
     * @param $companyId
     * @param mixed $payment
     */
    public function testWithInvalidParams($payment)
    {
        $this->assertSame(
            [],
            $this->repo->getCardDetailFromPayment($payment)
        );
    }

    public function testWithValidResource()
    {
        $this->assertSame(
            [
                'last4' => '0001',
                'country' => 'FR',
                'exp_year' => 2030,
                'exp_month' => 9,
                'brand' => 'CB',
            ],
            $this->repo->getCardDetailFromPayment($this->payment)
        );
    }
}
