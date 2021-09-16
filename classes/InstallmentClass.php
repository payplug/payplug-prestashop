<?php
/**
 * 2013 - 2021 PayPlug SAS.
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

namespace PayPlug\classes;

use Db;
use Exception;
use Payplug\Exception\ConfigurationNotSetException;
use Payplug\InstallmentPlan;
use Payplug\Payment;

class InstallmentClass extends \PaymentModule
{
    /**
     * Retrieve payment informations.
     *
     * @param $inst_id
     *
     * @return null|bool|\Payplug\Resource\InstallmentPlan
     */
    public static function retrieveInstallment($inst_id)
    {
        try {
            return InstallmentPlan::retrieve($inst_id);
        } catch (Exception $e) {
            // add logger
            return false;
        }
    }

    /**
     * @param $installment
     *
     * @return bool
     */
    public static function updatePayplugInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = InstallmentPlan::retrieve($installment);
        }
        if (isset($installment->schedule)) {
            $step_count = count($installment->schedule);
            $index = 0;
            foreach ($installment->schedule as $schedule) {
                ++$index;
                $pay_id = '';
                if (count($schedule->payment_ids) > 0) {
                    $pay_id = $schedule->payment_ids[0];
                    $payment = Payment::retrieve($pay_id);
                    $status = PayPlugClass::getPaymentStatusByPayment($payment);
                } else {
                    if (1 == (int) $installment->is_active) {
                        $status = 6; //ongoing
                    } else {
                        $status = 7; //cancelled
                    }
                }
                $step = $index.'/'.$step_count;

                if ($step2update = self::getStoredInstallmentTransaction($installment, $step)) {
                    $req_insert_installment = '
                        UPDATE `'._DB_PREFIX_.'payplug_installment` 
                        SET `id_payment` = \''.pSQL($pay_id).'\', 
                        `status` = \''.(int) $status.'\' 
                        WHERE `id_payplug_installment` = '.(int) $step2update['id_payplug_installment'];
                    $res_insert_installment = Db::getInstance()->Execute($req_insert_installment);

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
     * @param $installment
     * @param $order
     *
     * @throws ConfigurationNotSetException
     *
     * @return bool
     */
    public static function addPayplugInstallment($installment, $order)
    {
        if (!is_object($installment)) {
            $installment = InstallmentPlan::retrieve($installment);
        }

        if (self::getStoredInstallment($installment)) {
            self::updatePayplugInstallment($installment);
        } else {
            if (isset($installment->schedule)) {
                $step_count = count($installment->schedule);
                $index = 0;
                foreach ($installment->schedule as $schedule) {
                    ++$index;
                    $pay_id = '';
                    if (is_array($schedule->payment_ids) && count($schedule->payment_ids) > 0) {
                        $pay_id = $schedule->payment_ids[0];
                        $status = PayPlugClass::getPaymentStatusByPayment($pay_id);
                    } else {
                        $status = 6;
                    }
                    $amount = (int) $schedule->amount;
                    $step = $index.'/'.$step_count;
                    $date = $schedule->date;
                    $req_insert_installment = '
                INSERT INTO `'._DB_PREFIX_.'payplug_installment` (
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
                    \''.$installment->id.'\', 
                    \''.$pay_id.'\', 
                    \''.$order->id.'\', 
                    \''.$order->id_customer.'\', 
                    \''.(int) (($order->total_paid * 1000) / 10).'\', 
                    \''.$step.'\', 
                    \''.$amount.'\', 
                    \''.$status.'\', 
                    \''.$date.'\'
                )';

                    $res_insert_installment = Db::getInstance()->Execute($req_insert_installment);

                    if (!$res_insert_installment) {
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
    public static function getInstallmentByCart($id_cart)
    {
        $req_installment_cart = '
            SELECT pic.id_payment 
            FROM '._DB_PREFIX_.'payplug_payment pic 
            WHERE pic.id_cart = '.(int) $id_cart.' AND pic.payment_method = \'installment\'';
        $res_installment_cart = Db::getInstance()->getValue($req_installment_cart);
        if (!$res_installment_cart) {
            return false;
        }

        return $res_installment_cart;
    }

    /**
     * @param $installment
     * @param $step
     *
     * @return null|array|bool|object
     */
    private static function getStoredInstallmentTransaction($installment, $step)
    {
        if (!is_object($installment)) {
            $installment = InstallmentPlan::retrieve($installment);
        }
        $req_installment = '
            SELECT pi.*
            FROM `'._DB_PREFIX_.'payplug_installment` pi 
            WHERE pi.id_installment = \''.$installment->id.'\' 
            AND pi.step = '.(int) $step;
        $res_installment = Db::getInstance()->getRow($req_installment);

        if (!$res_installment) {
            return false;
        }

        return $res_installment;
    }

    /**
     * @param $installment
     *
     * @throws PrestaShopDatabaseException
     *
     * @return null|array|bool|false|mysqli_result|PDOStatement|resource
     */
    private static function getStoredInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = InstallmentPlan::retrieve($installment);
        }
        $req_installment = '
            SELECT pi.*
            FROM `'._DB_PREFIX_.'payplug_installment` pi
            WHERE pi.id_payment = \''.$installment->id.'\'';
        $res_installment = Db::getInstance()->executeS($req_installment);

        if (!$res_installment) {
            return false;
        }

        return $res_installment;
    }
}
