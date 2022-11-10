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

namespace PayPlug\src\repositories;

use PayPlug\src\application\dependencies\BaseClass;
use PayPlug\src\exceptions\BadParameterException;

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

        $this->logger->setParams(['process' => 'cache']);
    }

    public static function factory()
    {
        return new CacheRepository();
    }

    /**
     * @description Set every Oney Simulation in the DB
     *
     * @param string $cache_key
     * @param string $cache_value
     *
     * @throws BadParameterException
     *
     * @return bool
     */
    public function setCache($cache_key, $cache_value)
    {
        // check if exists
        $cache = $this->getCacheByKey($cache_key);

        if (!$cache['result']) {
            $this->cacheEntity->setDateAdd($this->logger->udate('Y-m-d H:i:s'));

            $this->query
                ->insert()
                ->into($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_cache')
                ->fields('cache_key')->values(pSQL($cache_key))
                ->fields('cache_value')->values(json_encode($cache_value))
                ->fields('date_add')->values(pSQL($this->cacheEntity->getDateAdd()))
                ->fields('date_upd')->values(pSQL($this->cacheEntity->getDateAdd()));

            if (!$this->query->build()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @description Set Cache
     *
     * @param int    $amount
     * @param string $country
     * @param array  $operation  contain x3|4_with_fees or x3|4_without_fees
     * @param mixed  $operations
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
            ($this->config->get(
                $this->dependencies->getConfigurationKey('sandboxMode')
            ) ? 'test' : 'live');

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

        $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_cache')
            ->where('`cache_key` = \'' . $this->query->escape($cache_key) . '\'')
        ;

        $result = $this->query->build();

        if (!$result) {
            return [
                'result' => false,
                'message' => 'No cache found',
            ];
        }

        $cache = reset($result);

        // if the cache is older than 48 hours, return false after delete it
        $lifetime = new \DateInterval('P2D');
        $date_limit = new \DateTime('now');
        $date_limit->sub($lifetime);
        $date_add = new \DateTime($cache['date_add']);
        if ($date_limit >= $date_add) {
            $this->deleteCacheByKey($cache_key);

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
     * @description Delete cache for a given key
     *
     * @param $cache_key
     */
    public function deleteCacheByKey($cache_key)
    {
        $this->query
            ->delete()
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_cache')
            ->where('`cache_key` = \'' . $this->query->escape($cache_key) . '\'')
            ->build()
        ;
    }

    /**
     * @description Flush all the cache.
     * Remove all Oney Simulation.
     *
     * @return bool
     */
    public function flushCache()
    {
        $this->query
            ->truncate()
            ->table($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_cache')
        ;

        if (!$this->query->build()) {
            $error_message = 'Error during flush the Oney Simulation DB cache [PayPlugCache.php]';
            $error_level = 'error';
            $this->logger->addLog($error_message, $error_level);

            return false;
        }

        return true;
    }
}
