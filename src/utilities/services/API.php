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

use Payplug\Authentication;
use Payplug\Card;
use Payplug\Core\APIRoutes;
use Payplug\Core\HttpClient;
use Payplug\Exception\BadRequestException;
use Payplug\Exception\ConfigurationNotSetException;
use Payplug\Exception\NotFoundException;
use Payplug\Exception\PayplugServerException;
use Payplug\Exception\UndefinedAttributeException;
use Payplug\InstallmentPlan;
use Payplug\OneySimulation;
use Payplug\Payment;
use Payplug\Payplug;
use Payplug\Refund;
use PayPlug\src\exceptions\BadParameterException;
use Symfony\Component\Dotenv\Dotenv;

if (!defined('_PS_VERSION_')) {
    exit;
}

class API
{
    private $current_api_key = '';
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
     * @description Abort InstallmentPlan from api for given id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function abortInstallment($resource_id = '')
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => InstallmentPlan::abort($resource_id, $this->api),
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
     * @todo: cover this method
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function abortPayment($resource_id = '')
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => Payment::abort($resource_id, $this->api),
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
     * @description Capture Payment from api for given id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function capturePayment($resource_id = '')
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => Payment::capture($resource_id, $this->api),
                ];
            }
        } catch (NotAllowedException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (ForbiddenException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (ConfigurationNotSetException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
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
     * @description Create InstallmentPlan from api for given attributes
     *
     * @param array $attributes
     *
     * @return array
     */
    public function createInstallment($attributes = [])
    {
        if (!$attributes || !is_array($attributes)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $attributes given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => InstallmentPlan::create($attributes, $this->api),
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
     * @description Create Payment from api for given attributes
     *
     * @param array $attributes
     *
     * @return array
     */
    public function createPayment($attributes = [])
    {
        if (!$attributes || !is_array($attributes)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $attributes given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => Payment::create($attributes, $this->api),
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
     * @description Delete Card from api for given id
     *
     * @param false $card_id
     *
     * @return array
     */
    public function deleteCard($card_id = false)
    {
        if (!$card_id || !is_string($card_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $card_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => Card::delete($card_id, $this->api),
                ];
            }
        } catch (ConfigurationNotSetException $e) {
            $response = [
                'result' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (NotFoundException $e) {
            $response = [
                'result' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            $response = [
                'result' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Get account permission from Payplug API
     *
     * @param $api_key
     * @param bool $sandbox
     * @param bool $treat_account
     *
     * @return array
     */
    public function getAccount($api_key = '', $sandbox = true, $treat_account = true)
    {
        if (!is_string($api_key) || !$api_key) {
            return [];
        }
        if (!is_bool($sandbox)) {
            return [];
        }
        if (!is_bool($treat_account)) {
            return [];
        }

        $this->api = $this->initialize($api_key);
        if (!$this->api) {
            return [];
        }

        try {
            $response = Authentication::getAccount();
        } catch (\Exception $e) {
            if (401 == (int) $e->getCode()) {
                $this->dependencies
                    ->getPlugin()
                    ->getConfigurationAction()
                    ->logoutAction();
            }

            return [];
        }

        $json_answer = $response['httpResponse'];

        if (!$treat_account) {
            return $json_answer;
        }

        return $this->treatAccountResponse($json_answer, $sandbox);
    }

    /**
     * @description get the api url
     *
     * @return string
     */
    public function getApiUrl()
    {
        return $this->api_url;
    }

    /**
     * @param string $session
     *
     * @return array
     */
    public function getClientData($session = '')
    {
        if (!is_string($session) || !$session) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $session given',
            ];
        }

        try {
            $response = [
                'result' => true,
                'code' => 200,
                'data' => Authentication::getClientData($session),
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
     * @description get the oney simulations from the api
     *
     * @param array $data
     *
     * @return array
     */
    public function getOneySimulations($data = [])
    {
        if (!$data || !is_array($data)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $data given',
            ];
        }

        try {
            $api = $this->initialize();

            if (!$api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => OneySimulation::getSimulations($data, $api),
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
     * @description get the portal url
     *
     * @return string
     */
    public function getPortalUrl()
    {
        return $this->portal_url;
    }

    /**
     * @description  get the site url
     *
     * @return mixed
     */
    public function getSiteUrl()
    {
        return $this->site_url;
    }

    /**
     * @description Initialize the api session
     *
     * @param string $token
     *
     * @return Payplug
     */
    public function initialize($token = '')
    {
        if (!is_string($token)) {
            return null;
        }

        if (!$token && null != $this->getCurrentApiKey()) {
            $token = $this->getCurrentApiKey();
        }

        if (!$token) {
            return null;
        }

        $this->current_api_key = $token;

        $this->setUserAgent();

        try {
            $this->api = Payplug::init([
                'secretKey' => $token,
                'apiVersion' => $this->dependencies->getPlugin()->getApiVersion(),
            ]);
        } catch (\Exception $e) {
            $this->api = null;
        }

        return $this->api;
    }

    /**
     * @description set the api keys
     *
     * @param null $sandbox
     *
     * @return Payplug
     */
    public function initializeFromMode($sandbox = null)
    {
        if (null === $sandbox && $this->current_api_key) {
            $payplug_key = $this->current_api_key;
        } else {
            $configuration_key = ($sandbox ? 'test' : 'live') . '_api_key';
            $payplug_key = (bool) $this->dependencies
                ->getPlugin()
                ->getConfigurationClass()
                ->getValue($configuration_key);
        }

        return $this->initialize($payplug_key);
    }

    /**
     * @description login to Payplug API
     *
     * @param string $email
     * @param string $password
     *
     * @return bool
     */
    public function login($email = '', $password = '')
    {
        if (!is_string($email) || !$email) {
            return false;
        }

        if (!is_string($password) || !$password) {
            return false;
        }

        try {
            $this->setUserAgent();
            $response = Authentication::getKeysByLogin($email, $password);
            $json_answer = $response['httpResponse'];

            if ($this->setApiKeysbyJsonResponse($json_answer)) {
                return true;
            }

            return false;
        } catch (BadRequestException $e) {
            json_encode([
                'content' => null,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (PayplugServerException $e) {
            json_encode([
                'content' => null,
                'error' => $e->getMessage(),
            ]);

            return false;
        } catch (\Exception $e) {
            json_encode([
                'content' => null,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @description Patch Payment from api for given attributes
     *
     * @param string $resource_id
     * @param array $attributes
     * @param mixed $page
     *
     * @return array
     */
    public function patchPayment($resource_id = '', $attributes = [])
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ];
        }

        if (!$attributes || !is_array($attributes)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $attributes given',
            ];
        }

        $retrieve = $this->retrievePayment($resource_id);
        if (!$retrieve['result']) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Can\'t patch the payment: ' . $retrieve['message'],
            ];
        }

        $payment = $retrieve['resource'];

        try {
            $response = [
                'result' => true,
                'code' => 200,
                'resource' => $payment->update($attributes),
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
     * @description Refund Payment from api
     *
     * @param string $resource_id
     * @param array $attributes
     *
     * @return array
     */
    public function refundPayment($resource_id = '', $attributes = [])
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ];
        }

        if (!$attributes || !is_array($attributes)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $attributes given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => Refund::create($resource_id, $attributes),
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
     * @description Retrieve InstallmentPlan from api for given id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function retrieveInstallment($resource_id = '')
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => InstallmentPlan::retrieve($resource_id, $this->api),
                ];
            }
        } catch (ConfigurationNotSetException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (NotFoundException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (UndefinedAttributeException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @param string $resource_id
     *
     * @return array
     */
    public function retrievePayment($resource_id = '')
    {
        if (!$resource_id || !is_string($resource_id)) {
            return [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $resource_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->initialize();
            }

            if (!$this->api) {
                $response = [
                    'result' => false,
                    'code' => 500,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => Payment::retrieve($resource_id, $this->api),
                ];
            }
        } catch (ConfigurationNotSetException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (NotFoundException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        } catch (UndefinedAttributeException $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Get the current api key from database
     *
     * @return string
     */
    public function getCurrentApiKey()
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
     * @description Check current environment to defined the api route
     */
    protected function checkEnvironment()
    {
        if (isset($_SERVER['SERVER_NAME']) && preg_match(
            '/(localhost|shopshelf|notpayplug.com|payplug.com|payplug.fr|ngrok.io|ngrok-free.app|prestashop-qa.test)/i',
            $_SERVER['SERVER_NAME']
        )) {
            $dotenv = new Dotenv();
            $dotenvFile = dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/payplugroutes/.env';
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
     * @description Register API Keys
     *
     * @param $json_answer
     *
     * @return bool
     */
    protected function setApiKeysbyJsonResponse($json_answer)
    {
        if (!is_array($json_answer) || empty($json_answer)) {
            return null;
        }

        if (isset($json_answer['object']) && 'error' == $json_answer['object']) {
            return null;
        }

        $api_keys = [];
        $api_keys['test_key'] = '';
        $api_keys['live_key'] = '';

        if (isset($json_answer['secret_keys'])) {
            if (isset($json_answer['secret_keys']['test'])) {
                $api_keys['test_key'] = $json_answer['secret_keys']['test'];
            }
            if (isset($json_answer['secret_keys']['live'])) {
                $api_keys['live_key'] = $json_answer['secret_keys']['live'];
            }
        }

        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();
        $configuration->set('test_api_key', $api_keys['test_key']);
        $configuration->set('live_api_key', $api_keys['live_key']);

        $is_sandbox = (bool) $configuration->getValue('sandbox_mode');

        return $this->initialize($api_keys[$is_sandbox ? 'test_key' : 'live_key']);
    }

    /**
     * @description Set the api url
     *
     * @param string $api_url
     *
     * @return $this
     */
    protected function setApiUrl($api_url = '')
    {
        if (!is_string($api_url)
            || !preg_match('/http(s?):\/\/api(-\w+|\.\w+)?.(payplug|notpayplug).(com|test)/', $api_url)) {
            throw new BadParameterException('Invalid argument, $api_url must be a a valid api url format');
        }
        $this->api_url = $api_url;

        return $this;
    }

    /**
     * @description Set the environment url
     */
    protected function setEnvironment()
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
     * @description Set the user Agent in API request
     */
    protected function setUserAgent()
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
     * @description Read API response and return permissions
     *
     * @param array $json_answer
     * @param bool $is_sandbox
     *
     * @return array
     */
    protected function treatAccountResponse($json_answer = [], $is_sandbox = true)
    {
        if (!is_array($json_answer) || empty($json_answer)) {
            return [];
        }

        if ((isset($json_answer['object']) && 'error' == $json_answer['object'])) {
            return [];
        }
        $configuration_class = $this->dependencies->getPlugin()->getConfigurationClass();
        $tools = $this->dependencies->getPlugin()->getTools();
        $payment_methods = json_decode($configuration_class->getValue('payment_methods'), true);
        $configuration = [
            'amounts' => json_decode($configuration_class->getValue('amounts'), true),
            'company_id' => isset($json_answer['id']) ? $json_answer['id'] : $configuration_class->getValue('company_id'),
            'company_iso' => isset($json_answer['country']) ? $json_answer['country'] : $configuration_class->getValue('company_iso'),
            'countries' => json_decode($configuration_class->getValue('countries'), true),
            'currencies' => $configuration_class->getValue('currencies'),
            'oney' => isset($json_answer['permissions']['can_use_oney']) ? (int) $json_answer['permissions']['can_use_oney'] : (bool) $payment_methods['oney'],
            'oney_allowed_countries' => $configuration_class->getValue('oney_allowed_countries'),
        ];

        if (isset($json_answer['configuration'])) {
            // Check payplug default amounts
            if (isset($json_answer['configuration']['min_amounts']) && !empty($json_answer['configuration']['min_amounts'])) {
                $configuration['amounts']['default']['min'] = '';
                foreach ($json_answer['configuration']['min_amounts'] as $key => $value) {
                    $configuration['amounts']['default']['min'] .= $key . ':' . $value . ';';
                }
                $configuration['amounts']['default']['min'] = $tools->substr($configuration['amounts']['default']['min'], 0, -1);
            }

            if (isset($json_answer['configuration']['max_amounts'])
                && !empty($json_answer['configuration']['max_amounts'])) {
                $configuration['amounts']['default']['max'] = '';
                foreach ($json_answer['configuration']['max_amounts'] as $key => $value) {
                    $configuration['amounts']['default']['max'] .= $key . ':' . $value . ';';
                }
                $configuration['amounts']['default']['max'] = $tools->substr($configuration['amounts']['default']['max'], 0, -1);
            }

            // Check Currency
            if (isset($json_answer['configuration']['currencies'])
                && !empty($json_answer['configuration']['currencies'])) {
                $configuration['currencies'] = [];
                foreach ($json_answer['configuration']['currencies'] as $key => $value) {
                    $configuration['currencies'][] = $value;
                }
            }

            // Check oney allowed countries
            if (isset($json_answer['configuration']['oney'], $json_answer['configuration']['oney']['allowed_countries'])) {
                $allowed_countries = $json_answer['configuration']['oney']['allowed_countries'];
                if (!empty($allowed_countries)) {
                    $allowed = '';
                    foreach ($json_answer['configuration']['oney']['allowed_countries'] as $country) {
                        $allowed .= $country . ',';
                    }
                    $configuration['oney_allowed_countries'] = $tools->substr($allowed, 0, -1);
                }
            }
        }

        $permissions = [
            'is_live' => $json_answer['is_live'],
            'use_live_mode' => $json_answer['permissions']['use_live_mode'],
            'can_save_cards' => $json_answer['permissions']['can_save_cards'],
            'apple_pay_allowed_domains' => [],
            'onboarding_oney_completed' => false,
            'can_use_oney' => $json_answer['permissions']['can_use_oney'],
            'can_create_installment_plan' => $json_answer['permissions']['can_create_installment_plan'],
            'can_create_deferred_payment' => $json_answer['permissions']['can_create_deferred_payment'],
            'can_use_integrated_payments' => $json_answer['permissions']['can_use_integrated_payments'],
        ];

        if (isset($json_answer['payment_methods'])) {
            $payment_methods = $json_answer['payment_methods'];
            foreach ($payment_methods as $payment_method_name => $payment_method) {
                // Check the permissions..
                if (isset($payment_method['enabled'])) {
                    $permissions['can_use_' . $payment_method_name] = $payment_method['enabled'];
                } else {
                    $permissions['can_use_' . $payment_method_name] = true;
                }

                // then check the apple domain to use..
                if ('apple_pay' == $payment_method_name && isset($payment_method['allowed_domain_names'])) {
                    $permissions['apple_pay_allowed_domains'] = $payment_method['allowed_domain_names'];
                }

                // then check the amount related to the feature..
                if (array_key_exists('min_amounts', $payment_method)) {
                    $configuration['amounts'][$payment_method_name]['min'] = 'EUR:' . $payment_method['min_amounts']['EUR'];
                }
                if (array_key_exists('max_amounts', $payment_method)) {
                    $configuration['amounts'][$payment_method_name]['max'] = 'EUR:' . $payment_method['max_amounts']['EUR'];
                }

                // then check the country restriction related to the feature
                if (array_key_exists('allowed_countries', $payment_method)) {
                    $allowed_countries = $payment_method['allowed_countries'];
                    if (!empty($allowed_countries) && !in_array('ALL', $allowed_countries)) {
                        $configuration['countries'][$payment_method_name] = $payment_method['allowed_countries'];
                    }
                }
            }

            // Check oney onboarding
            if ($configuration_class->getValue('live_api_key')) {
                foreach ($permissions as $permission => $enabled) {
                    if (false !== strpos($permission, 'can_use_oney_')) {
                        if (!$permissions['onboarding_oney_completed']) {
                            $permissions['onboarding_oney_completed'] = $enabled;
                        }
                    }
                }
            }
        }
        // Do not allow Spain or Belgium on Payplug
        if (in_array($configuration['oney_allowed_countries'], ['ES', 'BE'])
            && 'payplug' == $this->dependencies->name) {
            $permissions['can_use_oney'] = false;
        }

        // Update globale configuration from account response

        // Format amount, country and currency before update
        $configuration['amounts'] = json_encode($configuration['amounts']);
        $configuration['countries'] = json_encode($configuration['countries']);
        $configuration['currencies'] = implode(';', $configuration['currencies']);
        foreach ($configuration as $key => $value) {
            $configuration_class->set($key, $value);
        }

        return $permissions;
    }
}
