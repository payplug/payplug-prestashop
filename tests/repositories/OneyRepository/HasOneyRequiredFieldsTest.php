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
final class IsValidOneyAmountTest extends TestCase
{
    protected $payment_data;

    public function setUp()
    {
        $this->payment_data = [
            "currency" => "EUR",
            "shipping" => [
                "title" => null,
                "first_name" => "Lorem",
                "last_name" => "Ipsum",
                "company_name" => "Payplug",
                "email" => "customer@payplug.com",
                "landline_phone_number" => "+33123456789",
                "mobile_phone_number" => "+33623456789",
                "address1" => "1 rue de l'avenue",
                "address2" => null,
                "postcode" => "75000",
                "city" => "Paris",
                "country" => "FR",
                "language" => "fr",
                "delivery_type" => "BILLING",
            ],
            "billing" => [
                "title" => null,
                "first_name" => "Lorem",
                "last_name" => "Ipsum",
                "company_name" => "Payplug",
                "email" => "customer@payplug.com",
                "landline_phone_number" => "+33123456789",
                "mobile_phone_number" => "+33623456789",
                "address1" => "1 rue de l'avenue",
                "address2" => null,
                "postcode" => "75000",
                "city" => "Paris",
                "country" => "FR",
                "language" => "fr",
            ],
            "notification_url" => "http://prestashop.com/module/payplug/ipn",
            "force_3ds" => false,
            "hosted_payment" => [
                "return_url" => "http://prestashop.com/module/payplug/validation?ps=1&cartid=1",
                "cancel_url" => "http://prestashop.com/module/payplug/validation?ps=2&cartid=1",
            ],
            "metadata" => [
                "ID Client" => 1,
                "ID Cart" => 1,
                "Website" => "http://prestashop.com",
            ],
            "allow_save_card" => true,
            "authorized_amount" => 10277,
        ];
    }
}

//public function hasOneyRequiredFields($payment_data = [])
//{
//    if (!$payment_data) {
//        return false;
//    }
//
//    $tools = $this->toolsSpecific;
//
//    // Check the shipping fields
//    $shipping = $payment_data['shipping'];
//
//    // Validate email format
//    if ($tools->tool('strlen', $shipping['email'], 'UTF-8') > 100
//        && $tools->tool('$shipping[\'email\']', '+') !== false) {
//        return true;
//    } elseif ($tools->tool('strlen', $shipping['email'], 'UTF-8') > 100) {
//        return true;
//    } elseif (strpos($shipping['email'], '+') !== false) {
//        return true;
//    }
//
//    // Validate phone number
//    $valid_shipping_mobile = $this->payplug->isValidMobilePhoneNumber(
//        $shipping['mobile_phone_number'],
//        $shipping['country']
//    );
//    if (!$valid_shipping_mobile) {
//        return true;
//    }
//
//    // Validate address
//    if ($tools->tool('strlen', $shipping['city'], 'UTF-8') > 32) {
//        return true;
//    }
//
//    // Check the billing fields
//    $billing = $payment_data['billing'];
//
//    // Validate phone number
//    $valid_billing_mobile = $this->payplug->isValidMobilePhoneNumber(
//        $billing['mobile_phone_number'],
//        $billing['country']
//    );
//    if (!$valid_billing_mobile) {
//        return true;
//    }
//
//    // Validate address
//    if ($tools->tool('strlen', $billing['city'], 'UTF-8') > 32) {
//        return true;
//    }
//
//    return false;
//}
