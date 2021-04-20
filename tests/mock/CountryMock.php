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

namespace PayPlug\tests\mock;

class CountryMock
{
    public static function get()
    {
        $country = new \stdClass();

        $country->id = 1;
        $country->id_zone = 1;
        $country->id_currency = 1;
        $country->iso_code = "FR";
        $country->call_prefix = "33";
        $country->name = [
            1 => "France",
            2 => "France",
            3 => "France",
            4 => "France",
        ];
        $country->active = 1;
        $country->id_lang = null;

        return $country;
    }
}
