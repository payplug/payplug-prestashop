<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

use PayPlug\classes\MyLogPHP;
use PayPlug\src\specific\OrderStateSpecific;

class OrderStateRepository extends Repository
{
    private $configuration;
    private $language;
    private $query;
    private $tools;

    public function __construct($configuration, $language, $query, $tools)
    {
        $this->configuration = $configuration;
        $this->language = $language;
        $this->query = $query;
        $this->tools = $tools;
    }

    public function create($name, $state, $sandbox = true, $force = false)
    {
        $log = new MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $key_config = 'PAYPLUG_ORDER_STATE_' . $this->tools->strtoupper($name) . ($sandbox ? '_TEST' : '');

        $log->info('Order state: ' . $name . ($sandbox ? ' - test' : ''));
        $os = $this->configuration->get($key_config);

        // if we can't find order state with payplug key, check with configuration key
        if (!$os && !$sandbox && $state['cfg']) {
            $os = $this->configuration->get($state['cfg']);

            // if we don't find order state either, try with template name
            if (!$os && !$sandbox && $state['template'] != null) {
                $this->query
                    ->select()
                    ->fields('DISTINCT `id_order_state`')
                    ->from(_DB_PREFIX_.'order_state_lang')
                    ->where('template = \''.pSQL($state['template']).'\'')
                    ->limit(1, 1);

                $os = $this->query->build('unique_value');
            }
        }

        if (!$os || $force) {
            // before creating a new order state, we should check if a previous state correspond to our needs
            $previous_order_state_id = $this->findByName($state['name'], $sandbox);
            if ($previous_order_state_id) {
                $log->info('Update order state with: ' . $previous_order_state_id);
                return $this->configuration->updateValue($key_config, $previous_order_state_id);
            }

            $log->info('Creating new order state.');
            $order_state = OrderStateSpecific::getOrderState();
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
                $source = _PS_MODULE_DIR_ . $this->name . '/views/img/os/' . $name . '.gif';
                $destination = _PS_ROOT_DIR_ . '/img/os/' . $order_state->id . '.gif';
                @copy($source, $destination);
                $log->info('State created');
            }
            $os = $order_state->id;
            $log->info('ID: ' . $os);
        } else {
            $log->info('Order state already exists: ' . $os);
        }

        return $this->configuration->updateValue($key_config, $os);
    }

    public static function factory()
    {
        return new OrderStateRepository();
    }

    /**
     * Find id_order_state by name
     *
     * @param array $name
     * @param bool $test_mode
     * @return int OR bool
     */
    private function findByName($name, $test_mode = false)
    {
        if (!is_array($name) || empty($name)) {
            return false;
        } else {
            $this->query
                ->select()
                ->fields('DISTINCT `id_order_state`')
                ->from(_DB_PREFIX_.'order_state_lang')
                ->where(
                    'name LIKE \'' . pSQL($name['en'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                    OR name LIKE \'' . pSQL($name['fr'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                    OR name LIKE \'' . pSQL($name['es'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                    OR name LIKE \'' . pSQL($name['it'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\''
                )
                ->limit(1, 1);

            return $this->query->build('unique_value');
        }
    }

    public function getIdByName($name)
    {
        $this->query
            ->select()
            ->fields('osl.id_order_state')
            ->from(_DB_PREFIX_.'order_state_lang', 'osl')
            ->where('osl.name = \''.pSQL($name).'\'')
            ->limit(1, 1);

        return $this->query->build('unique_value');
    }

    public function getIdByKey($key)
    {
        return (int)$this->configuration->get($key);
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
        } else {
            $this->query
                ->select()
                ->fields('os.id_order_state')
                ->from(_DB_PREFIX_.'order_state', 'os')
                ->leftJoin(
                    _DB_PREFIX_.'order_state_lang',
                    'osl',
                    'osl.id_order_state = os.id_order_state AND osl.template = \''.pSQL($definition['template']).'\''
                )
                ->where('os.invoice = '.(int)$definition['invoice'])
                ->where('os.send_email = '.(int)$definition['send_email'])
                ->where('os.hidden = '.(int)$definition['hidden'])
                ->where('os.logable = '.(int)$definition['logable'])
                ->where('os.delivery = '.(int)$definition['delivery'])
                ->where('os.shipped = '.(int)$definition['shipped'])
                ->where('os.paid = '.(int)$definition['paid'])
                ->where('os.pdf_invoice = '.(int)$definition['pdf_invoice'])
                ->where('os.pdf_delivery = '.(int)$definition['pdf_delivery'])
                ->limit(1, 1);

            return (int)$this->query->build('unique_value');
        }
    }

    public function getIdsByModuleName($module_name)
    {
        $ids = [];
        $res = $this->query
            ->select()
            ->fields('os.id_order_state')
            ->from(_DB_PREFIX_.'order_state', 'os')
            ->where('os.module_name = \''.pSQL($module_name).'\'')
            ->build();

        foreach ($res as $os) {
            array_push($ids, (int)$os['id_order_state']);
        }

        return $ids;
    }

    public function getIdsUsedByPayPlug()
    {
        $ids = [];
        $res = $this->query
            ->select()
            ->fields('c.value')
            ->from(_DB_PREFIX_.'configuration', 'c')
            ->where('c.name LIKE \'%PAYPLUG_ORDER_STATE_%\'')
            ->build();

        foreach ($res as $os) {
            array_push($ids, (int)$os['value']);
        }

        return $ids;
    }

    public function isUsedByOrders($module_name)
    {
        $ids = [];
        $res = $this->query
            ->select()
            ->fields('o.current_state')
            ->from(_DB_PREFIX_.'orders', 'o')
            ->where('o.module = \''.pSQL($module_name).'\'')
            ->groupBy('o.current_state')
            ->build();

        foreach ($res as $os) {
            if ($os) {
                array_push($ids, (int)$os['current_state']);
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
                $os = new OrderStateSpecific($payplug_os_id);
                $deleted = $deleted && $os->softDelete();
            }
        }
        return $deleted;
    }
}
