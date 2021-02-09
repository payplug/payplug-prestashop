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

class PayPlugPaymentInstallment extends PayplugPayment
{

    /** @var int */
    public $nb_installment;

    /** @var int */
    public $min_amount;

    /** @var int */
    public $amount;

    /** @var array */
    protected $definition_schedules = [
        'type' => 'iterable',
        'field' => [
            'date' => ['type' => 'date', 'validate' => 'isDate', 'copy_post' => false],
            'amount' => ['type' => 'int', 'validate' => 'isInt', 'required' => true],
        ],
        'required' => true
    ];

    /**
     * Constructor
     *
     * @param string $id_card
     * @return PayplugPayment
     */
    public function __construct($id_card = null, $options = [])
    {
        parent::__construct($id_card, $options);

        $this->definition_tab['schedules'] = $this->definition_schedules;

        $this->nb_installment = $this->module->getConfiguration('PAYPLUG_INST_MODE');
        $this->min_amount = $this->module->getConfiguration('PAYPLUG_INST_MIN_AMOUNT');
        $this->is_allowed = $this->module->getConfiguration('PAYPLUG_INST');
        $this->type = 'installment';

        $this->generatePaymentTab();
        $this->validatePaymentTab();

        return $this;
    }

    /**
     * Create payment from PayPlug lib
     *
     * @return array
     */
    public function create()
    {
        if (!$this->is_valid) {
            // todo: add log failure create
            return [
                'resource' => null,
                'error' => true,
                'message' => 'Cannot create payment, invalid payment method',
            ];
        }

        $this->register();

        if ($this->debug) {
            $log = new MyLogPHP(_PS_MODULE_DIR_ . '/payplug/log/prepare_payment.csv');
            $log->info('Starting installment.');
        }

        try {
            $installment = \Payplug\InstallmentPlan::create($this->payment_tab);
            return [
                'resource' => $installment,
                'error' => false,
                'message' => null,
            ];
        } catch (Exception $e) {
            return [
                'resource' => null,
                'error' => true,
                'message' => $e->__toString(),
            ];
        }
    }

    /**
     * Get payment from PayPlug lib
     *
     * @param string $pay_id
     * @return PayplugPayment $installment
     */
    public function get($inst_id)
    {
        try {
            $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
        } catch (Exception $e) {
            // todo: add log
            $installment = false;
        }

        return $installment;
    }

    /**
     * Generate the tab to create the installment in Payplug API
     *
     * @return bool
     */
    public function generatePaymentTab()
    {
        parent::generatePaymentTab();

        $this->amount = $this->getCartAmount($this->payment_tab['currency']);
        $this->payment_tab['schedule'] = $this->getSchedulesFromAmount();
    }

    /**
     * Generate the tab to create the installment in Payplug API
     *
     * @param int $amount
     * @return array|false
     */
    public function getSchedulesFromAmount()
    {
        if ($this->amount < $this->min_amount) {
            return false;
        }

        $schedule = [];
        for ($i = 0; $i < $this->nb_installment; $i++) {
            if ($i == 0) {
                $schedule[$i]['date'] = 'TODAY';
                $int_part = (int)($this->amount / $this->nb_installment);
                if ($this->is_deferred) {
                    $schedule[$i]['authorized_amount'] =
                        (int)($int_part + ($this->amount - ($int_part * $this->nb_installment)));
                } else {
                    $schedule[$i]['amount'] = (int)($int_part + ($this->amount - ($int_part * $this->nb_installment)));
                }
            } else {
                $delay = $i * 30;
                $schedule[$i]['date'] = date('Y-m-d', strtotime("+ $delay days"));
                $schedule[$i]['amount'] = (int)($this->amount / $this->nb_installment);
            }
        }

        return $schedule;
    }

    /**
     * Register installment for later use
     *
     * @param string $inst_id
     * @return bool
     */
    public function register($inst_id = 'pending')
    {
        if ($pay_id = $this->getPaymentCart()) {
            $this->deletePaymentCart($pay_id);
        }

        $exists = Db::getInstance()->getRow('
            SELECT * FROM ' . _DB_PREFIX_ . 'payplug_installment_cart 
            WHERE `id_cart` = ' . (int)$this->cart->id);
        $date_upd = date('Y-m-d H:i:s');

        if (!$exists) {
            //insert
            $sql = '
                INSERT INTO ' . _DB_PREFIX_ . 'payplug_installment_cart (id_installment, id_cart, is_pending, date_upd)
                VALUES (\'' . pSQL($pay_id) . '\', ' . (int)$this->cart->id . ', 0, \'' . pSQL($date_upd) . '\')';
        } else {
            //update
            $sql = 'UPDATE ' . _DB_PREFIX_ . 'payplug_installment_cart pic  
                    SET pic.id_installment = \'' . pSQL($inst_id) . '\', pic.date_upd = \'' . pSQL($date_upd) . '\'
                    WHERE pic.id_cart = ' . (int)$this->cart->id;
        }

        return (bool)Db::getInstance()->execute($sql);
    }
}
