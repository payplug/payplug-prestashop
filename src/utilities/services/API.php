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

use Payplug\Core\APIRoutes;
use Payplug\Core\HttpClient;
use Payplug\Exception\ConfigurationNotSetException;
use Payplug\Exception\NotFoundException;
use Payplug\Exception\UndefinedAttributeException;
use Payplug\Payment;
use Payplug\Payplug;
use PayPlug\src\exceptions\BadParameterException;
use Symfony\Component\Dotenv\Dotenv;

if (!defined('_PS_VERSION_')) {
    exit;
}

class API
{
    private $site_url = '';
    private $portal_url = '';
    private $api_url = '';
    private $api;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;

        $this->checkEnvironment();
        $this->setEnvironment();
    }

    /**
     * @todo: cover this method
     *
     * @param mixed $resource_id
     */
    public function abortPayment($resource_id = false)
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $resource_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'code' => 500,
                    'result' => false,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'resource' => Payment::abort($resource_id, $this->api),
                    'code' => 200,
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Create Payment from api for given data
     *
     * @param false $resource_id
     * @param array $data
     * @param mixed $page
     *
     * @return array
     */
    public function patchPayment($resource_id = false, $data = [])
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $resource_id given',
            ];
        }

        if (!$data || !is_array($data)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $data given',
            ];
        }

        $retrieve = $this->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Can\'t patch the payment: ' . $retrieve['message'],
            ];
        }

        $payment = $retrieve['resource'];

        try {
            $response = [
                'result' => true,
                'resource' => $payment->update($data),
                'code' => 200,
            ];
        } catch (\Exception $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @param false
     * @param mixed $resource_id
     * @param mixed $data
     * @todo: cover this method
     */
    public function patchApplePayPayment($resource_id = false, $data = [])
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $resource_id given',
            ];
        }

        if (!$data || !is_array($data)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $data given',
            ];
        }

        $retrieve = $this->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Can\'t patch the payment: ' . $retrieve['message'],
            ];
        }

        $payment = $retrieve['resource'];

        try {
            $response = [
                'result' => true,
                'resource' => $payment->updateApplePay($data),
                'code' => 200,
            ];
        } catch (\Exception $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @todo: cover this method
     *
     * @param mixed $resource_id
     */
    public function retrievePayment($resource_id = false)
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $resource_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'code' => 500,
                    'result' => false,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'code' => 200,
                    'result' => true,
                    'resource' => Payment::retrieve($resource_id, $this->api),
                ];
            }
        } catch (ConfigurationNotSetException $e) {
            $response = [
                'code' => (int) $e->getCode(),
                'result' => false,
                'message' => $e->getMessage(),
            ];
        } catch (NotFoundException $e) {
            $response = [
                'code' => (int) $e->getCode(),
                'result' => false,
                'message' => $e->getMessage(),
            ];
        } catch (UndefinedAttributeException $e) {
            $response = [
                'code' => (int) $e->getCode(),
                'result' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Initialize the api session
     *
     * @param bool $token
     *
     * @return false|Payplug
     */
    private function initialize($token = false)
    {
        if (!$token && null != $this->getCurrentApiKey()) {
            $token = $this->getCurrentApiKey();
        }

        if (!$token) {
            return false;
        }

        $this->current_api_key = $token;

        $this->setUserAgent();

        $this->api = Payplug::init([
            'secretKey' => $token,
            'apiVersion' => $this->dependencies->getPlugin()->getApiVersion(),
        ]);

        return $this->api;
    }

    /**
     * @description Get the current api key from database
     *
     * @return string
     */
    private function getCurrentApiKey()
    {
        $sandbox_mode = (bool) $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue('sandbox_mode');

        return (string) $this->dependencies
            ->getPlugin()
            ->getConfigurationClass()
            ->getValue($sandbox_mode ? 'test_api_key' : 'live_api_key');
    }

    /**
     * @description Set the user Agent in API request
     */
    private function setUserAgent()
    {
        if (null != $this->current_api_key) {
            HttpClient::setDefaultUserAgentProduct(
                $this->dependencies->name . '-Prestashop',
                $this->dependencies->version,
                'Prestashop/' . _PS_VERSION_
            );
        }
    }

    /**
     * @description Check current environment to defined the api route
     */
    private function checkEnvironment()
    {
        if (isset($_SERVER['SERVER_NAME'])
            && 'localhost' == $_SERVER['SERVER_NAME']
            || preg_match(
                '/(shopshelf|notpayplug.com|payplug.com|payplug.fr|ngrok.io|ngrok-free.app|prestashop-qa.test)/i',
                $_SERVER['SERVER_NAME']
            )
        ) {
            $dotenv = new Dotenv();
            $dotenvFile = dirname(dirname(dirname(__FILE__))) . '/payplugroutes/.env';
            if (file_exists($dotenvFile)) {
                $dotenv->load($dotenvFile);
            }
        }
        if (isset($_ENV['API_BASE_URL'])) {
            APIRoutes::setApiBaseUrl($_ENV['API_BASE_URL']);
        }
        if (isset($_ENV['MERCHANT_PLUGINS_DATA_COLLECTOR_RESOURCE'])) {
            APIRoutes::setMerchantPluginsDataCollectorService($_ENV['MERCHANT_PLUGINS_DATA_COLLECTOR_RESOURCE']);
        }
    }

    /**
     * @description Set the environment url
     */
    private function setEnvironment()
    {
        if (isset($_ENV['API_BASE_URL']) && !empty($_ENV['API_BASE_URL'])) {
            $this->setApiUrl($_ENV['API_BASE_URL']);
        } else {
            $this->setApiUrl('https://api.payplug.com');
        }

        if (isset($_ENV['PAYPLUG_SITE_URL']) && !empty($_ENV['PAYPLUG_SITE_URL'])) {
            $this->site_url = $_ENV['PAYPLUG_SITE_URL'];
        } else {
            $this->site_url = 'https://www.payplug.com';
        }

        if (isset($_ENV['PAYPLUG_PORTAL_URL']) && !empty(['PAYPLUG_PORTAL_URL'])) {
            $this->portal_url = $_ENV['PAYPLUG_PORTAL_URL'];
        } else {
            $this->portal_url = 'https://portal.payplug.com';
        }
    }

    /**
     * @description Set the api url
     *
     * @param string $api_url
     *
     * @return $this
     */
    private function setApiUrl($api_url = '')
    {
        if (!is_string($api_url)
            || !preg_match('/http(s?):\/\/api(-\w+|\.\w+)?.(payplug|notpayplug).(com|test)/', $api_url)) {
            throw new BadParameterException('Invalid argument, $api_url must be a a valid api url format');
        }
        $this->api_url = $api_url;

        return $this;
    }
}
