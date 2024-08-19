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

class LockRepository extends EntityRepository
{
    private $fields = [
        'id_cart' => 'integer',
        'id_order' => 'string',
        'date_add' => 'string',
        'date_upd' => 'string',
    ];

    public function __construct($dependencies = null)
    {
        parent::__construct($dependencies);
        $this->table_name = $this->dependencies->name . '_lock';
    }

    /**
     * @description Create a lock from given parameters
     *
     * @param array $parameters
     *
     * @return bool
     */
    public function createLock($parameters = [])
    {
        if (!is_array($parameters) || empty($parameters)) {
            return false;
        }

        $this
            ->insert()
            ->into($this->getTableName($this->table_name));

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
                    default:
                        break;
                }
            }
        }

        return (bool) $this->build();
    }

    /**
     * @description Delete a lock from a given id
     *
     * @param int $cart_id
     *
     * @return bool
     */
    public function deleteLock($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return false;
        }

        $result = $this
            ->delete()
            ->from($this->getTableName($this->table_name))
            ->where('`id_cart` = ' . (int) $cart_id)
            ->build();

        return (bool) $result;
    }

    /**
     * @description Get a lock from a given cart id
     *
     * @param int $cart_id
     *
     * @return array
     */
    public function getByCartId($cart_id = 0)
    {
        if (!is_int($cart_id) || !$cart_id) {
            return [];
        }

        try {
            $result = $this
                ->select()
                ->fields('*')
                ->from($this->getTableName($this->table_name))
                ->where('`id_cart` = ' . (int) $cart_id)
                ->build('unique_row');
        } catch (Exception $exception) {
            return [];
        }

        return $result ?: [];
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
            ->table($this->getTableName($this->table_name))
            ->fields('`id_payplug_lock` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY')
            ->fields('`id_cart` INT(11) UNSIGNED NOT NULL')
            ->fields('`id_order` VARCHAR(100)')
            ->fields('`date_add` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->fields('`date_upd` DATETIME NOT NULL DEFAULT \'1000-01-01 00:00:00\'')
            ->condition('CONSTRAINT lock_cart_unique UNIQUE (id_cart)')
            ->engine($engine);

        return $this->build();
    }
}
