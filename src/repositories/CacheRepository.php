<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use DateInterval;
use DateTime;
use PayPlug\src\entities\CacheEntity;
use PayPlug\src\specific\ConfigurationSpecific;
use PayPlug\src\exceptions\BadParameterException;

class CacheRepository extends Repository
{
    public $cacheEntity;
    private $query;
    private $config;
    private $logger;

    public function __construct(
        $cacheEntity,
        $query,
        $config,
        $logger
    ) {
        $this->cacheEntity = $cacheEntity;
        $this->config = $config;
        $this->logger = $logger;
        $this->query = $query;

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
     * @return boolean
     * @throws BadParameterException
     */
    public function setCache($cache_key, $cache_value)
    {
        // check if exists
        $cache = $this->getCacheByKey($cache_key);

        if (!$cache) {
            $this->cacheEntity->setDateAdd($this->logger->udate('Y-m-d H:i:s'));

            $cache = $this->cacheEntity;

            $this->query
                ->insert()
                ->into(_DB_PREFIX_ . 'payplug_cache')
                ->fields('cache_key')->values(pSQL($cache_key))
                ->fields('cache_value')->values(json_encode($cache_value))
                ->fields('date_add')->values(pSQL($cache->getDateAdd()))
                ->fields('date_upd')->values(pSQL($cache->getDateAdd()));

            if (!$this->query->build()) {
                return false;
            }

            return true;
        }
    }

    /**
     * @description Set Cache
     *
     * @param int $amount
     * @param string $country
     * @param array $operation contain x3|4_with_fees or x3|4_without_fees
     * @return array
     */
    public function setCacheKey($amount, $country, $operations)
    {
        if (!is_int($amount)) {
            return [
                'result' => false,
                'message' => 'Amount is not a valid int'
            ];
        }

        if (!is_string($country)) {
            return [
                'result' => false,
                'message' => 'Country is not a valid string'
            ];
        }

        if (!is_array($operations)) {
            return [
                'result' => false,
                'message' => 'Operations is not a valid array'
            ];
        }

        $cache_id = 'Payplug::OneySimulations_' .
            (int)$amount . '_' .
            (string)$country . '_' .
            (string)implode('_', $operations) . '_' .
            ($this->config->get('PAYPLUG_SANDBOX_MODE') ? 'test' : 'live');

        return [
            'result' => $cache_id,
            'message' => 'success'
        ];
    }

    /**
     * @description Get the Oney simulation (identified by id_payplug_cache in parameter).
     *
     * @param string $cache_key
     * @return bool|mixed
     */
    public function getCacheByKey($cache_key)
    {
        if (!is_string($cache_key) || !$cache_key) {
            return [
                'result' => false,
                'message' => 'Invalid cache key format'
            ];
        }

        $this->query
            ->select()
            ->fields('*')
            ->from(_DB_PREFIX_ . 'payplug_cache')
            ->where('`cache_key` = \'' . (string)$cache_key . '\'');

        $cache = $this->query->build();

        if (!$cache) {
            return [
                'result' => false,
                'message' => 'No cache found'
            ];
        }

        // if the cache is older than 48 hours, return false after delete it
        $lifetime = new \DateInterval('P2D');
        $date_limit = new \DateTime('now');
        $date_limit->sub($lifetime);
        $date_add = new \DateTime($cache['date_add']);
        if ($date_limit > $date_add) {
            $this->query
                ->delete()
                ->from(_DB_PREFIX_ . 'payplug_cache')
                ->where('`cache_key` = \'' . (string)$cache_key . '\'');
            return [
                'result' => false,
                'message' => 'The current cache has been deleted'
            ];
        }


        return [
            'result' => $cache,
            'message' => 'Success'
        ];
    }

    /**
     * @description Flush all the cache.
     * Remove all Oney Simulation.
     *
     * @return boolean
     */
    public function flushCache()
    {
        $this->query
            ->truncate()
            ->table(_DB_PREFIX_ . 'payplug_cache');

        if (!$this->query->build()) {
            $error_message = 'Error during flush the Oney Simulation DB cache [PayPlugCache.php]';
            $error_level = 'error';
            $this->logger->addLog($error_message, $error_level);
            return false;
        }

        return true;
    }
}
