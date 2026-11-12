<?php

namespace PayPlug\tests\unified\helpers\UnifiedPaymentDetails;

use PayPlug\src\unified\helpers\UnifiedPaymentDetails;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group classes
 * @group unified_payment_details
 */
class fromOperationTest extends TestCase
{
    public function testMapsTheCardAndAuthenticationOfAPaidOperation()
    {
        $operation = [
            'operationType' => 'PAYMENT',
            'authentication' => ['status' => 'C', 'globalStatus' => 'OK', 'mode' => 'SCA'],
            'paymentMethod' => [
                'card' => ['code6x4' => '402205XXXXXX0001', 'type' => 'VISA', 'network' => 'VISA'],
                'details' => ['validityDate' => '2029-12', 'selectedBrand' => 'CB'],
            ],
        ];

        $this->assertSame([
            'brand' => 'VISA',
            'last4' => '0001',
            'exp_month' => '12',
            'exp_year' => '2029',
            'tds' => UnifiedPaymentDetails::TDS_YES,
        ], UnifiedPaymentDetails::fromOperation($operation));
    }

    public function testFallsBackOnTheSelectedBrandWhenTheCardHasNoType()
    {
        $operation = ['paymentMethod' => ['card' => ['type' => ''], 'details' => ['selectedBrand' => 'MASTERCARD']]];

        $this->assertSame('MASTERCARD', UnifiedPaymentDetails::fromOperation($operation)['brand']);
    }

    /**
     * @dataProvider validityDateProvider
     *
     * @param mixed $validity_date
     * @param string|null $month
     * @param string|null $year
     */
    public function testReadsTheExpiryDate($validity_date, $month, $year)
    {
        $details = UnifiedPaymentDetails::fromOperation(['paymentMethod' => ['details' => ['validityDate' => $validity_date]]]);

        $this->assertSame($month, $details['exp_month']);
        $this->assertSame($year, $details['exp_year']);
    }

    public function validityDateProvider()
    {
        return [
            'YYYY-MM' => ['2029-12', '12', '2029'],
            'MMYY' => ['0731', '07', '2031'],
            'surrounding spaces' => [' 2029-01 ', '01', '2029'],
            'month 00' => ['2029-00', null, null],
            'month 13' => ['1329', null, null],
            'unknown format' => ['12/2029', null, null],
            'not a string' => [202912, null, null],
        ];
    }

    /**
     * @dataProvider authenticationProvider
     *
     * @param array<string, mixed> $operation
     * @param string|null $expected
     */
    public function testMapsTheThreeDsAuthentication(array $operation, $expected)
    {
        $this->assertSame($expected, UnifiedPaymentDetails::fromOperation($operation)['tds']);
    }

    public function authenticationProvider()
    {
        return [
            'global status OK' => [['authentication' => ['globalStatus' => 'OK']], UnifiedPaymentDetails::TDS_YES],
            'another global status' => [['authentication' => ['globalStatus' => 'NOK']], UnifiedPaymentDetails::TDS_NO],
            'lower-case ok is another value' => [['authentication' => ['globalStatus' => 'ok']], UnifiedPaymentDetails::TDS_NO],
            'no global status' => [['authentication' => ['mode' => 'SCA']], null],
            'non-string global status' => [['authentication' => ['globalStatus' => true]], null],
            'no authentication' => [[], null],
            'authentication not an object' => [['authentication' => 'OK'], null],
        ];
    }

    /**
     * @dataProvider unusableOperationProvider
     *
     * @param mixed $operation
     */
    public function testReturnsOnlyNullsForAnUnusableOperation($operation)
    {
        $this->assertSame([
            'brand' => null,
            'last4' => null,
            'exp_month' => null,
            'exp_year' => null,
            'tds' => null,
        ], UnifiedPaymentDetails::fromOperation($operation));
    }

    public function unusableOperationProvider()
    {
        return [
            'null' => [null],
            'string' => ['{"paymentMethod":{}}'],
            'empty' => [[]],
            'payment method not an object' => [['paymentMethod' => 'card']],
            'non-string card fields' => [['paymentMethod' => ['card' => ['code6x4' => 4022050001, 'type' => ['VISA']]]]],
            'card number without 4 trailing digits' => [['paymentMethod' => ['card' => ['code6x4' => '402205XXXXXXXXXX']]]],
        ];
    }
}
