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
            'resource_id' => 'pay_5SnSQwmPty5UgKbUgrZQuT',
            'method' => 'standard',
            'paymentUrl' => 'payment_return_url',
            'paymentReturnUrl' => 'payment_return_url',
            'cart' => CartMock::get(),
        ];
    }

    /**
     * Parameters to test method with empty $paiementDetails.
     *
     * @return \Generator
     */
    public function invalidDataProvider()
    {
        yield [null, 'paymentDetails: null'];
        yield [[(string) 'I am a string!'], 'paymentDetails: ["I am a string!"]'];
        yield [['resource_id' => null], 'paymentDetails: {"resource_id":null}'];
    }

    /**
     * Test methods with nulled $paiementDetails.
     *
     * @dataProvider invalidDataProvider
     *
     * @param array $parameter
     * @param string $logMessage
     */
    public function testMethodWithInvalidData($parameter, $logMessage)
    {
        $this->repo
            ->shouldReceive([
                'returnPaymentError' => $logMessage,
            ])
        ;

        $this->assertSame(
            $this->repo->insertPaymentTable($parameter),
            $logMessage
        );
    }

    public function testWithInvalidCartHashGetter()
    {
        $error = '[insertPaymentTable] Problem with the getHashedCart method.';
        $this->repo
            ->shouldReceive([
                'getHashedCart' => ['result' => false],
                'returnPaymentError' => $error,
            ])
        ;

        $this->assertSame(
            $error,
            $this->repo->insertPaymentTable($this->paymentDetails)
        );
    }

    public function testInsertPaymentTableReturnFalse()
    {
        $error = '[insertPaymentCart] Unable to flush DB (build method)';
        $this->repo
            ->shouldReceive([
                'getHashedCart' => ['result' => true],
                'returnPaymentError' => $error,
            ])
        ;

        $this->payment_repository->shouldReceive([
            'getByCart' => [],
            'createPayment' => false,
        ]);

        $this->assertSame(
            $error,
            $this->repo->insertPaymentTable($this->paymentDetails)
        );
    }

    public function testInsertPaymentTableReturnValid()
    {
        $this->repo
            ->shouldReceive([
                'getHashedCart' => ['result' => true],
            ])
        ;

        $this->payment_repository->shouldReceive([
            'getByCart' => [],
            'createPayment' => true,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => 'Insert data in DB successfully',
            ],
            $this->repo->insertPaymentTable($this->paymentDetails)
        );
    }
}
