<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class formatPhoneNumberSafelyTest extends BaseOneyPaymentMethod
{
    public function testOneyIsPhoneRegionStrict()
    {
        $this->assertTrue($this->class->isPhoneRegionStrict());
    }

    public function testOwnCountryNumberIsFormatted()
    {
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('0612345678', 'FR'));
    }

    /**
     * @dataProvider foreignValidPhoneNumberProvider
     *
     * @param string $phone_number
     * @param string $iso_code
     */
    public function testForeignValidNumberIsDropped($phone_number, $iso_code)
    {
        $this->assertNull($this->class->formatPhoneNumberSafely($phone_number, $iso_code));
    }

    public function testForeignValidNumberIsLoggedMasked()
    {
        $this->class->formatPhoneNumberSafely('+32470123456', 'FR');

        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::on(function ($message) {
                return false !== strpos($message, '+32*******56')
                    && false === strpos($message, '+32470123456')
                    && false !== strpos($message, '"FR"');
            }), 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    /**
     * @dataProvider territoryProvider
     *
     * @param string $iso_code
     */
    public function testMetropolitanNumberOnFrenchTerritoryIsAccepted($iso_code)
    {
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('0612345678', $iso_code));
        $this->assertSame('+33781234567', $this->class->formatPhoneNumberSafely('07 81 23 45 67', $iso_code));
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('+33612345678', $iso_code));
    }

    public function testLocalTerritoryNumberStaysLocal()
    {
        $this->assertSame('+596696123456', $this->class->formatPhoneNumberSafely('0696 12 34 56', 'MQ'));
        $this->assertSame('+262269612345', $this->class->formatPhoneNumberSafely('02 69 61 23 45', 'YT'));
    }

    /**
     * @dataProvider sharedGuadeloupeNumberingProvider
     *
     * @param string $phone_number
     * @param string $iso_code
     * @param string $expected
     */
    public function testSharedGuadeloupeNumberingIsAcceptedOnSaintBarthelemyAndSaintMartin($phone_number, $iso_code, $expected)
    {
        $this->assertSame($expected, $this->class->formatPhoneNumberSafely($phone_number, $iso_code));
    }

    public function sharedGuadeloupeNumberingProvider()
    {
        return [
            'national mobile on a BL address' => ['0690123456', 'BL', '+590690123456'],
            'national mobile on a MF address' => ['0690123456', 'MF', '+590690123456'],
            'international mobile on a BL address' => ['+590690123456', 'BL', '+590690123456'],
            'international mobile on a MF address' => ['+590690123456', 'MF', '+590690123456'],
            'landline shared with BL on a MF address' => ['0590871234', 'MF', '+590590871234'],
            'landline shared with BL on a BL address' => ['0590871234', 'BL', '+590590871234'],
        ];
    }

    public function testSaintBarthelemyLandlineOnGuadeloupeAddressIsDropped()
    {
        $this->assertNull($this->class->formatPhoneNumberSafely('0590271234', 'GP'));
    }

    public function testTerritoryNumberOnMetropolitanAddressIsDropped()
    {
        $this->assertNull($this->class->formatPhoneNumberSafely('+596696123456', 'FR'));
    }

    public function foreignValidPhoneNumberProvider()
    {
        return [
            'BE mobile on a FR address' => ['+32470123456', 'FR'],
            'BE mobile with 00 prefix on a FR address' => ['0032470123456', 'FR'],
            'FR mobile on a DE address' => ['+33612345678', 'DE'],
            'FR mobile with 00 prefix on a BE address' => ['0033609860479', 'BE'],
        ];
    }

    public function territoryProvider()
    {
        $data = [];
        foreach (['GP', 'MQ', 'RE', 'GF', 'YT', 'PM', 'BL', 'MF', 'NC', 'PF', 'WF'] as $iso_code) {
            $data[$iso_code] = [$iso_code];
        }

        return $data;
    }
}
