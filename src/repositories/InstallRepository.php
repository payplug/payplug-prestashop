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

if (!defined('_PS_VERSION_')) {
    exit;
}

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
}
