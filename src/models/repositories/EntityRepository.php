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

class EntityRepository extends QueryRepository
{
    /** @var string */
    public $entity_name = '';

    /**
     * @description Insert an entity in the database.
     *
     * @param array $fields
     *
     * @return int
     */
    public function createEntity($fields = [])
    {
        if (!is_array($fields) || empty($fields)) {
            return 0;
        }
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return 0;
        }

        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return 0;
        }
        $definition = $entity->getDefinition();
        $this
            ->insert()
            ->into($this->getTableName($definition['table']));

        // Check if all required fields are correctly given
        $has_error = false;
        foreach ($definition['fields'] as $name => $field) {
            if ((!isset($field['required']) || !(bool) $field['required']) || $has_error) {
                continue;
            }

            if (!isset($fields[$name])) {
                $has_error = true;

                continue;
            }

            $value = $fields[$name];
            switch ($field['type']) {
                case 'string':
                    if (!is_string($value) || !$value) {
                        $has_error = true;
                    }

                    break;
                case 'integer':
                    if (!is_int($value)) {
                        $has_error = true;
                    }

                    break;
                case 'boolean':
                    if (!is_bool($value)) {
                        $has_error = true;
                    }

                    break;
                default:
                    break;
            }
            unset($value);
        }
        if ($has_error) {
            return 0;
        }

        foreach ($fields as $key => $value) {
            if (array_key_exists($key, $definition['fields'])) {
                switch ($definition['fields'][$key]['type']) {
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
                    case 'boolean':
                        if (is_bool($value)) {
                            $this->fields($key)->values($value ? 1 : 0);
                        }

                        break;
                    default:
                        break;
                }
            }
        }

        return (bool) $this->build() ? $this->lastId() : 0;
    }

    /**
     * @description Delete all entities from database.
     *
     * @return bool
     */
    public function deleteAll()
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

        return $result->build() ?: false;
    }

    /**
     * @description Delete entities from database for given key and value.
     *
     * @param string $key
     * @param null $value
     *
     * @return array
     */
    public function deleteBy($key = '', $value = null)
    {
        if (!is_string($key) || !$key) {
            return false;
        }
        if (is_null($value)) {
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
        if (!isset($definition['fields']) || !array_key_exists($key, $definition['fields'])) {
            return false;
        }

        $this
            ->delete()
            ->from($this->getTableName($definition['table']));

        // check $value type
        if (!$this->formatWhereFromType($key, $value)) {
            return false;
        }

        return (bool) $this->build();
    }

    /**
     * @description Delete an entity in the database.
     *
     * @param int $id
     *
     * @return bool
     */
    public function deleteEntity($id = 0)
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
            ->where('`' . $definition['primary'] . '` = ' . (int) $id)
            ->build();

        return $result ?: false;
    }

    /**
     * @description Get an collection from database for given key and value.
     *
     * @return array
     */
    public function getAll()
    {
        if (!is_string($this->entity_name) || !$this->entity_name) {
            return [];
        }

        $entity = $this->getEntityObject($this->entity_name);
        if (!$entity) {
            return [];
        }

        $definition = $entity->getDefinition();
        if (!is_array($definition) || !isset($definition['table'])) {
            return [];
        }

        $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']));

        return $this->build() ?: [];
    }

    /**
     * @description Get an collection from database for given key and value.
     *
     * @param string $key
     * @param null $value
     *
     * @return array
     */
    public function getAllBy($key = '', $value = null)
    {
        if (!is_string($key) || !$key) {
            return [];
        }
        if (is_null($value)) {
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
        if (!isset($definition['fields']) || !array_key_exists($key, $definition['fields'])) {
            return [];
        }

        $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']));

        // check $value type
        if (!$this->formatWhereFromType($key, $value)) {
            return [];
        }

        return $this->build() ?: [];
    }

    /**
     * @description Get an entity from database for given key and value.
     *
     * @param string $key
     * @param null $value
     *
     * @return array
     */
    public function getBy($key = '', $value = null)
    {
        if (!is_string($key) || !$key) {
            return [];
        }
        if (is_null($value)) {
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
        if (!isset($definition['fields']) || !array_key_exists($key, $definition['fields'])) {
            return [];
        }

        $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']));

        // check $value type
        if (!$this->formatWhereFromType($key, $value)) {
            return [];
        }

        $result = $this->build('unique_row');

        return $result ?: [];
    }

    /**
     * @description Get an entity from database for given identifier.
     *
     * @param int $id
     *
     * @return array
     */
    public function getEntity($id = 0)
    {
        if (!is_int($id) || !$id) {
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
        if (!is_array($definition) || !isset($definition['table'])) {
            return [];
        }
        $result = $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']))
            ->where('`' . $definition['primary'] . '` = ' . (int) $id)
            ->build('unique_row');

        return $result ?: [];
    }

    /**
     * @description initialize database
     *
     * @return bool
     */
    public function initialize()
    {
        $flag = true;
        $repositories = $this->getChildRepositories();

        $constant_class = 'PayPlug\src\application\adapter\ConstantAdapter';
        $constant_adapter = new $constant_class();
        $engine = $constant_adapter->get('_MYSQL_ENGINE_') ?: 'InnoDB';

        foreach ($repositories as $repository) {
            if ($flag) {
                $flag = $flag && $repository->initialize($engine);
            }
        }

        return $flag;
    }

    /**
     * @description Update an entity in the database for a given key
     *
     * @param string $key
     * @param null $value
     * @param array $fields
     *
     * @return bool
     */
    public function updateBy($key = '', $value = null, $fields = [])
    {
        if (!is_string($key) || !$key) {
            return false;
        }
        if (is_null($value)) {
            return false;
        }
        if (!is_array($fields) || empty($fields)) {
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
        if (!isset($definition['fields']) || !array_key_exists($key, $definition['fields'])) {
            return false;
        }

        $this
            ->update()
            ->table($this->getTableName($definition['table']));

        foreach ($fields as $field_key => $field_value) {
            if (array_key_exists($field_key, $definition['fields'])) {
                switch ($definition['fields'][$field_key]['type']) {
                    case 'string':
                        if (is_string($field_value) && $field_value) {
                            $this->set($field_key . ' = "' . $this->escape($field_value) . '"');
                        }

                        break;
                    case 'integer':
                        if (is_int($field_value)) {
                            $this->set($field_key . ' = ' . (int) $field_value);
                        }

                        break;
                    case 'boolean':
                        if (is_bool($field_value)) {
                            $this->set($field_key . ' = ' . ($field_value ? 1 : 0));
                        }

                        break;
                    default:
                        break;
                }
            }
        }

        // check $value type
        if (!$this->formatWhereFromType($key, $value)) {
            return false;
        }

        $result = $this->build();

        return $result ?: false;
    }

    /**
     * @description Update an entity in the database
     *
     * @param array $fields
     * @param mixed $id
     *
     * @return bool
     */
    public function updateEntity($id = 0, $fields = [])
    {
        if (!is_int($id) || !$id) {
            return false;
        }
        if (!is_array($fields) || empty($fields)) {
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
            ->update()
            ->table($this->getTableName($definition['table']));

        foreach ($fields as $key => $value) {
            if (array_key_exists($key, $definition['fields'])) {
                switch ($definition['fields'][$key]['type']) {
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
                    case 'boolean':
                        if (is_bool($value)) {
                            $this->set($key . ' = ' . ($value ? 1 : 0));
                        }

                        break;
                    default:
                        break;
                }
            }
        }

        $this->where('`' . $definition['primary'] . '` = ' . (int) $id);

        return (bool) $this->build();
    }

    /**
     * @description uninstall database
     *
     * @return bool
     */
    public function uninstall()
    {
        $flag = true;
        $repositories = $this->getChildRepositories();
        foreach ($repositories as $repository) {
            if (!$flag) {
                continue;
            }

            if ($repository->table_name) {
                $table_name = $repository->table_name;
            } else {
                $entity = $this->getEntityObject($repository->entity_name);
                if (!$entity) {
                    continue;
                }
                $definition = $entity->getDefinition();
                $table_name = $definition['table'];
            }

            $table_name = $this->getTableName($table_name);
            $exists = $this->ifExists()
                ->table($table_name)
                ->build();

            if (!$exists) {
                continue;
            }

            $flag = $flag && $this
                ->drop()
                ->table($table_name)
                ->build();
        }

        return $flag;
    }

    /**
     * @description Get Entity Object for a given class name.
     *
     * @param string $class_name
     *
     * @return mixed|null
     */
    protected function getEntityObject($class_name = '')
    {
        if (!is_string($class_name) || !$class_name) {
            return null;
        }

        $path = 'PayPlug\src\models\entities\\' . $class_name;
        if (!class_exists($path)) {
            return null;
        }

        return new $path();
    }

    /**
     * @description Get the table name with prefix.
     *
     * @param string $table
     *
     * @return string
     */
    protected function getTableName($table = '')
    {
        if (!is_string($table) || !$table) {
            return '';
        }
        $constant_class = 'PayPlug\src\application\adapter\ConstantAdapter';
        $constant_adapter = new $constant_class();

        return $constant_adapter->get('_DB_PREFIX_') . $table;
    }

    /**
     * @description Format where request from the value type
     *
     * @param string $key
     * @param null $value
     *
     * @return bool
     */
    private function formatWhereFromType($key = '', $value = null)
    {
        if (!is_string($key) || !$key) {
            return false;
        }
        if (is_null($value)) {
            return false;
        }

        switch (true) {
            case is_string($value):
                if ('' !== $value) {
                    $this->where('`' . $key . '` = "' . $this->escape($value) . '"');
                }

                break;
            case is_int($value):
                $this->where('`' . $key . '` = ' . (int) $value);

                break;
            case is_bool($value):
                $this->where('`' . $key . '` = ' . ($value ? 1 : 0));

                break;
            default:
                return false;
        }

        return true;
    }

    /**
     * @description get Child Repositories
     *
     * @return array
     */
    private function getChildRepositories()
    {
        $repositories = [];

        $query = get_class($this);

        foreach (get_declared_classes() as $repository) {
            if (is_subclass_of($repository, $query)) {
                $repository_class = new $repository($this->dependencies);
                if ($repository_class->entity_name) {
                    $repositories[] = $repository_class;
                }
                if ($repository_class->table_name && false !== strpos($repository_class->table_name, $this->dependencies->name)) {
                    $repositories[] = $repository_class;
                }
            }
        }

        return $repositories;
    }
}
