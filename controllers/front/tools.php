<?php

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
            array(
                'remove_payplug_order_states_link' => $this->context->link->getModuleLink('payplug', 'tools', array('action' => 'remove_payplug_order_states'))
            )
        );
        $this->setTemplate('tools/list.tpl');
    }

    public function postProcess()
    {
        $action = Tools::getValue('action');
        switch ($action) {
            case 'remove_payplug_order_states' :
                $this->removePayPlugOrderStates();
                break;
            default :
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
