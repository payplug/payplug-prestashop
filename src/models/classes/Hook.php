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

namespace PayPlug\src\models\classes;

use PayPlug\classes\DependenciesClass;

if (!defined('_PS_VERSION_')) {
    exit;
}

class Hook
{
    public $dependencies;
    public $hook_action;
    public $module;

    public function __construct()
    {
        $this->dependencies = new DependenciesClass();
    }

    /**
     * @description Trigger action on order status update
     *
     * @param array $params
     *
     * @return bool
     */
    public function actionObjectOrderHistoryAddAfter($params)
    {
        if (!is_array($params) || empty($params)) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Hook::actionOrderStatusUpdate() - Invalid argument given, $params must be a non null object.', 'critical');

            return false;
        }
        if (!is_object($params['object']) || !$params['object']->id) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Hook::actionOrderStatusUpdate() - Invalid argument given, $params object must be a non null object.', 'critical');

            return false;
        }
        if (!$this->dependencies->configClass->isAllowed()) {
            return true;
        }

        $this->setParameters();

        // Get the order
        $order = $this->dependencies
            ->getPlugin()
            ->getOrder()
            ->get((int) $params['object']->id_order);
        if ($this->dependencies->name != $order->module) {
            $this->dependencies
                ->getPlugin()
                ->getLogger()
                ->addLog('Hook::createPaymentLinkAction() - Create order should be set with payplug to use payment link');

            return true;
        }

        // Check the order state
        $configuration = $this->dependencies
            ->getPlugin()
            ->getConfigurationClass();

        $order_State_id = (int) $params['object']->id_order_state;
        $cart_id = (int) $order->id_cart;

        switch (true) {
            case $order_State_id == $configuration->getValue('order_state_email_link'):
            case $order_State_id == $configuration->getValue('order_state_email_link_test'):
            case $order_State_id == $configuration->getValue('order_state_sms_link'):
            case $order_State_id == $configuration->getValue('order_state_sms_link_test'):
                $hook_exec = (bool) $this->hook_action->createPaymentLinkAction((int) $order_State_id, (int) $cart_id);

                break;

            case $order_State_id == $configuration->getValue('deferred_state'):
                $hook_exec = (bool) $this->hook_action->autoCapturePaymentAction($order);

                break;

            default:
                $hook_exec = true;

                break;
        }

        if (!$hook_exec) {
            $stored_resource = $this->dependencies
                ->getPlugin()
                ->getPaymentRepository()
                ->getBy('id_cart', (int) $cart_id);
            $is_live = !empty($stored_resource)
                ? $stored_resource['is_live']
                : !(bool) $configuration->getValue('sandbox_mode');
            $state_addons = $is_live ? '' : '_test';
            $order_state_error = $configuration->getValue('order_state_error' . $state_addons);
            $hook_exec = $this->dependencies
                ->getPlugin()
                ->getOrderClass()
                ->updateOrderState($order, (int) $order_state_error);
        }

        return $hook_exec;
    }

    protected function setParameters()
    {
        $this->module = $this->module ?: $this->dependencies
            ->getPlugin()
            ->getModule()
            ->getInstanceByName($this->dependencies->name);
        $this->hook_action = $this->hook_action ?: $this->module->getService('payplug.action.hook');
    }
}
