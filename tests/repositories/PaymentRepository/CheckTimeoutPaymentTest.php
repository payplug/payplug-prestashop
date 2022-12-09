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
            'paymentMethod' => 'payment_method',
        ];
    }

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function checkTimeoutPaymentParameters()
    {
        // Test if (!$idCart)
        yield [null, 'id cart: null'];

        // Test if (!is_int($idCart))
        yield [
            (string) 'I am a string!',
            'id cart: "I am a string!"',
        ];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider checkTimeoutPaymentParameters
     *
     * @param array  $parameter
     * @param string $logMessage
     *
     * @throws \Exception
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->checkTimeoutPayment($parameter),
            $logMessage
        );
    }

    public function testWithTimeoutLessThan3min()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'date_upd' => (new \DateTime('-2 min'))->format('Y-m-d H:i:s'),
                ],
            ])
        ;

        $this->assertSame(
            true,
            $this->repo->checkTimeoutPayment($this->paymentDetails['cartId'])
        );
    }

    public function testWithTimoutMoreThan3min()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'date_upd' => date('Y-m-d H:i:s', strtotime('-5 minutes')),
                ],
            ])
        ;

        $this->assertSame(
            false,
            $this->repo->checkTimeoutPayment($this->paymentDetails['cartId'])
        );
    }
}
