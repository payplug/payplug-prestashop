<?php

namespace PayPlug\tests\models\classes\paymentMethod\BancontactPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group bancontact_payment_method_class
 */
class getOptionTest extends BaseBancontactPaymentMethod
{
    public $config;

    public function setUp(): void
    {
        parent::setUp();
        $this->config = [
            'bancontact' => true,
        ];
    }

    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption($this->config)['available_test_mode']);
    }

    public function testTheBelgiumOnlySwitchIsNoLongerOffered()
    {
        $this->assertArrayNotHasKey('options', $this->class->getOption($this->config));
    }

    public function testTheMerchantCanSelectTheBillingCountries()
    {
        $this->country_adapter->shouldReceive('getCountries')
            ->andReturn([
                ['iso_code' => 'BE', 'name' => 'Belgique'],
                ['iso_code' => 'FR', 'name' => 'France'],
            ]);

        $countries = $this->class->getOption($this->config)['countries'];

        $this->assertSame('bancontact_countries', $countries['name']);
        $this->assertSame(['BE', 'FR'], $countries['value']);
    }
}
