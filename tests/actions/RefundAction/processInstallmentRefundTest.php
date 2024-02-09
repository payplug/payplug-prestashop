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
class processInstallmentRefundTest extends BaseRefundAction
{
    /**
     * @description  test ProcessInstallmentRefund
     * when retrieveInstallment return false
     */
    public function testProcessInstallmentRefundWithInValidInstallment()
    {
        $inst_id = 'inst_5jjL5sWDZ5pkSty6eNjPtU';
        $amount = 100;
        $metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];
        $this->dependencies->apiClass
            ->shouldReceive(
                [
                            'initializeApi' => true,
                        ]
            );

        $this->dependencies->apiClass
            ->shouldReceive(
                [
                            'retrieveInstallment' => [
                                'result' => false,
                            ],
                            'refundPayment' => [
                                'result' => true,

                            ],
                        ]
            );
        $this->dependencies->installmentClass
            ->shouldReceive([
                                        'updatePayplugInstallment' => true,
                                        ]);

        $this->payment_validator
            ->shouldReceive([
                                        'canBeRefund' => [
                                            'result' => true,
                                        ], ]);

        $result = $this->action->processInstallmentRefund($inst_id, $amount, $metadata);
        $this->assertSame(
            'error',
            $result
        );
    }

    /**
     * @description  test processInstallmentrefund When
     * amount can not be refund
     */
    public function testProcessInstallmentRefundWithCantBeRefunded()
    {
        $inst_id = 'inst_5jjL5sWDZ5pkSty6eNjPtU';
        $amount = 100;
        $metadata = [
        'ID Client' => 4,
        'reason' => 'Refunded with Prestashop',
    ];
        $this->dependencies->apiClass
            ->shouldReceive(
                [
                'initializeApi' => true,
            ]
            );

        $this->dependencies->apiClass
            ->shouldReceive(
                ['retrievePayment' => [
                            'result' => true,
                            'resource' => PaymentMock::getStandard(),
                        ],
                            'retrieveInstallment' => [
                                'result' => true,
                                'resource' => PaymentMock::getInstallment(),
                            ],
                            'refundPayment' => [
                                'result' => true,

                            ],
                        ]
            );
        $this->dependencies->installmentClass
            ->shouldReceive([
                            'updatePayplugInstallment' => true,
                        ]);

        $this->payment_validator
            ->shouldReceive([
                            'canBeRefund' => [
                                'result' => false,
                            ], ]);
        $this->payment_validator
            ->shouldReceive([
                                        'canBeRefund' => [
                                            'result' => true,
                                        ], ]);

        $result = $this->action->processInstallmentRefund($inst_id, $amount, $metadata);
        $this->assertSame(
            'error',
            $result
        );
    }
}
