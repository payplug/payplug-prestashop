<?php

namespace PayPlug\tests\models\classes\paymentMethod\BizumPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group bizum_payment_method_class
 */
class formatPhoneNumberSafelyTest extends BaseBizumPaymentMethod
{
    public function testBizumIsPhoneRegionStrict()
    {
        $this->assertTrue($this->class->isPhoneRegionStrict());
    }

    public function testOwnCountryNumberIsFormatted()
    {
        $this->assertSame('+34612345678', $this->class->formatPhoneNumberSafely('612 34 56 78', 'ES'));
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
        $this->class->formatPhoneNumberSafely('+33612345678', 'ES');

        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::on(function ($message) {
                return false !== strpos($message, '+33*******78')
                    && false === strpos($message, '+33612345678')
                    && false !== strpos($message, '"ES"');
            }), 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testMetropolitanNumberOnFrenchTerritoryIsAccepted()
    {
        $this->assertSame('+33781234567', $this->class->formatPhoneNumberSafely('07 81 23 45 67', 'MQ'));
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('+33612345678', 'MQ'));
    }

    public function testLocalTerritoryNumberStaysLocal()
    {
        $this->assertSame('+596696123456', $this->class->formatPhoneNumberSafely('0696 12 34 56', 'MQ'));
    }

    public function testSharedGuadeloupeNumberingIsAcceptedOnSaintBarthelemyAndSaintMartin()
    {
        $this->assertSame('+590690123456', $this->class->formatPhoneNumberSafely('0690123456', 'BL'));
        $this->assertSame('+590690123456', $this->class->formatPhoneNumberSafely('0690123456', 'MF'));
        $this->assertSame('+590590871234', $this->class->formatPhoneNumberSafely('0590871234', 'MF'));
    }

    public function foreignValidPhoneNumberProvider()
    {
        return [
            'FR mobile on an ES address' => ['+33612345678', 'ES'],
            'FR mobile with 00 prefix on an ES address' => ['0033609860479', 'ES'],
        ];
    }
}
