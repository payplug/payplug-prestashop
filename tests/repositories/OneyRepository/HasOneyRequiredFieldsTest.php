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
final class HasOneyRequiredFieldsTest extends TestCase
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

    public function testPaymentDataIsArray()
    {
        $this->assertTrue(
            is_array($this->payment_data)
        );
    }

    public function testShippingIsArray()
    {
        $this->assertTrue(
            is_array($this->payment_data['shipping'])
        );
    }

    public function testShippingEmail()
    {
        $this->assertSame(
            'customer@payplug.com',
            $this->payment_data['shipping']['email']
        );
    }

    public function testShippingEmailAString()
    {
        $this->assertTrue(
            is_string($this->payment_data['shipping']['email'])
        );
    }

    public function testShippingEmailHaveAValidEmailFormat()
    {
        $this->assertRegExp(
            '/^([\w\.\-_]+)?\w+@[\w-_]+(\.\w+){1,}$/',
            $this->payment_data['shipping']['email']
        );
    }

    public function testShippingEmailIsShortEnough()
    {
        $condition = strlen($this->payment_data['shipping']['email']) < 100;
        $this->assertTrue($condition);
    }

    public function testShippingCity()
    {
        $this->assertSame(
            'Paris',
            $this->payment_data['shipping']['city']
        );
    }

    public function testShippingCityAString()
    {
        $this->assertTrue(
            is_string($this->payment_data['shipping']['city'])
        );
    }

    public function testShippingCityIsShortEnough()
    {
        $condition = strlen($this->payment_data['shipping']['city']) < 32;
        $this->assertTrue($condition);
    }

    public function testShippingMobilePhone()
    {
        $this->assertSame(
            '+33623456789',
            $this->payment_data['shipping']['mobile_phone_number']
        );
    }

    public function testShippingMobilePhoneAString()
    {
        $this->assertTrue(
            is_string($this->payment_data['shipping']['mobile_phone_number'])
        );
    }

    public function testShippingMobilePhoneHaveAValidPhoneFormat()
    {
        $this->assertRegExp(
            '/^(?:0|\+?33)?([6-7]\d{5,12})$/',
            $this->payment_data['shipping']['mobile_phone_number']
        );
    }

    public function testBillingCity()
    {
        $this->assertSame(
            'Paris',
            $this->payment_data['billing']['city']
        );
    }

    public function testBillingCityAString()
    {
        $this->assertTrue(
            is_string($this->payment_data['billing']['city'])
        );
    }

    public function testBillingCityIsShortEnough()
    {
        $condition = strlen($this->payment_data['billing']['city']) < 32;
        $this->assertTrue($condition);
    }

    public function testBillingMobilePhone()
    {
        $this->assertSame(
            '+33623456789',
            $this->payment_data['billing']['mobile_phone_number']
        );
    }

    public function testBillingMobilePhoneAString()
    {
        $this->assertTrue(
            is_string($this->payment_data['billing']['mobile_phone_number'])
        );
    }

    public function testBillingMobilePhoneHaveAValidPhoneFormat()
    {
        $this->assertRegExp(
            '/^(?:0|\+?33)?([6-7]\d{5,12})$/',
            $this->payment_data['billing']['mobile_phone_number']
        );
    }
}
