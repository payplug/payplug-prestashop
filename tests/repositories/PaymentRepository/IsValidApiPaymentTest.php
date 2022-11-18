<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @internal
 * @coversNothing
 */
final class IsValidApiPaymentTest extends BasePaymentRepository
{
    /**
     * Parameters to test method with empty $paiementDetails
     *
     * @return \Generator
     */
    public function invalidDataProvider()
    {
        yield [null, 'paymentDetails: null'];
        yield [[(string) 'I am a string!'], 'paymentDetails: ["I am a string!"]'];
        yield [['cartId' => null], 'paymentDetails: {"cartId":null}'];
        yield [['cartId' => 'string'], 'paymentDetails: {"cartId":null}'];
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
            $this->repo->isValidApiPayment($parameter),
            $logMessage
        );
    }

    public function testMethodWithValidData()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'payment_method' => 'standard',
                    'id_payment' => 1,
                ],
            ])
        ;
        $paymentDetails = [
            'cartId' => 1,
        ];

        $this->config
            ->shouldReceive([
                'get' => true,
            ])
        ;

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'code' => 200,
                    'result' => true,
                    'resource' => PaymentMock::getStandard(),
                ],
            ])
        ;

        $this->assertSame(
            $this->repo->isValidApiPayment($paymentDetails),
            [
                'result' => true,
                'paymentDetails' => $paymentDetails,
                'response' => 'Valid API payment/installment',
            ]
        );
    }

    public function testMethodWithThrowException()
    {
        $this->repo
            ->shouldReceive([
                'checkPaymentTable' => [
                    'payment_method' => 'standard',
                    'id_payment' => 1,
                ],
            ])
        ;

        $this->paymentApi
            ->shouldReceive(['retrieve' => mt_rand()])
            ->andThrow('Payplug\Exception\HttpException', 'Bad request', 400)
        ;
    }
}
