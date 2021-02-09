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

class PayplugToolsModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        $this->displayToolsList();
    }

    private function displayToolsList()
    {
        $this->context->smarty->assign(
            [
                'remove_payplug_order_states_link' => $this->context->link->getModuleLink(
                    'payplug',
                    'tools',
                    ['action' => 'remove_payplug_order_states']
                )
            ]
        );
        $this->setTemplate('tools/list.tpl');
    }

    public function postProcess()
    {
        $action = Tools::getValue('action');
        switch ($action) {
            case 'remove_payplug_order_states':
                $this->removePayPlugOrderStates();
                break;
            default:
                echo 'action invalide';
                break;
        }
    }

    private function removePayPlugOrderStates()
    {
        $order_states_to_remove = $this->getAllPayPlugOrderStates();
        if (is_array($order_states_to_remove) && !empty($order_states_to_remove)) {
            foreach ($order_states_to_remove as $order_state) {
                $os = new OrderState((int)$order_state['id_order_state']);
                if (Validate::isLoadedObject($os)) {
                    $os->delete();
                }
            }
        }
    }

    private function getAllPayPlugOrderStates()
    {
        $cache_id = 'PayPlug::getAllPayPlugOrderStates';
        if (!Cache::isStored($cache_id)) {
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS('
			SELECT `id_order_state`
			FROM `'._DB_PREFIX_.'order_state` os
			WHERE `module_name` = \'payplug\' 
			ORDER BY `id_order_state` ASC');
            Cache::store($cache_id, $result);
            return $result;
        }
        return Cache::retrieve($cache_id);
    }
}
