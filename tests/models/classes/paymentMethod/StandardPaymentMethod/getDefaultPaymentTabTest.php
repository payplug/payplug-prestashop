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
        $this->assertSame('', $result);

        // Also verify a properly formatted loose number (no trailing text) is accepted
        $result_valid = $this->class->formatPhoneNumberSafely('01 23 45 67 89', 'FR');
        // This should attempt formatting (not testing the full format here, just
        // that it's not immediately rejected by the regex guard)
        $this->assertNotEquals('', $result_valid);
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

    /**
     * @description Documents the cross-border behavior tightening this PR
     * introduces on the live checkout path (PRE-3591): formatPhoneNumberSafely()
     * now rejects a phone number that is valid, but not for the given country,
     * instead of formatting it through as-is. '+32470123456' is a genuine
     * Belgian mobile number, submitted here with country 'FR' to reproduce the
     * scenario flagged in the PR description (a Belgian/Swiss mobile number on
     * a French address).
     */
    public function testFormatPhoneNumberSafelyRejectsPhoneNumberValidForADifferentCountry()
    {
        $result = $this->class->formatPhoneNumberSafely('+32470123456', 'FR');

        $this->assertSame('', $result);
    }
}
