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

namespace PayPlug\src\application\adapter;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\src\interfaces\QueryInterface;

class QueryAdapter implements QueryInterface
{
    private $db;

    private $last_error = '';

    public function __construct()
    {
        try {
            $this->db = \Db::getInstance();
        } catch (\Exception $e) {
            // todo do something about this
            var_dump($e);
        }
    }

    /**
     * @description Called from src/repositories/QueryRepository.php
     *
     * @param $SQLRequest
     * @param bool $use_cache false reads from the database even when the PrestaShop SQL cache is enabled
     *
     * @return mixed
     */
    public function query($SQLRequest, $use_cache = true)
    {
        try {
            $action = 'execute';

            if (false !== stripos(substr($SQLRequest, 0, 8), 'SELECT')
                || false !== stripos(substr($SQLRequest, 0, 16), 'SHOW TABLES LIKE')) {
                $action = 'executeS';
            }

            if ('executeS' == $action) {
                return $this->db->executeS($SQLRequest, true, (bool) $use_cache);
            }

            return $this->db->execute($SQLRequest, (bool) $use_cache);
        } catch (\Exception $e) {
            // todo add log
            return false;
        }
    }

    public function getLastId()
    {
        return $this->db->Insert_ID();
    }

    /**
     * @description Number of rows affected by the last INSERT/UPDATE/DELETE run through query().
     *              Used where a caller needs to know whether a conditional UPDATE actually
     *              matched a row (e.g. an atomic compare-and-swap), not just that it executed
     *              without error.
     *
     * @return int
     */
    public function getAffectedRows()
    {
        return (int) $this->db->Affected_Rows();
    }

    // @todo : A optimiser dans QueryRepository
    public function getValue($id)
    {
        return $this->db->getValue($id);
    }

    public function escape($string, $htmlOK = false)
    {
        return $this->db->escape($string, $htmlOK);
    }

    /**
     * @description Take a MySQL named lock, waiting up to $timeout seconds.
     * The SQL cache is bypassed so a stale result is never returned.
     *
     * @param string $name
     * @param int $timeout
     *
     * @return mixed '1' when acquired, '0' on timeout, null or false on error
     */
    public function getNamedLock($name = '', $timeout = 0)
    {
        return $this->getLockValue("SELECT GET_LOCK('" . pSQL($name) . "', " . (int) $timeout . ')');
    }

    /**
     * @description Release a MySQL named lock held by the current connection
     *
     * @param string $name
     *
     * @return mixed '1' when released, '0' or null when the lock was not held, false on error
     */
    public function releaseNamedLock($name = '')
    {
        return $this->getLockValue("SELECT RELEASE_LOCK('" . pSQL($name) . "')");
    }

    /**
     * @description Cause of the last failed named lock query, empty when it succeeded
     *
     * @return string
     */
    public function getLastError()
    {
        return $this->last_error;
    }

    /**
     * @param string $sql
     *
     * @return mixed
     */
    private function getLockValue($sql)
    {
        $this->last_error = '';

        try {
            $value = $this->db->getValue($sql, false);
        } catch (\Exception $e) {
            $this->last_error = $e->getMessage();

            return false;
        }

        if (false === $value) {
            $this->last_error = (string) $this->db->getMsgError();
        }

        return $value;
    }
}
