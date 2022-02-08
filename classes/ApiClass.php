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

use Authentication;
use Configuration;
use Payplug\Core\HttpClient;
use Payplug;
use Payplug\Exception\BadRequestException;
use Payplug\Exception\ConfigurationException;
use PayPlug\src\exceptions\BadParameterException;
use PayPlug\src\repositories\PluginRepository;
use Symfony\Component\Dotenv\Dotenv;
use Tools;

class ApiClass
{
    /** @var string */
    private $api_url;

    /** @var string */
    public $current_api_key;

    /** var DependenciesClass */
    public $dependencies;

    /**
     * @var mixed
     */
    private $site_url;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->checkEnvironment();
        $this->setEnvironment();
        self::setSecretKey();
        $this->current_api_key = self::getCurrentApiKey();

        $this->setUserAgent();
    }

    /**
     * @description Check environnement and try to set API_BASE_URL into payplug-php lib
     */
    public function checkEnvironment()
    {
        if (isset($_SERVER['SERVER_NAME'])
            && $_SERVER['SERVER_NAME'] == "localhost"
            || preg_match(
                "/(shopshelf|notpayplug.com|payplug.com|payplug.fr|ngrok.io)/i",
                $_SERVER['SERVER_NAME']
            )
        ) {
            $dotenv = new Dotenv();
            $dotenvFile = dirname(dirname(dirname(__FILE__))) . "/payplugroutes/.env";
            if (file_exists($dotenvFile)) {
                $dotenv->load($dotenvFile);
            }
        }
        if (isset($_ENV['API_BASE_URL'])) {
            \Payplug\Core\APIRoutes::setApiBaseUrl($_ENV['API_BASE_URL']);
        }
    }

    /**
     * @description Check if account is premium
     *
     * @param string $api_key
     * @return bool
     * @throws Payplug\Exception\ConfigurationNotSetException|ConfigurationException
     */
    public static function getAccountPermissions($api_key = null)
    {
        if ($api_key == null) {
            $api_key = self::setAPIKey();
        }
        return self::getAccount($api_key, false);
    }

    /**
     * @description Get account permission from Payplug API
     *
     * @param string $api_key
     * @param boolean $sandbox
     * @return array | bool
     * @throws Payplug\Exception\ConfigurationNotSetException|ConfigurationException
     */
    public static function getAccount($api_key, $sandbox = true)
    {
        self::setSecretKey($api_key);
        $response = \Payplug\Authentication::getAccount();
        $json_answer = $response['httpResponse'];
        if ($permissions = self::treatAccountResponse($json_answer, $sandbox)) {
            return $permissions;
        } else {
            return false;
        }
    }

    /**
     * @description set publishable keys from payplug/payplug-php
     * @throws Payplug\Exception\ConfigurationNotSetException
     * @throws ConfigurationException
     */
    public function setPublishableKeys()
    {
        $sandbox = Configuration::get('PAYPLUG_SANDBOX_MODE');
        $flag = true;

        // Set the publishable for the given sandbox configuration
        try {
            $response = \Payplug\Authentication::getPublishableKeys();
            $publishable_key = isset($response['httpResponse']['publishable_key'])
            && $response['httpResponse']['publishable_key']
                ? $response['httpResponse']['publishable_key']
                : null;

            if (!$publishable_key) {
                Configuration::deleteByName('PAYPLUG_PUBLISHABLE_KEY');
                Configuration::deleteByName('PAYPLUG_PUBLISHABLE_KEY_TEST');
                Configuration::updateValue('PAYPLUG_EMBEDDED_MODE', 'redirected');
                return [
                    'result' => false,
                    'error' => [
                        'name' => 'EMPTY_PUBLISHABLE_KEY',
                        'message' => ''
                    ]
                ];
            }

            $flag = $flag
                && Configuration::updateValue(
                    'PAYPLUG_PUBLISHABLE_KEY' . ($sandbox ? '_TEST' : ''),
                    $publishable_key
                );
        } catch (BadRequestException $e) {
            return [
                'result' => $flag,
                'error' => [
                    'name' => 'BAD_REQUEST_EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }

        if ($sandbox) {
            self::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
        } else {
            self::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
        }

        // Set the publishable for the other sandbox configuration
        try {
            $response = \Payplug\Authentication::getPublishableKeys();
            $publishable_key = isset($response['httpResponse']['publishable_key'])
                && $response['httpResponse']['publishable_key']
                    ? $response['httpResponse']['publishable_key']
                    : null;

            if (!$publishable_key) {
                Configuration::deleteByName('PAYPLUG_PUBLISHABLE_KEY');
                Configuration::deleteByName('PAYPLUG_PUBLISHABLE_KEY_TEST');
                Configuration::updateValue('PAYPLUG_EMBEDDED_MODE', 'redirected');
                return [
                    'result' => false,
                    'error' => [
                        'name' => 'EMPTY_PUBLISHABLE_KEY',
                        'message' => ''
                    ]
                ];
            }

            $flag = $flag
                && Configuration::updateValue(
                    'PAYPLUG_PUBLISHABLE_KEY' . (!$sandbox ? '_TEST' : ''),
                    $publishable_key
                );
        } catch (BadRequestException $e) {
            return [
                'result' => $flag,
                'error' => [
                    'name' => 'BAD_REQUEST_EXCEPTION',
                    'message' => $e->getMessage()
                ]
            ];
        }

        if (!$sandbox) {
            self::setSecretKey(Configuration::get('PAYPLUG_LIVE_API_KEY'));
        } else {
            self::setSecretKey(Configuration::get('PAYPLUG_TEST_API_KEY'));
        }

        return [
            'result' => $flag,
            'error' => []
        ];
    }

    /**
     * @description Read API response and return permissions
     *
     * @param string $json_answer
     * @param bool $is_sandbox
     * @return array OR bool
     */
    private static function treatAccountResponse($json_answer, $is_sandbox = true)
    {
        if ((isset($json_answer['object']) && $json_answer['object'] == 'error')
            || empty($json_answer)
        ) {
            return false;
        }

        $id = $json_answer['id'];

        $configuration = [
            'currencies' => Configuration::get('PAYPLUG_CURRENCIES'),
            'min_amounts' => Configuration::get('PAYPLUG_MIN_AMOUNTS'),
            'max_amounts' => Configuration::get('PAYPLUG_MAX_AMOUNTS'),
            'oney_allowed_countries' => Configuration::get('PAYPLUG_ONEY_ALLOWED_COUNTRIES'),
            'oney_max_amounts' => Configuration::get('PAYPLUG_ONEY_MAX_AMOUNTS'),
            'oney_min_amounts' => Configuration::get('PAYPLUG_ONEY_MIN_AMOUNTS'),
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
                $configuration['min_amounts'] = Tools::substr($configuration['min_amounts'], 0, -1);
            }

            if (isset($json_answer['configuration']['max_amounts'])
                && !empty($json_answer['configuration']['max_amounts'])) {
                $configuration['max_amounts'] = '';
                foreach ($json_answer['configuration']['max_amounts'] as $key => $value) {
                    $configuration['max_amounts'] .= $key . ':' . $value . ';';
                }
                $configuration['max_amounts'] = Tools::substr($configuration['max_amounts'], 0, -1);
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
                    $configuration['oney_allowed_countries'] = Tools::substr($allowed, 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['min_amounts'])
                    && !empty($json_answer['configuration']['oney']['min_amounts'])
                ) {
                    $configuration['oney_min_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['min_amounts'] as $key => $value) {
                        $configuration['oney_min_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_min_amounts'] = Tools::substr($configuration['oney_min_amounts'], 0, -1);
                }

                if (isset($json_answer['configuration']['oney']['max_amounts'])
                    && !empty($json_answer['configuration']['oney']['max_amounts'])
                ) {
                    $configuration['oney_max_amounts'] = '';
                    foreach ($json_answer['configuration']['oney']['max_amounts'] as $key => $value) {
                        $configuration['oney_max_amounts'] .= $key . ':' . $value . ';';
                    }
                    $configuration['oney_max_amounts'] = Tools::substr($configuration['oney_max_amounts'], 0, -1);
                }
            }
        }

        if (isset($json_answer['payment_methods']['bancontact']['enabled'])) {
            $can_use_bancontact = $json_answer['payment_methods']['bancontact']['enabled'];
        } else {
            $can_use_bancontact = true;
        }

        $permissions = [
            'use_live_mode' => $json_answer['permissions']['use_live_mode'],
            'can_save_cards' => $json_answer['permissions']['can_save_cards'],
            'can_create_installment_plan' => $json_answer['permissions']['can_create_installment_plan'],
            'can_create_deferred_payment' => $json_answer['permissions']['can_create_deferred_payment'],
            'can_use_oney' => $json_answer['permissions']['can_use_oney'],
            'can_use_bancontact' => $can_use_bancontact,
        ];

        // If sandbox mode active, no allowed countries sent
        // Then set default as `FR,MQ,YT,RE,GF,GP,IT`
        if (isset($json_answer['is_live']) && !$json_answer['is_live']) {
            $configuration['oney_allowed_countries'] = 'FR,MQ,YT,RE,GF,GP,IT';
        }

        // Get company country
        $company_iso = isset($json_answer['country']) && $json_answer['country'] ? $json_answer['country'] : false;

        Configuration::updateValue('PAYPLUG_COMPANY_ID' . ($is_sandbox ? '_TEST' : ''), $id);
        Configuration::updateValue('PAYPLUG_COMPANY_ISO', $company_iso);
        Configuration::updateValue('PAYPLUG_CURRENCIES', implode(';', $configuration['currencies']));
        Configuration::updateValue('PAYPLUG_MIN_AMOUNTS', $configuration['min_amounts']);
        Configuration::updateValue('PAYPLUG_MAX_AMOUNTS', $configuration['max_amounts']);
        Configuration::updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', $configuration['oney_allowed_countries']);
        Configuration::updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', $configuration['oney_max_amounts']);
        Configuration::updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', $configuration['oney_min_amounts']);

        return $permissions;
    }

    /**
     * @return string
     */
    public static function getCurrentApiKey()
    {
        if ((int)Configuration::get('PAYPLUG_SANDBOX_MODE') === 1) {
            return Configuration::get('PAYPLUG_TEST_API_KEY');
        } else {
            return Configuration::get('PAYPLUG_LIVE_API_KEY');
        }
    }

    /**
     * Determine wich API key to use
     *
     * @return string
     */
    public static function setAPIKey()
    {
        $sandbox_mode = (int)Configuration::get('PAYPLUG_SANDBOX_MODE');
        $valid_key = null;
        if ($sandbox_mode) {
            $valid_key = Configuration::get('PAYPLUG_TEST_API_KEY');
        } else {
            $valid_key = Configuration::get('PAYPLUG_LIVE_API_KEY');
        }

        return $valid_key;
    }

    /**
     * Register API Keys
     *
     * @param string $json_answer
     * @return bool
     * @throws ConfigurationException
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
        Configuration::updateValue('PAYPLUG_TEST_API_KEY', $api_keys['test_key']);
        Configuration::updateValue('PAYPLUG_LIVE_API_KEY', $api_keys['live_key']);

        $is_sandbox = Configuration::get('PAYPLUG_SANDBOX_MODE');
        if ($is_sandbox) {
            self::setSecretKey($api_keys['test_key']);
        } else {
            self::setSecretKey($api_keys['live_key']);
        }

        return true;
    }

    /**
     * Determine witch environment is used
     *
     * @return void
     */
    private function setEnvironment()
    {
        if (isset($_SERVER['PAYPLUG_API_URL'])) {
            $this->setApiUrl($_SERVER['PAYPLUG_API_URL']);
        } else {
            $this->setApiUrl('https://api.payplug.com');
        }

        if (isset($_SERVER['PAYPLUG_SITE_URL'])) {
            $this->site_url = $_SERVER['PAYPLUG_SITE_URL'];
        } else {
            $this->site_url = 'https://www.payplug.com';
        }
    }

    /**
     * @param string $api_url
     * @return self
     * @throws BadParameterException
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
     * @param bool $token
     * @return Payplug\Payplug
     * @throws ConfigurationException
     */
    public static function setSecretKey($token = false)
    {
        if (!$token && self::getCurrentApiKey() != null) {
            $token = self::getCurrentApiKey();
        }

        if (!$token) {
            return false;
        }

        return \Payplug\Payplug::init([
            'secretKey' => $token,
            'apiVersion' => '2019-08-06'
        ]);
    }

    /**
     * Set the user-agent referenced in every API call to identify the module
     *
     * @return void
     */
    private function setUserAgent()
    {
        if ($this->current_api_key != null) {
            HttpClient::setDefaultUserAgentProduct(
                'PayPlug-Prestashop',
                $this->dependencies->version,
                'Prestashop/' . _PS_VERSION_
            );
        }
    }

    public function initializeApi($sandbox = null)
    {
        if ($sandbox === null) {
            $payplug_key = $this->current_api_key;
        } else {
            $payplug_key = Configuration::get('PAYPLUG_' . ($sandbox ? 'TEST' : 'LIVE') . '_API_KEY');
        }

        try {
            \Payplug\Payplug::init([
                'secretKey' => $payplug_key,
                'apiVersion' => $this->dependencies->getPlugin()->getApiVersion()
            ]);

            return $payplug_key;
        } catch (Exception $e) {
            // todo: return error log
            return false;
        }
    }

    /**
     * Return exeption error form API
     * @param $str
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
            // add specific error message
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
     * @return bool
     */
    public static function hasLiveKey()
    {
        return (bool)Configuration::get('PAYPLUG_LIVE_API_KEY');
    }

    /**
     * login to Payplug API
     *
     * @param string $email
     * @param string $password
     * @return bool
     * @throws BadRequestException
     */
    public function login($email, $password)
    {
        try {
            $response = \Payplug\Authentication::getKeysByLogin($email, $password);
            $json_answer = $response['httpResponse'];

            if ($this->setApiKeysbyJsonResponse($json_answer)) {
                $publishable_keys = $this->setPublishableKeys();
                return $publishable_keys;
            } else {
                return false;
            }
        } catch (BadRequestException $e) {
            json_encode([
                'content' => null,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return $this->api_url;
    }

    public function getSiteUrl()
    {
        return $this->site_url;
    }
}
