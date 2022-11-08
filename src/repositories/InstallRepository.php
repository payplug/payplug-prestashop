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

use Db;
use PayPlug\src\application\dependencies\BaseClass;

class InstallRepository extends BaseClass
{
    /** @var array */
    public $errors;

    /** @var object */
    public $log;
    /** @var object */
    protected $config;

    /** @var object */
    protected $constant;

    /** @var object */
    protected $context;

    /** @var object */
    protected $dependencies;

    /** @var object OrderStateRepository */
    protected $order_state;

    /** @var object OrderStateRepository */
    protected $order_state_entity;

    /** @var object */
    protected $order_state_adapter;

    /** @var object */
    protected $shop;

    /** @var object */
    protected $sql;

    /** @var object */
    protected $tools;

    /** @var object */
    protected $validate;

    public function __construct(
        $config,
        $constant,
        $context,
        $dependencies,
        $order_state,
        $order_state_entity,
        $order_state_adapter,
        $shop,
        $sql,
        $tools,
        $validate,
        $mylogphp
    ) {
        $this->config = $config;
        $this->constant = $constant;
        $this->context = $context;
        $this->dependencies = $dependencies;
        $this->order_state = $order_state;
        $this->order_state_entity = $order_state_entity;
        $this->order_state_adapter = $order_state_adapter;
        $this->shop = $shop;
        $this->sql = $sql;
        $this->tools = $tools;
        $this->validate = $validate;
        $this->log = $mylogphp;

        $this->setParams();
    }

    /**
     * @description Check if payplug order state are well installed
     */
    public function checkOrderStates()
    {
        $order_states_list = $this->order_state_entity->getList();

        foreach ($order_states_list as $key => $state) {
            // Check live OrderState
            $key_config_live = $this->dependencies->concatenateModuleNameTo('ORDER_STATE_')
                . $this->tools->tool('strtoupper', $key);
            $id_order_state_live = (int) $this->config->get($key_config_live);
            $order_state_live = $this->order_state_adapter->get((int) $id_order_state_live);
            if (!$this->validate->validate('isLoadedObject', $order_state_live)
                || (isset($order_state_live->deleted) && $order_state_live->deleted)) {
                $this->order_state->create($key, $state, false, true);
            }

            // Check sandbox OrderState
            $key_config_sandbox = $key_config_live . '_TEST';
            $id_order_state_sandbox = (int) $this->config->get($key_config_sandbox);
            $order_state_sandbox = $this->order_state_adapter->get((int) $id_order_state_sandbox);
            if (!$this->validate->validate('isLoadedObject', $order_state_sandbox)
                || (isset($order_state_sandbox->deleted) && $order_state_sandbox->deleted)) {
                $this->order_state->create($key, $state, true, true);
            }
        }

        $this->order_state->removeIdsUnusedByPayPlug();
    }

    /**
     * @description Create usual status
     *
     * @return bool
     */
    public function createOrderStates()
    {
        $order_states_list = $this->order_state_entity->getList();
        foreach ($order_states_list as $key => $state) {
            $this->order_state->create($key, $state, true);
            $this->order_state->create($key, $state, false);
        }

        $this->order_state->removeIdsUnusedByPayPlug();

        return true;
    }

    /**
     * @description Create usual status
     *
     * @return bool
     */
    public function createOrderStatesType()
    {
        $this->log->info('Execute createOrderStatesType');
        $order_states_list = $this->order_state_entity->getList();
        foreach ($order_states_list as $key => $state) {
            // live status
            $live_key = $this->order_state->getConfigKey($key, false);
            $id_order_state_live = $this->config->get($live_key);
            $this->log->info('Live key : ' . $live_key . ' / Id Order State: ' . $id_order_state_live);
            if ($id_order_state_live) {
                $res = $this->order_state->saveType((int) $id_order_state_live, $state['type']);
                $this->log->info('Save type: ' . $state['type'] . ' - result: ' . ($res ? 'ok' : 'ko'));
            }

            // sandbox status
            $sandbox_key = $this->order_state->getConfigKey($key, true);
            $id_order_state_sandbox = $this->config->get($sandbox_key);
            $this->log->info('Sandbox key : ' . $sandbox_key . ' / Id Order State: ' . $id_order_state_sandbox);
            if ($id_order_state_sandbox) {
                $res = $this->order_state->setType((int) $id_order_state_sandbox, $state['type']);
                $this->log->info('Save type: ' . $state['type'] . ' - result: ' . ($res ? 'ok' : 'ko'));
            }
        }
        // mapping of the native prestashop statuses
        $prestashop_order_states = [
            'PS_OS_PAYMENT' => 'paid',
            'PS_OS_WS_PAYMENT' => 'nothing',
            'PS_OS_CANCELED' => 'cancelled',
            'PS_OS_REFUND' => 'refund',
            'PS_OS_ERROR' => 'error',
            'PS_OS_CHEQUE' => 'nothing',
            'PS_OS_BANKWIRE' => 'nothing',
            'PS_OS_PREPARATION' => 'nothing',
            'PS_OS_SHIPPING' => 'nothing',
            'PS_OS_DELIVERED' => 'nothing',
        ];

        if (version_compare(_PS_VERSION_, '1.6.0.14', '<')) {
            $prestashop_order_states += [
                'PS_OS_OUTOFSTOCK' => 'nothing',
            ];
        } else {
            $prestashop_order_states += [
                'PS_OS_OUTOFSTOCK_PAID' => 'paid',
                'PS_OS_OUTOFSTOCK_UNPAID' => 'pending',
                'PS_OS_COD_VALIDATION' => 'nothing',
            ];
        }
        $date = date('Y-m-d');
        $queries = [];
        foreach ($prestashop_order_states as $key => $type) {
            $id_order_state = $this->config->get($key);
            $getTypeQuery = ' 
                SELECT `type` 
                FROM `' . _DB_PREFIX_ . $this->dependencies->name . '_order_state` 
                WHERE  `id_order_state` = ' . $id_order_state;
            $sqlGetType = Db::getInstance()->executeS($getTypeQuery);
            if ($sqlGetType && $sqlGetType != $type) {
                $queries[] = 'UPDATE `' . _DB_PREFIX_ . $this->dependencies->name . '_order_state` 
                                 SET `type` = ' . "'{$type}'" . ' 
                                 WHERE  `id_order_state` = ' . $id_order_state;
            } else {
                $queries[] = 'INSERT INTO `' . _DB_PREFIX_ . $this->dependencies->name . '_order_state` 
                                (`id_order_state`, `type`, `date_add`, `date_upd`)
                                VALUES (' . $id_order_state . ', "' . $type . '", "' . $date . '", "' . $date . '")';
            }
        }
        if ($queries) {
            foreach ($queries as $sql) {
                Db::getInstance()->execute($sql);
                unset($sql);
            }
        }

        return true;
    }

    /**
     * @description Install PayPlug Module
     *
     * @param bool $soft_install
     *
     * @return bool
     *
     * @see Module::install()
     */
    public function install()
    {
        $this->log->info('Starting to install again.');

        // check requirement
        $report = $this->dependencies->configClass->checkRequirements();
        if (!$report['php']['up2date']) {
            return $this->setInstallError('Install failed: PHP Requirement.');
        }
        if (!$report['curl']['up2date']) {
            return $this->setInstallError('Install failed: cURL Requirement.');
        }
        if (!$report['openssl']['up2date']) {
            return $this->setInstallError('Install failed: OpenSSL Requirement.');
        }

        // Check if multishop feature is active then set the context
        if ($this->shop->isFeatureActive()) {
            $this->shop->setContext();
        }

        // Set payplug config
        if (!$this->setConfig()) {
            return $this->setInstallError('Install failed:setConfig()');
        }

        // Install SQL
        if (!$this->sql->installSQL()) {
            return $this->setInstallError('Install failed: Install SQL tables.');
        }

        // Install order state
        if (!$this->createOrderStates()) {
            return $this->setInstallError('Install failed: Create order states.');
        }

        // Install order state type
        if (!$this->createOrderStatesType()) {
            return $this->setInstallError('Install failed: Create order states type.');
        }

        // Install tab
        if (!$this->installTab()) {
            return $this->setInstallError('Install failed: Install Tab');
        }

        $this->log->info('Install successful.');

        return true;
    }

    public function installTab()
    {
        return $this->dependencies->loadAdapterPresta()->installTab();
    }

    /**
     * @description Create basic configuration
     *
     * @return bool
     */
    public function setConfig()
    {
        foreach (array_keys($this->dependencies->configurationKeys) as $key) {
            if ($this->dependencies->getConfigurationKeyOption($key, 'setConf')) {
                if ($key == 'oney' && $this->dependencies->name == 'pspaylater') {
                    $this->config->updateValue(
                        $this->dependencies->getConfigurationKey($key),
                        1
                    );
                } else {
                    $this->config->updateValue(
                        $this->dependencies->getConfigurationKey($key),
                        $this->dependencies->getConfigurationKeyOption($key, 'defaultValue')
                    );
                }
            }
        }

        return true;
    }

    /**
     * @description Set error on module install
     *
     * @param $error
     *
     * @return bool
     */
    public function setInstallError($error = '')
    {
        $this->log->error($error);
        $this->errors[] = $this->tools->tool('displayError', $error);

        $this->log->info('Install failed.');
        $this->log->info('Install error: ' . $error);

        // revert installation
        $this->uninstall();

        return false;
    }

    /**
     * @description Set error on module uninstall
     *
     * @param $error
     *
     * @return bool
     */
    public function setUninstallError($error = '')
    {
        $this->log->error($error);

        return false;
    }

    /**
     * @description Uninstall PayPlug Module
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->log->info('Starting to uninstall.');

        $keep_cards = (bool) $this->config->get('PAYPLUG_KEEP_CARDS');
        if (!$keep_cards) {
            $this->log->info('Saved cards will be deleted.');

            if (!$this->dependencies->cardClass->uninstallCards()) {
                return $this->setUninstallError('Unable to delete saved cards.');
            }

            $this->log->info('Saved cards successfully deleted.');
        } else {
            $this->log->info('Cards will be kept.');
        }

        if (!$this->deleteConfig()) {
            return $this->setUninstallError('Uninstall failed: configuration.');
        }

        if (!$this->sql->uninstallSQL($keep_cards)) {
            return $this->setUninstallError('Uninstall failed: sql.');
        }

        if (!$this->dependencies->loadAdapterPresta()->uninstallTab()) {
            return $this->setUninstallError('Uninstall failed: tab.');
        }

        $this->log->info('Uninstall succeeded.');

        return true;
    }

    /**
     * @description Set module order state
     */
    protected function setParams()
    {
        $this->order_state_entity->setList([
            'paid' => [
                'cfg' => 'PS_OS_PAYMENT',
                'template' => 'payment',
                'logable' => true,
                'send_email' => true,
                'paid' => true,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#04b404',
                'name' => [
                    'en' => 'Payment accepted',
                    'fr' => 'Paiement effectué',
                    'es' => 'Pago efectuado',
                    'it' => 'Pagamento effettuato',
                ],
                'type' => 'paid',
            ],
            'refund' => [
                'cfg' => 'PS_OS_REFUND',
                'template' => 'refund',
                'logable' => false,
                'send_email' => true,
                'paid' => false,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => true,
                'color' => '#ea3737',
                'name' => [
                    'en' => 'Refunded',
                    'fr' => 'Remboursé',
                    'es' => 'Reembolsado',
                    'it' => 'Rimborsato',
                ],
                'type' => 'refund',
            ],
            'pending' => [
                'cfg' => 'PS_OS_PENDING',
                'template' => null,
                'logable' => false,
                'send_email' => false,
                'paid' => false,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => false,
                'color' => '#a1f8a1',
                'name' => [
                    'en' => 'Payment in progress',
                    'fr' => 'Paiement en cours',
                    'es' => 'Pago en curso',
                    'it' => 'Pagamento in corso',
                ],
                'type' => 'pending',
            ],
            'error' => [
                'cfg' => 'PS_OS_ERROR',
                'template' => 'payment_error',
                'logable' => false,
                'send_email' => true,
                'paid' => false,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => false,
                'color' => '#8f0621',
                'name' => [
                    'en' => 'Payment failed',
                    'fr' => 'Paiement échoué',
                    'es' => 'Payment failed',
                    'it' => 'Payment failed',
                ],
                'type' => 'error',
            ],
            'cancelled' => [
                'cfg' => 'PS_OS_CANCELED',
                'template' => 'order_canceled',
                'logable' => false,
                'send_email' => true,
                'paid' => false,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => false,
                'color' => '#2C3E50',
                'name' => [
                    'en' => 'Payment cancelled',
                    'fr' => 'Paiement annulé',
                    'es' => 'Payment cancelled',
                    'it' => 'Payment cancelled',
                ],
                'type' => 'cancelled',
            ],
            'auth' => [
                'cfg' => null,
                'template' => null,
                'logable' => false,
                'send_email' => false,
                'paid' => true,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => false,
                'color' => '#04b404',
                'name' => [
                    'en' => 'Payment authorized',
                    'fr' => 'Paiement autorisé',
                    'es' => 'Pago',
                    'it' => 'Pagamento',
                ],
                'type' => 'pending',
            ],
            'exp' => [
                'cfg' => null,
                'template' => null,
                'logable' => false,
                'send_email' => false,
                'paid' => false,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => false,
                'color' => '#8f0621',
                'name' => [
                    'en' => 'Autorization expired',
                    'es' => 'Autorización vencida',
                    'fr' => 'Autorisation expirée',
                    'it' => 'Autorizzazione scaduta',
                ],
                'type' => 'expired',
            ],
            'oney_pg' => [
                'cfg' => null,
                'template' => null,
                'logable' => false,
                'send_email' => false,
                'paid' => false,
                'module_name' => $this->dependencies->name,
                'hidden' => false,
                'delivery' => false,
                'invoice' => false,
                'color' => '#a1f8a1',
                'name' => [
                    'en' => 'Oney - Pending',
                    'fr' => 'Oney - En attente',
                    'es' => 'Oney - Pending',
                    'it' => 'Oney - Pending',
                ],
                'type' => 'pending',
            ],
        ]);
    }

    /**
     * @description Delete basic configuration
     *
     * @return bool
     */
    private function deleteConfig()
    {
        foreach (array_keys($this->dependencies->configurationKeys) as $key) {
            $this->config->deleteByName(
                $this->dependencies->getConfigurationKey($key)
            );
        }

        return true;
    }
}
