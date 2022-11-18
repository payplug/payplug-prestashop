<?php

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
final class CheckHashTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $cart = CartMock::get();
        $cart->date_add = $cart->date_upd = null;
        $cart->id_address_delivery = (string) $cart->id_address_delivery;
        $cart->id_address_invoice = (string) $cart->id_address_invoice;

        $this->paymentDetails = [
            'cartId' => $cart->id,
            'cart' => $cart,
            'paymentMethod' => 'payment_method',
            'forceHash' => false,
        ];
    }

    public function InvalidDataProvider()
    {
        yield [null, 'paymentDetails: null'];
        yield ['I am a string!', 'paymentDetails: ["I am a string!"]'];
        yield [['wrong_parameters'], 'paymentDetails: can\'t find cartId'];
        yield [['cartId' => null], 'paymentDetails: {"cartId":null}'];
        yield [['cartId' => false], 'paymentDetails: {"cartId":null}'];
    }

    /**
     * @dataProvider InvalidDataProvider
     *
     * @param mixed $parameter
     * @param mixed $logMessage
     */
    public function testMethodWithInvalidData($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->checkHash($parameter),
            $logMessage
        );
    }

    public function testWithSameHash()
    {
        $tempPaymentDetails = [
            'paymentTab' => mt_rand(),
            'paymentId' => mt_rand(),
            'paymentUrl' => 'url',
            'paymentReturnUrl' => 'url',
            'authorizedAt' => 'date',
            'isPaid' => true,
        ];

        $this->paymentDetails = array_merge($this->paymentDetails, $tempPaymentDetails);

        // same hash expected
        $hash = hash('sha256', $this->paymentDetails['paymentMethod'] . json_encode($this->paymentDetails['cart']));

        $this->query
            ->shouldReceive([
                'update' => $this->query,
                'table' => $this->query,
                'set' => $this->query,
                'where' => $this->query,
                'build' => true,
            ])
        ;

        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => $hash,
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ],
                'createPayment' => [
                    'result' => true,
                    'paymentDetails' => $this->paymentDetails,
                    'response' => '[createPayment] Payment successfully created',
                ],
                'getHashedCart' => 'b0a30e26e83b2a',
            ])
        ;

        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => 'Payment created and updated successfully',
            ],
            $this->repo->checkHash($this->paymentDetails)
        );
    }

    public function testWithInvalidCreatePayment()
    {
        $error_message = 'An error occurred in payment creation';

        $expected_error = [
            ['name' => 'paymentDetails', 'value' => $this->paymentDetails],
            $error_message,
        ];

        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => 'different_hash',
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ],
                'createPayment' => [
                    'result' => false,
                    'response' => $error_message,
                ],
                'returnPaymentError' => $expected_error,
                'getHashedCart' => 'b0a30e26e83b2a',
            ])
        ;

        $this->assertSame(
            $expected_error,
            $this->repo->checkHash($this->paymentDetails)
        );
    }

    public function testWithInvalidUpdatePayment()
    {
        $error_message = 'An error occurred in payment update';

        $expected_error = [
            ['name' => 'paymentDetails', 'value' => $this->paymentDetails],
            $error_message,
        ];

        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => 'different_hash',
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ],
                'createPayment' => [
                    'result' => true,
                    'paymentDetails' => $this->paymentDetails,
                    'response' => '[createPayment] Payment successfully created',
                ],
                'updatePaymentTable' => [
                    'result' => false,
                    'response' => $error_message,
                    'paymentDetails' => $this->paymentDetails,
                ],
                'returnPaymentError' => $expected_error,
                'getHashedCart' => 'b0a30e26e83b2a',
            ])
        ;

        $this->assertSame(
            $expected_error,
            $this->repo->checkHash($this->paymentDetails)
        );
    }

    public function testWithValidMethod()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'cart_hash' => 'different_hash',
                    'payment_method' => $this->paymentDetails['paymentMethod'],
                ],
                'createPayment' => [
                    'result' => true,
                    'paymentDetails' => $this->paymentDetails,
                    'response' => '[createPayment] Payment successfully created',
                ],
                'updatePaymentTable' => [
                    'result' => true,
                    'response' => 'Success message',
                    'paymentDetails' => $this->paymentDetails,
                ],
                'getHashedCart' => 'b0a30e26e83b2a',
            ])
        ;

        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => 'Payment created and updated successfully',
            ],
            $this->repo->checkHash($this->paymentDetails)
        );
    }
}
