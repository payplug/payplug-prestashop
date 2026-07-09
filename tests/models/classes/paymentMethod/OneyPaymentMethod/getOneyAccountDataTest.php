<?php

namespace PayPlug\tests\models\classes\paymentMethod\OneyPaymentMethod;

use PayPlug\src\models\classes\Oney\OneyAccountData;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group oney_payment_method_class
 */
final class getOneyAccountDataTest extends BaseOneyPaymentMethod
{
    public function setUp(): void
    {
        parent::setUp();

        $this->configuration->shouldReceive('getValue')
            ->with('payment_methods')
            ->andReturn('{"oney":true}');
        $this->configuration->shouldReceive('getValue')
            ->with('oney_min_amounts')
            ->andReturn('{"EUR":10000}');
        $this->configuration->shouldReceive('getValue')
            ->with('oney_max_amounts')
            ->andReturn('{"EUR":300000}');
        $this->configuration->shouldReceive('getValue')
            ->with('oney_show_legal_notices')
            ->andReturn(1);
        $this->configuration->shouldReceive('getValue')
            ->with('oney_countries_metadata')
            ->andReturn(
                '{"FR":{"merchant_guid":"d5104abda4e74c45a78c08901107bb08","oney_business_codes":{"x3_with_fees":"W3135","x4_with_fees":"W4144","x3_without_fees":"DLN04","x4_without_fees":"DLN05"}}}'
            );
    }

    public function testItRebuildsOneyAccountDataFromConfiguration()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('FR,IT,ES,NL');

        $account_data = $this->class->getOneyAccountData();

        $this->assertInstanceOf(OneyAccountData::class, $account_data);
        $this->assertTrue($account_data->isEnabled());
        $this->assertSame('d5104abda4e74c45a78c08901107bb08', $account_data->getMerchantGuid('FR'));
        $this->assertNull($account_data->getMerchantGuid('IT'));
        $this->assertTrue($account_data->showLegalNotices());
        $this->assertTrue($account_data->isCountryAllowed('FR'));
        $this->assertFalse($account_data->isCountryAllowed('DE'));
        $this->assertTrue($account_data->isAmountEligible(10000, 'EUR'));
        $this->assertFalse($account_data->isAmountEligible(9999, 'EUR'));
        $this->assertSame('W3135', $account_data->getBusinessCodes('FR')->get('x3', true));
        $this->assertSame(
            ['W3135', 'W4144', 'DLN04', 'DLN05'],
            $account_data->getBusinessCodes('FR')->toList()
        );
    }

    public function testItHandlesEmptyAllowedCountries()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('oney_allowed_countries')
            ->andReturn('');

        $account_data = $this->class->getOneyAccountData();

        $this->assertFalse($account_data->isCountryAllowed('FR'));
    }
}
