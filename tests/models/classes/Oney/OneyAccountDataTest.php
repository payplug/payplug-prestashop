<?php

namespace PayPlug\tests\models\classes\Oney;

use PayPlug\src\models\classes\Oney\OneyAccountData;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group class
 * @group oney_account_data_class
 */
final class OneyAccountDataTest extends TestCase
{
    const RAW_ACCOUNT_DATA = [
        'enabled' => true,
        'allowed_countries' => ['PF', 'GP', 'BL', 'FR', 'MF', 'MQ', 'GF', 'NC', 'RE', 'YT'],
        'min_amounts' => ['EUR' => 10000],
        'max_amounts' => ['EUR' => 300000],
        'show_legal_notices' => true,
        'countries_metadata' => [
            'FR' => [
                'merchant_guid' => 'd5104abda4e74c45a78c08901107bb08',
                'psp_guid' => '0699c7373e544d038d930072e10cd575',
                'oney_business_codes' => [
                    'x3_with_fees' => 'W3135',
                    'x4_with_fees' => 'W4144',
                    'x3_without_fees' => 'DLN04',
                    'x4_without_fees' => 'DLN05',
                ],
            ],
        ],
    ];

    public function testMapsAllFieldsFromAccountResponse()
    {
        $data = OneyAccountData::fromAccountResponse(self::RAW_ACCOUNT_DATA);

        $this->assertTrue($data->isEnabled());
        $this->assertTrue($data->showLegalNotices());
        $this->assertSame('d5104abda4e74c45a78c08901107bb08', $data->getMerchantGuid('FR'));
        $this->assertSame('0699c7373e544d038d930072e10cd575', $data->getPspGuid('FR'));
        $this->assertSame('W3135', $data->getBusinessCodes('FR')->get('x3', true));
        $this->assertSame(
            ['W3135', 'W4144', 'DLN04', 'DLN05'],
            $data->getBusinessCodes('FR')->toList()
        );
    }

    public function testMerchantGuidAndBusinessCodesAreCaseInsensitiveByCountry()
    {
        $data = OneyAccountData::fromAccountResponse(self::RAW_ACCOUNT_DATA);

        $this->assertSame('d5104abda4e74c45a78c08901107bb08', $data->getMerchantGuid('fr'));
        $this->assertSame(['W3135', 'W4144', 'DLN04', 'DLN05'], $data->getBusinessCodes('fr')->toList());
    }

    public function testReturnsNullOrEmptyForCountryWithoutMetadata()
    {
        $data = OneyAccountData::fromAccountResponse(self::RAW_ACCOUNT_DATA);

        $this->assertNull($data->getMerchantGuid('IT'));
        $this->assertNull($data->getPspGuid('IT'));
        $this->assertSame([], $data->getBusinessCodes('IT')->toList());
    }

    public function testHandlesBusinessCodesExplicitlyNullForACountry()
    {
        $data = OneyAccountData::fromAccountResponse([
            'countries_metadata' => [
                'FR' => [
                    'merchant_guid' => 'd5104abda4e74c45a78c08901107bb08',
                    'oney_business_codes' => null,
                    'psp_guid' => '0699c7373e544d038d930072e10cd575',
                ],
            ],
        ]);

        $this->assertSame('d5104abda4e74c45a78c08901107bb08', $data->getMerchantGuid('FR'));
        $this->assertSame([], $data->getBusinessCodes('FR')->toList());
    }

    public function testHandlesMissingOptionalFieldsGracefully()
    {
        $data = OneyAccountData::fromAccountResponse([]);

        $this->assertFalse($data->isEnabled());
        $this->assertNull($data->getMerchantGuid('FR'));
        $this->assertFalse($data->showLegalNotices());
        $this->assertSame([], $data->getBusinessCodes('FR')->toList());
        $this->assertFalse($data->isCountryAllowed('FR'));
        $this->assertFalse($data->isAmountEligible(10000, 'EUR'));
    }

    public function testGetAllowedCountriesReturnsRawList()
    {
        $data = OneyAccountData::fromAccountResponse(self::RAW_ACCOUNT_DATA);

        $this->assertSame(
            ['PF', 'GP', 'BL', 'FR', 'MF', 'MQ', 'GF', 'NC', 'RE', 'YT'],
            $data->getAllowedCountries()
        );
    }

    public function testGetAllowedCountriesReturnsEmptyArrayWhenMissing()
    {
        $data = OneyAccountData::fromAccountResponse([]);

        $this->assertSame([], $data->getAllowedCountries());
    }

    public function testIsCountryAllowedIsCaseInsensitive()
    {
        $data = OneyAccountData::fromAccountResponse(self::RAW_ACCOUNT_DATA);

        $this->assertTrue($data->isCountryAllowed('FR'));
        $this->assertTrue($data->isCountryAllowed('fr'));
        $this->assertFalse($data->isCountryAllowed('DE'));
    }

    /**
     * @dataProvider amountEligibilityProvider
     *
     * @param int $amountInCents
     * @param bool $expected
     */
    public function testIsAmountEligible($amountInCents, $expected)
    {
        $data = OneyAccountData::fromAccountResponse(self::RAW_ACCOUNT_DATA);

        $this->assertSame($expected, $data->isAmountEligible($amountInCents, 'EUR'));
    }

    /**
     * @return array<string, array{0: int, 1: bool}>
     */
    public function amountEligibilityProvider()
    {
        return [
            'below minimum' => [9999, false],
            'exactly minimum (inclusive)' => [10000, true],
            'within bounds' => [150000, true],
            'exactly maximum (inclusive)' => [300000, true],
            'above maximum' => [300001, false],
        ];
    }

    public function testIsAmountEligibleReturnsFalseForUnknownCurrency()
    {
        $data = OneyAccountData::fromAccountResponse(self::RAW_ACCOUNT_DATA);

        $this->assertFalse($data->isAmountEligible(50000, 'USD'));
    }
}
