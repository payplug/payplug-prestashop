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

namespace PayPlug\classes;

class InstallmentClass
{
    private $dependencies;
    private $query;
    private $constant;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->query = $this->dependencies->getPlugin()->getQuery();
        $this->constant = $this->dependencies->getPlugin()->getConstant();
    }

    /**
     * @description update the id_payplug_installment
     *
     * @param $installment
     *
     * @return bool
     */
    public function updatePayplugInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($installment);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        }

        if (isset($installment->schedule)) {
            $step_count = count($installment->schedule);
            $index = 0;
            foreach ($installment->schedule as $schedule) {
                ++$index;
                $pay_id = '';
                if (count($schedule->payment_ids) > 0) {
                    $pay_id = $schedule->payment_ids[0];
                    $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
                    if (!$payment['result']) {
                        return false;
                    }
                    $payment = $payment['resource'];
                    $status = $this->dependencies->paymentClass->getPaymentStatusByPayment($payment);
                } else {
                    if ((int) $installment->is_active == 1) {
                        $status = 6; //ongoing
                    } else {
                        $status = 7; //cancelled
                    }
                }
                $step = $index . '/' . $step_count;

                if ($step2update = $this->getStoredInstallmentTransaction($installment, $step)) {
                    $res_insert_installment = $this->query
                        ->update()
                        ->table($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_installment')
                        ->set('id_payment = "' . $this->query->escape($pay_id) . '"')
                        ->set('status = ' . (int) $status)
                        ->where('id_payplug_installment = ' . (int) $step2update['id_payplug_installment'])
                        ->build()
                    ;

                    if (!$res_insert_installment) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }
    }

    /**
     * @description insert installment payment in the database
     *
     * @param $installment
     * @param $order
     *
     * @return bool
     */
    public function addPayplugInstallment($installment, $order)
    {
        if (!is_object($installment)) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($installment);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        }

        if ($this->getStoredInstallment($installment)) {
            $this->updatePayplugInstallment($installment);
        } else {
            if (isset($installment->schedule)) {
                $step_count = count($installment->schedule);
                $index = 0;
                foreach ($installment->schedule as $schedule) {
                    ++$index;
                    $pay_id = '';
                    if (is_array($schedule->payment_ids) && count($schedule->payment_ids) > 0) {
                        $pay_id = $schedule->payment_ids[0];
                        $status = $this->dependencies->paymentClass->getPaymentStatusByPayment($pay_id);
                    } else {
                        $status = 6;
                    }
                    $amount = (int) $schedule->amount;
                    $step = $index . '/' . $step_count;
                    $date = $schedule->date;
                    $req_insert_installment =
                        $this->query
                            ->insert()
                            ->into($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_installment')
                            ->fields('id_installment')->values($this->query->escape($installment->id))
                            ->fields('id_payment')->values($this->query->escape($pay_id))
                            ->fields('id_order')->values((int) $order->id)
                            ->fields('id_customer')->values((int) $order->id_customer)
                            ->fields('order_total')->values((int) (($order->total_paid * 1000) / 10))
                            ->fields('step')->values($this->query->escape($this->query->escape($step)))
                            ->fields('amount')->values((int) $amount)
                            ->fields('status')->values((int) $status)
                            ->fields('scheduled_date')->values($this->query->escape($date))
                            ->build()
                        ;

                    if (!$req_insert_installment) {
                        return false;
                    }
                }
            }
        }
    }

    /**
     * @description ONLY FOR VALIDATION
     * Retrieve installment stored
     *
     * @param int $id_cart
     *
     * @return int OR bool
     */
    public function getInstallmentByCart($id_cart)
    {
        if (!$id_cart || !is_int($id_cart)) {
            return false;
        }
        $req_installment_cart = $this->query
            ->select()
            ->fields('id_payment')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_payment')
            ->where('id_cart = ' . (int) $id_cart)
            ->where('payment_method = "installment"')
            ->build('unique_value')
        ;

        if (!$req_installment_cart) {
            return false;
        }

        return $req_installment_cart;
    }

    /**
     * @description get the installment payment details
     * related to id_installment
     *
     * @param $installment
     * @param $step
     *
     * @return null|array|bool|object
     */
    private function getStoredInstallmentTransaction($installment, $step)
    {
        if (!is_object($installment)) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($installment);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        }

        $req_installment = $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_installment')
            ->where('id_installment  = "' . $this->query->escape($installment->id) . '"')
            ->where('step = "' . $this->query->escape($step) . '"')
            ->build()
        ;

        if (!$req_installment) {
            return false;
        }

        return $req_installment[0];
    }

    /**
     * @description get the installment payment details
     *
     * @param $installment
     *
     * @throws PrestaShopDatabaseException
     *
     * @return null|array|bool|false|mysqli_result|PDOStatement|resource
     */
    private function getStoredInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($installment);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        }
        $req_installment = $this->query
            ->select()
            ->fields('*')
            ->from($this->constant->get('_DB_PREFIX_') . $this->dependencies->name . '_installment')
            ->where('id_payment = ' . $this->query->escape(($installment->id)))
            ->build()
        ;
        if (!$req_installment) {
            return false;
        }

        return $req_installment;
    }
}
