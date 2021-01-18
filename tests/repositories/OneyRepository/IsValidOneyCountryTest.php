<?php
/**
 * 2013 - 2021 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 * @author    PayPlug SAS
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

use PHPUnit\Framework\TestCase;

/**
 * @group repository
 * @group oney
 * @group oney_repository
 */
final class IsValidOneyCountryTest extends TestCase
{
    protected $isoCode;
    protected $allowCountries;

    public function setUp()
    {
        $this->isoCode = 'FR';
        $this->allowCountries = 'GF,GP,YT,MQ,RE,FR';
    }

    public function testShippingAndBillingIsoAreDifferent()
    {
        $this->assertNotEquals('shipping_iso', 'billing_iso');
    }

    public function testIsoCode()
    {
        $this->assertSame(
            'FR',
            $this->isoCode
        );
    }

    public function testIsoCodeIsAString()
    {
        $this->assertTrue(
            is_string($this->isoCode)
        );
    }

    public function testIsIsoCodeWellFormated()
    {
        $iso_code = 'fr';
        $this->assertSame(
            $this->isoCode,
            strtoupper($iso_code)
        );
    }

    public function testIsAllowCountriesWellFormated()
    {
        $allow_countries = 'gf,gp,yt,mq,re,fr';
        $this->assertSame(
            $this->allowCountries,
            strtoupper($allow_countries)
        );
    }

    public function testAllowCountries()
    {
        $allow_countries = 'GF,GP,YT,MQ,RE,FR';
        $this->assertSame(
            'GF,GP,YT,MQ,RE,FR',
            $this->allowCountries
        );
    }

    public function testAllowCountriesIsAString()
    {
        $this->assertTrue(
            is_string($this->allowCountries)
        );
    }

    public function testIsoCodeIsInAllowCountries()
    {
        $iso_list = explode(',', $this->allowCountries);
        $this->assertTrue(
            in_array($this->isoCode, $iso_list, true)
        );
    }
}
