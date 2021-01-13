<?php

namespace PayPlug\src\repositories;

class OrderRepository extends Repository
{
    /**
     * @description Add Order Payment
     *
     * @param int $id_order
     * @param string $id_payment
     * @return bool
     */
    public function addPayplugOrderPayment($id_order, $id_payment)
    {
        $sql = 'INSERT INTO ' . _DB_PREFIX_ . 'payplug_order_payment (id_order, id_payment) 
                VALUE (' . (int)$id_order . ',\'' . pSQL($id_payment) . '\')';

        return Db::getInstance()->execute($sql);
    }

    /**
     * @description Check if payplug order state are well installed
     */
    public function checkOrderStates()
    {
        $order_states = array_merge($this->order_states, $this->oney_order_state);

        foreach ($order_states as $key => $state) {
            // Check live OrderState
            $key_config_live = 'PAYPLUG_ORDER_STATE_' . Tools::strtoupper($key);
            $id_order_state_live = Configuration::get($key_config_live);
            $order_state_live = new OrderState((int)$id_order_state_live);
            if (!Validate::isLoadedObject($order_state_live)) {
                $this->createOrderState($key, $state, false, true);
            }

            // Check sandbox OrderState
            $key_config_sandbox = $key_config_live . '_TEST';
            $id_order_state_sandbox = Configuration::get($key_config_sandbox);
            $order_state_sandbox = new OrderState((int)$id_order_state_sandbox);

            if (!Validate::isLoadedObject($order_state_sandbox)) {
                $this->createOrderState($key, $state, true, true);
            }
        }

        $this->order_state->removeIdsUnusedByPayPlug();
    }

    public function createOrderState($name, $state, $sandbox = true, $force = false)
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $key_config = 'PAYPLUG_ORDER_STATE_' . Tools::strtoupper($name) . ($sandbox ? '_TEST' : '');

        $log->info('Order state: ' . $name . ($sandbox ? ' - test' : ''));
        $os = Configuration::get($key_config);

        // if we can't find order state with payplug key, check with configuration key
        if (!$os && !$sandbox && $state['cfg']) {
            $os = Configuration::get($state['cfg']);

            // if we don't find order state either, try with template name
            if (!$sandbox && $state['template'] != null) {
                $sql = 'SELECT DISTINCT `id_order_state`
                        FROM `' . _DB_PREFIX_ . 'order_state_lang` 
                        WHERE `template` = \'' . pSQL($state['template']) . '\'';
                $os = Db::getInstance()->getValue($sql);
            }
        }

        if (!$os || $force) {
            $log->info('Creating new order state.');
            $order_state = new OrderState();
            $order_state->logable = $state['logable'];
            $order_state->send_email = $state['send_email'];
            $order_state->paid = $state['paid'];
            $order_state->module_name = $state['module_name'];
            $order_state->hidden = $state['hidden'];
            $order_state->delivery = $state['delivery'];
            $order_state->invoice = $state['invoice'];
            $order_state->color = $state['color'];

            $tag = $sandbox ? ' [TEST]' : ' [PayPlug]';
            foreach (Language::getLanguages(false) as $lang) {
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
        }

        return Configuration::updateValue($key_config, $os);
    }

    /**
     * Create usual status
     *
     * @return bool
     * @throws Exception
     */
    public function createOrderStates()
    {
        $log = new Payplug\classes\MyLogPHP(_PS_MODULE_DIR_ . 'payplug/log/install-log.csv');
        $this->log_install->info('Order state creation starting.');

        foreach ($this->order_states as $key => $state) {
            $this->createOrderState($key, $state, true);
            $this->createOrderState($key, $state, false);
        }

        $this->order_state->removeIdsUnusedByPayPlug();

        $log->info('Order state creation ended.');
        return true;
    }

    /**
     * Find id_order_state by name
     *
     * @param array $name
     * @param bool $test_mode
     * @return int OR bool
     */
    private function findOrderState($name, $test_mode = false)
    {
        if (!is_array($name) || empty($name)) {
            return false;
        } else {
            $req_order_state = new DbQuery();
            $req_order_state->select('DISTINCT osl.id_order_state');
            $req_order_state->from('order_state_lang', 'osl');
            $req_order_state->where(
                'osl.name LIKE \'' . pSQL($name['en'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                OR osl.name LIKE \'' . pSQL($name['fr'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                OR osl.name LIKE \'' . pSQL($name['es'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\' 
                OR osl.name LIKE \'' . pSQL($name['it'] . ($test_mode ? ' [TEST]' : ' [PayPlug]')) . '\''
            );
            $res_order_state = Db::getInstance()->getValue($req_order_state);

            if (!$res_order_state) {
                return false;
            } else {
                return (int)$res_order_state;
            }
        }
    }

    /**
     * @param null $id_lang
     * @return array
     */
    private function getOrderStates($id_lang = null)
    {
        if ($id_lang === null) {
            $id_lang = $this->context->language->id;
        }
        $order_states = OrderState::getOrderStates($id_lang);
        return $order_states;
    }


    /**
     * @description
     * get order payment
     *
     * @param int $id_order
     * @return integer
     */
    public function getPayplugOrderPayment($id_order)
    {
        $sql = 'SELECT id_payment 
                FROM ' . _DB_PREFIX_ . 'payplug_order_payment   
                WHERE id_order = ' . (int)$id_order;

        return Db::getInstance()->getValue($sql);
    }

    /**
     * @description
     * get all order payment for given id order
     *
     * @param int $id_order
     * @return array
     */
    public function getPayplugOrderPayments($id_order)
    {
        $sql = 'SELECT * 
                FROM ' . _DB_PREFIX_ . 'payplug_order_payment 
                WHERE id_order = ' . (int)$id_order;

        return Db::getInstance()->executeS($sql);
    }

    /**
     * Only 1.6
     * @description Get the current Order State Id for a given Order ID
     *
     * @param bool $id_order
     * @return integer|false
     */
    public function getCurrentOrderState($id_order = false)
    {
        if (!$id_order) {
            return false;
        }

        $sql = 'SELECT `current_state` FROM `'._DB_PREFIX_.'orders` WHERE `id_order` = ' . (int)$id_order;
        return Db::getInstance()->getValue($sql);
    }

    /**
     * Display Oney CTA on Shopping cart page
     *
     * @param array $params
     * @return bool|mixedf
     */
    public function hookDisplayBeforeShoppingCartBlock($params)
    {
        if (!$this->oney->isOneyAllowed()) {
            return false;
        }

        $amount = $params['cart']->getOrderTotal(true, Cart::BOTH);
        $is_valid_amount = $this->oney->isValidOneyAmount($amount, $params['cart']->id_currency);

        $this->smarty->assign([
            'payplug_oney_amount' => $amount,
            'payplug_oney_allowed' => $is_valid_amount['result'],
            'payplug_oney_error' => $is_valid_amount['error'],
        ]);

        return $this->oney->getOneyCTA('checkout');
    }

}