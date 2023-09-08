<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group old_repository
 * @group payment
 * @group payment_repository
 *
 * @internal
 * @coversNothing
 * @runTestsInSeparateProcesses
 */
final class IsValidApiPaymentTest extends BasePaymentRepository
{
    private $paymentDetails;

    public function setUp()
    {
        parent::setUp();

        $this->paymentDetails = [
            'cartId' => 42,
            'paymentMethod' => 'payment_method',
        ];
    }

    public function invalidArrayFormatDataProvider()
    {
        yield [42];
        yield [null];
        yield [false];
        yield ['lorem ipsum'];
    }

    public function invalidIntegerFormatDataProvider()
    {
        yield [null];
        yield [['key' => 'value']];
        yield [true];
        yield ['lorem ipsum'];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param string $paymentDetails
     */
    public function testWhenGivenPaymentDetailsIsNotAValidFormat($paymentDetails)
    {
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($paymentDetails),
                'response' => '[isValidApiPayment] Invalid parameters given, $paymentDetails must be an non empty array',
            ],
            $this->repo->isValidApiPayment($paymentDetails)
        );
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param string $cartId
     */
    public function testWhenGivenPaymentDetailsCartIdIsNotAValidFormat($cartId)
    {
        $paymentDetails = [
            'cartId' => $cartId,
        ];
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($paymentDetails),
                'response' => '[isValidApiPayment] Invalid parameters given, $paymentDetail[cartId] must be a non-null integer',
            ],
            $this->repo->isValidApiPayment($paymentDetails)
        );
    }

    public function testWhenNoStoredPaymentIsFound()
    {
        $this->payment_repository->shouldReceive([
            'getByCart' => [],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => '[isValidApiPayment] No payment found for given cart id',
            ],
            $this->repo->isValidApiPayment($this->paymentDetails)
        );
    }

    public function testWhenPaymentMethodIsMissingInStoredPayment()
    {
        $this->payment_repository->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'id_payment' => 'pay_1234567890azerty',
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => '[isValidApiPayment] Invalid stored payment getted, payment_method is not given',
            ],
            $this->repo->isValidApiPayment($this->paymentDetails)
        );
    }

    public function testWhenIdPaymentIsMissingInStoredPayment()
    {
        $this->payment_repository->shouldReceive([
            'getByCart' => [
                'id_cart' => 42,
                'payment_method' => 'standard',
            ],
        ]);

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => '[isValidApiPayment] Invalid stored payment getted, id_payment is not given',
            ],
            $this->repo->isValidApiPayment($this->paymentDetails)
        );
    }

    public function testWhenInstallmentCantBeRetrieve()
    {
        $payment = [
            'id_cart' => 42,
            'id_payment' => 'pay_1234567890azerty',
            'payment_method' => 'installment',
        ];
        $this->payment_repository->shouldReceive([
            'getByCart' => $payment,
        ]);

        $this->dependencies->apiClass
            ->shouldReceive([
                'retrieveInstallment' => [
                    'code' => 500,
                    'result' => false,
                    'message' => 'An error occured',
                ],
            ]);

        $this->assertSame(
            [
                'result' => false,
                'storedPayment' => json_encode($payment),
                'response' => '[isValidApiPayment] Cannot retrieve payment with id: ' . $payment['id_payment'],
            ],
            $this->repo->isValidApiPayment($this->paymentDetails)
        );
    }

    public function testWhenPaymentCantBeRetrieve()
    {
        $payment = [
            'id_cart' => 42,
            'id_payment' => 'pay_1234567890azerty',
            'payment_method' => 'standard',
        ];
        $this->payment_repository->shouldReceive([
            'getByCart' => $payment,
        ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'code' => 500,
                    'result' => false,
                    'message' => 'An error occured',
                ],
            ]);
        $this->repo->shouldReceive([
            'createPayment' => [
                'paymentDetails' => $this->paymentDetails,
            ],
            'updatePaymentTable' => true,
        ]);
        $this->assertSame(
            true,
            $this->repo->isValidApiPayment($this->paymentDetails)
        );
    }

    public function testWhenPaymentIsRetrieve()
    {
        $payment = [
            'id_cart' => 42,
            'id_payment' => 'pay_1234567890azerty',
            'payment_method' => 'standard',
        ];
        $this->payment_repository->shouldReceive([
            'getByCart' => $payment,
        ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'code' => 200,
                    'result' => true,
                    'resource' => PaymentMock::getStandard(),
                ],
            ]);
        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'response' => 'Valid API payment/installment',
            ],
            $this->repo->isValidApiPayment($this->paymentDetails)
        );
    }
}
