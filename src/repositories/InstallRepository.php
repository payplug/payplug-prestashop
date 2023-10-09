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

namespace PayPlug\src\repositories;

use PayPlug\src\application\dependencies\BaseClass;
use PayPlug\src\models\classes\Configuration;

class InstallRepository extends BaseClass
{
    /** @var array */
    public $errors;

    /** @var object */
    public $log;

    /** @var object */
    protected $configuration;

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
    protected $query;

    /** @var object */
    protected $shop;

    /** @var object */
    protected $sql;

    /** @var object */
    protected $tools;

    /** @var object */
    protected $validate;

    public function __construct(
        $configuration,
        $constant,
        $context,
        $dependencies,
        $order_state,
        $order_state_entity,
        $order_state_adapter,
        $query,
        $shop,
        $sql,
        $tools,
        $validate,
        $myLogPhp
    ) {
        $this->configuration = $configuration;
        $this->constant = $constant;
        $this->context = $context;
        $this->dependencies = $dependencies;
        $this->order_state = $order_state;
        $this->order_state_entity = $order_state_entity;
        $this->order_state_adapter = $order_state_adapter;
        $this->query = $query;
        $this->shop = $shop;
        $this->sql = $sql;
        $this->tools = $tools;
        $this->validate = $validate;
        $this->log = $myLogPhp;
    }

    /**
     * @description Check if payplug order state are well installed
     */
    public function checkOrderStates()
    {
        $order_states_list = $this->dependencies->getPlugin()->getConfigurationClass()->order_states;

        foreach ($order_states_list as $key => $state) {
            // Check live OrderState
            $key_config_live = 'order_state_' . $this->tools->tool('strtolower', $key);
            $id_order_state_live = (int) $this->configuration->getValue($key_config_live);
            $order_state_live = $this->order_state_adapter->get((int) $id_order_state_live);
            if (!$this->validate->validate('isLoadedObject', $order_state_live)
                || (isset($order_state_live->deleted) && $order_state_live->deleted)) {
                $this->order_state->create($key, $state, false, true);
            }

            // Check sandbox OrderState
            $key_config_sandbox = $key_config_live . '_test';
            $id_order_state_sandbox = (int) $this->configuration->getValue($key_config_sandbox);
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
        $order_states_list = $this->dependencies->getPlugin()->getConfigurationClass()->order_states;

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
        $order_states_list = $this->dependencies->getPlugin()->getConfigurationClass()->order_states;

        foreach ($order_states_list as $key => $state) {
            // live status
            $live_key = 'order_state_' . $key;
            $id_order_state_live = $this->configuration->getValue($live_key);
            if ($id_order_state_live) {
                $this->order_state->saveType((int) $id_order_state_live, $state['type']);
            }

            // sandbox status
            $sandbox_key = 'order_state_' . $key . '_test';
            $id_order_state_sandbox = $this->configuration->getValue($sandbox_key);
            if ($id_order_state_sandbox) {
                $this->order_state->saveType((int) $id_order_state_sandbox, $state['type']);
            }
        }

        // mapping of the native prestashop statuses
        $prestashop_order_states = [
            'PS_OS_BANKWIRE' => 'nothing',
            'PS_OS_CANCELED' => 'cancelled',
            'PS_OS_CHEQUE' => 'nothing',
            'PS_OS_COD_VALIDATION' => 'nothing',
            'PS_OS_DELIVERED' => 'nothing',
            'PS_OS_ERROR' => 'error',
            'PS_OS_PAYMENT' => 'paid',
            'PS_OS_PREPARATION' => 'nothing',
            'PS_OS_OUTOFSTOCK_PAID' => 'paid',
            'PS_OS_OUTOFSTOCK_UNPAID' => 'pending',
            'PS_OS_SHIPPING' => 'nothing',
            'PS_OS_REFUND' => 'refund',
            'PS_OS_WS_PAYMENT' => 'nothing',
        ];
        $configurationAdapter = $this->dependencies->getPlugin()->getConfiguration();
        foreach ($prestashop_order_states as $config_key => $type) {
            $id_order_state = $configurationAdapter->get($config_key);
            if ($id_order_state) {
                $this->order_state->saveType((int) $id_order_state, $type);
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
        $this->log->info('Starting to install');

        // check requirement
        $this->log->info('Check requirement');
        $report = $this->dependencies->configClass->getReportRequirements();
        if (!$report['php']['up2date']) {
            return $this->setInstallError('Install failed: PHP Requirement.');
        }
        if (!$report['curl']['up2date']) {
            return $this->setInstallError('Install failed: cURL Requirement.');
        }
        if (!$report['openssl']['up2date']) {
            return $this->setInstallError('Install failed: OpenSSL Requirement.');
        }
        $this->log->info('Check requirement: OK');

        // Check if multishop feature is active then set the context
        if ($this->shop->isFeatureActive()) {
            $this->log->info('Set context');
            $this->shop->setContext();
        }

        // Set payplug config
        $this->log->info('Set configuration');
        if (!$this->setConfig()) {
            return $this->setInstallError('Install failed:setConfig()');
        }
        $this->log->info('Set configuration: OK');

        // Install SQL
        $this->log->info('Install SQL');
        if (!$this->sql->installSQL()) {
            return $this->setInstallError('Install failed: Install SQL tables.');
        }
        $this->log->info('Install SQL: OK');

        // Install order state
        $this->log->info('Install order state');
        if (!$this->createOrderStates()) {
            return $this->setInstallError('Install failed: Create order states.');
        }
        $this->log->info('Install order state: OK');

        // Install order state type
        $this->log->info('Install order state type');
        if (!$this->createOrderStatesType()) {
            return $this->setInstallError('Install failed: Create order states type.');
        }
        $this->log->info('Install order state type: OK');

        // Install tab
        $this->log->info('Install tab');
        if (!$this->installTab()) {
            return $this->setInstallError('Install failed: Install Tab');
        }
        $this->log->info('Install tab: OK');

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
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();

        if ($configuration->configurations) {
            foreach ($configuration->configurations as $key => $config) {
                if ($config['setConf']) {
                    if ('payment_methods' == $key && 'pspaylater' == $this->dependencies->name) {
                        $payment_method = json_decode($config['defaultValue'], true);
                        $payment_method['oney'] = true;
                        $configuration->set('payment_methods', json_encode($payment_method));
                    } else {
                        $configuration->set($key, $config['defaultValue']);
                    }
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

        $keep_cards = (bool) $this->configuration->getValue('keep_cards');
        if (!$keep_cards) {
            $this->log->info('Saved cards will be deleted.');
            $uninstall_cards = $this->dependencies
                ->getPlugin()
                ->getCardAction()
                ->uninstallAction();
            if (!$uninstall_cards) {
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
     * @description Delete basic configuration
     *
     * @return bool
     */
    private function deleteConfig()
    {
        $configuration = $this->dependencies->getPlugin()->getConfigurationClass();

        if ($configuration->configurations) {
            foreach ($configuration->configurations as $key => $config) {
                $configuration->delete($key);
            }
        }

        return true;
    }
}
