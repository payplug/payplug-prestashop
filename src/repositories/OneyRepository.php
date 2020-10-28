<?php

namespace PayPlug\src\repositories;

use PayPlug\src\specific\ConfigurationSpecific;

class OneyRepository extends \Payplug
{
    public $logger;
    public $SQLtable;
    protected $configurationSpecific;

    public function __construct()
    {
        $this->logger = (new PluginRepository())->getEntity()->getLogger();
        $this->SQLtable = new SQLtableRepository();
        $this->configurationSpecific = new ConfigurationSpecific();

        $params['process'] = 'Install Oney';
        $this->logger->setParams($params);
    }

    /**
     * Install Oney feature
     */
    public function installOney()
    {
        $this->logger->addLog('Install Oney features (Hook, config, order states, SQL and carriers)', 'info');
        return (new HookRepository())->installOneyHook()
            && $this->installOneyConfig()
            && $this->installOneyOrderStates()
            && $this->SQLtable->installOneySql()
            && $this->installOneyCarriers();
    }

    /**
     * Install Oney Config
     * @return bool
     */
    public function installOneyConfig()
    {
        $this->logger->addLog('Install Oney config', 'info');
        
        $config = $this->configurationSpecific;

        $flag = true;
        if (!$this->configurationSpecific->updateValue('PAYPLUG_ONEY', 0) ||
            !$this->configurationSpecific->updateValue('PAYPLUG_ONEY_ALLOWED_COUNTRIES', '') ||
            !$this->configurationSpecific->updateValue('PAYPLUG_ONEY_MAX_AMOUNTS', 'EUR:2000') ||
            !$this->configurationSpecific->updateValue('PAYPLUG_ONEY_MIN_AMOUNTS', 'EUR:150') ||
            !$this->configurationSpecific->updateValue('PAYPLUG_ONEY_TOS', 0) ||
            !$this->configurationSpecific->updateValue('PAYPLUG_ONEY_TOS_URL', '')
        ) {
            $this->logger->addLog('Installation failed: Oney config', 'error');
            $flag = false;
        }
        return $flag;
    }

    /**
     * Install Oney Order State
     */
    public function installOneyOrderStates()
    {
        $oney_order_state = [
            'oney_pg' => [
                'cfg' => null,
                'template' => null,

                // OS have to be "logable" to register transaction_id
                'logable' => false,
                'send_email' => false,
                'paid' => false,
                'module_name' => 'payplug',
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
            ],
        ];

        $flag = true;

        foreach ($oney_order_state as $key => $state) {
            $flag = $flag && $this->createOrderState($key, $state, true) && $this->createOrderState($key, $state,false);
        }
        return $flag;
    }

    /**
     * Install Oney feature
     */
    public function uninstallOney()
    {
        return $this->deleteOneyConfig() && $this->SQLtable->uninstallOneySql();
    }

    /**
     * Delete basic configuration
     *
     * @return bool
     */
    public function deleteOneyConfig()
    {
        return ($this->configurationSpecific->deleteByName('PAYPLUG_ONEY')
            && $this->configurationSpecific->deleteByName('PAYPLUG_ONEY_ALLOWED_COUNTRIES')
            && $this->configurationSpecific->deleteByName('PAYPLUG_ONEY_MAX_AMOUNTS')
            && $this->configurationSpecific->deleteByName('PAYPLUG_ONEY_MIN_AMOUNTS')
            && $this->configurationSpecific->deleteByName('PAYPLUG_ONEY_TOS')
            && $this->configurationSpecific->deleteByName('PAYPLUG_ONEY_TOS_URL'));
    }
}