<?php

namespace PayPlug\tests\actions\RefundAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group refund_action
 *
 * @runTestsInSeparateProcesses
 */
class processPaymentRefundTest extends BaseRefundAction
{
    /**
     * Process payment refund when
     * all param are valids.
     */
    public function testProcessPaymentRefundWithValidParameters()
    {
        $payment_id = 'pay_19movrH1FmfuNtpkG6EC4Z';
        $amount = 100;
        $metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];

        $this->dependencies->apiClass
            ->shouldReceive(
                [
                    [
                        'initializeApi' => true,
                    ],
                    'retrievePayment' => [
                        'result' => true,
                        'resource' => PaymentMock::getStandard(
                            [
                                'is_paid' => true,
                                'metadata' => ['Order' => 42],
                            ]
                        ),
                    ],
                    'refundPayment' => [
                        'result' => true,

                    ],
                ]
            );

        $result = $this->action->processPaymentRefund($payment_id, $amount, $metadata);
        $this->assertSame(
            true,
            $result['response']['result']
        );
    }

    /**
     * @description  test ProcessPaymentRefund
     * When Payment param is Invalid
     */
    public function testProcessPaymentRefundWithInValidPayment()
    {
        $payment_id = 'pay_19movrH1FmfuNtpkG6EC4Z';
        $amount = -100;
        $metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];

        $this->dependencies->apiClass
            ->shouldReceive(
                [
                    [
                        'initializeApi' => true,
                    ],
                    'retrievePayment' => [
                        'result' => true,
                        'resource' => PaymentMock::getStandard(
                            [
                                'is_paid' => true,
                                'metadata' => ['Order' => 42],
                            ]
                        ),
                    ],
                    'refundPayment' => [
                        'result' => true,

                    ],
                ]
            );
        $result = $this->action->processPaymentRefund($payment_id, $amount, $metadata);
        $this->assertSame(
            null,
            $result
        );
    }

    public function testProcessPaymentRefundWithInValidAmount()
    {
        $payment_id = 'pay_19movrH1FmfuNtpkG6EC4Z';
        $amount = 100;
        $metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];

        $this->dependencies->apiClass
            ->shouldReceive(
                [
                    [
                        'initializeApi' => true,
                    ],
                    'retrievePayment' => [
                        'result' => false,
                        'resource' => PaymentMock::getInstallment(),
                    ],
                ]
            );

        $result = $this->action->processPaymentRefund($payment_id, $amount, $metadata);
        $this->assertSame(
            null,
            $result
        );
    }
}
