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

class PaymentRepository extends QueryRepository
{
    private $fields = [
        'resource_id' => 'string',
        'method' => 'string',
        'id_cart' => 'integer',
        'cart_hash' => 'string',
        'schedules' => 'string',
        'date_upd' => 'string',
    ];

    public function __construct($prefix = '', $dependencies = null)
    {
        parent::__construct($prefix, $dependencies);
        $this->table_name = $this->prefix . $this->dependencies->name . '_payment';
    }

    /**
     * @description Create a payment from given parameters
     *
     * @param array $parameters
     *
     * @return bool
     */
    public function createPayment($parameters = [])
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

        return (bool) $this->build();
    }

    /**
     * @description Get all payments for a given method name.
     *
     * @param string $method_name
     * @param bool $asc
     *
     * @return array
     */
    public function getAllByMethod($method_name = '', $asc = false)
    {
        if (!is_string($method_name) || !$method_name) {
            return [];
        }
        if (!is_bool($asc)) {
            return [];
        }

        $query = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->where('`method` = "' . $this->escape($method_name) . '"');

        if ($asc) {
            $query->orderBy('`id_payplug_payment` DESC');
        }

        return $query->build() ?: [];
    }

    /**
     * @description Get payment id from given cart id
     *
     * @param int $cart_id
     *
     * @return array
     */
    public function getByCart($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return [];
        }
        $result = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->where('id_cart = ' . (int) $cart_id)
            ->build('unique_row');

        return $result ?: [];
    }

    /**
     * @description Get a payment for a given id.
     *
     * @param int $id
     *
     * @return array
     */
    public function getById($id = 0)
    {
        if (!is_int($id) || !$id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->where('`id_payplug_payment` = ' . (int) $id)
            ->build('unique_row');

        return $result ?: [];
    }

    /**
     * @description Get cart id from given resource id
     *
     * @param string $resource_id
     *
     * @return array
     */
    public function getByResourceId($resource_id = '')
    {
        if (!is_string($resource_id) || !$resource_id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->where('`resource_id` = "' . $this->escape($resource_id) . '"')
            ->build('unique_row');

        return $result ?: [];
    }

    public function getAllByResourceId($resource_id = '')
    {
        if (!is_string($resource_id) || !$resource_id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->table_name)
            ->where('`resource_id` = "' . $this->escape($resource_id) . '"')
            ->orderBy('`date_upd` DESC')
            ->build('result');
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
            ->fields('`id_payplug_payment` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`resource_id` VARCHAR(255) NULL')
            ->fields('`method` VARCHAR(255) NULL')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`cart_hash` VARCHAR(64) NULL')
            ->fields('`schedules` TEXT NULL')
            ->fields('`date_upd` DATETIME NULL')
            ->condition('CONSTRAINT lock_cart_unique UNIQUE (id_cart)')
            ->engine($engine);

        return $this->build();
    }

    /**
     * @description Delete stored payment
     *
     * @param int $cart_id
     *
     * @return bool
     */
    public function remove($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return false;
        }

        $result = $this
            ->delete()
            ->from($this->table_name)
            ->where('id_cart = ' . (int) $cart_id)
            ->build();

        return $result ?: false;
    }

    /**
     * @description Delete stored payment from a given resource id
     *
     * @param string $resource_id
     *
     * @return false
     */
    public function removeByResourceId($resource_id = '')
    {
        if (!is_string($resource_id) || !$resource_id) {
            return false;
        }

        $result = $this
            ->delete()
            ->from($this->table_name)
            ->where('`resource_id` = "' . $this->escape($resource_id) . '"')
            ->build();

        return $result ?: false;
    }

    /**
     * @description Update an existing payment for a given cart id.
     *
     * @param int $cart_id
     * @param array $parameters
     *
     * @return bool
     */
    public function updateByCart($cart_id = 0, $parameters = [])
    {
        if (!is_int($cart_id) || !$cart_id) {
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

        $this->where('id_cart = ' . (int) $cart_id);

        return (bool) $this->build();
    }

    /**
     * @description Update an existing payment for a given cart id.
     *
     * @param int $cart_id
     * @param array $parameters
     * @param mixed $resource_id
     *
     * @return bool
     */
    public function updateByResourceId($resource_id = '', $parameters = [])
    {
        if (!is_string($resource_id) || !$resource_id) {
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

        $this->where('`resource_id` = "' . $this->escape($resource_id) . '"');

        return (bool) $this->build();
    }
}
