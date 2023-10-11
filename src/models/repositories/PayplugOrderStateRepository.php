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

class PayplugOrderStateRepository extends QueryRepository
{
    public function __construct($prefix = '', $dependencies = null)
    {
        parent::__construct($prefix, $dependencies);
        $this->table_name = $this->prefix . $this->dependencies->name . '_order_state';
    }

    /**
     * @description Create a Payplug order state
     *
     * @param int $id_order_state
     * @param string $type
     *
     * @return bool
     */
    public function setOrderState($id_order_state = 0, $type = '')
    {
        if (!is_int($id_order_state) || !$id_order_state) {
            return false;
        }

        if (!is_string($type) || !$type) {
            return false;
        }

        $current_date = date('Y-m-d H:i:s');
        $result = $this
            ->insert()
            ->into($this->table_name)
            ->fields('id_order_state')->values((int) $id_order_state)
            ->fields('type')->values($this->escape($type))
            ->fields('date_add')->values($this->escape($current_date))
            ->fields('date_upd')->values($this->escape($current_date))
            ->build();

        return (bool) $result;
    }

    /**
     * @description Remove a Payplug order state by id_order_state
     *
     * @param int $id_order_state
     *
     * @return bool
     */
    public function removeByIdOrderState($id_order_state)
    {
        if (!is_int($id_order_state) || !$id_order_state) {
            return false;
        }

        $result = $this
            ->delete()
            ->from($this->table_name)
            ->where('id_order_state = ' . (int) $id_order_state)
            ->build()
        ;

        return (bool) $result;
    }

    /**
     * @description Get a Payplug order state
     *
     * @return array
     */
    public function getAll()
    {
        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . $this->dependencies->name . 'order_state')
            ->build()
        ;

        return $result ?: [];
    }

    /**
     * @description Update a Payplug order state
     *
     * @param int $id_order_state
     * @param string $type
     *
     * @return bool
     */
    public function updateByOderState($id_order_state = 0, $type = '')
    {
        if (!is_int($id_order_state) || !$id_order_state) {
            return false;
        }

        if (!is_string($type) || !$type) {
            return false;
        }

        $current_date = date('Y-m-d H:i:s');
        $result = $this
            ->update()
            ->table($this->table_name)
            ->set('type = "' . $this->escape($type) . '"')
            ->set('date_upd = "' . $this->escape($current_date) . '"')
            ->where('id_order_state = ' . (int) $id_order_state)
            ->build()
        ;

        return (bool) $result;
    }

    /**
     * @description Get a Payplug order state Type using id_order_state
     *
     * @param int $id_order_state
     *
     * @return array
     */
    public function getTypeByIdOrderState($id_order_state)
    {
        if (!is_int($id_order_state) || !$id_order_state) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('type')
            ->from($this->table_name)
            ->where('id_order_state = ' . (int) $id_order_state)
            ->build('unique_value')
        ;

        return $result ?: [];
    }
}
