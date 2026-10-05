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

namespace PayPlug\src\models\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayplugUnifiedCore\Contracts\ILogger;

class UpcLogger implements ILogger
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log($message, $context, 'info');
    }

    public function info(string $message, array $context = []): void
    {
        $this->log($message, $context, 'info');
    }

    public function error(string $message, array $context = []): void
    {
        $this->log($message, $context, 'error');
    }

    private function log(string $message, array $context, string $level): void
    {
        // A logging call must never interrupt its caller: no-op on an empty message
        // rather than letting LoggerRepository::addLog()'s BadParameterException propagate.
        if ('' === $message) {
            return;
        }

        $full_message = $context ? $message . ' ' . json_encode($context) : $message;
        $this->dependencies->getPlugin()->getLogger()->addLog($full_message, $level);
    }
}
