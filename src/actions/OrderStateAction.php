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

namespace PayPlug\src\actions;

if (!defined('_PS_VERSION_')) {
    exit;
}

class OrderStateAction
{
    private $dependencies;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
    }

    /**
     * @description This is a function that allows to update the type of the order state
     *
     * todo: add coverage to this method
     *
     * @param $param
     * @param mixed $id_order_state
     * @param mixed $type
     *
     * @return bool
     */
    public function saveTypeAction($id_order_state = 0, $type = '')
    {
        if (!is_int($id_order_state) || !$id_order_state) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('OrderStateAction::saveTypeAction() - Invalid argument given, $id_order_state must be a non null integer.');

            return false;
        }

        if (!is_string($type) || !$type) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('OrderStateAction::saveTypeAction() - Invalid argument given, $type must be a non empty string.');

            return false;
        }

        $order_state = $this->dependencies
            ->getPlugin()
            ->getOrderStateAdapter()
            ->get((int) $id_order_state);

        if (!$this->dependencies
            ->getPlugin()
            ->getValidate()
            ->validate('isLoadedObject', $order_state)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('OrderStateAction::saveTypeAction() - Retrieve OrderState object is not valid');

            return false;
        }

        if (isset($order_state->deleted) && $order_state->deleted) {
            return $this->deleteTypeAction((int) $id_order_state);
        }

        $order_state = $this->dependencies
            ->getPlugin()
            ->getStateRepository()
            ->getBy('id_order_state', (int) $id_order_state);

        $current_date = date('Y-m-d H:i:s');
        if (empty($order_state)) {
            $fields = [
                'id_order_state' => (int) $id_order_state,
                'type' => $type,
                'date_add' => $current_date,
                'date_upd' => $current_date,
            ];
            $result = (bool) $this->dependencies
                ->getPlugin()
                ->getStateRepository()
                ->createEntity($fields);
        } else {
            $result = (bool) $this->dependencies
                ->getPlugin()
                ->getStateRepository()
                ->updateEntity((int) $order_state['id_payplug_order_state'], [
                    'type' => $type,
                    'date_upd' => $current_date,
                ]);
        }

        return $result;
    }

    /**
     * @description This is a function that deletes an order state
     *
     * @param $param
     * @param mixed $id_order_state
     *
     * @return bool
     */
    public function deleteTypeAction($id_order_state = 0)
    {
        if (!is_int($id_order_state) || !$id_order_state) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('OrderStateAction::deleteTypeAction() - Invalid argument given, $id_order_state must be a non null integer.');

            return false;
        }

        return (bool) $this->dependencies
            ->getPlugin()
            ->getStateRepository()
            ->deleteBy('id_order_state', (int) $id_order_state);
    }

    /**
     * @description Display a form in the admin statuses template
     *
     * todo: add coverage to this method
     *
     * @param $param
     *
     * @return string
     */
    public function renderOption()
    {
        $translations = $this->dependencies->getPlugin()->getTranslationClass();
        $types = $translations
            ->getOrderStateActionRenderTranslations();

        $tools = $this->dependencies->getPlugin()->getTools();
        $id_order_state = $tools->tool('getValue', 'id_order_state');

        $order_state = $this->dependencies
            ->getPlugin()
            ->getStateRepository()
            ->getBy('id_order_state', (int) $id_order_state);

        $context = $this->dependencies->getPlugin()->getContext()->get();
        $payplug_order_state_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($context->getContext()->language->iso_code)['order_state'];

        $context->getContext()->smarty->assign([
            'payplug_order_state_url' => $payplug_order_state_url,
            'current_order_state_type' => $order_state['type'],
            'order_state_types' => $types,
        ]);

        return $this->dependencies->configClass->fetchTemplate('order_state/type.tpl');
    }

    /**
     * @description Process order state type installation
     *
     * todo: add coverage to this method
     *
     * @param $param
     *
     * @return string
     */
    public function installTypeAction()
    {
        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();

        foreach ($configuration->order_states as $key => $state) {
            // live status
            $live_key = 'order_state_' . $key;
            $id_order_state_live = $configuration->getValue($live_key);
            if ($id_order_state_live) {
                $this->saveTypeAction((int) $id_order_state_live, $state['type']);
            }

            // sandbox status
            $sandbox_key = 'order_state_' . $key . '_test';
            $id_order_state_sandbox = $configuration->getValue($sandbox_key);
            if ($id_order_state_sandbox) {
                $this->saveTypeAction((int) $id_order_state_sandbox, $state['type']);
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
        foreach ($prestashop_order_states as $config_key => $type) {
            $id_order_state = $configuration->getValue($config_key);
            if ($id_order_state) {
                $this->saveTypeAction((int) $id_order_state, $type);
            }
        }

        return true;
    }
}
