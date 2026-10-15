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

    public function testForeignValidNumberIsDroppedAndLoggedMasked()
    {
        $this->assertNull($this->class->formatPhoneNumberSafely('+33612345678', 'ES'));

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
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('0612345678', 'MQ'));
    }
}
