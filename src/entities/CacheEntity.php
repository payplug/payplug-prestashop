<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 * @copyright 2013 - 2020 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\entities;


class CacheEntity
{
    /** @var string */
    private $id_payplug_cache;

    /** @var string */
    private $cache_key;

    /** @var string */
    private $cache_value;

    /** @var datetime */
    private $date_add;

    /** @var datetime */
    private $date_upd;

    /** @var PayPlugLogger  */
    private $logger;

    /** @var array */
    private $definition;

    /**
     * @return string
     */
    public function getIdPayplugCache()
    {
        return $this->id_payplug_cache;
    }

    /**
     * @param string $id_payplug_cache
     * @return CacheEntity
     */
    public function setIdPayplugCache(string $id_payplug_cache)
    {
        $this->id_payplug_cache = $id_payplug_cache;
        return $this;
    }

    /**
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cache_key;
    }

    /**
     * @param string $cache_key
     * @return CacheEntity
     */
    public function setCacheKey(string $cache_key)
    {
        $this->cache_key = $cache_key;
        return $this;
    }

    /**
     * @return string
     */
    public function getCacheValue()
    {
        return $this->cache_value;
    }

    /**
     * @param string $cache_value
     * @return CacheEntity
     */
    public function setCacheValue(string $cache_value)
    {
        $this->cache_value = $cache_value;
        return $this;
    }

    /**
     * @return datetime
     */
    public function getDateAdd()
    {
        return $this->date_add;
    }

    /**
     * @param datetime $date_add
     * @return CacheEntity
     */
    public function setDateAdd(datetime $date_add)
    {
        $this->date_add = $date_add;
        return $this;
    }

    /**
     * @return datetime
     */
    public function getDateUpd()
    {
        return $this->date_upd;
    }

    /**
     * @param datetime $date_upd
     * @return CacheEntity
     */
    public function setDateUpd(datetime $date_upd)
    {
        $this->date_upd = $date_upd;
        return $this;
    }

    /**
     * @return PayPlugLogger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param PayPlugLogger $logger
     * @return CacheEntity
     */
    public function setLogger(PayPlugLogger $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return array
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @param array $definition
     * @return CacheEntity
     */
    public function setDefinition($definition)
    {
        $this->definition = $definition;
        return $this;
    }
}