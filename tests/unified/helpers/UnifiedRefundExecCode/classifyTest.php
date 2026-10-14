<?php

namespace PayPlug\tests\unified\helpers\UnifiedRefundExecCode;

use PayPlug\src\unified\helpers\UnifiedRefundExecCode;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group classes
 * @group unified_refund_exec_code
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
        $this->assertSame($expected, UnifiedRefundExecCode::classify($exec_code));
    }

    public function execCodeProvider()
    {
        return [
            'success' => ['0000', UnifiedRefundExecCode::ACCEPTED],
            'waiting provider' => ['0002', UnifiedRefundExecCode::ACCEPTED],
            'waiting status' => ['0003', UnifiedRefundExecCode::ACCEPTED],
            'timeout, result notified later' => ['5004', UnifiedRefundExecCode::ACCEPTED],
            'reference transaction not refundable' => ['2004', UnifiedRefundExecCode::DECLINED],
            'invalid refund amount' => ['2008', UnifiedRefundExecCode::DECLINED],
            'bank refused' => ['4001', UnifiedRefundExecCode::DECLINED],
            'bank network failure' => ['5002', UnifiedRefundExecCode::DECLINED],
            'fraud decline' => ['6002', UnifiedRefundExecCode::DECLINED],
            // 0001 (3DS) and 0004 (partial acceptance) make no sense for a refund: not a failure.
            '3ds required' => ['0001', UnifiedRefundExecCode::UNKNOWN],
            'partially accepted' => ['0004', UnifiedRefundExecCode::UNKNOWN],
            'undocumented' => ['9999', UnifiedRefundExecCode::UNKNOWN],
            'empty' => ['', UnifiedRefundExecCode::UNKNOWN],
            'not zero-padded' => [0, UnifiedRefundExecCode::UNKNOWN],
        ];
    }
}
