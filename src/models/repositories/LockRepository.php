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

class LockRepository extends QueryRepository
{
    private $fields = [
        'id_cart' => 'integer',
        'id_order' => 'string',
        'date_add' => 'string',
        'date_upd' => 'string',
    ];

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
            ->into($this->prefix . $this->dependencies->name . '_lock');

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
            ->from($this->prefix . $this->dependencies->name . '_lock')
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

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . $this->dependencies->name . '_lock')
            ->where('`id_cart` = ' . (int) $cart_id)
            ->build('unique_row');

        return $result ?: [];
    }
}
