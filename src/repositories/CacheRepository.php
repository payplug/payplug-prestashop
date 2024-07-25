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

namespace PayPlug\src\repositories;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\application\dependencies\BaseClass;

class CacheRepository extends BaseClass
{
    public $cacheEntity;
    private $query;
    private $config;
    private $dependencies;
    private $logger;
    private $constant;

    public function __construct(
        $cacheEntity,
        $query,
        $config,
        $dependencies,
        $logger,
        $constant
    ) {
        $this->cacheEntity = $cacheEntity;
        $this->config = $config;
        $this->dependencies = $dependencies;
        $this->logger = $logger;
        $this->query = $query;
        $this->constant = $constant;

        $this->logger->setProcess('cache');
    }

    /**
     * @description Set every Oney Simulation in the DB
     *
     * @param $cache_key
     * @param $cache_value
     *
     * @return bool
     */
    public function setCache($cache_key, $cache_value)
    {
        // check if exists
        $cache = $this->getCacheByKey($cache_key);

        if (!$cache['result']) {
            $this->cacheEntity->setDateAdd($this->logger->udate('Y-m-d H:i:s'));
            $parameters = [
                'cache_key' => $cache_key,
                'cache_value' => json_encode($cache_value),
                'date_add' => $this->cacheEntity->getDateAdd(),
                'date_upd' => $this->cacheEntity->getDateAdd(),
            ];

            $create_cache = $this->dependencies
                ->getPlugin()
                ->getCacheRepository()
                ->createEntity($parameters);

            return (bool) $create_cache;
        }

        return true;
    }

    /**
     * @description Set Cache
     *
     * @param $amount
     * @param $country
     * @param $operations
     *
     * @return array
     */
    public function setCacheKey($amount, $country, $operations)
    {
        if (!is_numeric($amount)) {
            return [
                'result' => false,
                'message' => 'Amount is not a valid int',
            ];
        }

        if (!is_string($country)) {
            return [
                'result' => false,
                'message' => 'Country is not a valid string',
            ];
        }

        if (!is_array($operations)) {
            return [
                'result' => false,
                'message' => 'Operations is not a valid array',
            ];
        }

        $cache_id = 'Payplug::OneySimulations_' .
            (int) $amount . '_' .
            (string) $country . '_' .
            (string) implode('_', $operations) . '_' .
            ($this->config->getValue('sandbox_mode') ? 'test' : 'live');

        return [
            'result' => $cache_id,
            'message' => 'success',
        ];
    }

    /**
     * @description Get the Oney simulation (identified by id_payplug_cache in parameter).
     *
     * @param string $cache_key
     *
     * @return bool|mixed
     */
    public function getCacheByKey($cache_key)
    {
        if (!is_string($cache_key) || !$cache_key) {
            return [
                'result' => false,
                'message' => 'Invalid cache key format',
            ];
        }

        $cache = $this->dependencies
            ->getPlugin()
            ->getCacheRepository()
            ->getBy('cache_key', $cache_key);

        if (empty($cache)) {
            return [
                'result' => false,
                'message' => 'No cache found',
            ];
        }

        // if the cache is older than 48 hours, return false after delete it
        $lifetime = new \DateInterval('P2D');
        $date_limit = new \DateTime('now');
        $date_limit->sub($lifetime);
        $date_add = new \DateTime($cache['date_add']);
        if ($date_limit >= $date_add) {
            $this->dependencies
                ->getPlugin()
                ->getCacheRepository()
                ->deleteCache($cache_key);

            return [
                'result' => false,
                'message' => 'The current cache has been deleted',
            ];
        }

        return [
            'result' => $cache,
            'message' => 'Success',
        ];
    }

    /**
     * @description Flush all the cache.
     * Remove all Oney Simulation.
     *
     * @return bool
     */
    public function flushCache()
    {
        $truncate = $this->dependencies
            ->getPlugin()
            ->getCacheRepository()
            ->flushCache();

        if (!$truncate) {
            $error_message = 'Error during flush the Oney Simulation DB cache [PayPlugCache.php]';
            $error_level = 'error';
            $this->logger->addLog($error_message, $error_level);

            return false;
        }

        return true;
    }
}
