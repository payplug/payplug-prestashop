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
    private $fields = [
        'process' => 'string',
        'content' => 'string',
        'date_add' => 'string',
        'date_upd' => 'string',
    ];

    public function __construct($prefix = '', $dependencies = null)
    {
        parent::__construct($prefix, $dependencies);
        $this->table_name = $this->prefix . $this->dependencies->name . '_logger';
    }

    /**
     * @description Create a log from given parameters
     *
     * @param array $parameters
     *
     * @return bool
     */
    public function createLog($parameters = [])
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

        return (bool) $this->build() ? $this->lastId() : false;
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

        $this
            ->delete()
            ->from($this->table_name)
            ->where('`date_add` < ' . $this->escape($date));

        return (bool) $this->build();
    }

    /**
     * @description Delete all log from a given id.
     *
     * @param int $id_logger
     *
     * @return bool
     */
    public function deleteFromId($id_logger = 0)
    {
        if (!is_int($id_logger) || !$id_logger) {
            return false;
        }

        $this
            ->delete()
            ->from($this->table_name)
            ->where('`id_payplug_logger` < ' . (int) $id_logger);

        return (bool) $this->build();
    }

    /**
     * @description Flush the log table.
     *
     * @return bool
     */
    public function flushLog()
    {
        $result = $this
            ->truncate()
            ->table($this->table_name);

        return (bool) $result;
    }

    /**
     * @description Get all log from table.
     *
     * @return array
     */
    public function getAllLog()
    {
        $result = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->build();

        return $result ?: [];
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

        $result = $this
            ->select()
            ->fields('`id_payplug_logger`')
            ->from($this->table_name)
            ->orderBy('`id_payplug_logger` DESC')
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

    /**
     * @description Update an existing payment for a given id logger.
     *
     * @param int $id_logger
     * @param array $parameters
     *
     * @return bool
     */
    public function updateLog($id_logger = 0, $parameters = [])
    {
        if (!is_int($id_logger) || !$id_logger) {
            return false;
        }

        if (!is_array($parameters) || empty($parameters)) {
            return false;
        }

        $this
            ->update()
            ->table($this->table_name);

        foreach ($parameters as $key => $value) {
            if (array_key_exists($key, $this->fields)) {
                switch ($this->fields[$key]) {
                    case 'string':
                        if (is_string($value) && $value) {
                            $this->set($key . ' = "' . $this->escape($value) . '"');
                        }

                        break;
                    case 'integer':
                        if (is_int($value)) {
                            $this->set($key . ' = ' . (int) $value);
                        }

                        break;
                    case 'bool':
                        if (is_bool($value)) {
                            $this->set($key . ' = ' . ($value ? 1 : 0));
                        }

                        break;
                    default:
                        break;
                }
            }
        }

        $this->where('`id_payplug_logger` = ' . (int) $id_logger);

        return (bool) $this->build();
    }
}
