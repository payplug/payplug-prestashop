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

        // base64: EntityRepository::createEntity()/updateEntity() escape string fields via
        // Db::escape($value, $htmlOK = false), which runs strip_tags() on the raw value. A
        // cached value may itself be HTML (e.g. a 3DS challenge form), so it's stored/read back
        // as base64 to survive that unrelated to what the caller puts in it.
        $value = base64_decode((string) $decoded['value'], true);

        return false !== $value ? $value : null;
    }

    public function set(string $key, string $value, int $ttlSeconds): void
    {
        $repository = $this->dependencies->getPlugin()->getCacheRepository();
        $physical_key = self::KEY_PREFIX . $key;
        $row = $repository->getBy('cache_key', $physical_key);
        $cache_value = json_encode(['value' => base64_encode($value), 'expires_at' => time() + $ttlSeconds]);

        if ($row) {
            $updated = $repository->updateEntity((int) $row['id_payplug_cache'], ['cache_value' => $cache_value]);

            if (!$updated) {
                // The ITokenCache contract returns void - a write failure can't be propagated to
                // the caller, so this is the only place it's observable at all. A caller storing
                // e.g. uhf_pending_operation right after a real Unified API payment was created
                // depends on this write succeeding; logging here is what lets a silent DB
                // error/truncation/duplicate key be diagnosed after the fact instead of vanishing.
                $this->logger()->error('UpcTokenCache::set - Failed to update cache row for key: ' . $key);
            }

            return;
        }

        $created_id = $repository->createEntity(['cache_key' => $physical_key, 'cache_value' => $cache_value]);

        if (!$created_id) {
            $this->logger()->error('UpcTokenCache::set - Failed to create cache row for key: ' . $key);
        }
    }

    public function delete(string $key): void
    {
        $this->dependencies->getPlugin()->getCacheRepository()->deleteBy('cache_key', self::KEY_PREFIX . $key);
    }

    private function logger(): UpcLogger
    {
        return new UpcLogger($this->dependencies);
    }
}
