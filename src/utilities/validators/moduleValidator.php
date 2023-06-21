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

namespace PayPlug\src\utilities\validators;

class moduleValidator
{
    /**
     * @description Check if the module can be shown
     *
     * @param false $configuration
     *
     * @return array
     */
    public function canBeShown($configuration = false)
    {
        if (!is_bool($configuration)) {
            return [
                'result' => false,
                'message' => 'Invalid parameters given, $configuration and $showed must be a boolean',
            ];
        }

        if (!$configuration) {
            return [
                'result' => false,
                'message' => 'The module is setted to be hide',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if the account is linked to ps account
     *
     * @param object $module
     *
     * @return array
     */
    public function isAccountLinkedToPsAccount($module = null)
    {
        if (!is_object($module) || !$module) {
            return [
                'result' => false,
                'message' => 'Invalid parameters given, $module must be an non empty object',
            ];
        }

        try {
            $accountsFacade = $module->getService('ps_accounts.facade');
            $accountsService = $accountsFacade->getPsAccountsService();

            return [
                'result' => (bool) $accountsService->isAccountLinked(),
                'message' => '',
            ];
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleNotInstalledException $e) {
            return [
                'result' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\ModuleVersionException $e) {
            return [
                'result' => false,
                'message' => $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @description Check if the usage of the module is available
     *
     * @param false $enable
     * @param false $shown
     *
     * @return array
     */
    public function isAllowed($enable = false, $shown = false)
    {
        if (!is_bool($enable) || !is_bool($shown)) {
            return [
                'result' => false,
                'message' => 'Invalid parameters given, $enable and $shown must be a boolean',
            ];
        }

        if (!$enable && !$shown) {
            return [
                'result' => false,
                'message' => 'The module is not enable and is setted to be hidden',
            ];
        }

        if (!$enable) {
            return [
                'result' => false,
                'message' => 'The module is not enable',
            ];
        }

        if (!$shown) {
            return [
                'result' => false,
                'message' => 'The module is setted to be hidden',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check the requirement for the usage of the module.
     *
     * @param array $report
     *
     * @return array
     */
    public function isAllRequirementsChecked($report = [])
    {
        if (!is_array($report) || empty($report)) {
            return [
                'result' => false,
                'code' => 'format',
                'message' => 'Invalid parameters given, $report must be an non empty array',
            ];
        }

        if (!isset($report['php']) || !is_array($report['php']) || empty($report['php'])) {
            return [
                'result' => false,
                'code' => 'php_format',
                'message' => 'Invalid argument given, $report[php] must be a non empty array',
            ];
        }
        if (!isset($report['php']['up2date'])) {
            return [
                'result' => false,
                'code' => 'php_format',
                'message' => 'Missing array key: $report[php][up2date]',
            ];
        }
        if (!(bool) $report['php']['up2date']) {
            return [
                'result' => false,
                'code' => 'php_requirements',
                'message' => 'Wrong requirement: The minimum requirement for PHP is not respected',
            ];
        }

        if (!isset($report['curl']) || !is_array($report['curl']) || empty($report['curl'])) {
            return [
                'result' => false,
                'code' => 'curl_format',
                'message' => 'Invalid argument given, $report[curl] must be a non empty array',
            ];
        }
        if (!isset($report['curl']['installed'])) {
            return [
                'result' => false,
                'code' => 'curl_format',
                'message' => 'Missing array key: $report[curl][installed]',
            ];
        }
        if (!(bool) $report['curl']['installed']) {
            return [
                'result' => false,
                'code' => 'curl_requirements',
                'message' => 'Wrong requirement: The minimum requirement for Curl is not respected',
            ];
        }

        if (!isset($report['openssl']) || !is_array($report['openssl']) || empty($report['openssl'])) {
            return [
                'result' => false,
                'code' => 'openssl_format',
                'message' => 'Invalid argument given, $report[openssl] must be a non empty array',
            ];
        }
        if (!isset($report['openssl']['installed']) || !isset($report['openssl']['up2date'])) {
            return [
                'result' => false,
                'code' => 'openssl_format',
                'message' => 'Missing array key: $report[openssl][installed] and/or $report[openssl][up2date]',
            ];
        }
        if (!(bool) $report['openssl']['installed'] || !(bool) $report['openssl']['up2date']) {
            return [
                'result' => false,
                'code' => 'openssl_requirements',
                'message' => 'Wrong requirement: The minimum requirement for OpenSSL is not respected',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if a feature of the module is valid
     *
     * @param array  $features
     * @param string $name
     *
     * @return array
     */
    public function isFeature($features = [], $name = '')
    {
        if (!is_array($features) || empty($features)) {
            return [
                'result' => false,
                'message' => 'Invalid parameters given, $features must be an non empty array',
            ];
        }

        if (!is_string($name) || !$name) {
            return [
                'result' => false,
                'message' => 'Invalid parameters given, $name must be an non empty string',
            ];
        }

        foreach ($features['features'] as $feature) {
            if ($feature == $name) {
                return [
                    'result' => true,
                    'message' => '',
                ];
            }
        }

        return [
            'result' => false,
            'message' => 'The given $feature can\'t be use',
        ];
    }
}
