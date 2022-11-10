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

namespace PayPlug\src\repositories;

use PayPlug\src\application\adapter\OrderStateAdapter;
use PayPlug\src\application\dependencies\BaseClass;

class OrderStateRepository extends BaseClass
{
    /** @var object */
    protected $constant;

    /** @var object */
    private $configuration;

    /** @var object */
    private $dependencies;

    /** @var object */
    private $language;

    /** @var object */
    private $order_state_adapter;

    /** @var object */
    private $query;

    /** @var object */
    private $tools;

    /** @var object */
    private $validate;

    public function __construct(
        $configuration,
        $constant,
        $dependencies,
        $language,
        $order_state_adapter,
        $query,
        $tools,
        $validate,
        $myLogPHP
    ) {
        $this->configuration = $configuration;
        $this->constant = $constant;
        $this->dependencies = $dependencies;
        $this->language = $language;
        $this->order_state_adapter = $order_state_adapter;
        $this->query = $query;
        $this->tools = $tools;
        $this->validate = $validate;
        $this->log = $myLogPHP;
    }

    public function add($name, $state = [], $sandbox = true)
    {
        if (!is_array($state)
            || empty($state)) {
            return false;
        }

        $this->log->info('Creating new order state.');
        $order_state = $this->order_state_adapter->get();
        $order_state->logable = $state['logable'];
        $order_state->send_email = $state['send_email'];
        $order_state->paid = $state['paid'];
        $order_state->module_name = $state['module_name'];
        $order_state->hidden = $state['hidden'];
        $order_state->delivery = $state['delivery'];
        $order_state->invoice = $state['invoice'];
        $order_state->color = $state['color'];

        $tag = $sandbox ? ' [TEST]' : ' [PayPlug]';
        $languages = $this->language->getLanguages(false);
        foreach ($languages as $lang) {
            $order_state->template[$lang['id_lang']] = $state['template'];
            if (in_array($lang['iso_code'], ['en', 'au', 'ca', 'ie', 'gb', 'uk', 'us'], true)) {
                $order_state->name[$lang['id_lang']] = $state['name']['en'] . $tag;
            } elseif (in_array($lang['iso_code'], ['fr', 'be', 'lu', 'ch'], true)) {
                $order_state->name[$lang['id_lang']] = $state['name']['fr'] . $tag;
            } elseif (in_array($lang['iso_code'], ['es', 'ar', 'cl', 'co', 'mx', 'py', 'uy', 've'], true)) {
                $order_state->name[$lang['id_lang']] = $state['name']['es'] . $tag;
            } elseif (in_array($lang['iso_code'], ['it', 'sm', 'va'], true)) {
                $order_state->name[$lang['id_lang']] = $state['name']['it'] . $tag;
            } else {
                $order_state->name[$lang['id_lang']] = $state['name']['en'] . $tag;
            }
        }

        if ($order_state->add()) {
            $source = $this->constant->get('_PS_MODULE_DIR_') . $this->name . '/views/img/os/' . $name . '.gif';
            $destination = $this->constant->get('_PS_ROOT_DIR_') . '/img/os/' . $order_state->id . '.gif';
            @copy($source, $destination);
            $this->log->info('State created with id: ' . $order_state->id);

            return $order_state->id;
        }

        return false;
    }

    public function create($name = false, $state = [], $sandbox = true, $force = false)
    {
        if (!is_string($name)
            || !$name
            || !is_array($state)
            || empty($state)) {
            return false;
        }

        $this->log->info('Order state: ' . $name . ($sandbox ? ' - test' : ''));

        $key_config = $this->getConfigKey($name, $sandbox);
        $id_order_state = $this->configuration->get($key_config);

        // Get order state id with given configuration key
        if (!$id_order_state && !$sandbox && isset($state['cfg']) && $state['cfg']) {
            $id_order_state = $this->getOrderStateByConfiguration($state['cfg']);
            if ($id_order_state) {
                // Valide order state
                $os = $this->order_state_adapter->get((int) $id_order_state);
                if ($this->validate->validate('isLoadedObject', $os) && (!isset($os->deleted) || !$os->deleted)) {
                    return $this->configuration->updateValue($key_config, $os->id);
                }
            }
        }

        // Get order state id with given template
        if (!$id_order_state && !$sandbox && isset($state['template']) && $state['template']) {
            $id_order_state = $this->getOrderStateByTemplate($state['template']);
        }

        // Get order state id with given name
        if (!$id_order_state && isset($state['name']) && $state['name']) {
            $id_order_state = $this->findByName($state['name'], $sandbox);
        }

        // Create order state if no id order state found
        if (!$id_order_state || $force) {
            $id_order_state = $this->add($name, $state, $sandbox);
        }

        // Check if order state is valid
        $order_state = $this->order_state_adapter->get((int) $id_order_state);
        if (!$this->validate->validate('isLoadedObject', $order_state)
            || (isset($order_state->deleted) && $order_state->deleted)) {
            $id_order_state = $this->add($name, $state, $sandbox);
        }

        return $this->configuration->updateValue($key_config, $id_order_state);
    }

    /**
     * @param int $id_order_state
     *
     * @return bool
     */
    public function deleteType($id_order_state)
    {
        // FIXME: from php7, psr12 requires return type

        if (!$id_order_state || !is_int($id_order_state)) {
            return false;
        }
        $this->query
            ->delete()
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_order_state')
            ->where('id_order_state = ' . (int) $id_order_state)
            ->build()
        ;

        return true;
    }

    public static function factory()
    {
        return new OrderStateRepository();
    }

    /**
     * Find id_order_state by name
     *
     * @param array $name
     * @param bool  $test_mode
     *
     * @return int OR bool
     */
    public function findByName($name, $test_mode = false)
    {
        if (!is_array($name) || empty($name)) {
            return false;
        }
        $this->query
            ->select()
            ->fields('DISTINCT osl.`id_order_state`')
            ->from(_DB_PREFIX_ . 'order_state_lang', 'osl')
            ->leftJoin(_DB_PREFIX_ . 'order_state', 'os', 'osl.`id_order_state` = os.`id_order_state`')
            ->where(
                'osl.`name` LIKE \'' . $this->query->escape($name['en'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                    OR osl.`name` LIKE \'' . $this->query->escape($name['fr'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                    OR osl.`name` LIKE \'' . $this->query->escape($name['es'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                    OR osl.`name` LIKE \'' . $this->query->escape($name['it'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\''
            )
        ;

        if (version_compare(_PS_VERSION_, '1.7.7.0', '>=')) {
            $this->query->where('os.`deleted` = 0');
        }

        return $this->query->build('unique_value');
    }

    public function getConfigKey($name = false, $sandbox = false)
    {
        if (!is_string($name)
            || !$name) {
            return false;
        }

        return $this->dependencies->concatenateModuleNameTo('ORDER_STATE_')
                    . $this->tools->tool('strtoupper', $name)
                    . ($sandbox ? '_TEST' : '');
    }

    public function getIdByDefinition($definition)
    {
        if (!isset($definition['template'])
            || !isset($definition['invoice'])
            || !isset($definition['send_email'])
            || !isset($definition['hidden'])
            || !isset($definition['logable'])
            || !isset($definition['delivery'])
            || !isset($definition['shipped'])
            || !isset($definition['paid'])
            || !isset($definition['pdf_invoice'])
            || !isset($definition['pdf_delivery'])
        ) {
            return false;
        }
        $this->query
            ->select()
            ->fields('os.id_order_state')
            ->from(_DB_PREFIX_ . 'order_state', 'os')
            ->leftJoin(
                _DB_PREFIX_ . 'order_state_lang',
                'osl',
                'osl.id_order_state = os.id_order_state 
                     AND osl.template = \'' . pSQL($definition['template']) . '\''
            )
            ->where('os.invoice = ' . (int) $definition['invoice'])
            ->where('os.send_email = ' . (int) $definition['send_email'])
            ->where('os.hidden = ' . (int) $definition['hidden'])
            ->where('os.logable = ' . (int) $definition['logable'])
            ->where('os.delivery = ' . (int) $definition['delivery'])
            ->where('os.shipped = ' . (int) $definition['shipped'])
            ->where('os.paid = ' . (int) $definition['paid'])
            ->where('os.pdf_invoice = ' . (int) $definition['pdf_invoice'])
            ->where('os.pdf_delivery = ' . (int) $definition['pdf_delivery'])
            ->limit(1, 1)
        ;

        return (int) $this->query->build('unique_value');
    }

    public function getIdByKey($key)
    {
        return (int) $this->configuration->get($key);
    }

    public function getIdByName($name)
    {
        $this->query
            ->select()
            ->fields('osl.id_order_state')
            ->from(_DB_PREFIX_ . 'order_state_lang', 'osl')
            ->where('osl.name = \'' . $this->query->escape($name) . '\'')
            ->limit(1, 1)
        ;

        return $this->query->build('unique_value');
    }

    public function getIdsByModuleName($module_name)
    {
        $ids = [];
        $res = $this->query
            ->select()
            ->fields('os.id_order_state')
            ->from(_DB_PREFIX_ . 'order_state', 'os')
            ->where('os.module_name = \'' . $this->query->escape($module_name) . '\'')
            ->build()
        ;

        foreach ($res as $os) {
            array_push($ids, (int) $os['id_order_state']);
        }

        return $ids;
    }

    public function getIdsUsedByPayPlug()
    {
        $ids = [];
        $res = $this->query
            ->select()
            ->fields('c.value')
            ->from(_DB_PREFIX_ . 'configuration', 'c')
            ->where('c.name LIKE \'%PAYPLUG_ORDER_STATE_%\'')
            ->build()
        ;

        foreach ($res as $os) {
            array_push($ids, (int) $os['value']);
        }

        return $ids;
    }

    public function getOrderStateByConfiguration($key = false)
    {
        if (!is_string($key) || !$key) {
            return false;
        }

        return $this->configuration->get($key);
    }

    public function getOrderStateByTemplate($template = false)
    {
        if (!is_string($template) || !$template) {
            return false;
        }

        $this->query
            ->select()
            ->fields('DISTINCT `id_order_state`')
            ->from($this->constant->get('_DB_PREFIX_') . 'order_state_lang')
            ->where('template = "' . $this->query->escape($template) . '"')
            ->limit(1, 1)
        ;

        return $this->query->build('unique_value');
    }

    public function getType($id_order_state)
    {
        return $this->query
            ->select()
            ->fields('type')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_order_state')
            ->where('id_order_state = ' . (int) $id_order_state)
            ->build('unique_value')
        ;
    }

    public function isUsedByOrders($module_name)
    {
        $ids = [];
        $res = $this->query
            ->select()
            ->fields('o.current_state')
            ->from(_DB_PREFIX_ . 'orders', 'o')
            ->where('o.module = \'' . $this->query->escape($module_name) . '\'')
            ->groupBy('o.current_state')
            ->build()
        ;

        foreach ($res as $os) {
            if ($os) {
                array_push($ids, (int) $os['current_state']);
            }
        }

        return $ids;
    }

    public function removeIdsUnusedByPayPlug()
    {
        $deleted = true;
        $payplug_os_id_list = $this->getIdsByModuleName('payplug');
        $used_order_os_id_list = $this->isUsedByOrders('payplug');
        $used_os_id_list = $this->getIdsUsedByPayPlug();
        foreach ($payplug_os_id_list as $payplug_os_id) {
            if (!in_array($payplug_os_id, $used_os_id_list) && !in_array($payplug_os_id, $used_order_os_id_list)) {
                $os = new OrderStateAdapter($payplug_os_id);
                $deleted = $deleted && $os->softDelete();
            }
        }

        return $deleted;
    }

    public function saveType($id_order_state = false, $type = '')
    {
        if (!$id_order_state || !is_int($id_order_state)) {
            return false;
        }

        if (!$type || !is_string($type)) {
            return false;
        }

        if ($this->getType($id_order_state)) {
            return $this->updateType($id_order_state, $type);
        }

        return $this->setType($id_order_state, $type);
    }

    public function setType($id_order_state, $type)
    {
        $date = date('Y-m-d');
        $this->query
            ->insert()
            ->into($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_order_state')
            ->fields('id_order_state')->values(pSQL($id_order_state))
            ->fields('type')->values(pSQL($type))
            ->fields('date_add')->values($date)
            ->fields('date_upd')->values($date);

        return $this->query->build();
    }

    public function updateType($id_order_state, $type)
    {
        $date = date('Y-m-d');
        $this->query
            ->update()
            ->table($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_order_state')
            ->set('type = \'' . $this->query->escape($type) . '\'')
            ->set('date_upd = \'' . $this->query->escape($date) . '\'')
            ->where('id_order_state = ' . (int) $id_order_state)
        ;

        return $this->query->build();
    }
}
