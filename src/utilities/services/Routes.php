<?php
/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS.
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
 * @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */

namespace PayPlug\src\utilities\services;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Dotenv\Dotenv;

class Routes
{
    /**
     * @var bool whether payplugroutes/.env has already been parsed into this
     *           request. getSourceUrl() alone calls into getApiUrl()/getCDNUrl()/getOneyLoaderUrl(),
     *           so without this the same file would be parsed up to three times per call.
     */
    private static $dotenvLoaded = false;

    /**
     * @description Get the Api url
     *
     * @return string
     */
    public function getApiUrl()
    {
        $dotenvFile = \dirname(__FILE__, 5) . '/payplugroutes/.env';
        if (\file_exists($dotenvFile)) {
            $this->loadDotenv($dotenvFile);
        }

        return isset($_ENV['API_BASE_URL'])
            ? $_ENV['API_BASE_URL']
            : 'https://api.payplug.com';
    }

    /**
     * @description Get the CDN url
     *
     * @return string
     */
    public function getCDNUrl()
    {
        $dotenvFile = \dirname(__FILE__, 5) . '/payplugroutes/.env';
        if (\file_exists($dotenvFile)) {
            $this->loadDotenv($dotenvFile);
        }

        return isset($_ENV['CDN_BASE_URL'])
            ? $_ENV['CDN_BASE_URL']
            : 'https://cdn.payplug.com';
    }

    /**
     * @description Get the Oney widget loader url
     *
     * @return string
     */
    public function getOneyLoaderUrl()
    {
        $dotenvFile = \dirname(__FILE__, 5) . '/payplugroutes/.env';
        if (\file_exists($dotenvFile)) {
            $this->loadDotenv($dotenvFile);
        }

        return isset($_ENV['ONEY_LOADER_URL'])
            ? $_ENV['ONEY_LOADER_URL']
            : 'https://assets.oney.io/build/loader.min.js';
    }

    /**
     * @return array
     */
    public function getSourceUrl()
    {
        return [
            'applepay' => 'https://applepay.cdn-apple.com/jsapi/1.latest/apple-pay-sdk.js',
            'embedded' => $this->getApiUrl() . '/js/1/form.latest.js',
            'integrated' => $this->getCDNUrl() . '/js/integrated-payment/v1@1/index.js',
            'oney' => $this->getOneyLoaderUrl(),
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

        if ('en' == $iso_code) {
            $iso_code = 'en-gb';
        }

        $default = 'https://support.payplug.com/hc/' . $iso_code;

        return [
            'activation' => $default . '/articles/360021328991',
            'amex' => $default . '/articles/5701208563996',
            'applepay' => $default . '/articles/5149384347292',
            'bancontact' => $default . '/articles/4408157435794',
            'contact' => 'https://www.payplug.com/contact',
            'default' => $default,
            'deferred' => $default . '/articles/360010088420',
            'embedded' => $default . '/articles/4409698334098',
            'forgot_password' => 'https://portal.payplug.com/forgot_password',
            'guide' => $default . '/articles/360011715080',
            'help' => $default . '/requests/new',
            'ideal' => $default . '/articles/8089119071132',
            'install' => $default . '/articles/360021389891',
            'installments' => $default . '/articles/360022447972',
            'integrated' => $default . '/articles/360021390191',
            'mail' => 'mailto:support@payplug.com',
            'mybank' => $default . '/articles/8089123857564',
            'one_click' => $default . '/articles/360022213892',
            'oney' => $default . '/articles/360013071080',
            'oney_cgv' => 'https://portal.payplug.com/#/configuration/oney',
            'order_state' => $default . '/articles/4406805105298',
            'payment_page' => $default . '/articles/360021142312',
            'portal' => 'https://portal.payplug.com/',
            'refund' => $default . '/articles/360022214692',
            'sandbox' => $default . '/articles/360021142492',
            'satispay' => $default . '/articles/8089121532700',
            'signup' => 'https://portal.payplug.com/auth/signup',
        ];
    }

    /**
     * @description Parse payplugroutes/.env into $_ENV, at most once per request.
     *
     * @param string $dotenvFile
     */
    private function loadDotenv($dotenvFile)
    {
        if (self::$dotenvLoaded) {
            return;
        }

        (new Dotenv())->load($dotenvFile);
        self::$dotenvLoaded = true;
    }
}
