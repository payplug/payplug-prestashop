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
/*
 * @todo: Petit rappel, avant de créer la doc
 * Comment ça marche :
 *
 * Dans n'importe quelle classe :
 * public function __construct()
 * {
 *      $this->query = new QueryRepository();
 * }
 *
 * SELECT * FROM ma_table WHERE (champ_1 = donnee_1, champ_2 = donnee_2) :
 * $this->query
 * ->select()
 * ->fields('*')
 * ->from('ma_table')
 * ->where('champ_1 = donnee_1')
 * ->where('champ_2 = donnee_2')
 * ->build()
 *
 * INSERT INTO ma_table (champ_1, champ_2) VALUES (donnee_1, donnee_2) :
 * $this->query
 * ->insert()
 * ->into('ma_table')
 * ->fields('champ_1, champ_2')
 * ->values('donnee_1, donnee_2')
 * ->build()
 *
 * UPDATE ma_table SET champ_1 = donnee_1, champ_2 = donnee_2 WHERE id = 3 :
 * ->update()
 * ->table('ma_table')
 * ->set('ma_table.champ_1 = donnee_1')
 * ->set('ma_table.champ_2 = donnee_2')
 * ->where(id = 3)
 * ->build()
 *
 * DELETE FROM ma_table WHERE id = 3 :
 * $this->query
 * ->delete()
 * ->from('ma_table')
 * ->where('id = 3')
 * ->build()
 *
 * TRUNCATE TABLE ma_table :
 * $this->query
 * ->truncate()
 * ->table('ma_table')
 * ->build()
 *
 * CREATE IF NOT EXIST ma_table :
 * $this->query
 * ->create()
 * ->table('ma_table')
 * ->fields('`id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
 * ->fields('`champ_2` VARCHAR(255) NOT NULL')
 * ->engine(_MYSQL_ENGINE_)
 * ->build()
 */

namespace PayPlug\src\repositories;

use PayPlug\src\application\adapter\QueryAdapter;
use PayPlug\src\application\dependencies\BaseClass;

class QueryRepository extends BaseClass
{
    protected $query = [
        'type' => [],
        'fields' => [],
        'values' => [],
        'from' => [],
        'into' => [],
        'table' => [],
        'set' => [],
        'join' => [],
        'where' => [],
        'group' => [],
        'having' => [],
        'order' => [],
        'limit' => ['offset' => 0, 'limit' => 0],
        'lastId' => [],
    ];

    private $adapter_class;

    public function __construct()
    {
        $this->adapter_class = QueryAdapter::factory();
    }

    /**
     * Converts object to string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->build();
    }

    public static function factory()
    {
        return new QueryRepository();
    }

    public function select()
    {
        $this->query['type'] = 'SELECT';

        return $this;
    }

    public function insert()
    {
        $this->query['type'] = 'INSERT';

        return $this;
    }

    public function update()
    {
        $this->query['type'] = 'UPDATE';

        return $this;
    }

    public function truncate()
    {
        $this->query['type'] = 'TRUNCATE';

        return $this;
    }

    public function delete()
    {
        $this->query['type'] = 'DELETE';

        return $this;
    }

    public function create()
    {
        $this->query['type'] = 'CREATE';

        return $this;
    }

    public function ifExists()
    {
        $this->query['type'] = 'SHOW TABLES LIKE';

        return $this;
    }

    public function drop()
    {
        $this->query['type'] = 'DROP';

        return $this;
    }

    public function fields($fields)
    {
        if (!empty($fields)) {
            $this->query['fields'][] = $fields;
        }

        return $this;
    }

    public function values($values)
    {
        if (!empty($values) || $values == 0) {
            $this->query['values'][] = '\'' . $values . '\'';
        }

        return $this;
    }

    public function from($table, $alias = null)
    {
        if (!empty($table)) {
            if (empty($this->query['from'])) {
                $this->query['from'] = [];
            }
            $this->query['from'][] = '`' . $table . '`' . ($alias ? ' ' . $alias : '');
        }

        return $this;
    }

    public function into($table, $alias = null)
    {
        if (!empty($table)) {
            if (empty($this->query['into'])) {
                $this->query['into'] = [];
            }
            $this->query['into'][] = '`' . $table . '`' . ($alias ? ' ' . $alias : '');
        }

        return $this;
    }

    public function table($table, $alias = null)
    {
        if (!empty($table)) {
            if (empty($this->query['table'])) {
                $this->query['table'] = [];
            }
            $this->query['table'][] = '`' . $table . '`' . ($alias ? ' ' . $alias : '');
        }

        return $this;
    }

    public function set($set)
    {
        if (!empty($set)) {
            $this->query['set'][] = $set;
        }

        return $this;
    }

    public function condition($condition)
    {
        if (!empty($condition)) {
            $this->query['condition'][] = $condition;
        }

        return $this;
    }

    public function engine($engine)
    {
        if (!empty($engine)) {
            $this->query['engine'][] = $engine;
        }

        return $this;
    }

    public function join($join)
    {
        if (!empty($join)) {
            $this->query['join'][] = $join;
        }

        return $this;
    }

    public function leftJoin($table, $alias = null, $on = null)
    {
        return $this->join('LEFT JOIN `' . bqSQL($table) . '`' . ($alias ? ' `' . pSQL($alias) . '`' : '') .
            ($on ? ' ON ' . $on : ''));
    }

    public function innerJoin($table, $alias = null, $on = null)
    {
        return $this->join('INNER JOIN `' . bqSQL($table) . '`' . ($alias ? ' ' . pSQL($alias) : '') .
            ($on ? ' ON ' . $on : ''));
    }

    public function leftOuterJoin($table, $alias = null, $on = null)
    {
        return $this->join('LEFT OUTER JOIN `' . bqSQL($table) . '`' . ($alias ? ' ' . pSQL($alias) : '') .
            ($on ? ' ON ' . $on : ''));
    }

    public function naturalJoin($table, $alias = null)
    {
        return $this->join('NATURAL JOIN `' . bqSQL($table) . '`' . ($alias ? ' ' . pSQL($alias) : ''));
    }

    public function rightJoin($table, $alias = null, $on = null)
    {
        return $this->join('RIGHT JOIN `' . bqSQL($table) . '`' . ($alias ? ' `' . pSQL($alias) . '`' : '') .
            ($on ? ' ON ' . $on : ''));
    }

    public function where($restriction)
    {
        if (!empty($restriction)) {
            $this->query['where'][] = $restriction;
        }

        return $this;
    }

    public function having($restriction)
    {
        if (!empty($restriction)) {
            $this->query['having'][] = $restriction;
        }

        return $this;
    }

    public function orderBy($fields)
    {
        if (!empty($fields)) {
            $this->query['order'][] = $fields;
        }

        return $this;
    }

    public function groupBy($fields)
    {
        if (!empty($fields)) {
            $this->query['group'][] = $fields;
        }

        return $this;
    }

    public function limit($limit, $offset = 0)
    {
        $offset = (int) $offset;
        if ($offset < 0) {
            $offset = 0;
        }

        $this->query['limit'] = [
            'offset' => $offset,
            'limit' => (int) $limit,
        ];

        return $this;
    }

    public function lastId()
    {
        return $this->adapter_class->getLastId();
    }

    public function getValue($id)
    {
        return $this->adapter_class->getValue($id);
    }

    public function build($param = false)
    {
        if ($this->query['type'] == 'SELECT') {
            $sql = 'SELECT ' . ((($this->query['fields'])) ? implode(",\n", $this->query['fields']) : '*') . "\n";
            if (!$this->query['from']) {
                $this->query = null;

                exit('Table name not set in QueryRepository (->from() is empty / not set / null). 
                Cannot build a valid SQL query.');
            }

            $sql .= 'FROM ' . implode(', ', $this->query['from']) . "\n";
        } elseif ($this->query['type'] == 'INSERT') {
            $sql = 'INSERT INTO ' . implode(",\n", $this->query['into']) . "\n";
            $sql .= '(' . implode(",\n", $this->query['fields']) . ')' . "\n";

            if ($this->query['values']) {
                $sql .= 'VALUES (' . "\n" . implode(",\n", $this->query['values']) . ')' . "\n";
            }
        } elseif ($this->query['type'] == 'UPDATE') {
            $sql = 'UPDATE ' . ((($this->query['table'])) ?
                    implode(",\n", $this->query['table']) :
                    implode(",\n", $this->query['into'])) . "\n";

            if ($this->query['set'] && (!empty($this->query['set']))) {
                $sql .= 'SET ' . implode(',' . "\n", $this->query['set']) . "\n";
            }
        } elseif ($this->query['type'] == 'TRUNCATE') {
            $sql = 'TRUNCATE TABLE ' . ((($this->query['table'])) ?
                    implode(",\n", $this->query['table']) :
                    implode(",\n", $this->query['into'])) . "\n";
        } elseif ($this->query['type'] == 'DELETE') {
            if (!$this->query['from']) {
                throw new PrestaShopException('Table name not set in QueryRepository. Cannot build a valid SQL query.');
            }

            $sql = 'DELETE FROM ' . ((isset($this->query['table']) && (!empty($this->query['table']))) ?
                    implode(",\n", $this->query['table']) :
                    implode(",\n", $this->query['from'])) . "\n";
        } elseif ($this->query['type'] == 'CREATE') {
            if (!$this->query['table']) {
                throw new PrestaShopException('Can\'t create table because ->table() is not set or empty');
            }

            if (!$this->query['fields']) {
                throw new PrestaShopException('Can\'t create table because ->fields() is not set or empty');
            }

            $sql = 'CREATE TABLE IF NOT EXISTS ' . implode($this->query['table']);

            $condition = (isset($this->query['condition']) && (!empty($this->query['condition']))) ?
                ', ' . implode(' ', $this->query['condition']) :
                '';
            $sql .= '(' . implode(",\n", $this->query['fields']) . "\n {$condition})\n";
            if (isset($this->query['engine']) && (!empty($this->query['engine']))) {
                $sql .= "\n" . 'ENGINE = ' . implode($this->query['engine']);
            }
        } elseif ($this->query['type'] == 'DROP') {
            if (!$this->query['table']) {
                throw new PrestaShopException('Table name not set in QueryRepository. Cannot drop it.');
            }

            $sql = 'DROP TABLE IF EXISTS ' . implode($this->query['table']) . "\n";
        } elseif ($this->query['type'] == 'SHOW TABLES LIKE') {
            if (!$this->query['table']) {
                throw new PrestaShopException('Table name not set in QueryRepository. Cannot check if exists.');
            }
            $table = str_replace('`', '', implode($this->query['table']));
            $sql = "SHOW TABLES LIKE '%{$table}%'\n";
        } else {
            $sql = $this->query['type'] . ' ';
        }

        if (isset($this->query['join']) && (!empty($this->query['join']))) {
            $sql .= implode("\n", $this->query['join']) . "\n";
        }

        if (isset($this->query['where']) && (!empty($this->query['where']))) {
            $sql .= 'WHERE (' . implode(') AND (', $this->query['where']) . ")\n";
        }

        if (isset($this->query['group']) && (!empty($this->query['group']))) {
            $sql .= 'GROUP BY ' . implode(', ', $this->query['group']) . "\n";
        }

        if (isset($this->query['having']) && (!empty($this->query['having']))) {
            $sql .= 'HAVING (' . implode(') AND (', $this->query['having']) . ")\n";
        }

        if (isset($this->query['order']) && (!empty($this->query['order']))) {
            $sql .= 'ORDER BY ' . implode(', ', $this->query['order']) . "\n";
        }

        if ((isset($this->query['limit']))
            && (($this->query['limit']['limit'] > 0) || ($this->query['limit']['offset'] > 0))) {
            $limit = $this->query['limit'];
            $sql .= 'LIMIT ' . ($limit['offset'] ? $limit['offset'] . ', ' : '') . $limit['limit'];
        }

        if (isset($param) && $param == 'debug') {
            var_dump($sql);

            exit;
        }

        try {
            $result = $this->adapter_class->query($sql);
        } catch (\Exception $e) {
            return false;
            // @todo : AddLog
        }

        if (isset($param) && $param == 'unique_value' && isset($result[0])) {
            $result = reset($result[0]);
        }

        if (isset($param) && $param == 'unique_row' && isset($result[0])) {
            $result = $result[0];
        }

        $this->query = null;
        $sql = null;

        return $result;
    }

    public function escape($string, $htmlOK = false)
    {
        return $this->adapter_class->escape($string, $htmlOK);
    }
}
