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

use ReflectionClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class EntityRepository extends QueryRepository
{
    /** @var string */
    public $entity_name = '';

    /**
     * @description Get an entity from database for given identifier.
     *
     * @param int $id
     *
     * @return array
     */
    protected function getEntity($id = 0)
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
            ->where('`' . $definition['primary'] . '`=' . (int) $id)
            ->build('unique_value');

        return $result ?: [];
    }

    /**
     * @description Get an entity from database or collection for given key and value.
     *
     * @param string $key
     * @param null $value
     *
     * @return array
     */
    protected function getBy($key = '', $value = null)
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
        if (!is_array($definition) || !isset($definition['table'])) {
            return [];
        }

        $this
            ->select()
            ->fields('*')
            ->from($this->getTableName($definition['table']));

        // check $value type
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
                return [];
        }

        $result = $this->build('unique_value');

        return $result ?: [];
    }

    /**
     * @description Insert an entity in the database.
     *
     * @param array $fields
     *
     * @return int
     */
    protected function createEntity($fields = [])
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
            if ((isset($field['required']) && !(bool) $field['required']) || $has_error) {
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
     * @description Update an entity in the database
     *
     * @param array $fields
     * @param mixed $id
     *
     * @return bool
     */
    protected function updateEntity($id = 0, $fields = [])
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
     * @description Delete an entity in the database.
     *
     * @param int $id
     *
     * @return bool
     */
    protected function deleteEntity($id = 0)
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
     * @description Get Entity Object for a given class name.
     *
     * @param string $class_name
     *
     * @return ReflectionClass|null
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

        return new ReflectionClass($path);
    }

    protected function getTableName($table = '')
    {
        if (!is_string($table) || !$table) {
            return '';
        }
        $constant_adapter = new ReflectionClass('PayPlug\src\application\adapter\ConstantAdapter');

        return $constant_adapter->get('_DB_PREFIX_') . $table;
    }
}
