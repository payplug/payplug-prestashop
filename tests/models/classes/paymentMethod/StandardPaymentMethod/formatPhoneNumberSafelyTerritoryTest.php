<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group standard_payment_method_class
 */
class formatPhoneNumberSafelyTerritoryTest extends BaseStandardPaymentMethod
{
    /**
     * @dataProvider territoryProvider
     *
     * @param string $iso_code
     */
    public function testMetropolitanNationalMobileIsAcceptedOnTerritory($iso_code)
    {
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('0612345678', $iso_code));
        $this->assertSame('+33781234567', $this->class->formatPhoneNumberSafely('07 81 23 45 67', $iso_code));
    }

    public function testTerritoryIsoCodeIsCaseInsensitive()
    {
        $this->assertSame('+33781234567', $this->class->formatPhoneNumberSafely('07 81 23 45 67', 'mq'));
    }

    /**
     * @description "06 96" is a Martinique mobile prefix: the territory region is tried
     * before FR, so the number stays a Martinique one.
     */
    public function testLocalTerritoryNumberIsNotReinterpretedAsFrench()
    {
        $this->assertSame('+596696123456', $this->class->formatPhoneNumberSafely('0696 12 34 56', 'MQ'));
    }

    /**
     * @description "02 69 61 23 45" is valid both as a Mayotte and as a metropolitan
     * number: the territory region wins.
     */
    public function testNumberValidInTheTerritoryAndInFranceStaysInTheTerritory()
    {
        $this->assertSame('+262269612345', $this->class->formatPhoneNumberSafely('02 69 61 23 45', 'YT'));
    }

    public function testSharedGuadeloupeNumberingIsAcceptedOnSaintBarthelemyAndSaintMartin()
    {
        $this->assertSame('+590690123456', $this->class->formatPhoneNumberSafely('0690123456', 'BL'));
        $this->assertSame('+590690123456', $this->class->formatPhoneNumberSafely('0690123456', 'MF'));
        $this->assertSame('+590590871234', $this->class->formatPhoneNumberSafely('0590871234', 'MF'));
    }

    public function testInvalidNumberOnTerritoryIsNull()
    {
        $this->assertNull($this->class->formatPhoneNumberSafely('0612 34', 'MQ'));
    }

    /**
     * @dataProvider nonTerritoryProvider
     *
     * @param string $iso_code
     * @param string|null $expected
     */
    public function testNationalNumberIsNotRetriedAsFrenchOutsideTerritories($iso_code, $expected)
    {
        $this->assertSame($expected, $this->class->formatPhoneNumberSafely('0612345678', $iso_code));
    }

    public function territoryProvider()
    {
        $data = [];
        foreach (['GP', 'MQ', 'RE', 'GF', 'YT', 'PM', 'BL', 'MF', 'NC', 'PF', 'WF'] as $iso_code) {
            $data[$iso_code] = [$iso_code];
        }

        return $data;
    }

    public function nonTerritoryProvider()
    {
        return [
            'BE' => ['BE', null],
            'AD' => ['AD', null],
            'TF' => ['TF', null],
            'DE landline' => ['DE', '+49612345678'],
            'MC mobile' => ['MC', '+377612345678'],
        ];
    }
}
