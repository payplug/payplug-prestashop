<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\CartMock;

/**
 * @group unit
 * @group old_repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetPaymentReturnUrlTest extends BasePaymentRepository
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
            'authorizedAt' => true,
            'isEmbedded' => true,
            'isMobileDevice' => true,
            'isPaid' => true,
            'paymentMethod' => 'payment_method',
            'paymentReturnUrl' => 'payment_return_url',
            'paymentUrl' => 'payment_return_url',
            'isIntegrated' => false,
        ];
    }

    public function invalidArrayFormatDataProvider()
    {
        yield [42];
        yield [null];
        yield [false];
        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $paymentDetails
     */
    public function testWhenPaymentDetailsIsNotValid($paymentDetails)
    {
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($paymentDetails),
                'response' => '[getPaymentReturnUrl] Invalid parameters given, $paymentDetails must be an non empty array',
            ],
            $this->repo->getpaymentReturnUrl($paymentDetails)
        );
    }

    public function testWhenNoPaymentFound()
    {
        $this->payment_repository->shouldReceive([
            'getByCart' => [],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'paymentStored' => 'false',
                'response' => '[getPaymentReturnUrl] No payment found for given cart id',
            ],
            $this->repo->getpaymentReturnUrl($this->paymentDetails)
        );
    }

    public function testWhenInvalidPaymentMethodGiven()
    {
        $this->payment_repository->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'payment_method' => 'payment_method',
                'payment_url' => 'https://secure.payplug.com/pay/1234567890azertyuiop',
                'payment_return_url' => 'https://localhost:9080/fr/module/payplug/validation?ps=1&cartid=42',
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'paymentStored' => 'false',
                'response' => '[getPaymentReturnUrl] Invalid payment method given',
            ],
            $this->repo->getpaymentReturnUrl($this->paymentDetails)
        );
    }

    public function testWhenValidPaymentMethodGiven()
    {
        $this->payment_repository->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'payment_method' => 'standard',
                'payment_url' => 'https://secure.payplug.com/pay/1234567890azertyuiop',
                'payment_return_url' => 'https://localhost:9080/fr/module/payplug/validation?ps=1&cartid=42',
            ],
        ]);

        $this->paymentDetails['paymentMethod'] = 'standard';

        $this->assertSame(
            [
                'result' => true,
                'url' => [
                    'result' => 'new_card',
                    'embedded' => false,
                    'redirect' => true,
                    'return_url' => 'payment_return_url',
                ],
                'response' => 'Return URL successfully generated',
            ],
            $this->repo->getpaymentReturnUrl($this->paymentDetails)
        );
    }
}
