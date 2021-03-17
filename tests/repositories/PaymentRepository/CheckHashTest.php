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

use PayPlug\src\entities\PaymentEntity;
use PayPlug\src\repositories\PaymentRepository;
use PayPlug\tests\repositories\BaseTest;
use PayPlug\tests\mock\CartMock;

/**
 * @group payment_dev
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckHashTest extends BaseTest
{
    private $paymentRepository;

    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();
        parent::apiCall();

        $this->logger->shouldReceive([
            'setParams' => $this->logger,
        ]);

        $this->paymentRepository = \Mockery::mock(PaymentRepository::class, [
            $this->payplug,
            $this->cart,
            $this->logger,
            new PaymentEntity(),
            null
        ])->makePartial();


        $cart = CartMock::get();
        $cart->date_add = $cart->date_upd = null;
        $cart->id_address_delivery = (string)$cart->id_address_delivery;
        $cart->id_address_invoice = (string)$cart->id_address_invoice;

        $this->paymentDetails = [
            'cartId' => $cart->id,
            'cart' => $cart,
            'paymentMethod' => 'payment_method'
        ];
    }

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function checkHashParameters()
    {
        // Test if (!$paymentDetails)
        yield [null, 'paymentDetails: null'];

        // Test if (!is_array($paymentDetails))
        yield ['I am a string!', 'paymentDetails: ["I am a string!"]'];

        // Test if (!isset($paymentDetails['cartId']))
        yield [['wrong_parameters'], 'paymentDetails: can\'t find cartId'];

        // Test if (!$paymentDetails['cartId'])
        yield [['cartId' => null], 'paymentDetails: {"cartId":null}'];

        // Test if (!$paymentDetails['cartId'])
        yield [['cartId' => false], 'paymentDetails: {"cartId":null}'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkHashParameters
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $this->paymentRepository
            ->shouldReceive([
                'paymentError' => $logMessage
            ]);

        $this->assertSame(
            $this->paymentRepository->checkHash($parameter),
            $logMessage
        );
    }

    public function testWithSameHash()
    {
        // same hash expected
        $hash = hash('sha256', $this->paymentDetails['paymentMethod'] . json_encode($this->paymentDetails['cart']));

        $this->paymentRepository
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => $hash,
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ]
            ]);

        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => 'OK. Comparaison result: Same hash and same payment method.'
            ],
            $this->paymentRepository->checkHash($this->paymentDetails)
        );
    }

    public function testWithCreatePaymentThrowingException()
    {
        $expected_error = [
            ['name' => 'paymentDetails', 'value' => $this->paymentDetails],
            '[checkHash -> createPayment] Error: An error occurred'
        ];

        $this->paymentRepository
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => 'different_hash',
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ],
                'paymentError' => $expected_error
            ]);

        $this->paymentRepository
            ->shouldReceive('createPayment')
            ->andThrow('Payplug\Exception\ConfigurationNotSetException', 'An error occurred', 500);

        $this->assertSame(
            $expected_error,
            $this->paymentRepository->checkHash($this->paymentDetails)
        );
    }

    public function testWithValidCreatePayment()
    {
        $this->paymentRepository
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => 'different_hash',
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ],
                'createPayment' => [
                    'result' => true,
                    'paymentDetails' => $this->paymentDetails,
                    'response' => '[createPayment] Payment successfully created'
                ]
            ]);

        $this->assertSame(
            'QUOI METTRE ICI',
            $this->paymentRepository->checkHash($this->paymentDetails)
        );
    }

    public function testWithInvalidCreatePayment()
    {
        $error_message = 'An error occurred in payment creation';

        $expected_error = [
            ['name' => 'paymentDetails', 'value' => $this->paymentDetails],
            $error_message
        ];

        $this->paymentRepository
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => 'different_hash',
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ],
                'createPayment' => [
                    'result' => false,
                    'response' => $error_message
                ],
                'paymentError' => $expected_error
            ]);

        $this->assertSame(
            $expected_error,
            $this->paymentRepository->checkHash($this->paymentDetails)
        );
    }

    public function testWithValidUpdatePaymentTable()
    {
    }
}
