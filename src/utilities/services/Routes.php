<?php
/**
 * 2013 - 2023 Payplug SAS
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
 * @author    Payplug SAS
 * @copyright 2013 - 2023 Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\utilities\services;

use PayPlug\src\exceptions\BadParameterException;
use Symfony\Component\Dotenv\Dotenv;

class Routes
{
    /**
     * @description Get the Api url
     *
     * @throws BadParameterException
     *
     * @return string
     */
    public function getApiUrl()
    {
        $dotenv = new Dotenv();
        $dotenvFile = \dirname(__FILE__, 5) . '/payplugroutes/.env';
        if (\file_exists($dotenvFile)) {
            $dotenv->load($dotenvFile);
            $api_url = $_ENV['API_BASE_URL'];
        } else {
            $api_url = 'https://api.payplug.com';
        }

        if (!is_string($api_url)
            || !preg_match('/http(s?):\/\/api(-\w+|\.\w+)?.(payplug|notpayplug).(com|test)/', $api_url)) {
            throw (new BadParameterException('Invalid argument, $api_url must be a a valid api url format'));
        }

        return $api_url;
    }

    /**
     * @description Get the Api url
     *
     * @throws BadParameterException
     *
     * @return string
     */
    public function getCDNUrl()
    {
        $dotenv = new Dotenv();
        $dotenvFile = \dirname(__FILE__, 5) . '/payplugroutes/.env';
        if (\file_exists($dotenvFile)) {
            $dotenv->load($dotenvFile);
            $cdn_url = $_ENV['CDN_BASE_URL'];
        } else {
            $cdn_url = 'https://cdn.payplug.com';
        }

        return $cdn_url;
    }

    /**
     * @description get CDN url
     *
     * @return array
     */
    public function getSourceUrl()
    {
        return [
            'embedded' => $this->getApiUrl() . '/js/1/form.latest.js',
            'integrated' => $this->getCDNUrl() . '/js/integrated-payment/v1@1/index.js',
        ];
    }

    /**
     * @description Get external url
     *
     * @param string $iso_code
     *
     * @return array
     */
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
            'forgot_password' => 'https://portal.payplug.com/forgot_password',
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
            'portal' => 'https://portal.payplug.com/',
            'refund' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360022214692',
            'sandbox' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/360021142492',
            'signup' => 'https://portal.payplug.com/signup',
            'embedded' => 'https://support.payplug.com/hc/' . $iso_code . '/articles/4409698334098',
        ];
    }
}
