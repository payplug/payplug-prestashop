<?php
/**
 * 2013 - 2022 PayPlug SAS
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
 * @copyright 2013 - 2022 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\classes;

use Exception;
use Payplug\Authentication;
use Payplug\Card;
use Payplug\Core\APIRoutes;
use Payplug\Core\HttpClient;
use Payplug\Exception\BadRequestException;
use Payplug\Exception\ConfigurationException;
use Payplug\Exception\ConfigurationNotSetException;
use Payplug\Exception\ForbiddenException;
use Payplug\Exception\NotAllowedException;
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

class ApiClass
{
    /** @var string */
    public $current_api_key;

    /** var Configuration */
    public $config;

    /** var DependenciesClass */
    public $dependencies;
    /** @var object */
    private $api;

    /** @var string */
    private $api_url;

    /** @var string */
    private $portal_url;

    /**
     * @var mixed
     */
    private $site_url;

    private $tools;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->config = $this->dependencies->getPlugin()->getConfiguration();
        $this->tools = $this->dependencies->getPlugin()->getTools();

        $this->checkEnvironment();
        $this->setEnvironment();
        $this->current_api_key = $this->getCurrentApiKey();
    }

    /**
     * @description Check environnement and try to set API_BASE_URL into payplug-php lib
     */
    public function checkEnvironment()
    {
        if (isset($_SERVER['SERVER_NAME'])
            && $_SERVER['SERVER_NAME'] == 'localhost'
            || preg_match(
                '/(shopshelf|notpayplug.com|payplug.com|payplug.fr|ngrok.io)/i',
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
    }

    /**
     * @description Check if account is premium
     *
     * @param null $api_key
     *
     * @throws ConfigurationException
     *
     * @return array|bool
     */
    public function getAccountPermissions($api_key = null)
    {
        if ($api_key == null) {
            $api_key = $this->setAPIKey();
        }

        return $this->getAccount($api_key, false);
    }

    /**
     * @description Get account permission from Payplug API
     *
     * @param $api_key
     * @param bool $sandbox
     *
     * @throws ConfigurationException
     *
     * @return array|false
     */
    public function getAccount($api_key, $sandbox = true)
    {
        $this->setSecretKey($api_key);

        try {
            $response = Authentication::getAccount();
        } catch (ConfigurationException $e) {
            return false;
        }

        $json_answer = $response['httpResponse'];

        if ($permissions = $this->treatAccountResponse($json_answer, $sandbox)) {
            return $permissions;
        }

        return false;
    }

    /**
     * @description set publishable keys from payplug/payplug-php
     *
     * @throws Payplug\Exception\ConfigurationNotSetException
     * @throws ConfigurationException
     */
    public function setPublishableKeys()
    {
        if (!isset($this->current_api_key)) {
            return [
                'result' => false,
            ];
        }
        $sandbox = $this->config->get(
            $this->dependencies->getConfigurationKey('sandboxMode')
        );
        $flag = true;

        $this->setSecretKey();

        // Set the publishable for the given sandbox configuration
        try {
            $response = Authentication::getPublishableKeys();
            $publishable_key = isset($response['httpResponse']['publishable_key'])
            && $response['httpResponse']['publishable_key']
                ? $response['httpResponse']['publishable_key']
                : null;

            if (!$publishable_key) {
                $this->config->deleteByName($this->dependencies->getConfigurationKey('publishableKey'));
                $this->config->deleteByName($this->dependencies->getConfigurationKey('publishableKeyTest'));
                $this->config->updateValue($this->dependencies->getConfigurationKey('embeddedMode'), 'redirected');

                return [
                    'result' => false,
                    'error' => [
                        'name' => 'EMPTY_PUBLISHABLE_KEY',
                        'message' => '',
                    ],
                ];
            }

            $flag = $flag
                && $this->config->updateValue(
                    $this->dependencies->getConfigurationKey('publishableKey') . ($sandbox ? '_TEST' : ''),
                    $publishable_key
                );
        } catch (BadRequestException $e) {
            return [
                'result' => $flag,
                'error' => [
                    'name' => 'BAD_REQUEST_EXCEPTION',
                    'message' => $e->getMessage(),
                ],
            ];
        }

        if ($sandbox) {
            $this->setSecretKey($this->config->get(
                $this->dependencies->getConfigurationKey('liveApiKey')
            ));
        } else {
            $this->setSecretKey($this->config->get(
                $this->dependencies->getConfigurationKey('testApiKey')
            ));
        }

        // Set the publishable for the other sandbox configuration
        try {
            $response = Authentication::getPublishableKeys();
            $publishable_key = isset($response['httpResponse']['publishable_key'])
                && $response['httpResponse']['publishable_key']
                    ? $response['httpResponse']['publishable_key']
                    : null;

            if (!$publishable_key) {
                $this->config->deleteByName(
                    $this->dependencies->getConfigurationKey('publishableKey')
                );
                $this->config->deleteByName(
                    $this->dependencies->getConfigurationKey('publishableKeyTest')
                );
                $this->config->updateValue(
                    $this->dependencies->getConfigurationKey('embeddedMode'),
                    'redirected'
                );

                return [
                    'result' => false,
                    'error' => [
                        'name' => 'EMPTY_PUBLISHABLE_KEY',
                        'message' => '',
                    ],
                ];
            }

            $flag = $flag
                && $this->config->updateValue(
                    $this->dependencies->getConfigurationKey('publishableKey') . (!$sandbox ? '_TEST' : ''),
                    $publishable_key
                );
        } catch (BadRequestException $e) {
            return [
                'result' => $flag,
                'error' => [
                    'name' => 'BAD_REQUEST_EXCEPTION',
                    'message' => $e->getMessage(),
                ],
            ];
        }

        if (!$sandbox) {
            $this->setSecretKey($this->config->get(
                $this->dependencies->getConfigurationKey('liveApiKey')
            ));
        } else {
            $this->setSecretKey($this->config->get(
                $this->dependencies->getConfigurationKey('testApiKey')
            ));
        }

        return [
            'result' => $flag,
            'error' => [],
        ];
    }

    /**
     * @return string
     */
    public function getCurrentApiKey()
    {
        if ((int) $this->config->get(
            $this->dependencies->getConfigurationKey('sandboxMode')
        ) === 1) {
            return $this->config->get(
                $this->dependencies->getConfigurationKey('testApiKey')
            );
        }

        return $this->config->get(
            $this->dependencies->getConfigurationKey('liveApiKey')
        );
    }

    /**
     * @description Determine wich API key to use
     *
     * @return string
     */
    public function setAPIKey()
    {
        $sandbox_mode = (int) $this->config->get(
            $this->dependencies->getConfigurationKey('sandboxMode')
        );

        return $sandbox_mode ? $this->config->get(
            $this->dependencies->getConfigurationKey('testApiKey')
        ) : $this->config->get(
            $this->dependencies->getConfigurationKey('liveApiKey')
        );
    }

    /**
     * @description configure the api url
     *
     * @param string $api_url
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setApiUrl($api_url)
    {
        if (!is_string($api_url)
            || !preg_match('/http(s?):\/\/api(-\w+|\.\w+)?.(payplug|notpayplug).(com|test)/', $api_url)) {
            throw (new BadParameterException('Invalid argument, $api_url must be a a valid api url format'));
        }
        $this->api_url = $api_url;

        return $this;
    }

    /**
     * @description Set the current secret key used to interact with PayPlug API
     *
     * @param false $token
     *
     * @throws ConfigurationException
     *
     * @return false|Payplug
     */
    public function setSecretKey($token = false)
    {
        if (!$token && $this->getCurrentApiKey() != null) {
            $token = $this->getCurrentApiKey();
        }

        if (!$token) {
            return false;
        }

        $this->setUserAgent();

        $this->api = Payplug::init([
            'secretKey' => $token,
            'apiVersion' => $this->dependencies->getPlugin()->getApiVersion(),
        ]);

        return $this->api;
    }

    /**
     * @description set the api keys
     *
     * @param null $sandbox
     *
     * @throws ConfigurationException
     *
     * @return false|Payplug
     */
    public function initializeApi($sandbox = null)
    {
        if ($sandbox === null && $this->current_api_key) {
            $payplug_key = $this->current_api_key;
        } else {
            $configuration_key = ($sandbox ? 'TEST' : 'LIVE') . '_API_KEY';
            $payplug_key = $this->config->get($this->dependencies->concatenateModuleNameTo($configuration_key));
        }

        return $this->setSecretKey($payplug_key);
    }

    /**
     * @description  return exeption error form API
     *
     * @param $str
     *
     * @return array
     */
    public function catchErrorsFromApi($str)
    {
        $parses = explode(';', $str);
        $response = null;
        foreach ($parses as $parse) {
            if (strpos($parse, 'HTTP Response') !== false) {
                $parse = str_replace('HTTP Response:', '', $parse);
                $parse = trim($parse);
                $response = json_decode($parse, true);
            }
        }

        $errors = [];
        $errors[] = $str;
        if (!isset($response['details']) || empty($response['details'])) {
            // set a default error message
            $error_key = md5('The transaction was not completed and your card was not charged.');
            $errors[$error_key] = $this->dependencies->l(
                'payplug.catchErrorsFromApi.transactionNotCompleted',
                'apiclass'
            );

            return $errors;
        }

        $keys = array_keys($response['details']);
        foreach ($keys as $key) {
            // add adapter error message
            switch ($key) {
                default:
                    $error_key = md5('The transaction was not completed and your card was not charged.');
                    // push error only if not catched before
                    if (!array_key_exists($error_key, $errors)) {
                        $errors[$error_key] =
                            $this->dependencies->l('payplug.catchErrorsFromApi.transactionNotCompleted', 'apiclass');
                    }
            }
        }

        return $errors;
    }

    /**
     * @description determine if the account has a live api key
     *
     * @return bool
     */
    public function hasLiveKey()
    {
        return (bool) $this->config->get(
            $this->dependencies->getConfigurationKey('liveApiKey')
        );
    }

    /**
     * @description login to Payplug API
     *
     * @param $email
     * @param $password
     *
     * @throws BadRequestException|ConfigurationException
     *
     * @return bool
     */
    public function login($email, $password)
    {
        try {
            $this->setUserAgent();
            $response = Authentication::getKeysByLogin($email, $password);
            $json_answer = $response['httpResponse'];

            if ($this->setApiKeysbyJsonResponse($json_answer)) {
                if ($this->dependencies->configClass->isValidFeature('feature_integrated') && (version_compare(
                    _PS_VERSION_,
                    '1.7',
                    '>='
                ))) {
                    if ($this->setPublishableKeys()) {
                        return true;
                    }
                } else {
                    return true;
                }
            } else {
                return false;
            }
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
        }
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
     * @description  get the site url
     *
     * @return mixed
     */
    public function getSiteUrl()
    {
        return $this->site_url;
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
     * @description Abort InstallmentPlan from api for given id
     *
     * @param false $inst_id
     *
     * @return array
     */
    public function abortInstallment($inst_id = false)
    {
        if (!$inst_id || !is_string($inst_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $inst_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
            }

            if (!$this->api) {
                $response = [
                    'code' => 500,
                    'result' => false,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $this->setUserAgent();
                $response = [
                    'result' => true,
                    'resource' => InstallmentPlan::abort($inst_id, $this->api),
                    'code' => 200,
                ];
            }
        } catch (Exception $e) {
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
     * @param array $atttributes
     *
     * @throws ConfigurationNotSetException
     *
     * @return array
     */
    public function createInstallment($atttributes = [])
    {
        if (!$atttributes || !is_array($atttributes)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $atttributes given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => InstallmentPlan::create($atttributes, $this->api),
                ];
            }
        } catch (Exception $e) {
            $response = [
                'code' => (int) $e->getCode(),
                'result' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Retrieve InstallmentPlan from api for given id
     *
     * @param $inst_id
     *
     * @throws ConfigurationException
     *
     * @return array
     */
    public function retrieveInstallment($inst_id = false)
    {
        if (!$inst_id || !is_string($inst_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $inst_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => InstallmentPlan::retrieve($inst_id, $this->api),
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
     * @description Abort Payment from api for given id
     *
     * @param false $pay_id
     *
     * @throws Exception
     *
     * @return array
     */
    public function abortPayment($pay_id = false)
    {
        if (!$pay_id || !is_string($pay_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $pay_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => Payment::abort($pay_id, $this->api),
                    'code' => 200,
                ];
            }
        } catch (Exception $e) {
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
     * @param $pay_id
     *
     * @throws ConfigurationException
     *
     * @return array
     */
    public function capturePayment($pay_id = false)
    {
        if (!$pay_id || !is_string($pay_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $pay_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => Payment::capture($pay_id, $this->api),
                    'code' => 200,
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
        }

        return $response;
    }

    /**
     * @description Create Payment from api for given attributes
     *
     * @param array $atttributes
     *
     * @throws ConfigurationNotSetException
     *
     * @return array
     */
    public function createPayment($atttributes = [])
    {
        if (!$atttributes || !is_array($atttributes)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $atttributes given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => Payment::create($atttributes, $this->api),
                ];
            }
        } catch (Exception $e) {
            $response = [
                'code' => (int) $e->getCode(),
                'result' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Create Payment from api for given data
     *
     * @param false $pay_id
     * @param array $data
     *
     * @return array
     */
    public function patchPayment($pay_id = false, $data = [])
    {
        if (!$pay_id || !is_string($pay_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $pay_id given',
            ];
        }

        if (!$data || !is_array($data)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $data given',
            ];
        }

        $retrieve = $this->retrievePayment($pay_id);
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
        } catch (Exception $e) {
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
     * @param false $pay_id
     * @param array $data
     *
     * @throws ConfigurationNotSetException
     *
     * @return array
     */
    public function refundPayment($pay_id = false, $data = [])
    {
        if (!$pay_id || !is_string($pay_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $pay_id given',
            ];
        }

        if (!$data || !is_array($data)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $data given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => Refund::create($pay_id, $data, $this->api),
                    'code' => 200,
                ];
            }
        } catch (Exception $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Retrieve Payment from api for given id and mode
     *
     * @param $pay_id false
     * @param $mode false
     *
     * @return array
     */
    public function retrievePayment($pay_id = false)
    {
        if (!$pay_id || !is_string($pay_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $pay_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => Payment::retrieve($pay_id, $this->api),
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
     * @description Delete Card from api for given id
     *
     * @param false $card_id
     *
     * @throws ConfigurationException
     *
     * @return array
     */
    public function deleteCard($card_id = false)
    {
        if (!$card_id || !is_string($card_id)) {
            return [
                'code' => null,
                'result' => false,
                'message' => 'Wrong $card_id given',
            ];
        }

        try {
            if (!$this->api) {
                $this->api = $this->setSecretKey();
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
                    'resource' => Card::delete($card_id, $this->api),
                ];
            }
        } catch (ConfigurationNotSetException $e) {
            $response = [
                'code' => $e->getCode(),
                'result' => true,
                'message' => $e->getMessage(),
            ];
        } catch (NotFoundException $e) {
            $response = [
                'code' => $e->getCode(),
                'result' => true,
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
                'code' => null,
                'result' => false,
                'message' => 'Wrong $data given',
            ];
        }

        try {
            $api = $this->setSecretKey();

            if (!$api) {
                $response = [
                    'code' => 500,
                    'result' => false,
                    'message' => 'Cannot connect to the API',
                ];
            } else {
                $response = [
                    'result' => true,
                    'code' => 200,
                    'resource' => OneySimulation::getSimulations($data, $api),
                ];
            }
        } catch (Exception $e) {
            $response = [
                'result' => false,
                'code' => (int) $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }

    /**
     * @description Read API response and return permissions
     *
     * @param $json_answer
     * @param bool $is_sandbox
     *
     * @return array|false
     */
    private function treatAccountResponse($json_answer, $is_sandbox = true)
    {
        if ((isset($json_answer['object']) && $json_answer['object'] == 'error')
            || empty($json_answer)
        ) {
            return false;
        }

        $id = $json_answer['id'];

        $configuration = [
            'currencies' => $this->config->get(
                $this->dependencies->getConfigurationKey('currencies')
            ),
            'min_amounts' => $this->config->get(
                $this->dependencies->getConfigurationKey('minAmounts')
            ),
            'max_amounts' => $this->config->get(
                $this->dependencies->getConfigurationKey('maxAmounts')
            ),
            'oney_allowed_countries' => $this->config->get(
                $this->dependencies->getConfigurationKey('oneyAllowedCountries')
            ),
            'oney_max_amounts' => $this->config->get(
                $this->dependencies->getConfigurationKey('oneyMaxAmounts')
            ),
            'oney_min_amounts' => $this->config->get(
                $this->dependencies->getConfigurationKey('oneyMinAmounts')
            ),
        ];

        if (isset($json_answer['configuration'])) {
            if (isset($json_answer['configuration']['currencies'])
                && !empty($json_answer['configuration']['currencies'])) {
                $configuration['currencies'] = [];
                foreach ($json_answer['configuration']['currencies'] as $value) {
                    $configuration['currencies'][] = $value;
                }
            }

            if (isset($json_answer['configuration']['min_amounts'])
                && !empty($json_answer['configuration']['min_amounts'])) {
                $configuration['min_amounts'] = '';
                foreach ($json_answer['configuration']['min_amounts'] as $key => $value) {
                    $configuration['min_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['min_amounts'] = $this->tools->substr($configuration['min_amounts'], 0, -1);
            }

            if (isset($json_answer['configuration']['max_amounts'])
                && !empty($json_answer['configuration']['max_amounts'])) {
                $configuration['max_amounts'] = '';
                foreach ($json_answer['configuration']['max_amounts'] as $key => $value) {
                    $configuration['max_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['max_amounts'] = $this->tools->substr($configuration['max_amounts'], 0, -1);
            }

            if (isset($json_answer['configuration']['oney'])) {
                if (isset($json_answer['configuration']['oney']['allowed_countries'])
                    && !empty($json_answer['configuration']['oney']['allowed_countries'])
                    && sizeof($json_answer['configuration']['oney']['allowed_countries'])
                ) {
                    $allowed = '';
                    foreach ($json_answer['configuration']['oney']['allowed_countries'] as $country) {
                        $allowed .= $country . ',';
                    }
                    $configuration['oney_allowed_countries'] = $this->tools->substr($allowed, 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['min_amounts'])
                    && !empty($json_answer['configuration']['oney']['min_amounts'])
                ) {
                    $configuration['oney_min_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['min_amounts'] as $key => $value) {
                        $configuration['oney_min_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_min_amounts'] = $this->tools->substr($configuration['oney_min_amounts'], 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['max_amounts'])
                    && !empty($json_answer['configuration']['oney']['max_amounts'])
                ) {
                    $configuration['oney_max_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['max_amounts'] as $key => $value) {
                        $configuration['oney_max_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_max_amounts'] = $this->tools->substr($configuration['oney_max_amounts'], 0, -1);
                }
            }
        }

        if (isset($json_answer['payment_methods']['bancontact']['enabled'])) {
            $can_use_bancontact = $json_answer['payment_methods']['bancontact']['enabled'];
        } else {
            $can_use_bancontact = true;
        }

        if (isset($json_answer['payment_methods']['apple_pay']['enabled'])) {
            $can_use_applepay = $json_answer['payment_methods']['apple_pay']['enabled'];
        } else {
            $can_use_applepay = true;
        }

        if (isset($json_answer['payment_methods']['american_express']['enabled'])) {
            $can_use_amex = $json_answer['payment_methods']['american_express']['enabled'];
        } else {
            $can_use_amex = true;
        }

        $onboardingOneyCompleted = false;
        if (isset($json_answer['payment_methods']) && !empty(
            $this->config->get(
                $this->dependencies->getConfigurationKey('liveApiKey')
            )
        )) {
            $oney_methods = [];
            foreach ($json_answer['payment_methods'] as $key => $val) {
                if ($this->tools->substr($key, 0, 5) == 'oney_') {
                    $oney_methods[] = $val['enabled'];
                }
            }
            foreach ($oney_methods as $value) {
                if ($value == 'true') {
                    $onboardingOneyCompleted = true;
                }
            }
        }

        $permissions = [
            'is_live' => $json_answer['is_live'],
            'use_live_mode' => $json_answer['permissions']['use_live_mode'],
            'can_save_cards' => $json_answer['permissions']['can_save_cards'],
            'can_create_installment_plan' => $json_answer['permissions']['can_create_installment_plan'],
            'can_create_deferred_payment' => $json_answer['permissions']['can_create_deferred_payment'],
            'can_use_oney' => $json_answer['permissions']['can_use_oney'],
            'can_use_bancontact' => $can_use_bancontact,
            'can_use_applepay' => $can_use_applepay,
            'can_use_amex' => $can_use_amex,
            'onboardingOneyCompleted' => $onboardingOneyCompleted,
        ];
        if (isset($json_answer['payment_methods']['apple_pay']['allowed_domain_names'])) {
            $permissions['apple_pay_allowed_domains'] = $json_answer['payment_methods']['apple_pay']['allowed_domain_names'];
        }

        // If sandbox mode active, no allowed countries sent
        // Then set default as `FR,MQ,YT,RE,GF,GP,IT`
        if (isset($json_answer['is_live']) && !$json_answer['is_live']) {
            $configuration['oney_allowed_countries'] = 'FR,MQ,YT,RE,GF,GP,IT';
        }

        // Get company country
        $company_iso = isset($json_answer['country']) && $json_answer['country'] ? $json_answer['country'] : false;

        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('companyId') . ($is_sandbox ? '_TEST' : ''),
            $id
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('companyIso'),
            $company_iso
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('currencies'),
            implode(';', $configuration['currencies'])
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('minAmounts'),
            $configuration['min_amounts']
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('maxAmounts'),
            $configuration['max_amounts']
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('oneyAllowedCountries'),
            $configuration['oney_allowed_countries']
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('oneyMaxAmounts'),
            $configuration['oney_max_amounts']
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('oneyMinAmounts'),
            $configuration['oney_min_amounts']
        );

        return $permissions;
    }

    /**
     * @description  Register API Keys
     *
     * @param $json_answer
     *
     * @throws ConfigurationException
     *
     * @return bool
     */
    private function setApiKeysbyJsonResponse($json_answer)
    {
        if (isset($json_answer['object']) && $json_answer['object'] == 'error') {
            return false;
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
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('testApiKey'),
            $api_keys['test_key']
        );
        $this->config->updateValue(
            $this->dependencies->getConfigurationKey('liveApiKey'),
            $api_keys['live_key']
        );

        $is_sandbox = $this->config->get($this->dependencies->getConfigurationKey('sandboxMode'));
        if ($is_sandbox) {
            $this->setSecretKey($api_keys['test_key']);
        } else {
            $this->setSecretKey($api_keys['live_key']);
        }

        return true;
    }

    /**
     * @description Determine witch environment is used
     *
     * @throws BadParameterException
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
     * @description Set the user-agent referenced in every API call to identify the module
     */
    private function setUserAgent()
    {
        if ($this->current_api_key != null) {
            HttpClient::setDefaultUserAgentProduct(
                $this->dependencies->name . '-Prestashop',
                $this->dependencies->version,
                'Prestashop/' . _PS_VERSION_
            );
        }
    }
}
