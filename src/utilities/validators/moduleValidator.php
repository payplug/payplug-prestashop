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

        if (!is_string($name) || empty($name)) {
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
}
