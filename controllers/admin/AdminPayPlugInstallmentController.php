<?php
/**
 * 2013 - 2020 PayPlug SAS
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
 *  @copyright 2013 - 2020 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

/**
 * @since 1.6.0
 */

require_once(_PS_MODULE_DIR_.'payplug/payplug.php');

class AdminPayPlugInstallmentController extends ModuleAdminController
{
    private $payplug;

    public function __construct()
    {
        $this->payplug = new Payplug();
        $this->bootstrap = true;
        $this->table = 'payplug_installment';
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
            LEFT JOIN `'._DB_PREFIX_.'customer` c ON (c.`id_customer` = a.`id_customer`) 
            LEFT JOIN `'._DB_PREFIX_.'orders` o ON (o.`id_order` = a.`id_order`)';
        $this->_orderBy = 'id_payplug_installment';
        $this->_orderWay = 'DESC';
        $this->_use_found_rows = true;

        parent::__construct();

        $this->fields_list = array(
            'id_payplug_installment' => array(
                'title' => $this->l('ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs',
            ),
            'id_installment' => array(
                'title' => $this->l('Installment ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'id_payment' => array(
                'title' => $this->l('Payment ID'),
                'align' => 'text-center',
                'class' => 'fixed-width-xs'
            ),
            'reference' => array(
                'title' => $this->l('Order reference'),
            ),
            'customer' => array(
                'title' => $this->l('Customer'),
                'havingFilter' => true,
            ),
            'order_total' => array(
                'title' => $this->l('Order total'),
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency',
            ),
            'step' => array(
                'title' => $this->l('Installment payment #'),
            ),
            'amount' => array(
                'title' => $this->l('Installment amount'),
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency',
            ),
            'status' => array(
                'title' => $this->l('PayPlug payment status'),
                'callback' => 'getPaymentStatusById',
                'type' => 'select',
                'list' => $this->payplug->payment_status,
                'filter_key' => 'a!status',
                'filter_type' => 'int',
            ),
            'scheduled_date' => array(
                'title' => $this->l('Date'),
                'type' => 'datetime',
            ),
        );
    }

    public function getPaymentStatusById($id_status)
    {
        $id_lang = $this->context->language->id;
        return $this->payplug->getPaymentStatusById($id_status, $id_lang);
    }

    public static function setOrderCurrency($amount, $tr)
    {
        $order = new Order($tr['id_order']);
        return Tools::displayPrice(($amount / 100), (int)$order->id_currency);
    }

    public function viewpayplug_installment()
    {
        $id_payplug_installment = (int)(Tools::getValue('id_payplug_installment'));
        $id_order = $this->getOrderIdByPayplugInstallmentId($id_payplug_installment);
        Tools::redirectAdmin('index.php?tab=AdminOrders&id_order='.$id_order.'&vieworder&token='.Tools::getAdminTokenLite('AdminOrders'));
    }

    public function getOrderIdByPayplugInstallmentId($id_payplug_installment)
    {
        $req_order = '
            SELECT DISTINCT pi.id_order
            FROM `'._DB_PREFIX_.'payplug_installment` pi 
            WHERE pi.id_payplug_installment = '.pSQL($id_payplug_installment);
        $res_order = DB::getInstance()->getValue($req_order);

        if (!$res_order) {
            return false;
        } else {
            return (int)$res_order;
        }
    }
    public function postProcess()
    {
        if (Tools::isSubmit('viewpayplug_installment')) {
            $this->viewpayplug_installment();
        }
        return parent::postProcess();
    }

    public function initToolbar()
    {
        if ($this->allow_export) {
            $this->toolbar_btn['export'] = array(
                'href' => self::$currentIndex.'&export'.$this->table.'&token='.$this->token,
                'desc' => $this->l('Export')
            );
        }
        parent::initToolbar();
        unset($this->toolbar_btn['new']);
    }
}
