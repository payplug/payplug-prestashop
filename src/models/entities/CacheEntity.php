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

namespace PayPlug\src\models\entities;

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
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCacheKey($cache_key)
    {
        if (!is_string($cache_key)) {
            throw (new BadParameterException('Invalid argument, $cache_key must be a string.'));
        }

        $this->cache_key = $cache_key;

        return $this;
    }

    /**
     * @param string $cache_value
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setCacheValue($cache_value)
    {
        if (!is_string($cache_value)) {
            throw (new BadParameterException('Invalid argument, $cache_value must be a string.'));
        }

        $this->cache_value = $cache_value;

        return $this;
    }

    /**
     * @param string $date_add with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setDateAdd($date_add)
    {
        if (!is_string($date_add) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_add)) {
            throw (
                new BadParameterException(
                    'Invalid argument, $date_add must be a string looking like \'yyyy-mm-dd hh:mm:ss\''
                )
            );
        }

        $this->date_add = $date_add;

        return $this;
    }

    /**
     * @param string $date_upd with a specific pattern matching 'yyyy-mm-dd hh:mm:ss'
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setDateUpd($date_upd)
    {
        if (!is_string($date_upd) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_upd)) {
            throw (
                new BadParameterException(
                    'Invalid argument, $date_upd must be a string looking like \'yyyy-mm-dd hh:mm:ss\''
                )
            );
        }

        $this->date_upd = $date_upd;

        return $this;
    }

    /**
     * @param array $definition
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setDefinition($definition)
    {
        if (!is_array($definition)) {
            throw (new BadParameterException('Invalid argument, $definition must be an array'));
        }

        $this->definition = $definition;

        return $this;
    }

    /**
     * @param string $id_payplug_cache
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setIdPayPlugCache($id_payplug_cache)
    {
        if (!is_string($id_payplug_cache)) {
            throw (new BadParameterException('Invalid argument, $id_payplug_cache must be a string'));
        }

        $this->id_payplug_cache = $id_payplug_cache;

        return $this;
    }

    /**
     * @param string $table
     *
     * @throws BadParameterException
     *
     * @return self
     */
    public function setTable($table)
    {
        if (!is_string($table)) {
            throw (new BadParameterException('Invalid argument, $table must be a string'));
        }

        $this->table = $table;

        return $this;
    }
}
