<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\CartMock;
use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group old_repository
 * @group payment
 * @group old_payment_repository
 * @group debug
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
            'method' => 'method',
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
                'resource_id' => 'pay_azerty12345',
                'method' => 'method',
                'payment_url' => 'https://secure.payplug.com/pay/1234567890azertyuiop',
                'payment_return_url' => 'https://localhost:9080/fr/module/payplug/validation?ps=1&cartid=42',
            ],
        ]);
        $this->dependencies->apiClass->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
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
                'resource_id' => 'pay_azerty12345',
                'method' => 'standard',
                'payment_url' => 'https://secure.payplug.com/pay/1234567890azertyuiop',
                'payment_return_url' => 'https://localhost:9080/fr/module/payplug/validation?ps=1&cartid=42',
            ],
        ]);

        $payment = PaymentMock::getStandard();
        $this->dependencies->apiClass->shouldReceive([
            'retrievePayment' => [
                'result' => true,
                'resource' => $payment,
            ],
        ]);

        $this->paymentDetails['method'] = 'standard';

        $this->assertSame(
            [
                'result' => true,
                'url' => [
                    'result' => 'new_card',
                    'embedded' => false,
                    'redirect' => true,
                    'return_url' => $payment->hosted_payment->payment_url,
                ],
                'response' => 'Return URL successfully generated',
            ],
            $this->repo->getpaymentReturnUrl($this->paymentDetails)
        );
    }
}
