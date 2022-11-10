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

use PayPlug\src\application\dependencies\BaseClass;

class HookRepository extends BaseClass
{
    protected $constant;
    protected $dependencies;
    protected $context;
    protected $tools;

    private $mediaClass;

    public function __construct($dependencies, $constant, $context, $tools)
    {
        $this->dependencies = $dependencies;
        $this->constant = $constant;
        $this->context = $context;
        $this->tools = $tools;
    }

    /**
     * @description This is a hook function that allows
     * creating a new type of the order state
     *
     * @param $param
     */
    public function actionObjectOrderStateAddAfter($param)
    {
        $order_state = $param['object'];
        $type = $this->tools->tool('getValue', 'order_state_type');

        return $this->dependencies->getPlugin()->getOrderState()->saveType((int) $order_state->id, $type);
    }

    /**
     * @description This is a hook function that allows
     * to update the type of the order state
     *
     * @param $param
     */
    public function actionObjectOrderStateUpdateAfter($param)
    {
        $order_state = $param['object'];
        if (isset($order_state->deleted) && $order_state->deleted) {
            return $this->actionObjectOrderStateDeleteAfter($param);
        }

        return $this->actionObjectOrderStateAddAfter($param);
    }

    /**
     * @description This is a hook function that deletes
     * an order state
     *
     * @param $param
     */
    public function actionObjectOrderStateDeleteAfter($param)
    {
        $order_state = $param['object'];

        return $this->dependencies->getPlugin()->getOrderState()->deleteType((int) $order_state->id);
    }

    /**
     * @description This hook is used to display
     * a select box in the order state page (BO)
     * in order to create/update a type
     *
     * @param $param
     *
     * @return mixed
     */
    public function displayAdminStatusesForm()
    {
        $types = [
            'undefined' => $this->dependencies->l('hook.displayAdminStatusesForm.undefined', 'hookrepository'),
            'nothing' => $this->dependencies->l('hook.displayAdminStatusesForm.orderStateTypeNothing', 'hookrepository'),
            'cancelled' => $this->dependencies->l('hook.displayAdminStatusesForm.orderStateTypeCancelled', 'hookrepository'),
            'error' => $this->dependencies->l('hook.displayAdminStatusesForm.orderStateTypeError', 'hookrepository'),
            'expired' => $this->dependencies->l('hook.displayAdminStatusesForm.orderStateTypeExpired', 'hookrepository'),
            'paid' => $this->dependencies->l('hook.displayAdminStatusesForm.orderStateTypePaid', 'hookrepository'),
            'pending' => $this->dependencies->l('hook.displayAdminStatusesForm.orderStateTypePending', 'hookrepository'),
            'refund' => $this->dependencies->l('hook.displayAdminStatusesForm.orderStateTypeRefund', 'hookrepository'),
        ];

        $id_order_state = $this->tools->tool('getValue', 'id_order_state');
        $current_order_state_type = $this->dependencies->getPlugin()->getOrderState()->getType((int) $id_order_state);
        $payplug_order_state_url = 'https://support.payplug.com/hc/'
            . $this->context->getContext()->language->iso_code
            . '/articles/4406805105298';
        $this->context->getContext()->smarty->assign([
            'payplug_order_state_url' => $payplug_order_state_url,
            'current_order_state_type' => $current_order_state_type,
            'order_state_types' => $types,
        ]);

        return $this->dependencies->configClass->fetchTemplate('order_state/type.tpl');
    }

    public function exe($method = false, $params = [])
    {
        if (!$method
            || !is_string($method)
            || !is_array($params)
            || !method_exists($this, $method)) {
            return false;
        }

        return $this->{$method}($params);
    }
}
