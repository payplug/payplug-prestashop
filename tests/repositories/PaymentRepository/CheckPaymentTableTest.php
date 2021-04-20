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

namespace PayPlug\tests\repositories\PaymentRepository;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckPaymentTableTest extends BasePaymentRepository
{
    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function checkPaymentTableParameters()
    {
        yield [null, 'cart id: null'];
        yield [(string)'I am a string!', 'cart id: "I am a string!"'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkPaymentTableParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage
            ]);

        $this->assertSame(
            $this->repo->checkPaymentTable($parameter),
            $logMessage
        );
    }

    public function testCheckPaymentWithValidData()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => ['item1', 'item2'],
            ]);

        $this->assertSame(
            'item2',
            $this->repo->checkPaymentTable(1)
        );
    }

    public function testCheckPaymentWithInvalidData()
    {
        $this->query
            ->shouldReceive([
                'select' => $this->query,
                'fields' => $this->query,
                'from' => $this->query,
                'where' => $this->query,
                'build' => false,
            ]);

        $this->assertSame(
            false,
            $this->repo->checkPaymentTable(1)
        );
    }
}
