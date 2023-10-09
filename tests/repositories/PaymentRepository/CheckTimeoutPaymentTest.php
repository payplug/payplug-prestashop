<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group old_repository
 * @group payment
 * @group old_payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CheckTimeoutPaymentTest extends BasePaymentRepository
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
            'method' => 'payment_method',
        ];
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [null];
        yield [['key' => 'value']];
        yield [true];
        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param string $idCart
     */
    public function testWhenGivenPaymentDetailsCartIdIsNotAValidFormat($idCart)
    {
        $this->assertFalse(
            $this->repo->checkTimeoutPayment($idCart)
        );
    }

    public function testWhenNoPaymentFound()
    {
        $idCart = 42;
        $this->payment_repository->shouldReceive([
            'getByCart' => [],
        ]);
        $this->assertTrue(
            $this->repo->checkTimeoutPayment($idCart)
        );
    }

    public function testWhenPaymentIsNotTimeout()
    {
        $idCart = 42;
        $this->payment_repository->shouldReceive([
            'getByCart' => [
                'resource_id' => 'pay_12345678azertyu',
                'date_upd' => '2023-01-01 00:00:00',
            ],
        ]);
        $this->validators['payment']->shouldReceive(
            [
                'isTimeoutCachedPayment' => [
                    'result' => false,
                    'message' => '',
                ],
            ]
        );
        $this->assertFalse(
            $this->repo->checkTimeoutPayment($idCart)
        );
    }

    public function testWhenPaymentIsTimeout()
    {
        $idCart = 42;
        $this->payment_repository->shouldReceive([
            'getByCart' => [
                'resource_id' => 'pay_12345678azertyu',
                'date_upd' => '2023-01-01 00:00:00',
            ],
        ]);
        $this->validators['payment']->shouldReceive(
            [
                'isTimeoutCachedPayment' => [
                    'result' => true,
                    'message' => '',
                ],
            ]
        );
        $this->assertTrue(
            $this->repo->checkTimeoutPayment($idCart)
        );
    }
}
