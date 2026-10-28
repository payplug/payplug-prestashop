<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group standard_payment_method_class
 */
class getDefaultPaymentTabTest extends BaseStandardPaymentMethod
{
    use MockeryPHPUnitIntegration;

    protected $validate_amount_calls;

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
            ->andReturn('EUR;JPY');
        $this->validate_amount_calls = [];
        $this->helpers['amount']->shouldReceive('validateAmount')
            ->andReturnUsing(function () {
                $this->validate_amount_calls[] = func_get_args();

                return [
                    'result' => true,
                ];
            })
            ->byDefault();
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

    /**
     * @dataProvider foreignValidPhoneNumberProvider
     *
     * @param string $phone_number
     * @param string $iso_code
     * @param string $expected
     */
    public function testFormatPhoneNumberSafelyAcceptsPhoneNumberValidForADifferentCountry($phone_number, $iso_code, $expected)
    {
        $this->assertSame($expected, $this->class->formatPhoneNumberSafely($phone_number, $iso_code));
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
     * @description The Payplug API refuses an empty string phone number but accepts
     * a null one.
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

    public function foreignValidPhoneNumberProvider()
    {
        return [
            'FR mobile on a DE address' => ['+33612345678', 'DE', '+33612345678'],
            'FR mobile with 00 prefix on a BE address' => ['0033609860479', 'BE', '+33609860479'],
            'BE mobile with 00 prefix on a FR address' => ['0032470123456', 'FR', '+32470123456'],
            'BE mobile on a FR address' => ['+32470123456', 'FR', '+32470123456'],
            'territory number on a FR address' => ['+596696123456', 'FR', '+596696123456'],
        ];
    }

    public function invalidPhoneNumberProvider()
    {
        return [
            'too short for IT' => ['234234234', 'IT'],
            'truncated FR number on a DE address' => ['+3362345678', 'DE'],
            'metropolitan national number on a BE address' => ['0612345678', 'BE'],
            'empty iso' => ['0612345678', ''],
            'unknown region' => ['+33612345678', 'ZZ'],
            'empty phone' => ['', 'FR'],
        ];
    }

    public function testMobileIsCopiedToEmptyLandline()
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'BE',
        ]);
        $this->dependencies->configClass = $configClass;
        $address = $this->address_adapter->get(42);
        $address->phone = '';
        $address->phone_mobile = '0470 12 34 56';

        $payment_tab = $this->class->getDefaultPaymentTab();

        foreach (['billing', 'shipping'] as $type) {
            $this->assertSame('+32470123456', $payment_tab[$type]['landline_phone_number']);
            $this->assertSame('+32470123456', $payment_tab[$type]['mobile_phone_number']);
        }
    }

    public function testValidMobileIsCopiedToInvalidLandline()
    {
        $address = $this->address_adapter->get(42);
        $address->phone = '0612345';
        $address->phone_mobile = '0612345678';

        $payment_tab = $this->class->getDefaultPaymentTab();

        foreach (['billing', 'shipping'] as $type) {
            $this->assertSame('+33612345678', $payment_tab[$type]['landline_phone_number']);
            $this->assertSame('+33612345678', $payment_tab[$type]['mobile_phone_number']);
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

    public function testAmountIsValidatedAndConvertedInTheCartCurrency()
    {
        $this->context->currency->iso_code = 'JPY';

        $this->class->getDefaultPaymentTab();

        $this->helpers['amount']->shouldHaveReceived('validateAmount')
            ->with(\Mockery::type('array'), 42.42, 'JPY')
            ->once();
        $this->amountHelper->shouldHaveReceived('convertAmount')
            ->with(42.42, false, 'JPY')
            ->once();
        $this->addToAssertionCount(1);
    }

    public function testCardPaymentIsNotCheckedAgainstApmLimits()
    {
        $apm_eligibility = \Mockery::mock('ApmEligibility');
        $apm_eligibility->shouldReceive([
            'isPpro' => false,
        ]);
        $apm_eligibility->shouldNotReceive('isEligible');
        $this->class->set('apm_eligibility', $apm_eligibility);

        $this->assertNotEmpty($this->class->getDefaultPaymentTab());
    }

    public function testTheCartAmountIsValidatedInTheCartCurrency()
    {
        $this->class->getDefaultPaymentTab();

        $this->assertSame(
            [[['min' => 'EUR:30', 'max' => 'EUR:2000000'], 42.42, 'EUR']],
            $this->validate_amount_calls
        );
    }

    /**
     * @dataProvider amountLimitsProvider
     *
     * @param string $amounts
     * @param string $currency
     * @param int $cents cart amount once converted in the cart currency
     * @param bool $expected
     */
    public function testListingAndPaymentCreationAgreeOnTheAmountLimits($amounts, $currency, $cents, $expected)
    {
        $this->context->currency->iso_code = $currency;
        $this->dependencies->configClass->shouldReceive('getImgLang')->andReturn('fr');
        $this->configuration->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn($amounts);
        $this->helpers['amount']->shouldReceive('validateAmount')->passthru();
        $this->helpers['amount']->shouldReceive('convertAmount')
            ->with(42.42, false, $currency)
            ->andReturn($cents);

        $this->assertSame($expected, array_key_exists('standard', $this->class->getPaymentOption([])));
        $this->assertSame($expected, [] !== $this->class->getDefaultPaymentTab());
    }

    public function amountLimitsProvider()
    {
        return [
            'currency in the limits, amount within' => ['{"default":{"min":"EUR:30","max":"EUR:2000000"}}', 'EUR', 4242, true],
            'currency in the limits, amount above' => ['{"default":{"min":"EUR:30","max":"EUR:4000"}}', 'EUR', 4242, false],
            'currency missing from the limits' => ['{"default":{"min":"EUR:30","max":"EUR:4000"}}', 'JPY', 42, true],
            'currency missing from the limits and not convertible' => ['{"default":{"min":"EUR:30","max":"EUR:2000000"}}', 'JPY', 0, false],
            'only min, amount above' => ['{"default":{"min":"EUR:30"}}', 'EUR', 4242, true],
            'only min, amount below' => ['{"default":{"min":"EUR:5000"}}', 'EUR', 4242, false],
            'only max, amount below' => ['{"default":{"max":"EUR:5000"}}', 'EUR', 4242, true],
            'only max, amount above' => ['{"default":{"max":"EUR:4000"}}', 'EUR', 4242, false],
        ];
    }

    public function testCardAmountIsLeftToTheApiWhenNoLimitIsCached()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('amounts')
            ->andReturn('{}');

        $this->assertNotEmpty($this->class->getDefaultPaymentTab());
        $this->assertSame([], $this->validate_amount_calls);
    }
}
