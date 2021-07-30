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

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class InsertPaymentTableTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $cart = CartMock::get();

        $this->paymentDetails = [
            'cartId' => $cart->id,
            'authorizedAt' => true,
            'isPaid' => true,
            'paymentId' => 'pay_5SnSQwmPty5UgKbUgrZQuT',
            'paymentMethod' => 'standard',
            'paymentUrl' => 'payment_return_url',
            'paymentReturnUrl' => 'payment_return_url',
            'cart' => CartMock::get()
        ];
    }

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function invalidDataProvider()
    {
        yield [null, 'paymentDetails: null'];
        yield [[(string)'I am a string!'], 'paymentDetails: ["I am a string!"]'];
        yield [['paymentId' => null], 'paymentDetails: {"paymentId":null}'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider invalidDataProvider
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithInvalidData($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage
            ]);

        $this->assertSame(
            $this->repo->insertPaymentTable($parameter),
            $logMessage
        );
    }

    public function testInsertPaymentTableWithValidData()
    {
        $this->repo
            ->shouldReceive([
                'getHashedCart' => true
            ]);

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => true
            ]);

        $this->assertTrue($this->repo->insertPaymentTable($this->paymentDetails)['result']);

        $this->repo
            ->shouldReceive([
                $this->repo->insertPaymentTable($this->paymentDetails)['response'],
                'Insert data in DB successfully'
            ]);
    }

    public function testInsertPaymentTableWithInvalidData()
    {
        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => false
            ]);

        $this->assertFalse($this->repo->insertPaymentTable($this->paymentDetails)['result']);

        $this->repo
            ->shouldReceive([
                $this->repo->insertPaymentTable($this->paymentDetails)['response'],
                '[insertPaymentCart] Unable to flush DB (build method)'
            ]);
    }

    public function testInsertPaymentTableThrowException()
    {
        $this->repo
            ->shouldReceive([
                'getHashedCart' => true
            ]);

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
            ]);

        $this->query
            ->shouldReceive('build')
            ->andThrow('Payplug\Exception\ConfigurationNotSetException', 'Bad Request', 400)
        ;

        $this->assertFalse($this->repo->insertPaymentTable($this->paymentDetails)['result']);
        $this->assertSame(
            $this->repo->insertPaymentTable($this->paymentDetails)['response'],
            '[insertPaymentTable] Error: Bad Request'
        );
    }

    public function testInsertPaymentTableWithInvalidHashedCart()
    {
        $this->paymentDetails['cart'] = null;

        $this->assertFalse(
            $this->repo->insertPaymentTable($this->paymentDetails)['result']
        );

        $this->assertSame(
            $this->repo->insertPaymentTable($this->paymentDetails)['response'],
            '[insertPaymentTable] Problem with the getHashedCart method.'
        );
    }
}