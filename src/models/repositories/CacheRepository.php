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

namespace PayPlug\src\models\repositories;

if (!defined('_PS_VERSION_')) {
    exit;
}

class CacheRepository extends QueryRepository
{
    private $fields = [
        'cache_key' => 'string',
        'cache_value' => 'string',
        'date_add' => 'string',
        'date_upd' => 'string',
    ];

    public function __construct($prefix = '', $dependencies = null)
    {
        parent::__construct($prefix, $dependencies);
        $this->table_name = $this->prefix . $this->dependencies->name . '_cache';
    }

    /**
     * @description Create a cache from given parameters
     *
     * @param array $parameters
     *
     * @return bool
     */
    public function createCache($parameters = [])
    {
        if (!is_array($parameters) || empty($parameters)) {
            return false;
        }

        $this
            ->insert()
            ->into($this->table_name);

        foreach ($parameters as $key => $value) {
            if (array_key_exists($key, $this->fields)) {
                switch ($this->fields[$key]) {
                    case 'string':
                        if (is_string($value) && $value) {
                            $this->fields($key)->values($this->escape($value));
                        }

                        break;
                    case 'integer':
                        if (is_int($value)) {
                            $this->fields($key)->values((int) $value);
                        }

                        break;
                    case 'bool':
                        if (is_bool($value)) {
                            $this->fields($key)->values($value ? 1 : 0);
                        }

                        break;
                    default:
                        break;
                }
            }
        }

        return (bool) $this->build();
    }

    /**
     * @description Get cache saved from a given key
     *
     * @param string $cache_key
     *
     * @return array
     */
    public function getByKey($cache_key = '')
    {
        if (!is_string($cache_key) || !$cache_key) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->where('`cache_key` = "' . $this->escape($cache_key) . '"')
            ->build('unique_row');

        return $result ?: [];
    }

    /**
     * @description Delete the cache for a given key
     *
     * @param string $cache_key
     *
     * @return bool
     */
    public function deleteCache($cache_key = '')
    {
        if (!is_string($cache_key) || !$cache_key) {
            return false;
        }

        $result = $this
            ->delete()
            ->from($this->table_name)
            ->where('`cache_key` = \'' . $this->escape($cache_key) . '\'')
            ->build();

        return $result ?: false;
    }

    // todo: add coverage to this method
    public function flushCache()
    {
        $result = $this
            ->truncate()
            ->table($this->table_name);

        return (bool) $result;
    }

    /**
     * @description Create the table in the database
     *
     * @param string $engine
     *
     * @return bool
     */
    public function initialize($engine = '')
    {
        if (!is_string($engine) || !$engine) {
            return false;
        }

        $this
            ->create()
            ->table($this->table_name)
            ->fields('`id_payplug_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`cache_key` VARCHAR(255) NOT NULL')
            ->fields('`cache_value` TEXT NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->engine($engine);

        return $this->build();
    }
}
