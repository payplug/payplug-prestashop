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

class CacheRepository extends EntityRepository
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
        $this->entity_name = 'CacheEntity';
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
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return false;
        }

        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return false;
        }
        $definition = $entity->getDefinition();

        $result = $this
            ->delete()
            ->from($this->getTableName($definition['table']))
            ->where('`cache_key` = \'' . $this->escape($cache_key) . '\'')
            ->build();

        return $result ?: false;
    }

    /**
     * @desccription delete cache by its identifier
     *
     * @param $id_payplug_cache
     *
     * @return bool
     */
    public function deleteCacheByID($id_payplug_cache = 0)
    {
        $result = $this
            ->deleteEntity($id_payplug_cache);

        return $result ?: false;
    }

    // todo: add coverage to this method

    /**
     * @desccription flush cash
     *
     * @return CacheRepository|false
     */
    public function flushCache()
    {
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return false;
        }
        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return false;
        }
        $definition = $entity->getDefinition();

        $result = $this
            ->truncate()
            ->table($this->getTableName($definition['table']));

        return $result ?: false;
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
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return false;
        }

        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return false;
        }
        $definition = $entity->getDefinition();

        $this
            ->create()
            ->table($this->getTableName($definition['table']))
            ->fields('`id_payplug_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`cache_key` VARCHAR(255) NOT NULL')
            ->fields('`cache_value` TEXT NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->engine($engine);

        return $this->build();
    }
}
