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
class toE164Test extends TestCase
{
    public function testOwnCountryNumberIsFormatted()
    {
        $this->assertSame('+33612345678', PhoneFormatHelper::toE164('06 12 34 56 78', 'FR'));
    }

    public function testOwnCountryNumberIsFormattedWhenStrict()
    {
        $this->assertSame('+33612345678', PhoneFormatHelper::toE164('06 12 34 56 78', 'FR', true));
    }

    public function testIsoCodeIsCaseInsensitive()
    {
        $this->assertSame('+33612345678', PhoneFormatHelper::toE164('0612345678', 'fr'));
    }

    /**
     * @dataProvider foreignValidNumberProvider
     *
     * @param string $phone
     * @param string $iso
     * @param string $expected
     */
    public function testForeignValidNumberIsAcceptedWhenNotStrict($phone, $iso, $expected)
    {
        $this->assertSame($expected, PhoneFormatHelper::toE164($phone, $iso));
    }

    /**
     * @dataProvider foreignValidNumberProvider
     *
     * @param string $phone
     * @param string $iso
     */
    public function testForeignValidNumberIsRejectedWhenStrict($phone, $iso)
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneFormatHelper::toE164($phone, $iso, true);
    }

    /**
     * @dataProvider invalidNumberProvider
     *
     * @param string $phone
     * @param string $iso
     */
    public function testInvalidNumberThrowsWhenNotStrict($phone, $iso)
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneFormatHelper::toE164($phone, $iso);
    }

    /**
     * @dataProvider invalidNumberProvider
     *
     * @param string $phone
     * @param string $iso
     */
    public function testInvalidNumberThrowsWhenStrict($phone, $iso)
    {
        $this->expectException(InvalidPhoneNumberException::class);

        PhoneFormatHelper::toE164($phone, $iso, true);
    }

    public function foreignValidNumberProvider()
    {
        return [
            'FR mobile on a DE address' => ['+33612345678', 'DE', '+33612345678'],
            'FR mobile with 00 prefix on a BE address' => ['0033609860479', 'BE', '+33609860479'],
            'BE mobile with 00 prefix on a FR address' => ['0032470123456', 'FR', '+32470123456'],
        ];
    }

    public function invalidNumberProvider()
    {
        return [
            'too short for IT' => ['234234234', 'IT'],
            'truncated FR number on a DE address' => ['+3362345678', 'DE'],
            'letters' => ['abc', 'FR'],
            'empty' => ['', 'FR'],
            'unknown region' => ['0612345678', 'ZZ'],
        ];
    }
}
