<?php

namespace PayPlug\tests\upgrade;

use PHPUnit\Framework\TestCase;

/**
 * Each test runs in its own process: the script needs global PrestaShop symbols (Db, _DB_PREFIX_,
 * _MYSQL_ENGINE_) that must not leak into the rest of the suite.
 *
 * @group unit
 * @group upgrade
 */
class upgrade530Test extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreatesTheOperationTableWithTheRequestedAmountColumn()
    {
        $queries = $this->runUpgrade([]);

        $this->assertStringContainsString('`requested_amount` INT(11) UNSIGNED NULL', $queries[0]);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testAddsTheRequestedAmountColumnToAnExistingTable()
    {
        $queries = $this->runUpgrade([
            'payment_id' => [['Field' => 'payment_id']],
            'requested_amount' => [],
        ]);

        $this->assertContains(
            'ALTER TABLE `ps_payplug_unified_operation` ADD COLUMN `requested_amount` INT(11) UNSIGNED NULL AFTER `payment_id`',
            $queries
        );
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLeavesAnExistingRequestedAmountColumnUntouched()
    {
        $queries = $this->runUpgrade([
            'payment_id' => [['Field' => 'payment_id']],
            'requested_amount' => [['Field' => 'requested_amount']],
        ]);

        foreach ($queries as $query) {
            $this->assertStringNotContainsString('ALTER TABLE', $query);
        }
    }

    /**
     * @param array<string, array> $existing_columns SHOW COLUMNS result per column name
     *
     * @return string[] the queries executed, SHOW COLUMNS excluded
     */
    private function runUpgrade(array $existing_columns)
    {
        define('_DB_PREFIX_', 'ps_');
        define('_MYSQL_ENGINE_', 'InnoDB');
        eval('class PrestaShopDatabaseException extends \Exception {}');
        eval('class Db
        {
            public static $queries = [];
            public static $columns = [];

            public static function getInstance()
            {
                return new self();
            }

            public function execute($sql)
            {
                self::$queries[] = $sql;

                return true;
            }

            public function executeS($sql)
            {
                preg_match(\'/LIKE "(\w+)"/\', $sql, $matches);

                return isset(self::$columns[$matches[1]]) ? self::$columns[$matches[1]] : [];
            }
        }');
        \Db::$columns = $existing_columns;

        require_once __DIR__ . '/../../upgrade/upgrade-5.3.0.php';

        $logger = \Mockery::mock('Logger');
        $logger->shouldReceive('addLog');
        $plugin = \Mockery::mock('Plugin');
        $plugin->shouldReceive('getLogger')->andReturn($logger);
        $dependencies = \Mockery::mock('Dependencies');
        $dependencies->shouldReceive('getPlugin')->andReturn($plugin);
        $module = new \stdClass();
        $module->payplug_dependencies = $dependencies;

        $this->assertTrue(upgrade_module_5_3_0($module));

        return \Db::$queries;
    }
}
