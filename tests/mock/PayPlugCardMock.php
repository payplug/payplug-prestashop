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

class PayPlugCardMock
{
    public static function get()
    {
        $card = [
            'id_payplug_card' => 1,
            'id_customer' => 1,
            'id_company' => 123456,
            'is_sandbox' => 1,
            'id_card' => 'card_4242LoremIpsumSit42442',
            'last4' => 4242,
            'exp_month' => 6,
            'exp_year' => 2030,
            'brand' => 'Visa',
            'country' => 'FR',
            'metadata' => 'N;',
        ];

        return $card;
    }

    public static function getExpired()
    {
        $card = self::get();
        $card['exp_year'] = 2020;
        return $card;
    }
}
