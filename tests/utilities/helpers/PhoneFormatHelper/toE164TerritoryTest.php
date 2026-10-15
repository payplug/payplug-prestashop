<?php

namespace PayPlug\tests\utilities\helpers\PhoneFormatHelper;

use PayPlug\src\utilities\helpers\PhoneFormatHelper;
use PayplugUnifiedCore\Exceptions\InvalidPhoneNumberException;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group phone_format_helper
 */
class toE164TerritoryTest extends TestCase
{
    /**
     * @dataProvider territoryProvider
     *
     * @param string $iso
     * @param bool $strict
     */
    public function testMetropolitanNationalMobileIsAcceptedOnTerritory($iso, $strict)
    {
        $this->assertSame('+33612345678', PhoneFormatHelper::toE164('0612345678', $iso, $strict));
        $this->assertSame('+33781234567', PhoneFormatHelper::toE164('07 81 23 45 67', $iso, $strict));
    }

    public function testMetropolitanInternationalNumberIsAcceptedOnTerritoryWhenStrict()
    {
        $this->assertSame('+33612345678', PhoneFormatHelper::toE164('+33612345678', 'MQ', true));
    }

    public function testTerritoryIsoCodeIsCaseInsensitive()
    {
        $this->assertSame('+33612345678', PhoneFormatHelper::toE164('0612345678', 'mq', true));
    }

    /**
     * @description The territory region is tried before FR: "06 96" is a Martinique
     * mobile prefix, so a national number starting with it on an MQ address stays
     * a Martinique number even though it is also a valid metropolitan mobile.
     */
    public function testLocalTerritoryNumberIsNotReinterpretedAsFrench()
    {
        $this->assertSame('+596696123456', PhoneFormatHelper::toE164('0696123456', 'MQ'));
        $this->assertSame('+596696123456', PhoneFormatHelper::toE164('06 96 12 34 56', 'MQ', true));
    }

    /**
     * @dataProvider nonTerritoryInvalidProvider
     *
     * @param string $iso
     */
    public function testNationalMobileIsNotFrenchOutsideTerritories($iso)
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneFormatHelper::toE164('0612345678', $iso);
    }

    /**
     * @dataProvider nonTerritoryValidProvider
     *
     * @param string $iso
     * @param string $expected
     */
    public function testNationalNumberValidLocallyOutsideTerritoriesStaysLocal($iso, $expected)
    {
        $this->assertSame($expected, PhoneFormatHelper::toE164('0612345678', $iso));
        $this->assertSame($expected, PhoneFormatHelper::toE164('0612345678', $iso, true));
    }

    public function testTerritoryNumberOnMetropolitanAddressIsAcceptedWhenNotStrict()
    {
        $this->assertSame('+596696123456', PhoneFormatHelper::toE164('+596696123456', 'FR'));
    }

    public function testTerritoryNumberOnMetropolitanAddressIsRejectedWhenStrict()
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneFormatHelper::toE164('+596696123456', 'FR', true);
    }

    public function testInvalidNumberOnTerritoryStillThrows()
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneFormatHelper::toE164('0612', 'MQ');
    }

    public function testTerritoryListIsTheFrenchNumberingOne()
    {
        $this->assertSame(
            ['GP', 'MQ', 'RE', 'GF', 'YT', 'PM', 'BL', 'MF', 'NC', 'PF', 'WF'],
            PhoneFormatHelper::FRENCH_NUMBERING_TERRITORIES
        );
    }

    public function territoryProvider()
    {
        $data = [];
        foreach (['GP', 'MQ', 'RE', 'GF', 'YT', 'PM', 'BL', 'MF', 'NC', 'PF', 'WF'] as $iso) {
            $data[$iso . ' not strict'] = [$iso, false];
            $data[$iso . ' strict'] = [$iso, true];
        }

        return $data;
    }

    public function nonTerritoryInvalidProvider()
    {
        return [
            'BE' => ['BE'],
            'AD' => ['AD'],
            'TF' => ['TF'],
        ];
    }

    public function nonTerritoryValidProvider()
    {
        return [
            'DE landline' => ['DE', '+49612345678'],
            'MC mobile' => ['MC', '+377612345678'],
        ];
    }
}
