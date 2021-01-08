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

use PayPlug\src\entities\ConfigurationEntity;
use PayPlug\src\entities\OrderStateEntity;

class OrderStateRepository extends Repository
{
    private $query;
    private $configuration;

    public function __construct()
    {
        $this->query = new QueryRepository();
        $this->configuration = new ConfigurationEntity();
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

    public function removeIdsUnusedByPayPlug()
    {
        $payplug_os_id_list = $this->getIdsByModuleName('payplug');
        $used_os_id_list = $this->getIdsUsedByPayPlug();
        foreach ($payplug_os_id_list as $payplug_os_id) {
            if (!in_array($payplug_os_id, $used_os_id_list)) {
                $os = new OrderStateEntity($payplug_os_id);
                $os->delete();
            }
        }
    }
    /*
            public function getOrderState($config)
            {
                $id = 0;
                $case = 0;
                if (!isset($config['template'])
                    || !isset($config['invoice'])
                    || !isset($config['send_email'])
                    || !isset($config['hidden'])
                    || !isset($config['logable'])
                    || !isset($config['delivery'])
                    || !isset($config['shipped'])
                    || !isset($config['paid'])
                    || !isset($config['pdf_invoice'])
                    || !isset($config['pdf_delivery'])
                    || !isset($config['name'])
                    || !isset($config['key'])
                ) {
                    return false;
                } else {
                    while ((int)$id === 0) {
                        switch ($case) {
                            case 0:
                                $id = $this->getOrderStateIdByKey($config['key']);
                                $case ++;
                                break;
                            case 1:
                                $id = $this->getOrderStateIdByName($config['name']);
                                $case ++;
                                break;
                            case 2:
                                $id = $this->getOrderStateIdByConfig($config);
                                $case ++;
                                break;
                            default:
                                return false;
                        }
                    }

                    $os = new OrderState($id);
                    if (!Validate::isLoadedObject($os)) {
                        return false;
                    } else {
                        return $os;
                    }
                }
            }


*/
}
