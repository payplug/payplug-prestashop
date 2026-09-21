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

namespace PayPlug\src\utilities\traits;

if (!defined('_PS_VERSION_')) {
    exit;
}

use Symfony\Component\Dotenv\Dotenv;

/**
 * Parses payplugroutes/.env into $_ENV, at most once per request, and exposes a single getEnv()
 * accessor. NOTE: dirname(__FILE__, 5) below is resolved against THIS trait's own file (PHP
 * resolves magic constants inside a trait against the file the trait is defined in, not the file
 * of the class using it) — it depends on this trait living at the same path depth as the classes
 * using it (src/utilities/<subfolder>/File.php). If this trait ever moves, re-check the level count.
 */
trait DotenvGetter
{
    private static $dotenvLoaded = false;

    /**
     * @param string $key
     *
     * @return string|null
     */
    protected function getEnv($key)
    {
        if (!self::$dotenvLoaded) {
            $this->loadDotenv();
        }

        return isset($_ENV[$key]) ? $_ENV[$key] : null;
    }

    private function loadDotenv()
    {
        $dotenvFile = \dirname(__FILE__, 5) . '/payplugroutes/.env';
        if (self::$dotenvLoaded) {
            return;
        }
        self::$dotenvLoaded = (new Dotenv())->load($dotenvFile);
    }
}
