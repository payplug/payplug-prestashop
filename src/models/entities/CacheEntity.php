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

namespace PayPlug\src\models\entities;

if (!defined('_PS_VERSION_')) {
    exit;
}

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
    private static $definition = [
        'table' => 'payplug_cache',
        'primary' => 'id_payplug_cache',
        'fields' => [
            'cache_key' => ['type' => 'string', 'required' => true],
            'cache_value' => ['type' => 'string', 'required' => true],
            'date_add' => ['type' => 'string'],
            'date_upd' => ['type' => 'string'],
        ],
    ];

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
        return self::$definition;
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
     * @param $cache_key
     *
     * @return $this
     */
    public function setCacheKey($cache_key)
    {
        if (!is_string($cache_key)) {
            throw new BadParameterException('Invalid argument, $cache_key must be a string.');
        }

        $this->cache_key = $cache_key;

        return $this;
    }

    /**
     * @param $cache_value
     *
     * @return $this
     */
    public function setCacheValue($cache_value)
    {
        if (!is_string($cache_value)) {
            throw new BadParameterException('Invalid argument, $cache_value must be a string.');
        }

        $this->cache_value = $cache_value;

        return $this;
    }

    /**
     * @param $date_add
     *
     * @return $this
     */
    public function setDateAdd($date_add)
    {
        if (!is_string($date_add) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_add)) {
            throw new BadParameterException('Invalid argument, $date_add must be at format: \'Y-m-d h:m:s\'');
        }

        $this->date_add = $date_add;

        return $this;
    }

    /**
     * @param $date_upd
     *
     * @return $this
     */
    public function setDateUpd($date_upd)
    {
        if (!is_string($date_upd) || !preg_match('/(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})/', $date_upd)) {
            throw new BadParameterException('Invalid argument, $date_upd must be at format: \'Y-m-d h:m:s\'');
        }

        $this->date_upd = $date_upd;

        return $this;
    }

    /**
     * @param $id_payplug_cache
     *
     * @return $this
     */
    public function setIdPayPlugCache($id_payplug_cache)
    {
        if (!is_string($id_payplug_cache)) {
            throw new BadParameterException('Invalid argument, $id_payplug_cache must be a string');
        }

        $this->id_payplug_cache = $id_payplug_cache;

        return $this;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function setTable($table)
    {
        if (!is_string($table)) {
            throw new BadParameterException('Invalid argument, $table must be a string');
        }

        $this->table = $table;

        return $this;
    }
}
