<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group standard_payment_method_class
 */
class getDefaultPaymentTabTest extends BaseStandardPaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $this->class->set('name', 'standard');

        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']->shouldReceive([
            'validateAmount' => [
                'result' => true,
            ],
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $arg) {
                switch ($method) {
                    case 'getShopDomainSsl':
                        return true;

                    default:
                        return '';
                }
            });

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;

        $this->language_helper->shouldReceive([
            'getIsoFromCodeLang' => 'fr',
        ]);
    }

    /**
     * @description This exercises the real formatPhoneNumberSafely()/PhoneHelper
     * delegation with real, valid E.164 French phone numbers, since no other
     * test calls getDefaultPaymentTab() without mocking it away.
     */
    public function testPhoneNumbersAreFormattedToE164ForBillingAndShipping()
    {
        $payment_tab = $this->class->getDefaultPaymentTab();

        $this->assertSame('+33123456789', $payment_tab['billing']['landline_phone_number']);
        $this->assertSame('+33623456789', $payment_tab['billing']['mobile_phone_number']);
        $this->assertSame('+33123456789', $payment_tab['shipping']['landline_phone_number']);
        $this->assertSame('+33623456789', $payment_tab['shipping']['mobile_phone_number']);
    }

    /**
     * @description Verify that formatPhoneNumberSafely() rejects loosely-formatted
     * phone numbers with trailing junk (e.g., text in parentheses), restoring the
     * behavior-neutral regex guard that was present in the deprecated
     * PhoneNumber::formatPhoneNumber() method. This ensures that phone numbers
     * containing characters outside the set [+0-9. ()\/-] are rejected.
     */
    public function testFormatPhoneNumberSafelyRejectsLooselyFormattedNumbers()
    {
        // Test that the regex guard rejects phone numbers containing letters in
        // trailing text. The regex /^[+0-9. ()\/-]{6,}$/ allows only: +, digits,
        // space, dot, parentheses, slash, and dash. The strings "bureau" and
        // "mobile" contain letters not in this set, so they should be rejected.

        // Directly test formatPhoneNumberSafely with a loose phone number
        $result = $this->class->formatPhoneNumberSafely('01 23 45 67 89 (bureau)', 'FR');
        $this->assertNull($result);

        // Also verify a properly formatted loose number (no trailing text) is accepted
        $result_valid = $this->class->formatPhoneNumberSafely('01 23 45 67 89', 'FR');
        // This should attempt formatting (not testing the full format here, just
        // that it's not immediately rejected by the regex guard)
        $this->assertNotNull($result_valid);
    }

    /**
     * @description Verify that formatPhoneNumberSafely() correctly formats a valid
     * number for a non-FR country, proving the UPC PhoneHelper delegation isn't
     * accidentally FR-specific. '030 1234567' is a genuine German (DE) landline
     * number (Berlin), verified valid against the real libphonenumber data.
     */
    public function testFormatPhoneNumberSafelyFormatsNonFrenchNumbersToE164()
    {
        $result = $this->class->formatPhoneNumberSafely('030 1234567', 'DE');

        $this->assertSame('+49301234567', $result);
    }

    public function testFormatPhoneNumberSafelyAcceptsPhoneNumberValidForADifferentCountry()
    {
        $this->assertSame('+32470123456', $this->class->formatPhoneNumberSafely('+32470123456', 'FR'));
        $this->assertSame('+33609860479', $this->class->formatPhoneNumberSafely('0033609860479', 'BE'));
    }

    public function testFormatPhoneNumberSafelyAcceptsMetropolitanNumberOnFrenchTerritory()
    {
        $this->assertSame('+33612345678', $this->class->formatPhoneNumberSafely('0612345678', 'MQ'));
    }

    /**
     * @dataProvider invalidPhoneNumberProvider
     *
     * @param string $phone_number
     * @param string $iso_code
     */
    public function testFormatPhoneNumberSafelyReturnsNullForInvalidNumber($phone_number, $iso_code)
    {
        $this->assertNull($this->class->formatPhoneNumberSafely($phone_number, $iso_code));
    }

    public function testFormatPhoneNumberSafelyLogsInvalidNumberMasked()
    {
        $this->class->formatPhoneNumberSafely('+3362345678', 'DE');

        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::on(function ($message) {
                return false !== strpos($message, '+33******78')
                    && false === strpos($message, '+3362345678')
                    && false !== strpos($message, '"DE"');
            }), 'error')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testStandardPaymentMethodIsNotPhoneRegionStrict()
    {
        $this->assertFalse($this->class->isPhoneRegionStrict());
    }

    /**
     * @description French numbers on an address resolved to DE (a French customer
     * shipping to Germany) are valid numbers, so they are sent in E.164.
     */
    public function testCrossBorderPhoneNumbersAreSentInE164()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'DE',
        ]);
        $this->dependencies->configClass = $configClass;

        $payment_tab = $this->class->getDefaultPaymentTab();

        $this->assertSame('DE', $payment_tab['billing']['country']);
        $this->assertSame('+33123456789', $payment_tab['billing']['landline_phone_number']);
        $this->assertSame('+33623456789', $payment_tab['billing']['mobile_phone_number']);
        $this->assertSame('+33123456789', $payment_tab['shipping']['landline_phone_number']);
        $this->assertSame('+33623456789', $payment_tab['shipping']['mobile_phone_number']);
    }

    /**
     * @description The Payplug API refuses an empty string phone number (HTTP 400)
     * but accepts a null one.
     */
    public function testInvalidPhoneNumbersAreNullNeverEmptyString()
    {
        $address = $this->address_adapter->get(42);
        $address->phone = '0612345';
        $address->phone_mobile = '+3362345678';

        $payment_tab = $this->class->getDefaultPaymentTab();

        foreach (['billing', 'shipping'] as $type) {
            foreach (['landline_phone_number', 'mobile_phone_number'] as $field) {
                $this->assertArrayHasKey($field, $payment_tab[$type]);
                $this->assertNull($payment_tab[$type][$field]);
            }
        }
    }

    public function testForeignMobileIsCopiedToEmptyLandline()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'BE',
        ]);
        $this->dependencies->configClass = $configClass;
        $address = $this->address_adapter->get(42);
        $address->phone = '';
        $address->phone_mobile = '0033609860479';

        $payment_tab = $this->class->getDefaultPaymentTab();

        foreach (['billing', 'shipping'] as $type) {
            $this->assertSame('+33609860479', $payment_tab[$type]['landline_phone_number']);
            $this->assertSame('+33609860479', $payment_tab[$type]['mobile_phone_number']);
        }
    }

    /**
     * @description Billing and shipping share the address fixture: the mobile is
     * only valid for the shipping country.
     */
    public function testInvalidBillingPhoneDoesNotAffectShippingPhone()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive('getIsoCodeByCountryId')
            ->andReturn('BE', 'FR');
        $this->dependencies->configClass = $configClass;
        $address = $this->address_adapter->get(42);
        $address->phone = '';
        $address->phone_mobile = '0612345678';

        $payment_tab = $this->class->getDefaultPaymentTab();

        $this->assertSame('BE', $payment_tab['billing']['country']);
        $this->assertNull($payment_tab['billing']['landline_phone_number']);
        $this->assertNull($payment_tab['billing']['mobile_phone_number']);
        $this->assertSame('FR', $payment_tab['shipping']['country']);
        $this->assertSame('+33612345678', $payment_tab['shipping']['landline_phone_number']);
        $this->assertSame('+33612345678', $payment_tab['shipping']['mobile_phone_number']);
    }

    public function invalidPhoneNumberProvider()
    {
        return [
            'too short for IT' => ['234234234', 'IT'],
            'truncated FR number on a DE address' => ['+3362345678', 'DE'],
            'metropolitan national number on a BE address' => ['0612345678', 'BE'],
            'empty iso' => ['0612345678', ''],
        ];
    }

    /**
     * @description maskPhoneNumberForLogging() must never write a customer's
     * full phone number to the logs (Kibana/support exports), per code review
     * on this PR: keep only enough of it (first 3, last 2 characters) to
     * correlate a log line with a case.
     */
    public function testMaskPhoneNumberForLoggingKeepsOnlyPrefixAndSuffix()
    {
        $this->assertSame('+33*******78', $this->class->maskPhoneNumberForLogging('+33612345678'));
        $this->assertSame('*****', $this->class->maskPhoneNumberForLogging('01234'));
    }
}
