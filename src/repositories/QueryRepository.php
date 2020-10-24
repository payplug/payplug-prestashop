<?php

namespace PayPlug\src\repositories;

use PayPlug\src\specific\QuerySpecific;

class QueryRepository extends Repository
{
    protected $query = array(
        'type'   => array(),
        'fields' => array(),
        'from'   => array(),
        'join'   => array(),
        'where'  => array(),
        'group'  => array(),
        'having' => array(),
        'order'  => array(),
        'limit'  => array('offset' => 0, 'limit' => 0),
    );

    private $specific_class;

    public function __construct()
    {
        $this->specific_class = new QuerySpecific();
    }

    public function select()
    {
        $this->query['type'] = 'SELECT';
        return $this;
    }

    public function fields($fields)
    {
        if (!empty($fields)) {
            $this->query['fields'][] = $fields;
        }
        return $this;
    }

    public function from($table, $alias = null)
    {
        if (!empty($table)) {
            if (empty($this->query['from'])) {
                $this->query['from'] = array();
            }
            $this->query['from'][] = '`'._DB_PREFIX_.$table.'`'.($alias ? ' '.$alias : '');
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
        return $this->join('LEFT JOIN `'._DB_PREFIX_.bqSQL($table).'`'.($alias ? ' `'.pSQL($alias).'`' : '').($on ? ' ON '.$on : ''));
    }

    public function innerJoin($table, $alias = null, $on = null)
    {
        return $this->join('INNER JOIN `'._DB_PREFIX_.bqSQL($table).'`'.($alias ? ' '.pSQL($alias) : '').($on ? ' ON '.$on : ''));
    }

    public function leftOuterJoin($table, $alias = null, $on = null)
    {
        return $this->join('LEFT OUTER JOIN `'._DB_PREFIX_.bqSQL($table).'`'.($alias ? ' '.pSQL($alias) : '').($on ? ' ON '.$on : ''));
    }

    public function naturalJoin($table, $alias = null)
    {
        return $this->join('NATURAL JOIN `'._DB_PREFIX_.bqSQL($table).'`'.($alias ? ' '.pSQL($alias) : ''));
    }

    public function rightJoin($table, $alias = null, $on = null)
    {
        return $this->join('RIGHT JOIN `'._DB_PREFIX_.bqSQL($table).'`'.($alias ? ' `'.pSQL($alias).'`' : '').($on ? ' ON '.$on : ''));
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
        $offset = (int)$offset;
        if ($offset < 0) {
            $offset = 0;
        }

        $this->query['limit'] = array(
            'offset' => $offset,
            'limit'  => (int)$limit,
        );

        return $this;
    }

    public function build()
    {
        if ($this->query['type'] == 'SELECT') {
            $sql = 'SELECT '.((($this->query['fields'])) ? implode(",\n", $this->query['fields']) : '*')."\n";
        } else {
            $sql = $this->query['type'].' ';
        }

        if (!$this->query['from']) {
            throw new PrestaShopException('Table name not set in DbQuery object. Cannot build a valid SQL query.');
        }

        $sql .= 'FROM '.implode(', ', $this->query['from'])."\n";

        if ($this->query['join']) {
            $sql .= implode("\n", $this->query['join'])."\n";
        }

        if ($this->query['where']) {
            $sql .= 'WHERE ('.implode(') AND (', $this->query['where']).")\n";
        }

        if ($this->query['group']) {
            $sql .= 'GROUP BY '.implode(', ', $this->query['group'])."\n";
        }

        if ($this->query['having']) {
            $sql .= 'HAVING ('.implode(') AND (', $this->query['having']).")\n";
        }

        if ($this->query['order']) {
            $sql .= 'ORDER BY '.implode(', ', $this->query['order'])."\n";
        }

        if ($this->query['limit']['limit']) {
            $limit = $this->query['limit'];
            $sql .= 'LIMIT '.($limit['offset'] ? $limit['offset'].', ' : '').$limit['limit'];
        }
        return $this->specific_class->query($sql);
    }

    /**
     * Converts object to string
     *
     * @return string
     */
    public function __toString()
    {
//        return $this->specific_class->query($this->build());
    }

}
