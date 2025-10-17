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

namespace PayPlug\src\repositories;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlug\classes\MyLogPHP;

class SQLtableRepository
{
    /** @var object */
    private $query;

    /** @var object */
    private $dependencies;

    public function __construct($dependencies, $query)
    {
        $this->dependencies = $dependencies;
        $this->query = $query;
    }

    /**
     * Remove SQL tables used by module.
     *
     * @param $keep_cards
     *
     * @return bool
     */
    public function uninstallSQL($keep_cards = false)
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . $this->dependencies->name . '/log/install-log.csv');
        $log->info('uninstallSQL() starting.');

        $flag = true;

        $tables = [
            _DB_PREFIX_ . $this->dependencies->name . '_lock',
            _DB_PREFIX_ . $this->dependencies->name . '_payment',
            _DB_PREFIX_ . $this->dependencies->name . '_logger',
            _DB_PREFIX_ . $this->dependencies->name . '_cache',
            _DB_PREFIX_ . $this->dependencies->name . '_order_payment',
            _DB_PREFIX_ . $this->dependencies->name . '_order_state',
        ];

        if (!$keep_cards) {
            array_push($tables, _DB_PREFIX_ . $this->dependencies->name . '_card');
        }

        foreach ($tables as $table) {
            $flag = $flag && $this->query->drop()->table($table)->build();
        }

        $log->info('Uninstallation SQL ended.');

        return $flag;
    }

    /**
     * @description Check if existing table.
     *
     * @param string $table
     * @param int $canUsePayplugLogger
     *
     * @return bool
     */
    public function checkExistingTable($table = '', $canUsePayplugLogger = 1)
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . $this->dependencies->name . '/log/install-log.csv');
        $logger = null;

        if ($canUsePayplugLogger) {
            $logger = new LoggerRepository($this->dependencies);
            $logger->setProcess('sql');
        }

        if (!$table || !is_string($table)) {
            if ($canUsePayplugLogger) {
                $logger->addLog('checkExistingTable() : parameter $table is not a string', 'error');
                $logger->addLog('$table value : ' . json_encode($table), 'error');
            }

            if (null != $log) {
                $log->error('[SQLtableRepository] checkExistingTable() : parameter $table is not a string');
                $log->error('$table value : ' . json_encode($table), 'error');
            }

            return false;
        }

        $this->query
            ->ifExists()
            ->table($table)
        ;
        if (!$this->query->build()) {
            if (null != $log) {
                $log->error('[SQLtableRepository] checkExistingTable() : '
                    . 'Error during send request in DB for given table ' . $table);
            }

            if ($canUsePayplugLogger) {
                $logger->addLog(
                    '[SQLtableRepository] checkExistingTable() : '
                    . 'Error during send request in DB for given table ' . $table,
                    'error'
                );
            }

            return false;
        }

        return true;
    }
}
