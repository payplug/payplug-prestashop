<?php

namespace PayPlug\tests\models\classes\UpcRefundExecCode;

use PayPlug\src\models\classes\UpcRefundExecCode;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group classes
 * @group upc_refund_exec_code
 */
class classifyTest extends TestCase
{
    /**
     * @dataProvider execCodeProvider
     *
     * @param mixed $exec_code
     * @param string $expected
     */
    public function testClassifiesTheRefundExecCode($exec_code, $expected)
    {
        $this->assertSame($expected, UpcRefundExecCode::classify($exec_code));
    }

    public function execCodeProvider()
    {
        return [
            'success' => ['0000', UpcRefundExecCode::ACCEPTED],
            'waiting provider' => ['0002', UpcRefundExecCode::ACCEPTED],
            'waiting status' => ['0003', UpcRefundExecCode::ACCEPTED],
            'timeout, result notified later' => ['5004', UpcRefundExecCode::ACCEPTED],
            'reference transaction not refundable' => ['2004', UpcRefundExecCode::DECLINED],
            'invalid refund amount' => ['2008', UpcRefundExecCode::DECLINED],
            'bank refused' => ['4001', UpcRefundExecCode::DECLINED],
            'bank network failure' => ['5002', UpcRefundExecCode::DECLINED],
            'fraud decline' => ['6002', UpcRefundExecCode::DECLINED],
            // 0001 (3DS) and 0004 (partial acceptance) make no sense for a refund: not a failure.
            '3ds required' => ['0001', UpcRefundExecCode::UNKNOWN],
            'partially accepted' => ['0004', UpcRefundExecCode::UNKNOWN],
            'undocumented' => ['9999', UpcRefundExecCode::UNKNOWN],
            'empty' => ['', UpcRefundExecCode::UNKNOWN],
            'not zero-padded' => [0, UpcRefundExecCode::UNKNOWN],
        ];
    }
}
