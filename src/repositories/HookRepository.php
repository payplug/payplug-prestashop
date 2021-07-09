<?php
/**
 * 2013 - 2021 PayPlug SAS
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
 * @copyright 2013 - 2021 PayPlug SAS
 * @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

namespace PayPlug\src\repositories;

class HookRepository extends Repository
{
    protected $constant;
    protected $payplug;
    protected $context;
    protected $tools;

    public function __construct($payplug, $constant, $context, $tools)
    {
        $this->payplug = $payplug;
        $this->constant = $constant;
        $this->context = $context;
        $this->tools = $tools;
    }

    public function actionAdminControllerSetMedia()
    {
        $module_url = $this->constant->get('__PS_BASE_URI__') . 'modules/payplug/';

        if ($this->context->getContext()->controller->controller_name == 'AdminOrders') {
            $this->payplug->setMedia([
                $module_url . 'views/css/admin_order.css',
                $module_url . 'views/js/admin_order.js',
            ]);
        } else {
            $this->payplug->setMedia([
                $module_url . 'views/js/admin.js',
                $module_url . 'views/css/admin.css',
            ]);
        }
    }

    /**
     * This is a hook function that allows
     * creating a new type of the order state
     * @param $param
     */
    public function actionObjectOrderStateAddAfter($param)
    {
        $order_state = $param['object'];
        $type = $this->tools->tool('getValue', 'order_state_type');
        $this->payplug->getPlugin()->getOrderState()->saveType((int)$order_state->id, $type);
    }

    /**
     * This is a hook function that allows
     * to update the type of the order state
     * and make a soft delete for prestashop-version >= 1.7
     * @param $param
     */
    public function actionObjectOrderStateUpdateAfter($param)
    {
        $order_state = $param['object'];
        if (isset($order_state->delele) && $order_state->delete)
        {
            return $this->actionObjectOrderStateDeleteAfter($param);
        }
        $type = $this->tools->tool('getValue', 'order_state_type');
        return $this->payplug->getPlugin()->getOrderState()->updateType((int)$order_state->id, $type);
    }

    /**
     * This is a hook function that deletes
     * an order state
     * @param $param
     * @return mixed
     */
    public  function actionObjectOrderStateDeleteAfter($param)
    {
        $order_state = $param['object'];
        return $this->payplug->getPlugin()->getOrderState()->deleteType((int)$order_state->id);
    }

    /**
     * This hook is used to display
     * a select box in the order state page (BO)
     * in order to create/update a type
     * @param $param
     * @return mixed
     */
    public function displayAdminStatusesForm($param)
    {
        $types = [
            'cancel' => $this->l('order_state.type.cancelled'),
            'error' => $this->l('order_state.type.error'),
            'expired' => $this->l('order_state.type.expired'),
            'nothing' => $this->l('order_state.type.nothing'),
            'paid' => $this->l('order_state.type.paid'),
            'pending' => $this->l('order_state.type.pending'),
            'refund' => $this->l('order_state.type.refund'),
        ];

        $this->context->getContext()->smarty->assign('myOptions', $types);
        $this->context->getContext()->smarty->assign('mySelect', 'nothing');
        return $this->payplug->fetchTemplate('order/order_state.tpl');
    }

    public function exe($method = false, $params = [])
    {
        if (!$method
            || !is_string($method)
            || !is_array($params)
            || !method_exists($this, $method)) {
            return false;
        }

        return $this->$method($params);
    }
}
