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

namespace PayPlug\classes;

if (!defined('_PS_VERSION_')) {
    exit;
}

class InstallmentClass
{
    private $dependencies;
    private $query;
    private $constant;

    public function __construct($dependencies)
    {
        $this->dependencies = $dependencies;
        $this->query = $this->dependencies->getPlugin()->getQueryRepository();
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

        if (!isset($installment->schedule)) {
            return false;
        }

        $step_count = count($installment->schedule);
        $step_to_update = [];
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
                $status = $this->dependencies
                    ->getPlugin()
                    ->getPaymentMethodClass()
                    ->getPaymentMethod('standard')
                    ->getPaymentStatus($payment)['id_status'];
            } else {
                if (1 == (int) $installment->is_active) {
                    $status = 6; // ongoing
                } else {
                    $status = 7; // cancelled
                }
            }
            $step = $index . '/' . $step_count;
            $step_to_update[$step] = [
                'pay_id' => $pay_id,
                'status' => (int) $status,
            ];
        }

        $resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $installment->id);

        $schedules = json_decode($resource['schedules'], true);
        foreach ($schedules as &$schedule) {
            $step = $schedule['step'];
            if (array_key_exists($step, $step_to_update)) {
                $schedule['pay_id'] = $step_to_update[$step]['pay_id'];
                $schedule['status'] = $step_to_update[$step]['status'];
            }
        }

        return $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->updateBy('resource_id', $installment->id, [
                'schedules' => json_encode($schedules),
            ]);
    }

    /**
     * @description insert installment payment in the database
     *
     * @param $installment
     *
     * @return bool
     */
    public function addPayplugInstallment($installment)
    {
        if (!is_object($installment)) {
            $installment = $this->dependencies->apiClass->retrieveInstallment($installment);
            if (!$installment['result']) {
                return false;
            }

            $installment = $installment['resource'];
        }

        $resource = $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->getBy('resource_id', $installment->id);

        if (!empty($resource) && !empty($resource['schedules'])) {
            return $this->updatePayplugInstallment($installment);
        }

        if (!isset($installment->schedule)) {
            return false;
        }

        $step_count = count($installment->schedule);
        $index = 0;
        $schedules = [];
        foreach ($installment->schedule as $schedule) {
            ++$index;
            $pay_id = '';
            if (is_array($schedule->payment_ids) && count($schedule->payment_ids) > 0) {
                $pay_id = $schedule->payment_ids[0];
                $payment = $this->dependencies->apiClass->retrievePayment($pay_id);
                if ($payment['result']) {
                    $payment = $payment['resource'];
                    $status = $this->dependencies
                        ->getPlugin()
                        ->getPaymentMethodClass()
                        ->getPaymentMethod('standard')
                        ->getPaymentStatus($payment)['id_status'];
                }
            } else {
                $status = 6;
            }
            $amount = (int) $schedule->amount;
            $step = $index . '/' . $step_count;
            $date = $schedule->date;
            $schedules[] = [
                'id_payment' => $pay_id,
                'step' => $step,
                'amount' => (int) $amount,
                'status' => (int) $status,
                'scheduled_date' => $date,
            ];
        }

        return $this->dependencies
            ->getPlugin()
            ->getPaymentRepository()
            ->updateBy('resource_id', $installment->id, [
                'schedules' => json_encode($schedules),
            ]);
    }
}
