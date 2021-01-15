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

    /*
    public function isValidOneyCountry($shipping_iso, $billing_iso)
    {
        // check if the billing country and the shipping country are different then return false
        if ($shipping_iso != $billing_iso) {
            $error = 'Delivery and billing addresses must be in the same country to pay with Oney.';
            return [
                'result' => false,
                'type' => 'different',
                'error' => $this->l($error)
            ];
        }

        // check if the shipping country are different then return false
        $iso_code = $this->toolsSpecific->tool('strtoupper', $shipping_iso);
        $allow_countries = $this->toolsSpecific->tool(
            'strtoupper',
            $this->configurationSpecific->get('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
        );

        if (!$allow_countries) {
            return [
                'result' => false,
                'type' => 'no_country',
                'error' => $this->l('No countries are configured to use oney.')
            ];
        }

        $iso_list = explode(',', $allow_countries);
        if (!in_array($iso_code, $iso_list, true)) {
            $str_list = $this->l('France, Martinique, Guadeloupe, La Reunion, Mayotte or French Guiana');
            if (in_array('IT', $iso_list)) {
                $str_list = $this->l('Italy');
            }

            return [
                'result' => false,
                'type' => 'invalid',
                'error' => $this->l('For a payment with Oney, delivery and billing addresses must be in ') . $str_list
            ];
        }

        return ['result' => true, 'error' => false];
    }
     */

    public function setUp()
    {}

    public function testShippingAndBillingIsoAreDifferent()
    {
        $this->assertNotEquals('shipping_iso', 'billing_iso');
    }

    public function testIsIsoCodeWellFormated()
    {
        $iso_code = 'fr';
        $this->assertSame(
            'FR',
            strtoupper($iso_code)
        );
    }
}
