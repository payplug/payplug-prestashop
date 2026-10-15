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

    public function testForeignValidNumberIsDroppedAndLoggedMasked()
    {
        $this->assertNull($this->class->formatPhoneNumberSafely('+32470123456', 'FR'));

        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::on(function ($message) {
                return false !== strpos($message, '+32*******56')
                    && false === strpos($message, '+32470123456')
                    && false !== strpos($message, '"FR"');
            }), 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testMetropolitanNumberOnFrenchTerritoryIsAccepted()
    {
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('0612345678', 'MQ'));
    }
}
