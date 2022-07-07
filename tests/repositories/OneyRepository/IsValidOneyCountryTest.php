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

namespace PayPlug\tests\repositories\OneyRepository;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class IsValidOneyCountryTest extends BaseOneyRepository
{
    public function testWithDifferentIsoCode()
    {
        $shipping_iso = 'FR';
        $billing_iso = 'IT';
        $error = 'Delivery and billing addresses must be in the same country to pay with Oney.';

        $this->assertSame(
            [
                'result' => false,
                'type' => 'different',
                'error' => $error
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }

    public function testWithoutAllowCountries()
    {
        $shipping_iso = $billing_iso = 'FR';

        $this->config->shouldReceive('get')
            ->andReturnUsing(function ($key) {
                switch ($key) {
                    case 'PS_CURRENCY_DEFAULT':
                        return 1;
                    case 'PAYPLUG_ONEY_MIN_AMOUNTS':
                        return 'EUR:10000';
                    case 'PAYPLUG_ONEY_MAX_AMOUNTS':
                        return 'EUR:300000';
                    case 'PAYPLUG_ONEY_CUSTOM_MIN_AMOUNTS':
                        return 'EUR:100';
                    case 'PAYPLUG_ONEY_CUSTOM_MAX_AMOUNTS':
                        return 'EUR:3000';
                    case 'PS_SHOP_NAME':
                        return 'Payplug';
                    case 'PAYPLUG_ONEY_ALLOWED_COUNTRIES':
                        return '';
                    default:
                        return true;
                }
            });

        $this->assertSame(
            [
                'result' => false,
                'type' => 'no_country',
                'error' => 'No countries are configured to use oney.'
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }

    public function testWithDisallowCountries()
    {
        $shipping_iso = $billing_iso = 'wrong iso';

        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            ->andReturn('FR');

        $error = 'For a payment with Oney, delivery and billing addresses must be in ';
        $error .= 'France, Martinique, Guadeloupe, La Reunion, Mayotte or French Guiana';

        $this->assertSame(
            [
                'result' => false,
                'type' => 'invalid',
                'error' => $error
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }

    public function testWithValidIsoCode()
    {
        $shipping_iso = $billing_iso = 'FR';

        $this->config
            ->shouldReceive('get')
            ->with('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            ->andReturn('FR');

        $this->assertSame(
            [
                'result' => true,
                'error' => false
            ],
            $this->repo->isValidOneyCountry($shipping_iso, $billing_iso)
        );
    }

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
                'error' => $this->l('For a payment with Oney, delivery and billing addresses must be in') . ' ' .
                    $str_list
            ];
        }

        return ['result' => true, 'error' => false];
    }
}
