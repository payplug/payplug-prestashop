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

class OrderStateRepository extends QueryRepository
{
    /**
     * @description Get all order states for a given module name
     *
     * @param string $name
     *
     * @return array
     */
    public function getByModule($name = '')
    {
        if (!is_string($name) || !$name) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . 'order_state')
            ->where('module_name = \'' . $this->escape($name) . '\'')
            ->build()
        ;

        return $result ?: [];
    }

    /**
     * @description Get all order for a given order state
     *
     * @param string $name
     *
     * @return array
     */
    public function getUsedByModule($name = '')
    {
        if (!is_string($name) || !$name) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('*')
            ->from($this->prefix . 'orders', 'o')
            ->leftJoin($this->prefix . 'order_state', 'os', 'os.`id_order_state` = o.`current_state`')
            ->where('os.module_name = \'' . $this->escape($name) . '\'')
            ->build()
        ;

        return $result ?: [];
    }

    /**
     * @description Find id_order_state by name.
     *
     * @param array $name
     * @param bool $test_mode
     * @param mixed $check_version
     *
     * @return array
     */
    public function getByName($name = [], $test_mode = false, $check_version = false)
    {
        if (!is_array($name) || empty($name)) {
            return [];
        }

        if (!is_bool($test_mode) || empty($test_mode)) {
            return [];
        }

        if (!is_bool($check_version) || empty($check_version)) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('DISTINCT osl.`id_order_state`')
            ->from($this->prefix . 'order_state_lang', 'osl')
            ->leftJoin($this->prefix . 'order_state', 'os', 'osl.`id_order_state` = os.`id_order_state`')
            ->where('osl.`name` LIKE \'' . $this->escape($name['en'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')))
            ->whereOr('osl.`name` LIKE \'' . $this->escape($name['fr'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')))
            ->whereOr('osl.`name` LIKE \'' . $this->escape($name['es'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')))
            ->whereOr('osl.`name` LIKE \'' . $this->escape($name['it'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')));

        if ($check_version) {
            $result += $this->where('os.`deleted` = 0');
        }

        $result += $this->build('unique_value');

        return $result ?: [];
    }

    /**
     * @description Find a group of id_order_state by module_name.
     *
     * @param string $module_name
     *
     * @return array
     */
    public function getIdsByModuleName($module_name = '')
    {
        if (!is_string($module_name) || empty($module_name)) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('os.id_order_state')
            ->from($this->prefix . 'order_state', 'os')
            ->where('os.module_name = \'' . $this->escape($module_name) . '\'')
            ->build()
        ;

        return $result ?: [];
    }

    /**
     * @description Find a group of id_order_state used by Payplug.
     *
     * @return array
     */
    public function getIdsUsedByPayPlug()
    {
        $result = $this
            ->select()
            ->fields('c.value')
            ->from($this->prefix . 'configuration', 'c')
            ->where('c.name LIKE \'%' . $this->dependencies->getPlugin()->getTools()->tool('strtoupper', $this->dependencies->name) . '_ORDER_STATE_%\'')
            ->build()
        ;

        return $result ?: [];
    }

    /**
     * @description Find an id_order_state by template.
     *
     * @param string $template
     *
     * @return array
     */
    public function getOrderStateByTemplate($template = '')
    {
        if (!is_string($template) || !$template) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('DISTINCT `id_order_state`')
            ->from($this->prefix . 'order_state_lang')
            ->where('template = "' . $this->escape($template) . '"')
            ->limit(1, 1)
            ->build('unique_value')
        ;

        return $result ?: [];
    }

    public function getOrderHistory($order_id = 0, $lang_id = 0)
    {
        if (!is_int($order_id) || !$order_id) {
            return [];
        }

        if (!is_int($lang_id) || !$lang_id) {
            return [];
        }

        $result = $this
            ->select()
            ->fields('oh.id_order_state, osl.name')
            ->from($this->prefix . 'order_history', 'oh')
            ->leftJoin($this->prefix . 'order_state_lang', 'osl', 'osl.`id_order_state` = oh.`id_order_state`')
            ->where('oh.id_order = ' . (int) $order_id)
            ->where('osl.id_lang = ' . (int) $lang_id)
            ->build();

        return $result ?: [];
    }
}
