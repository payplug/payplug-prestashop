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

    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function invalidPaymentDetailDataProvider()
    {
        yield [null, 'paymentDetails: null'];
    }

    public function validPaymentMethodDataProvider()
    {
        yield [
            'oneclick',
            [
                'result' => true,
                'embedded' => true,
                'redirect' => true,
                'return_url' => 'payment_return_url',
            ],
        ];
        yield [
            'oney',
            [
                'result' => 'new_card',
                'embedded' => false,
                'redirect' => true,
                'return_url' => 'payment_return_url',
            ],
        ];
        yield [
            'standard',
            [
                'result' => 'new_card',
                'embedded' => false,
                'redirect' => true,
                'return_url' => 'payment_return_url',
            ],
        ];
        yield [
            'installment',
            [
                'result' => 'new_card',
                'embedded' => false,
                'redirect' => true,
                'return_url' => 'payment_return_url',
            ],
        ];
    }

    public function invalidPaymentMethodDataProvider()
    {
        yield ['wrong_payment_method'];
        yield [''];
        yield [null];
        yield [42];
    }

    /**
     * @dataProvider invalidPaymentDetailDataProvider
     *
     * @param mixed $parameter
     * @param mixed $logMessage
     */
    public function testMethodWithEmptyParams($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->getpaymentReturnUrl($parameter),
            $logMessage
        );
    }

    /**
     * @dataProvider validPaymentMethodDataProvider
     *
     * @param mixed $paymentMethod
     * @param mixed $paymentReturnUrl
     */
    public function testMethodWithValidData($paymentMethod, $paymentReturnUrl)
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => ['key' => 'value'],
            ])
        ;

        $this->paymentDetails['paymentMethod'] = $paymentMethod;

        $this->assertSame(
            [
                'result' => true,
                'url' => $paymentReturnUrl,
                'response' => 'Return URL successfully generated',
            ],
            $this->repo->getPaymentReturnUrl($this->paymentDetails)
        );
    }

    public function testMethodWithEmptyPaymentTable()
    {
        $errorMessage = 'checkPaymentTable return empty result';

        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => false,
                'returnPaymentError' => $errorMessage,
            ])
        ;

        $this->assertSame(
            $errorMessage,
            $this->repo->getPaymentReturnUrl($this->paymentDetails)
        );
    }

    /**
     * @dataProvider invalidPaymentMethodDataProvider
     *
     * @param mixed $paymentMethod
     */
    public function testMethodWithInvalidPaymentMethod($paymentMethod)
    {
        $errorMessage = 'Error: invalid payment method given';
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => ['key' => 'value'],
                'returnPaymentError' => $errorMessage,
            ])
        ;

        $this->paymentDetails['paymentMethod'] = $paymentMethod;

        $this->assertSame(
            $errorMessage,
            $this->repo->getPaymentReturnUrl($this->paymentDetails)
        );
    }
}
