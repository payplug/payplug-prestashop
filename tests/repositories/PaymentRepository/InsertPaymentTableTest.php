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
            'cart' => CartMock::get(),
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
        yield [[(string) 'I am a string!'], 'paymentDetails: ["I am a string!"]'];
        yield [['paymentId' => null], 'paymentDetails: {"paymentId":null}'];
    }

    /**
     * Test methods with nulled $paiementDetails
     *
     * @dataProvider invalidDataProvider
     *
     * @param array  $parameter
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

    public function testInsertPaymentTableThrowException()
    {
        $error = '[insertPaymentTable] Error: Bad Request';
        $this->repo
            ->shouldReceive([
                'getHashedCart' => ['result' => true],
                'returnPaymentError' => $error,
            ])
        ;
        $this->repo
            ->shouldReceive([
                'getPayment' => false,
            ])
        ;

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
            ])
        ;

        $this->query
            ->shouldReceive('build')
            ->andThrow('Payplug\Exception\ConfigurationNotSetException', 'Bad Request', 400)
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
        $this->repo
            ->shouldReceive([
                'getPayment' => false,
            ])
        ;

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => false,
            ])
        ;

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
        $this->repo
            ->shouldReceive([
                'getPayment' => false,
            ])
        ;

        $this->query
            ->shouldReceive([
                'insert' => $this->query,
                'into' => $this->query,
                'fields' => $this->query,
                'values' => $this->query,
                'build' => true,
            ])
        ;

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
