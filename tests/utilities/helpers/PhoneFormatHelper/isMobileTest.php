<?php

namespace PayPlug\tests\utilities\helpers\PhoneFormatHelper;

use PayPlug\src\utilities\helpers\PhoneFormatHelper;
use PHPUnit\Framework\TestCase;

/**
 * @group unit
 * @group helper
 * @group phone_format_helper
 */
class isMobileTest extends TestCase
{
    /**
     * @dataProvider mobileProvider
     *
     * @param string $phone
     * @param string $iso
     * @param bool $strict
     */
    public function testReturnsTrueForMobile($phone, $iso, $strict)
    {
        $this->assertTrue(PhoneFormatHelper::isMobile($phone, $iso, $strict));
    }

    /**
     * @dataProvider notMobileProvider
     *
     * @param mixed $phone
     * @param mixed $iso
     * @param bool $strict
     */
    public function testReturnsFalseWhenNotAUsableMobile($phone, $iso, $strict)
    {
        $this->assertFalse(PhoneFormatHelper::isMobile($phone, $iso, $strict));
    }

    public function testIsStrictByDefault()
    {
        $this->assertFalse(PhoneFormatHelper::isMobile('+32470123456', 'FR'));
    }

    public function mobileProvider()
    {
        return [
            'FR mobile on a FR address' => ['0612345678', 'FR', true],
            'BE mobile on a FR address, not strict' => ['+32470123456', 'FR', false],
            'metropolitan mobile on a MQ address' => ['0612345678', 'MQ', true],
            'Martinique mobile on a MQ address' => ['0696123456', 'MQ', true],
        ];
    }

    public function notMobileProvider()
    {
        return [
            'FR landline' => ['0123456789', 'FR', true],
            'BE mobile on a FR address, strict' => ['+32470123456', 'FR', true],
            'invalid number' => ['234234234', 'IT', true],
            'invalid number, not strict' => ['234234234', 'IT', false],
            'empty phone' => ['', 'FR', true],
            'empty iso' => ['0612345678', '', true],
            'null phone' => [null, 'FR', true],
            'non string iso' => ['0612345678', false, true],
            'array phone' => [['0612345678'], 'FR', true],
        ];
    }
}
