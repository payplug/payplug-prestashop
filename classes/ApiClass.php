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

namespace PayPlug\classes;

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

class ApiClass
{
    /** @var string */
    public $current_api_key;

    /** var ConfigurationClass */
    public $configuration;

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
        $this->configuration = $this->dependencies->getPlugin()->getConfigurationClass();
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
     * @description Check if account is premium
     *
     * @param null $api_key
     *
     * @return array
     */
    public function getAccountPermissions($api_key = null)
    {
        if (null == $api_key) {
            $api_key = $this->getCurrentApiKey();
        }

        return $this->getAccount($api_key, false);
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
    public function getAccount($api_key, $sandbox = true, $treat_account = true)
    {
        $this->setSecretKey($api_key);

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

        if ($permissions = $this->treatAccountResponse($json_answer, $sandbox)) {
            return $permissions;
        }

        return [];
    }

    /**
     * @description Determine wich API key to use
     *
     * @return string
     */
    public function getCurrentApiKey()
    {
        $sandbox_mode = (bool) $this->configuration->getValue('sandbox_mode');

        return (string) $this->configuration->getValue($sandbox_mode ? 'test_api_key' : 'live_api_key');
    }

    /**
     * @description configure the api url
     *
     * @param $api_url
     *
     * @return $this
     */
    public function setApiUrl($api_url)
    {
        if (!is_string($api_url)
            || !preg_match('/http(s?):\/\/api(-\w+|\.\w+)?.(payplug|notpayplug).(com|test)/', $api_url)) {
            throw new BadParameterException('Invalid argument, $api_url must be a a valid api url format');
        }
        $this->api_url = $api_url;

        return $this;
    }

    /**
     * @description Set the current secret key used to interact with PayPlug API.
     *
     * @param false $token
     *
     * @return Payplug
     */
    public function setSecretKey($token = false)
    {
        if (!$token && null != $this->getCurrentApiKey()) {
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
     * @return Payplug
     */
    public function initializeApi($sandbox = null)
    {
        if (null === $sandbox && $this->current_api_key) {
            $payplug_key = $this->current_api_key;
        } else {
            $configuration_key = ($sandbox ? 'test' : 'live') . '_api_key';
            $payplug_key = $this->configuration->getValue($configuration_key);
        }

        return $this->setSecretKey($payplug_key);
    }

    /**
     * @description Return exeption error form API
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
            if (false !== strpos($parse, 'HTTP Response')) {
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
            $errors[$error_key] = $this->dependencies
                ->getPlugin()
                ->getTranslationClass()
                ->l(
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
                            $this->dependencies
                                ->getPlugin()
                                ->getTranslationClass()
                                ->l('payplug.catchErrorsFromApi.transactionNotCompleted', 'apiclass');
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
        return (bool) $this->configuration->getValue('live_api_key');
    }

    /**
     * @description login to Payplug API
     *
     * @param $email
     * @param $password
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
     * @param array $atttributes
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
        } catch (\Exception $e) {
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
     * @param false $inst_id
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
     * @param false $pay_id
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
                'code' => null,
                'result' => false,
                'message' => 'Wrong $attributes given',
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
                    'resource' => Payment::create($attributes, $this->api),
                ];
            }
        } catch (\Exception $e) {
            $response = [
                'code' => (int) $e->getCode(),
                'result' => false,
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
     * @description Retrieve Payment from api for given id and mode
     *
     * @param false $pay_id
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
     * @description Read API response and return permissions
     *
     * @param $json_answer
     * @param bool $is_sandbox
     *
     * @return array|false
     */
    private function treatAccountResponse($json_answer, $is_sandbox = true)
    {
        if ((isset($json_answer['object']) && 'error' == $json_answer['object'])
            || empty($json_answer)
        ) {
            return false;
        }

        $payment_methods = json_decode($this->configuration->getValue('payment_methods'), true);
        $configuration = [
            'amounts' => json_decode($this->configuration->getValue('amounts'), true),
            'company_id' => isset($json_answer['id']) ? $json_answer['id'] : $this->configuration->getValue('company_id'),
            'company_iso' => isset($json_answer['country']) ? $json_answer['country'] : $this->configuration->getValue('company_iso'),
            'countries' => json_decode($this->configuration->getValue('countries'), true),
            'currencies' => $this->configuration->getValue('currencies'),
            'oney' => isset($json_answer['permissions']['can_use_oney']) ? (int) $json_answer['permissions']['can_use_oney'] : (bool) $payment_methods['oney'],
            'oney_allowed_countries' => $this->configuration->getValue('oney_allowed_countries'),
        ];

        if (isset($json_answer['configuration'])) {
            // Check payplug default amounts
            if (isset($json_answer['configuration']['min_amounts']) && !empty($json_answer['configuration']['min_amounts'])) {
                $configuration['amounts']['default']['min'] = '';
                foreach ($json_answer['configuration']['min_amounts'] as $key => $value) {
                    $configuration['amounts']['default']['min'] .= $key . ':' . $value . ';';
                }
                $configuration['amounts']['default']['min'] = $this->tools->substr($configuration['amounts']['default']['min'], 0, -1);
            }

            if (isset($json_answer['configuration']['max_amounts'])
                && !empty($json_answer['configuration']['max_amounts'])) {
                $configuration['amounts']['default']['max'] = '';
                foreach ($json_answer['configuration']['max_amounts'] as $key => $value) {
                    $configuration['amounts']['default']['max'] .= $key . ':' . $value . ';';
                }
                $configuration['amounts']['default']['max'] = $this->tools->substr($configuration['amounts']['default']['max'], 0, -1);
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
                    $configuration['oney_allowed_countries'] = $this->tools->substr($allowed, 0, -1);
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
            if ($this->configuration->getValue('live_api_key')) {
                foreach ($permissions as $permission => $enabled) {
                    if (false !== strpos($permission, 'can_use_oney_')) {
                        if (!$permissions['onboarding_oney_completed']) {
                            $permissions['onboarding_oney_completed'] = $enabled;
                        }
                    }
                }
            }
        }

        // Format amount, country and currency before update
        $configuration['amounts'] = json_encode($configuration['amounts']);
        $configuration['countries'] = json_encode($configuration['countries']);
        $configuration['currencies'] = implode(';', $configuration['currencies']);
        foreach ($configuration as $key => $value) {
            $this->configuration->set($key, $value);
        }

        return $permissions;
    }

    /**
     * @description Register API Keys
     *
     * @param $json_answer
     *
     * @return bool
     */
    private function setApiKeysbyJsonResponse($json_answer)
    {
        if (isset($json_answer['object']) && 'error' == $json_answer['object']) {
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
        $this->configuration->set('test_api_key', $api_keys['test_key']);
        $this->configuration->set('live_api_key', $api_keys['live_key']);

        $is_sandbox = (bool) $this->configuration->getValue('sandbox_mode');
        $this->setSecretKey($api_keys[$is_sandbox ? 'test_key' : 'live_key']);

        return true;
    }

    /**
     * @description Determine witch environment is used
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
        if (null != $this->current_api_key) {
            HttpClient::setDefaultUserAgentProduct(
                $this->dependencies->name . '-Prestashop',
                $this->dependencies->version,
                'Prestashop/' . _PS_VERSION_
            );
        }
    }
}
