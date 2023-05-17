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
    private $hash;
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $cart = CartMock::get();
        $cart->date_add = $cart->date_upd = null;
        $cart->id_address_delivery = (string) $cart->id_address_delivery;
        $cart->id_address_invoice = (string) $cart->id_address_invoice;

        $this->hash = '1234567890azertyuiop1234567890azertyuiop1234567890azertyuiop1234';
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
            ]);
        $this->assertSame(
            $this->repo->checkHash($parameter),
            $logMessage
        );
    }

    public function testWhenNoPaymentFound()
    {
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => '[checkHash] No payment found for given cart id',
            ],
            $this->repo->checkHash($this->paymentDetails)
        );
    }

    public function testWhenPaymentIsCached()
    {
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'payment_method' => 'standard',
                'cart_hash' => $this->hash,
            ],
        ]);
        $this->repo->shouldReceive([
            'getHashedCart' => $this->hash,
        ]);
        $this->validators['payment']->shouldReceive([
            'isCachedPayment' => [
                'result' => true,
            ],
        ]);
        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => 'OK. Comparaison result: Same hash and same payment method.',
            ],
            $this->repo->checkHash($this->paymentDetails)
        );
    }

    public function testWhenPaymentIsNotCreated()
    {
        $message = 'An error has occured';
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'payment_method' => 'standard',
                'cart_hash' => $this->hash,
            ],
        ]);
        $this->validators['payment']->shouldReceive([
            'isCachedPayment' => [
                'result' => false,
            ],
        ]);
        $this->repo->shouldReceive([
            'getHashedCart' => $this->hash,
            'createPayment' => [
                'result' => false,
                'response' => $message,
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => $message,
            ],
            $this->repo->checkHash($this->paymentDetails)
        );
    }

    public function testWhenPaymentIsNotUpdated()
    {
        $message = 'An error has occured';
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'payment_method' => 'standard',
                'cart_hash' => $this->hash,
            ],
        ]);
        $this->validators['payment']->shouldReceive([
            'isCachedPayment' => [
                'result' => false,
            ],
        ]);
        $this->repo->shouldReceive([
            'getHashedCart' => $this->hash,
            'createPayment' => [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => '[createPayment] Payment successfully created',
            ],
            'updatePaymentTable' => [
                'result' => false,
                'response' => $message,
                'paymentDetails' => $this->paymentDetails,
            ],
        ]);
        $expected_payment_details = [
            'result' => false,
            'response' => $message,
            'paymentDetails' => $this->paymentDetails,
        ];
        $this->assertSame(
            [
                'result' => false,
                'updatePaymentTable' => json_encode($expected_payment_details),
                'response' => 'An error has occured',
            ],
            $this->repo->checkHash($this->paymentDetails)
        );
    }

    public function testWhenPaymentIsUpdated()
    {
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'payment_method' => 'standard',
                'cart_hash' => $this->hash,
            ],
        ]);
        $this->validators['payment']->shouldReceive([
            'isCachedPayment' => [
                'result' => false,
            ],
        ]);
        $this->repo->shouldReceive([
            'getHashedCart' => $this->hash,
            'createPayment' => [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => '[createPayment] Payment successfully created',
            ],
            'updatePaymentTable' => [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
            ],
        ]);
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
