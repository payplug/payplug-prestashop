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

class LoggerRepository extends EntityRepository
{
    public function __construct($prefix = '', $dependencies = null)
    {
        parent::__construct($prefix, $dependencies);
        $this->entity_name = 'LoggerEntity';
    }

    /**
     * @description Delete all log from a given date.
     *
     * @param string $date
     *
     * @return bool
     */
    public function deleteFromDate($date = '')
    {
        if (!is_string($date) || !$date) {
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
            ->where('`date_add` < ' . $this->escape($date))
            ->build();

        return $result ?: false;
    }

    /**
     * @description Delete all log from a given id.
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteFromId($id = 0)
    {
        if (!is_int($id) || !$id) {
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
            ->where('`' . $definition['primary'] . '` < ' . (int) $id)
            ->build();

        return $result ?: false;
    }

    /**
     * @description Get last log from a given limit.
     *
     * @param int $limit
     *
     * @return array
     */
    public function getLastLimitLog($limit = 0)
    {
        if (!is_int($limit) || !$limit) {
            return [];
        }
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return [];
        }
        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return [];
        }
        $definition = $entity->getDefinition();
        $result = $this
            ->select()
            ->fields($definition['primary'])
            ->from($this->getTableName($definition['table']))
            ->orderBy($definition['primary'] . ' DESC')
            ->limit($limit, 1)
            ->build('unique_row');

        return $result ?: [];
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
            ->fields('`id_payplug_logger` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`process` VARCHAR(255) NOT NULL')
            ->fields('`content` TEXT NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->engine($engine);

        return $this->build();
    }
}
