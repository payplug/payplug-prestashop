<?php

namespace PayPlug\src\repositories;

class InstallmentRepository extends Repository
{
    // From classes/PPPaymentInstallment.php
    public function retrieve($id)
    {
        try {
            $payment = \Payplug\InstallmentPlan::retrieve($id);
        } catch (\Payplug\Exception $e) {
            $data = [
                'result' => false,
                'response' => $e->__toString(),
            ];
            return $data;
        }
        return $payment;
    }

    private function populateFromInstallment($installment)
    {
        $this->resource = $installment;
    }

    public function getPaymentList()
    {
        $list = [];
        $index = 0;
        foreach ($this->resource->schedule as $schedule) {
            if (count($schedule->payment_ids) > 0) {
                foreach ($schedule->payment_ids as $pay_id) {
                    $list[$index] = [
                        'pay_id' => $pay_id,
                        'date' => $schedule->date,
                        'amount' => $schedule->amount
                    ];
                    $index ++;
                }
            }
        }
        return $list;
    }

    public function getFirstPayment()
    {
        $payment_list = $this->getPaymentList();
        if (count($payment_list) > 0) {
            $payment = new PPPayment($payment_list[0]['pay_id']);
            return $payment;
        }
    }

    public function isDeferred()
    {
        $payment_list = $this->getPaymentList();
        if (count($payment_list) > 0) {
            $payment = new PPPayment($payment_list[0]['pay_id']);
            return $payment->isDeferred();
        }
        return false;
    }
    // (end from classes/PPPaymentInstallment.php)

    // From payplug.php
    /**
     * @param $installment
     * @param $order
     * @return bool
     * @throws ConfigurationNotSetException
     */
    public function addPayplugInstallment($installment, $order)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }

        if ($this->getStoredInstallment($installment)) {
            $this->updatePayplugInstallment($installment);
        } else {
            if (isset($installment->schedule)) {
                $step_count = count($installment->schedule);
                $index = 0;
                foreach ($installment->schedule as $schedule) {
                    $index++;
                    $pay_id = '';
                    if (is_array($schedule->payment_ids) && count($schedule->payment_ids) > 0) {
                        $pay_id = $schedule->payment_ids[0];
                        $status = $this->getPaymentStatusByPayment($pay_id);
                    } else {
                        $status = 6;
                    }
                    $amount = (int)$schedule->amount;
                    $step = $index . '/' . $step_count;
                    $date = $schedule->date;
                    $req_insert_installment = '
                INSERT INTO `' . _DB_PREFIX_ . 'payplug_installment` (
                    `id_installment`, 
                    `id_payment`, 
                    `id_order`, 
                    `id_customer`, 
                    `order_total`, 
                    `step`, 
                    `amount`, 
                    `status`, 
                    `scheduled_date`
                ) VALUES (
                    \'' . $installment->id . '\', 
                    \'' . $pay_id . '\', 
                    \'' . $order->id . '\', 
                    \'' . $order->id_customer . '\', 
                    \'' . (int)(($order->total_paid * 1000) / 10) . '\', 
                    \'' . $step . '\', 
                    \'' . $amount . '\', 
                    \'' . $status . '\', 
                    \'' . $date . '\'
                )';
                    $res_insert_installment = DB::getInstance()->Execute($req_insert_installment);

                    if (!$res_insert_installment) {
                        return false;
                    }
                }
            }
        }
    }

    /**
     * Delete stored installment
     *
     * @param string $inst_id
     * @param array $cart_id
     * @return bool
     */
    public function deleteInstallment($inst_id, $cart_id)
    {
        $req_installment_cart = '
            DELETE FROM ' . _DB_PREFIX_ . 'payplug_installment_cart  
            WHERE id_cart = ' . (int)$cart_id . ' 
            AND id_installment = \'' . pSQL($inst_id) . '\'';
        $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
        if (!$res_installment_cart) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve installment stored
     *
     * @param int $id_cart
     * @return int OR bool
     */
    public function getInstallmentByCart($id_cart)
    {
        $req_installment_cart = '
            SELECT pic.id_installment 
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic 
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_installment_cart = Db::getInstance()->getValue($req_installment_cart);
        if (!$res_installment_cart) {
            return false;
        }

        return $res_installment_cart;
    }

    /**
     * get cart installment
     *
     * @param $id_cart
     * @return bool
     */
    public function getPayplugInstallmentCart($id_cart)
    {
        $req_cart_installment = '
            SELECT pic.id_installment
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_cart_installment = Db::getInstance()->getValue($req_cart_installment);

        return $res_cart_installment;
    }

    /**
     * @param $installment
     * @return array|bool|false|mysqli_result|PDOStatement|resource|null
     * @throws PrestaShopDatabaseException
     * @throws ConfigurationNotSetException
     */
    public function getStoredInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }
        $req_installment = '
            SELECT pi.*
            FROM `' . _DB_PREFIX_ . 'payplug_installment` pi
            WHERE pi.id_installment = \'' . $installment->id . '\'';
        $res_installment = DB::getInstance()->executeS($req_installment);

        if (!$res_installment) {
            return false;
        } else {
            return $res_installment;
        }
    }

    /**
     * @param $installment
     * @param $step
     * @return array|bool|object|null
     * @throws ConfigurationNotSetException
     */
    public function getStoredInstallmentTransaction($installment, $step)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }
        $req_installment = '
            SELECT pi.*
            FROM `' . _DB_PREFIX_ . 'payplug_installment` pi 
            WHERE pi.id_installment = \'' . $installment->id . '\' 
            AND pi.step = ' . (int)$step;
        $res_installment = DB::getInstance()->getRow($req_installment);

        if (!$res_installment) {
            return false;
        } else {
            return $res_installment;
        }
    }

    /**
     * @param $installment
     * @return bool
     * @throws ConfigurationNotSetException
     *
     */
    public function updatePayplugInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = \Payplug\InstallmentPlan::retrieve($installment);
        }
        if (isset($installment->schedule)) {
            $step_count = count($installment->schedule);
            $index = 0;
            foreach ($installment->schedule as $schedule) {
                $index++;
                $pay_id = '';
                if (count($schedule->payment_ids) > 0) {
                    $pay_id = $schedule->payment_ids[0];
                    $payment = \Payplug\Payment::retrieve($pay_id);
                    $status = $this->getPaymentStatusByPayment($payment);
                } else {
                    if ((int)$installment->is_active == 1) {
                        $status = 6; //ongoing
                    } else {
                        $status = 7; //cancelled
                    }
                }
                $step = $index . '/' . $step_count;
                if ($step2update = $this->getStoredInstallmentTransaction($installment, $step)) {
                    $req_insert_installment = '
                        UPDATE `' . _DB_PREFIX_ . 'payplug_installment` 
                        SET `id_payment` = \'' . pSQL($pay_id) . '\', 
                        `status` = \'' . (int)$status . '\' 
                        WHERE `id_payplug_installment` = ' . (int)$step2update['id_payplug_installment'];
                    $res_insert_installment = DB::getInstance()->Execute($req_insert_installment);

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
     * @description Register installment for later use
     *
     * @param string $installment_id
     * @param int $id_cart
     * @return bool
     */
    public function storeInstallment($installment_id, $id_cart)
    {
        if ($pay_id = $this->getPaymentByCart($id_cart)) {
            $this->deletePayment($pay_id, $id_cart);
        }

        $req_installment_cart_exists = '
            SELECT * 
            FROM ' . _DB_PREFIX_ . 'payplug_installment_cart pic  
            WHERE pic.id_cart = ' . (int)$id_cart;
        $res_installment_cart_exists = Db::getInstance()->getRow($req_installment_cart_exists);
        $date_upd = date('Y-m-d H:i:s');
        $is_pending = 0;
        if (!$res_installment_cart_exists) {
            //insert
            $req_installment_cart = '
                INSERT INTO ' . _DB_PREFIX_ . 'payplug_installment_cart (id_installment, id_cart, is_pending, date_upd)
                VALUES (\'' . pSQL($installment_id) . '\', 
                ' . (int)$id_cart . ', 
                ' . (int)$is_pending . ', 
                \'' . pSQL($date_upd) . '\')';
            $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
            if (!$res_installment_cart) {
                return false;
            }
        } else {
            //update
            $req_installment_cart = '
                UPDATE ' . _DB_PREFIX_ . 'payplug_installment_cart pic  
                SET pic.id_installment = \'' . pSQL($installment_id) . '\', pic.date_upd = \'' . pSQL($date_upd) . '\'
                WHERE pic.id_cart = ' . (int)$id_cart;
            $res_installment_cart = Db::getInstance()->execute($req_installment_cart);
            if (!$res_installment_cart) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retrieve payment informations
     *
     * @param $inst_id
     * @return bool|\Payplug\Resource\InstallmentPlan|null
     */
    public function retrieveInstallment($inst_id)
    {
        try {
            $installment = \Payplug\InstallmentPlan::retrieve($inst_id);
        } catch (Exception $e) {
            return false;
        }
        return $installment;
    }
}