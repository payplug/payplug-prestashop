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
    private $cache_key;

    /** @var string */
    private $cache_value;

    /** @var string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss' */
    private $date_add;

    /** @var string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss' */
    private $date_upd;

    /** @var array */
    private $definition;

    /** @var string */
    private $id_payplug_cache;

    /** @var string */
    private $table;

    /**
     * @return string
     */
    public function getCacheKey()
    {
        return $this->cache_key;
    }

    /**
     * @return string
     */
    public function getCacheValue()
    {
        return $this->cache_value;
    }

    /**
     * @return string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     */
    public function getDateAdd()
    {
        return $this->date_add;
    }

    /**
     * @return string with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     */
    public function getDateUpd()
    {
        return $this->date_upd;
    }

    /**
     * @return array
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return string
     */
    public function getIdPayPlugCache()
    {
        return $this->id_payplug_cache;
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
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
     * @param string $cache_value
     * @return CacheEntity
     */
    public function setCacheValue(string $cache_value)
    {
        $this->cache_value = $cache_value;
        return $this;
    }

    /**
     * @param string $date_add with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     * @return CacheEntity
     * @throws BadParameterExceptionEntity
     */
    public function setDateAdd(string $date_add)
    {
        if (!preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_add)) {
            throw (
                new BadParameterExceptionEntity(
                    'Invalid datetime format, param $date_add must be like \'yyyy-mm-dd hh:mm:ss\''
                )
            );
        } else {
            $this->date_add = $date_add;
            return $this;
        }
    }

    /**
     * @param string $date_upd with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     * @return CacheEntity
     * @throws BadParameterExceptionEntity
     */
    public function setDateUpd(string $date_upd)
    {
        if (!preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_upd)) {
            throw (
                new BadParameterExceptionEntity(
                    'Invalid datetime format, param $date_upd must be like \'yyyy-mm-dd hh:mm:ss\''
                )
            );
        } else {
            $this->date_upd = $date_upd;
            return $this;
        }
    }

    /**
     * @param string $id_payplug_cache
     * @return CacheEntity
     */
    public function setIdPayPlugCache(string $id_payplug_cache)
    {
        $this->id_payplug_cache = $id_payplug_cache;
        return $this;
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

    /**
     * @param string $table
     * @return CacheEntity
     */
    public function setTable(string $table)
    {
        $this->table = $table;
        return $this;
    }
}