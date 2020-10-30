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

use PayPlug\src\exceptions\BadParameterException;

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
     * @throws BadParameterException
     */
    public function setCacheKey($cache_key)
    {
        if (!is_string($cache_key)) {
            throw (
                new BadParameterException(
                    'Invalid value, param $cache_key must be a string.'
                )
            );
        } else {
            $this->cache_key = $cache_key;
            return $this;
        }
    }

    /**
     * @param string $cache_value
     * @return CacheEntity
     * @throws BadParameterException
     */
    public function setCacheValue($cache_value)
    {
        if (!is_string($cache_value)) {
            throw (
                new BadParameterException(
                    'Invalid value, param $cache_value must be a string.'
                )
            );
        } else {
            $this->cache_value = $cache_value;
            return $this;
        }
    }

    /**
     * @param string $date_add with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     * @return CacheEntity
     * @throws BadParameterException
     */
    public function setDateAdd($date_add)
    {
        if (!is_string($date_add) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_add)) {
            throw (
                new BadParameterException(
                    'Invalid datetime format, param $date_add must be a string looking like \'yyyy-mm-dd hh:mm:ss\''
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
     * @throws BadParameterException
     */
    public function setDateUpd($date_upd)
    {
        if (!is_string($date_upd) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_upd)) {
            throw (
                new BadParameterException(
                    'Invalid datetime format, param $date_upd must be a string looking like \'yyyy-mm-dd hh:mm:ss\''
                )
            );
        } else {
            $this->date_upd = $date_upd;
            return $this;
        }
    }

    /**
     * @param array $definition
     * @return CacheEntity
     * @throws BadParameterException
     */
    public function setDefinition($definition)
    {
        if (!is_array($definition)) {
            throw (new BadParameterException('Invalid id, param $definition must be an array'));
        } else {
            $this->definition = $definition;
            return $this;
        }
    }

    /**
     * @param string $id_payplug_cache
     * @return CacheEntity
     * @throws BadParameterException
     */
    public function setIdPayPlugCache($id_payplug_cache)
    {
        if (!is_string($id_payplug_cache)) {
            throw (new BadParameterException('Invalid id, param $id_payplug_cache must be a string'));
        } else {
            $this->id_payplug_cache = $id_payplug_cache;
            return $this;
        }
    }

    /**
     * @param string $table
     * @return CacheEntity
     * @throws BadParameterException
     */
    public function setTable($table)
    {
        if (!is_string($table)) {
            throw (new BadParameterException('Invalid id, param $table must be a string'));
        } else {
            $this->table = $table;
            return $this;
        }
    }
}