<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
class getPaymentTabTest extends BaseOneyPaymentMethod
{
    public $expected_tab;
    public $oney;
    public $oney_schedule;
    public $oney_context;

    public function setUp(): void
    {
        parent::setUp();
        $this->oney_schedule = '2';
        $this->oney_context = 'oney_context';
        $this->class->shouldReceive([
            'isValidOneyCartQty' => [
                'result' => true,
            ],
            'isValidOneyAddresses' => [
                'result' => true,
            ],
            'isValidOneyAmount' => [
                'result' => true,
            ],
            'getOneyPaymentContext' => $this->oney_context,
        ]);

        $this->configuration->shouldReceive('getValue')
            ->with('PS_TAX')
            ->andReturn(42);
        $this->tools_adapter->shouldReceive('tool')
            ->withArgs(['getValue', 'payplugOney_type'])
            ->andReturn(2);
        $this->tools_adapter->shouldReceive('tool')
            ->withArgs(['getValue', 'io'])
            ->andReturn(1);
        $this->helpers['cookies']->shouldReceive([
            'setPaymentErrorsCookie' => true,
        ]);
    }

    public function testWhenParentMethodReturnEmptyArray()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => [],
        ]);
        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenCurrentCartIsntElligible()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);

        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => false,
                'code' => 'product_quantity',
            ],
        ]);

        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testWhenRequiredFieldsIsNeeded()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
            'hasOneyRequiredFields' => true,
        ]);
        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
            ],
        ]);
        $this->helpers['cookies']->shouldReceive([
            'getPaymentDataCookie' => true,
        ]);

        $this->assertSame(
            [],
            $this->class->getPaymentTab()
        );
    }

    public function testPaymentTabIsReturned()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
            'hasOneyRequiredFields' => false,
        ]);
        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
            ],
        ]);

        $expected_tab = $this->default_payment_tab;
        $expected_tab['authorized_amount'] = $expected_tab['amount'];
        $expected_tab['force_3ds'] = false;
        $expected_tab['auto_capture'] = true;
        $expected_tab['payment_method'] = 'oney_' . $this->oney_schedule;
        $expected_tab['payment_context'] = $this->oney_context;
        $expected_tab['hosted_payment']['return_url'] = 'link';

        unset($expected_tab['allow_save_card'], $expected_tab['amount']);

        $this->assertSame(
            $expected_tab,
            $this->class->getPaymentTab()
        );
    }

    /**
     * Documents that the landline-to-mobile fallback branch, previously
     * always a structural no-op because of the isMobilePhoneNumber()
     * guard-clause bug, is now reachable: when the billing
     * mobile_phone_number field is not a valid mobile number but the
     * landline_phone_number field is, the code copies the landline value
     * into mobile_phone_number.
     *
     * Note: $this->dependencies->getHelpers()['phone'] is invoked in
     * production code via static syntax (::isMobilePhoneNumber()), which
     * Mockery partial mocks cannot intercept. This means the call goes
     * through to the real PhoneHelper::isMobilePhoneNumber() implementation,
     * so genuine, correctly classified phone number fixtures are required
     * below (not mocked).
     */
    public function testFallsBackToLandlineNumberWhenBillingMobileNumberIsNotAValidMobile()
    {
        $payment_tab = $this->default_payment_tab;
        // '+33123456789' is a valid FR landline number, but not a valid mobile number.
        $payment_tab['billing']['mobile_phone_number'] = '+33123456789';
        // '+33612345678' is a genuine FR mobile number.
        $payment_tab['billing']['landline_phone_number'] = '+33612345678';

        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $payment_tab,
            'hasOneyRequiredFields' => false,
        ]);
        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
            ],
        ]);

        $result = $this->class->getPaymentTab();

        $this->assertSame('+33612345678', $result['billing']['mobile_phone_number']);
    }

    /**
     * Documents hydratePaymentTabFromPaymentData() — a private method with no
     * previous direct test coverage, since every other path mocks
     * hasOneyRequiredFields() to false and short-circuits before it's reached.
     * It is exercised here through its only caller, getPaymentTab(): the first
     * hasOneyRequiredFields() check reports missing fields, the cookie/form
     * payment data is hydrated into the payment tab, and the recheck reports
     * the fields are now satisfied.
     *
     * '0612345678' is a genuine local-format FR mobile number; the real
     * formatPhoneNumberSafely() call reachable from hydratePaymentTabFromPaymentData()
     * converts it to E.164 ('+33612345678'), verified empirically beforehand.
     */
    public function testHydratesPaymentTabFromPaymentDataOnRequiredFieldsRecheck()
    {
        $this->class->shouldReceive([
            'getDefaultPaymentTab' => $this->default_payment_tab,
        ]);
        $this->class->shouldReceive('hasOneyRequiredFields')
            ->andReturn(true, false);
        $this->validators['payment']->shouldReceive([
            'isOneyElligible' => [
                'result' => true,
            ],
        ]);
        $this->helpers['cookies']->shouldReceive([
            'getPaymentDataCookie' => [
                'billing-mobile_phone_number' => '0612345678',
            ],
        ]);

        $result = $this->class->getPaymentTab();

        $this->assertSame('+33612345678', $result['billing']['mobile_phone_number']);
    }
}
