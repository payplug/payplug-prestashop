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

if (!defined('_PS_VERSION_')) {
    exit;
}

class loggerValidator
{
    private $allowed_process = [
        'cache',
        'card',
        'config',
        'notification',
        'oney',
        'payment',
        'refund',
        'sql',
        'validation',
    ];

    /**
     * @description Check if given process is allowed
     *
     * @param string $process
     *
     * @return array
     */
    public function isAllowedProcess($process = '')
    {
        if (!is_string($process) || !$process) {
            return [
            'result' => false,
            'message' => 'Invalid argument given, $process must be a non empty string',
        ];
        }

        if (!in_array($process, $this->allowed_process)) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $process is not allowed',
            ];
        }

        return [
            'result' => true,
            'message' => '',
        ];
    }

    /**
     * @description Check if given content is valid
     *
     * @param string $message
     *
     * @return array
     */
    public function isContent($message = '')
    {
        if (!is_string($message) || !$message) {
            return [
                'result' => false,
                'message' => 'Invalid argument given, $message must be a non empty string',
            ];
        }

        // todo: add restriction on the content of the logger to avoid security breach
        // preg_match('/[\[\]^<>={}$*]/', $message)

        return [
            'result' => true,
            'message' => '',
        ];
    }
}
