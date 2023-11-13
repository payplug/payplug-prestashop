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
     * @description This is a function that allows creating a new type of the order state
     *
     * @param $param
     *
     * @return bool
     */
    public function addTypeAction($param)
    {
        if (!is_array($param) || !$param) {
            // todo: add log
            return false;
        }

        $order_state = $param['object'];
        if (!is_int($order_state->id) || !$order_state->id) {
            // todo: add log
            return false;
        }

        $tools = $this->dependencies->getPlugin()->getTools();
        $type = $tools->tool('getValue', 'order_state_type');
        if (!is_string($type) || !$type) {
            // todo: add log
            return false;
        }

        return (bool) $this->dependencies
            ->getPlugin()
            ->getPayplugOrderStateRepository()
            ->setOrderState($order_state->id, $type);
    }

    /**
     * @description This is a function that allows to update the type of the order state
     *
     * @param $param
     *
     * @return bool
     */
    public function updateTypeAction($param)
    {
        if (!is_array($param) || !$param) {
            // todo: add log
            return false;
        }

        $order_state = $param['object'];
        if (!is_int($order_state->id) || !$order_state->id) {
            // todo: add log
            return false;
        }

        $tools = $this->dependencies->getPlugin()->getTools();
        $type = $tools->tool('getValue', 'order_state_type');
        if (!is_string($type) || !$type) {
            // todo: add log
            return false;
        }

        if (isset($order_state->deleted) && $order_state->deleted) {
            return $this->deleteTypeAction($param);
        }

        $payplug_order_state = $this->dependencies
            ->getPlugin()
            ->getPayplugOrderStateRepository()
            ->getTypeByIdOrderState($order_state->id);

        if (empty($payplug_order_state)) {
            $result = $this->addTypeAction($param);
        } else {
            $result = (bool) $this->dependencies
                ->getPlugin()
                ->getPayplugOrderStateRepository()
                ->updateByOderState($order_state->id, $type);
        }

        return $result;
    }

    /**
     * @description This is a function that deletes an order state
     *
     * @param $param
     *
     * @return bool
     */
    public function deleteTypeAction($param)
    {
        if (!is_array($param) || !$param) {
            // todo: add log
            return false;
        }

        $order_state = $param['object'];
        if (!is_int($order_state->id) || !$order_state->id) {
            // todo: add log
            return false;
        }

        return (bool) $this->dependencies
            ->getPlugin()
            ->getPayplugOrderStateRepository()
            ->removeByIdOrderState($order_state->id);
    }

    /**
     * @description Display a form in the admin statuses template
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
        $current_order_state_type = $this->dependencies
            ->getPlugin()
            ->getPayplugOrderStateRepository()
            ->getTypeByIdOrderState((int) $id_order_state);

        $context = $this->dependencies->getPlugin()->getContext()->get();
        $payplug_order_state_url = $this->dependencies
            ->getPlugin()
            ->getRoutes()
            ->getExternalUrl($context->getContext()->language->iso_code)['order_state'];

        $context->getContext()->smarty->assign([
            'payplug_order_state_url' => $payplug_order_state_url,
            'current_order_state_type' => $current_order_state_type,
            'order_state_types' => $types,
        ]);

        return $this->dependencies->configClass->fetchTemplate('order_state/type.tpl');
    }
}
