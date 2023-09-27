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

class LoggerRepository extends QueryRepository
{
    private $fields = [
        'process' => 'string',
        'content' => 'string',
        'date_add' => 'string',
        'date_upd' => 'string',
    ];

    public function createLog($parameters = [])
    {
        if (!is_array($parameters) || empty($parameters)) {
            return false;
        }

        $this
            ->insert()
            ->into($this->prefix . $this->dependencies->name . '_logger');

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
                            $this->fields($key)->values(($value ? 1 : 0));
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
     * @description Update an existing payment for a given id logger.
     *
     * @param int   $id_logger
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
            ->table($this->prefix . $this->dependencies->name . '_logger');

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
