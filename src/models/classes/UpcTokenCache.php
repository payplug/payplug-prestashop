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

use PayplugUnifiedCore\Contracts\ITokenCache;

class UpcTokenCache implements ITokenCache
{
    private const KEY_PREFIX = 'upc_token:';

    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    public function get(string $key): ?string
    {
        $repository = $this->dependencies->getPlugin()->getCacheRepository();
        $row = $repository->getBy('cache_key', self::KEY_PREFIX . $key);

        if (!$row) {
            return null;
        }

        $decoded = json_decode($row['cache_value'], true);

        if (!is_array($decoded) || !isset($decoded['value'], $decoded['expires_at'])) {
            return null;
        }

        if ((int) $decoded['expires_at'] < time()) {
            $repository->deleteBy('cache_key', self::KEY_PREFIX . $key);

            return null;
        }

        return (string) $decoded['value'];
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $repository = $this->dependencies->getPlugin()->getCacheRepository();
        $physical_key = self::KEY_PREFIX . $key;
        $row = $repository->getBy('cache_key', $physical_key);
        $cache_value = json_encode(['value' => $value, 'expires_at' => time() + $ttlSeconds]);

        if ($row) {
            $repository->updateEntity((int) $row['id_payplug_cache'], ['cache_value' => $cache_value]);

            return;
        }

        $repository->createEntity(['cache_key' => $physical_key, 'cache_value' => $cache_value]);
    }

    public function delete(string $key): void
    {
        $this->dependencies->getPlugin()->getCacheRepository()->deleteBy('cache_key', self::KEY_PREFIX . $key);
    }
}
