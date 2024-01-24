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
 *  @author    Payplug SAS
 *  @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */
/**
 * @since 1.6.0
 */
require_once _PS_MODULE_DIR_ . 'payplug/payplug.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

include_once _PS_MODULE_DIR_ . 'payplug/classes/DependenciesClass.php';

class AdminPayPlugInstallmentController extends ModuleAdminController
{
    private $dependencies;
    private $orderClass;

    public function __construct()
    {
        $this->dependencies = new PayPlug\classes\DependenciesClass();
        $this->orderClass = $this->dependencies->orderClass;

        $this->bootstrap = true;
        $this->table = $this->dependencies->name . '_payment';
        $this->lang = false;
        $this->addRowAction('view');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_use_found_rows = true;

        parent::__construct();

        $this->fields_list = [
            'id_payplug_payment' => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'resource_id' => [
                'title' => $this->l('Installment ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'id_payment' => [
                'title' => $this->l('Payment ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'reference' => [
                'title' => $this->l('Order reference'),
            ],
            'customer' => [
                'title' => $this->l('Customer'),
                'havingFilter' => true,
            ],
            'order_total' => [
                'title' => $this->l('Order total'),
                'type' => 'price',
                'currency' => true,
            ],
            'step' => [
                'title' => $this->l('Installment payment #'),
            ],
            'amount' => [
                'title' => $this->l('Installment amount'),
                'type' => 'price',
                'currency' => true,
            ],
            'status' => [
                'title' => $this->l('PayPlug payment status'),
            ],
            'scheduled_date' => [
                'title' => $this->l('Date'),
                'type' => 'datetime',
            ],
        ];
    }

    public function getPaymentStatusById($id_status)
    {
        return $this->dependencies->paymentClass->getPaymentStatusById($id_status);
    }

    // Impossible to write this function in camelCase, Presta 1.6 & 1.7 need it as is
    public function viewPayplugInstallment()
    {
        $id_payplug_payment = (int) Tools::getValue('id_payplug_payment');
        $payment = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getById((int) $id_payplug_payment);

        $orders = $this->dependencies
            ->getPlugin()
            ->getOrderRepository()
            ->getByIdCart((int) $payment['id_cart']);

        if (empty($orders)) {
            Tools::redirectAdmin(self::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminPayPlugInstallment'));
        }
        $order = reset($orders);

        Tools::redirectAdmin(
            $this->context->link->getAdminLink('AdminOrders', true, [], [
                'id_order' => $order['id_order'],
                'vieworder' => true,
                'token' => Tools::getAdminTokenLite('AdminOrders'),
            ])
        );
    }

    public function postProcess()
    {
        if (Tools::isSubmit('viewpayplug_payment')) {
            $this->viewPayplugInstallment();
        }

        return parent::postProcess();
    }

    public function initToolbar()
    {
        if ($this->allow_export) {
            $this->toolbar_btn['export'] = [
                'href' => self::$currentIndex . '&export' . $this->table . '&token=' . $this->token,
                'desc' => $this->l('Export'),
            ];
        }
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }

    public function getList(
        $id_lang,
        $order_by = null,
        $order_way = null,
        $start = 0,
        $limit = null,
        $id_lang_shop = false
    ) {
        $payments = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getAllByMethod('installment', true);

        $this->_list = [];

        if (empty($payments)) {
            return false;
        }

        foreach ($payments as $payment) {
            if (!isset($payment['schedules']) || !$payment['schedules']) {
                continue;
            }
            $schedules = json_decode($payment['schedules'], true);
            $orders = $this->dependencies
                ->getPlugin()
                ->getOrderRepository()
                ->getByIdCart((int) $payment['id_cart']);
            $order = reset($orders);

            $customer = $this->dependencies
                ->getPlugin()
                ->getCustomer()
                ->get((int) $order['id_customer']);

            $amount = $this->dependencies
                ->getPlugin()
                ->getCart()
                ->get((int) $payment['id_cart'])
                ->getOrderTotal(true);

            foreach ($schedules as $schedule) {
                $this->_list[] = [
                    'id_payplug_payment' => $payment['id_payplug_payment'],
                    'resource_id' => $payment['resource_id'],
                    'id_payment' => $schedule['id_payment'] ?: 'N/A',
                    'reference' => $order['reference'],
                    'customer' => substr($customer->firstname, 0, 1) . ' ' . $customer->lastname,
                    'order_total' => $amount,
                    'step' => $schedule['step'],
                    'amount' => $this->dependencies
                        ->getHelpers()['amount']
                        ->convertAmount($schedule['amount'], true),
                    'status' => $this->getPaymentStatusById($schedule['status']),
                    'scheduled_date' => $schedule['scheduled_date'],
                ];
            }
        }

        $this->filterPayments();
        $this->sortPayments();
    }

    private function filterPayments()
    {
        if (!$this->_filter) {
            return false;
        }

        $filters = explode('AND ', $this->_filter);
        $wheres = [];
        foreach ($filters as $k => &$filter) {
            $filter = trim($filter);
            if ($filter) {
                preg_match_all('/\\`(.*?)\\`/', $filter, $key);
                $key = reset($key[1]);
                preg_match_all("/\\'\\%(.*?)\\%\\'/", $filter, $value);
                $value = reset($value[1]);
                $wheres[$key] = strtolower($value);
            }
        }

        if (empty($wheres)) {
            return false;
        }

        foreach ($wheres as $key => $value) {
            foreach ($this->_list as $k => $payment) {
                $value_to_check = strtolower($payment[$key]);
                if (false === strpos($value_to_check, $value)) {
                    unset($this->_list[$k]);
                }
            }
        }
    }

    private function sortPayments()
    {
        $order_by = Tools::getValue('payplug_paymentOrderby');
        $order_way = Tools::getValue('payplug_paymentOrderway');

        if (!$order_by || !$order_way) {
            return false;
        }

        $payments = [];
        foreach ($this->_list as $k => $payment) {
            $payments[$payment[$order_by] . '_' . $k] = $payment;
        }

        if ('asc' == $order_way) {
            ksort($payments);
        } else {
            krsort($payments);
        }
        $this->_list = $payments;
    }
}
