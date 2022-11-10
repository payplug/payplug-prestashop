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
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */
/**
 * @since 1.6.0
 */
require_once _PS_MODULE_DIR_ . 'payplug/payplug.php';

include_once _PS_MODULE_DIR_ . 'payplug/classes/DependenciesClass.php';

class AdminPayPlugInstallmentController extends ModuleAdminController
{
    private $dependencies;

    public function __construct()
    {
        $this->dependencies = new \PayPlug\classes\DependenciesClass();
        $this->bootstrap = true;
        $this->table = $this->dependencies->name . '_installment';
        $this->id = 'id_payplug_installment';
        $this->lang = false;
        $this->addRowAction('view');
        $this->explicitSelect = true;
        $this->allow_export = true;
        $this->deleted = false;
        $this->context = Context::getContext();

        $this->_select = '
            a.id_order AS `id_order`,
            CONCAT(LEFT(c.`firstname`, 1), \'. \', c.`lastname`) AS `customer`,
            o.reference AS `reference`
        ';
        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.`id_customer` = a.`id_customer`) 
            LEFT JOIN `' . _DB_PREFIX_ . 'orders` o ON (o.`id_order` = a.`id_order`)';
        $this->_orderBy = 'id_payplug_installment';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        parent::__construct();

        $this->fields_list = [
            'id_payplug_installment' => [
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ],
            'id_installment' => [
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
                'callback' => 'setOrderCurrency',
            ],
            'step' => [
                'title' => $this->l('Installment payment #'),
            ],
            'amount' => [
                'title' => $this->l('Installment amount'),
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency',
            ],
            'status' => [
                'title' => $this->l('PayPlug payment status'),
                'callback' => 'getPaymentStatusById',
                'type' => 'select',
                'list' => $this->dependencies->configClass->getPaymentStatus(),
                'filter_key' => 'a!status',
                'filter_type' => 'int',
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

    public static function setOrderCurrency($amount, $tr)
    {
        $order = new Order($tr['id_order']);

        return Tools::displayPrice(($amount / 100), (int) $order->id_currency);
    }

    // Impossible to write this function in camelCase, Presta 1.6 & 1.7 need it as is
    public function viewPayplugInstallment()
    {
        $id_payplug_installment = (int) (Tools::getValue('id_payplug_installment'));
        $id_order = $this->getOrderIdByPayplugInstallmentId($id_payplug_installment);
        Tools::redirectAdmin(
            'index.php?tab=AdminOrders&id_order=' . $id_order . '&vieworder&token=' .
            Tools::getAdminTokenLite('AdminOrders')
        );
    }

    public function getOrderIdByPayplugInstallmentId($id_payplug_installment)
    {
        $sql = 'SELECT DISTINCT id_order
                FROM `' . _DB_PREFIX_ . $this->table . '`
                WHERE `id_' . $this->dependencies->name . '_installment` = ' . (int) $id_payplug_installment;

        return Db::getInstance()->getValue($sql);
    }

    public function postProcess()
    {
        if (Tools::isSubmit('viewpayplug_installment')) {
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
}
