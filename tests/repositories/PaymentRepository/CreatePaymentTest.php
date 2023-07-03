<?php

namespace PayPlug\tests\repositories\PaymentRepository;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group repository
 * @group payment
 * @group payment_repository
 *
 * @runTestsInSeparateProcesses
 */
final class CreatePaymentTest extends BasePaymentRepository
{
    private $paymentDetails;
    private $payment;
    private $installment;

    public function setUp()
    {
        parent::setUp();

        $this->paymentDetails = [
            'paymentTab' => [
                'force_3ds' => false,
                'auto_capture' => true,
                'payment_method' => 'standard',
            ],
            'paymentMethod' => 'standard',
            'cartId' => 42,
        ];

        $this->payment = PaymentMock::getStandard();
        $this->installment = PaymentMock::getInstallment();
    }

    public function invalidArrayFormatDataProvider()
    {
        yield [42];
        yield [null];
        yield [false];
        yield ['lorem ipsum'];
    }

    public function invalidStringFormatDataProvider()
    {
        yield [42];
        yield [['key' => 'value']];
        yield [false];
        yield [''];
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
                'response' => '[createPayment] Invalid parameters given, $paymentDetails must be an non empty array',
            ],
            $this->repo->createPayment($paymentDetails)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param string $paymentTab
     */
    public function testWhenGivenPaymentDetailsPaymentTabIsNotAValidFormat($paymentTab)
    {
        $paymentDetails = [
            'paymentTab' => $paymentTab,
        ];
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($paymentDetails),
                'response' => '[createPayment] Invalid parameters given, $paymentDetails[paymentTab] must be an non empty array',
            ],
            $this->repo->createPayment($paymentDetails)
        );
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param string $paymentMethod
     */
    public function testWhenGivenPaymentDetailsPaymentMethodIsNotAValidFormat($paymentMethod)
    {
        $paymentDetails = [
            'paymentTab' => [
                'force_3ds' => false,
                'auto_capture' => true,
                'payment_method' => 'standard',
            ],
            'paymentMethod' => $paymentMethod,
        ];

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($paymentDetails),
                'response' => '[createPayment] Invalid parameters given, $paymentDetails[paymentMethod] must be a non empty string',
            ],
            $this->repo->createPayment($paymentDetails)
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
            'paymentTab' => [
                'force_3ds' => false,
                'auto_capture' => true,
                'payment_method' => 'standard',
            ],
            'paymentMethod' => 'standard',
            'cartId' => $cartId,
        ];

        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($paymentDetails),
                'response' => '[createPayment] Invalid parameters given, $paymentDetails[cartId] must be a non null integer',
            ],
            $this->repo->createPayment($paymentDetails)
        );
    }

    public function testWhenTryingToCreatePaymentWithOptionDisabled()
    {
        $config = '{"standard":false}';
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn($config);
        $this->assertSame(
            [
                'result' => false,
                'Configuration::get' => json_encode($config),
                'response' => '[createPayment] Try to create payment with disabled feature standard',
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }

    public function testWhenExistingCancellablePaymentCantBeAborted()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');
        $payment = [
            'id_cart' => 42,
            'id_payment' => 'pay_1234567890azerty',
            'payment_method' => 'standard',
        ];
        $this->repositories['payment']->shouldReceive([
            'getByCart' => $payment,
        ]);
        $this->validators['payment']->shouldReceive([
            'isCancellable' => [
                'result' => true,
                'message' => '',
            ],
        ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                'retrievePayment' => [
                    'code' => 200,
                    'result' => true,
                    'resource' => PaymentMock::getStandard(),
                ],
                'abortPayment' => [
                    'code' => 500,
                    'result' => false,
                    'message' => 'Payment cannot be aborted',
                ],
            ]);
        $this->assertSame(
            [
                'result' => false,
                'paymentId' => json_encode($payment['id_payment']),
                'response' => '[createPayment] Exception. Unable to abort payment. Error: Payment cannot be aborted',
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }

    public function testWhenPaymentCanNotBeCreated()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [],
        ]);

        $this->dependencies->apiClass
            ->shouldReceive([
                'createPayment' => [
                    'code' => 500,
                    'result' => false,
                    'message' => 'Payment cannot be created',
                ],
            ]);
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => '[createPayment] Exception. Unable to create payment. Error: Payment cannot be created',
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }

    public function testWhenPaymentIsCreatedWithFailure()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [],
        ]);
        $message = 'An error occured while creating the payment';
        $this->dependencies->apiClass
            ->shouldReceive([
                'createPayment' => [
                    'code' => 200,
                    'result' => true,
                    'resource' => PaymentMock::getStandard([
                        'failure' => [
                            'message' => $message,
                        ],
                    ]),
                ],
            ]);
        $this->validators['payment']->shouldReceive([
            'isFailed' => [
                'result' => true,
                'message' => $message,
            ],
        ]);
        $this->assertSame(
            [
                'result' => false,
                'paymentDetails' => json_encode($this->paymentDetails),
                'response' => $message,
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }

    public function testWhenPaymentIsCreatedWithoutFailure()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"standard":true}');
        $this->repositories['payment']->shouldReceive([
            'getByCart' => [],
        ]);
        $payment = PaymentMock::getStandard();
        $this->paymentDetails['paymentId'] = $payment->id;
        $this->paymentDetails['paymentReturnUrl'] = $payment->hosted_payment->return_url;
        $this->paymentDetails['isPaid'] = $payment->is_paid;
        $this->paymentDetails['paymentUrl'] = $payment->hosted_payment->payment_url;
        $this->dependencies->apiClass
            ->shouldReceive([
                'createPayment' => [
                    'code' => 200,
                    'result' => true,
                    'resource' => $payment,
                ],
            ]);
        $this->assertSame(
            [
                'result' => true,
                'paymentDetails' => $this->paymentDetails,
                'resource' => $payment,
                'response' => '[createPayment] Payment successfully created',
            ],
            $this->repo->createPayment($this->paymentDetails)
        );
    }
}
