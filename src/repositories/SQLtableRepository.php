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

namespace PayPlug\src\repositories;

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
     * Install SQL tables used by module
     *
     * @return bool
     */
    public function installSQL()
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . $this->dependencies->name . '/log/install-log.csv');
        $log->info('Installation SQL Starting.');

        if (!defined('_MYSQL_ENGINE_')) {
            define('_MYSQL_ENGINE_', 'InnoDB');
        }

        // Create module Lock table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_lock')
            ->fields('`id_payplug_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`id_order` VARCHAR(100)')
            ->fields('`date_add` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->fields('`date_upd` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->condition('CONSTRAINT lock_cart_unique UNIQUE (id_cart)')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_LOCK.');

            return false;
        }

        // Create module Card table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_card')
            ->fields('`id_payplug_card` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_customer` int(11) UNSIGNED NOT NULL')
            ->fields('`id_company` int(11) UNSIGNED NOT NULL')
            ->fields('`is_sandbox` int(1) UNSIGNED NOT NULL')
            ->fields('`id_card` varchar(255) NOT NULL')
            ->fields('`last4` varchar(4) NOT NULL')
            ->fields('`exp_month` varchar(4) NOT NULL')
            ->fields('`exp_year` varchar(4) NOT NULL')
            ->fields('`brand` varchar(255) DEFAULT NULL')
            ->fields('`country` varchar(3) NOT NULL')
            ->fields('`metadata` varchar(255) DEFAULT NULL')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_CARD.');

            return false;
        }

        // Create module Payment Cart table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_payment_cart')
            ->fields('`id_payplug_payment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_payment` VARCHAR(255) NOT NULL')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`cart_hash` VARCHAR(64) NOT NULL')
            ->fields('`is_pending` TINYINT(1) NOT NULL DEFAULT 0')
            ->fields('`date_upd` DATETIME NULL')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_PAYMENT_CART.');

            return false;
        }

        // Create module Payment table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_payment')
            ->fields('`id_payplug_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_payment` VARCHAR(255) NULL')
            ->fields('`payment_method` VARCHAR(255) NULL')
            ->fields('`payment_url` VARCHAR(255) NULL')
            ->fields('`payment_return_url` VARCHAR(255) NULL')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`cart_hash` VARCHAR(64) NULL')
            ->fields('`authorized_at` INT(20) NOT NULL DEFAULT 0')
            ->fields('`is_paid` TINYINT(1) NOT NULL DEFAULT 0')
            ->fields('`is_pending` TINYINT(1) NOT NULL DEFAULT 0')
            ->fields('`date_upd` DATETIME NULL')
            ->condition('CONSTRAINT lock_cart_unique UNIQUE (id_cart)')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_PAYMENT.');

            return false;
        }

        // Create module Installment Cart table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_installment_cart')
            ->fields('`id_payplug_installment_cart` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_installment` VARCHAR(255) NOT NULL')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`is_pending` TINYINT(1) NOT NULL DEFAULT 0')
            ->fields('`date_upd` DATETIME NULL')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_INSTALLMENT_CART.');

            return false;
        }

        // Create module Installment table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_installment')
            ->fields('`id_payplug_installment` int(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_installment` VARCHAR(255) NOT NULL')
            ->fields('`id_payment` VARCHAR(255) NULL')
            ->fields('`id_order` INT(11) UNSIGNED NOT NULL')
            ->fields('`id_customer` INT(11) UNSIGNED NOT NULL')
            ->fields('`order_total` INT(11) UNSIGNED NOT NULL')
            ->fields('`step` VARCHAR(11) NOT NULL')
            ->fields('`amount` INT(11) UNSIGNED NOT NULL')
            ->fields('`status` INT(11) UNSIGNED NOT NULL')
            ->fields('`scheduled_date` DATETIME NOT NULL')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_INSTALLMENT.');

            return false;
        }

        // Create module Logger table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_logger')
            ->fields('`id_payplug_logger` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`process` VARCHAR(255) NOT NULL')
            ->fields('`content` TEXT NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_LOGGER.');

            return false;
        }

        // Create module Logger table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_cache')
            ->fields('`id_payplug_cache` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`cache_key` VARCHAR(255) NOT NULL')
            ->fields('`cache_value` TEXT NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_CACHE.');

            return false;
        }

        // Create module Order Payment table
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_order_payment')
            ->fields('`id_payplug_order_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_order` INT(11) UNSIGNED NOT NULL')
            ->fields('`id_payment` VARCHAR(255) NOT NULL')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_ORDER_PAYMENT.');

            return false;
        }

        // Create module Order state type
        $this->query
            ->create()
            ->table(_DB_PREFIX_ . $this->dependencies->name . '_order_state')
            ->fields('`id_payplug_order_state` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_order_state` INT(11) UNSIGNED NOT NULL')
            ->fields('`type` VARCHAR(64) NOT NULL')
            ->fields('`date_add` DATETIME NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->condition('CONSTRAINT order_state_unique UNIQUE (id_order_state)')
            ->engine(_MYSQL_ENGINE_)
        ;

        if (!$this->query->build()) {
            $log->error('Installation SQL failed: ' . $this->dependencies->name . '_ORDER_STATE.');

            return false;
        }

        $log->info('Installation SQL ended.');

        return true;
    }

    /**
     * Remove SQL tables used by module
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
            _DB_PREFIX_ . $this->dependencies->name . '_payment_cart',
            _DB_PREFIX_ . $this->dependencies->name . '_payment',
            _DB_PREFIX_ . $this->dependencies->name . '_installment',
            _DB_PREFIX_ . $this->dependencies->name . '_installment_cart',
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
     * Check if existing table
     *
     * @param string   $table
     * @param bool|int $canUsePayplugLogger
     *
     * @return bool
     */
    public function checkExistingTable($table, $canUsePayplugLogger = 1)
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . $this->dependencies->name . '/log/install-log.csv');
        $logger = null;

        if ($canUsePayplugLogger) {
            $logger = new LoggerRepository($this->dependencies);
            $logger->setParams(['process' => 'SQLtableRepository']);
        }

        if (!$table || !is_string($table)) {
            if ($canUsePayplugLogger) {
                $logger->addLog('checkExistingTable() : parameter $table is not a string', 'error');
                $logger->addLog('$table value : ' . json_encode($table), 'error');
            }

            if ($log) {
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
            if ($log) {
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
