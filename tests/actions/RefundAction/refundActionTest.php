<?php

namespace PayPlug\tests\actions\RefundAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group refund_action
 *
 * @dontrunTestsInSeparateProcesses
 */
class refundActionTest extends BaseRefundAction
{
    /**
     * @description test refundACtion when all params are valid
     */
    public function testRefundActionWithValidParameters()
    {
        $pay_id = 'pay_19movrH1FmfuNtpkG6EC4Z';
        $amount = 50;
        $metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];
        $pay_mode = 'LIVE';
        $inst_id = false;
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $pay_mode) {
                if ('strtoupper' == $method) {
                    return strtoupper($pay_mode);
                }
            });
        $this->dependencies->apiClass
            ->shouldReceive([
                                'initializeApi' => true, ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                                'retrievePayment' => [
                                    'result' => true,
                                    'resource' => PaymentMock::getStandard([
                                                                               'is_paid' => true,
                                                                               'metadata' => ['Order' => 42],
                                                                           ]),
                                ],
                'refundPayment' => ['result' => true,

                ],
                            ]);

        $result = $this->action->refundAction($pay_id, $amount, $metadata, $pay_mode, $inst_id);
        $this->assertSame(
            ['result' => true],
            $result['response']
        );
    }

    /**
     * @description  test refundACtion when Amount is invalid
     * @dataProvider invalidNumericFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testRefundActionWhenGivenAmountIsntNumeric($amount)
    {
        $pay_id = 'pay_19movrH1FmfuNtpkG6EC4Z';
        $metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];
        $pay_mode = 'LIVE';
        $inst_id = false;
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $pay_mode) {
                if ('strtoupper' == $method) {
                    return strtoupper($pay_mode);
                }
            });
        $this->dependencies->apiClass
            ->shouldReceive([
                                'initializeApi' => true, ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                                'retrievePayment' => [
                                    'result' => true,
                                    'resource' => PaymentMock::getStandard([
                                                                               'is_paid' => true,
                                                                               'metadata' => ['Order' => 42],
                                                                           ]),
                                ],
                                'refundPayment' => ['result' => false,

                                ],
                            ]);

        $result = $this->action->refundAction($pay_id, $amount, $metadata, $pay_mode, $inst_id);

        $this->assertSame(
            'error',
            $result
        );
    }

    /**
     * @description  test Refund ACtion when Meta data
     * is invalid
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $metadata
     */
    public function testRefundActionWhenGivenMetaDataIsntArray($metadata)
    {
        $pay_id = 'pay_19movrH1FmfuNtpkG6EC4Z';

        $amount = 50;
        $pay_mode = 'LIVE';
        $inst_id = false;
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $pay_mode) {
                if ('strtoupper' == $method) {
                    return strtoupper($pay_mode);
                }
            });
        $this->dependencies->apiClass
            ->shouldReceive([
                                'initializeApi' => true, ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                                'retrievePayment' => [
                                    'result' => true,
                                    'resource' => PaymentMock::getStandard([
                                                                               'is_paid' => true,
                                                                               'metadata' => ['Order' => 42],
                                                                           ]),
                                ],
                                'refundPayment' => ['result' => false,

                                ],
                            ]);

        $result = $this->action->refundAction($pay_id, $amount, $metadata, $pay_mode, $inst_id);

        $this->assertSame(
            'error',
            $result
        );
    }

    /**
     * @description  testRefundAction when pay mode is invalid
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $pay_mode
     */
    public function testRefundActionWhenGivenPayModeIsntString($pay_mode)
    {
        $pay_id = 'pay_19movrH1FmfuNtpkG6EC4Z';
        $metadata = [
            'ID Client' => 4,
            'reason' => 'Refunded with Prestashop',
        ];
        $amount = 50;

        $inst_id = false;
        $this->toolsAdapter
            ->shouldReceive('tool')
            ->andReturnUsing(function ($method, $pay_mode) {
                if ('strtoupper' == $method) {
                    return strtoupper($pay_mode);
                }
            });
        $this->dependencies->apiClass
            ->shouldReceive([
                                'initializeApi' => true, ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                                'retrievePayment' => [
                                    'result' => true,
                                    'resource' => PaymentMock::getStandard([
                                                                               'is_paid' => true,
                                                                               'metadata' => ['Order' => 42],
                                                                           ]),
                                ],
                                'refundPayment' => ['result' => false,

                                ],
                            ]);

        $result = $this->action->refundAction($pay_id, $amount, $metadata, $pay_mode, $inst_id);

        $this->assertSame(
            'error',
            $result
        );
    }
}
