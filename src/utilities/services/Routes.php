<?php
/**
 * 2013 - 2023 PayPlug SAS
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
 * @copyright 2013 - 2023 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\utilities\services;

class Routes
{
    public function getExternalUrl($iso_code = '')
    {
        if (!is_string($iso_code)) {
            // todo: add log
            return [];
        }

        if (!$iso_code) {
            $iso_code = 'fr';
        }

        if ($iso_code == 'en') {
            $iso_code = 'en-gb';
        }

        return [
            'activation' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021328991',
            'amex' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/5701208563996',
            'applepay' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/5149384347292',
            'bancontact' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/4408157435794',
            'contact' => 'https://www.payplug.com/contact',
            'deferred' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360010088420',
            'forgot_password' => 'https://www.payplug.com/portal/forgot_password',
            'guide' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360011715080',
            'help' => 'https://support.payplug.com/hc/' . $iso_code . '/requests/new',
            'install' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021389891',
            'installments' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022447972',
            'integrated' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021390191',
            'mail' => 'mailto:support@payplug.com',
            'one_click' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022213892',
            'oney' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360013071080',
            'oney_cgv' => 'https://portal.payplug.com/#/configuration/oney',
            'order_state' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/4406805105298',
            'payment_page' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142312',
            'portal' => 'https://www.payplug.com/portal',
            'refund' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022214692',
            'sandbox' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142492',
            'signup' => 'https://www.payplug.com/signup',
            'support' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/4409698334098',
        ];
    }
}
