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

namespace PayPlug\src\utilities\services;

if (!defined('_PS_VERSION_')) {
    exit;
}

use PayPlugPluginMcp\Actions\PaymentAction;
use PayPlugPluginMcp\Models\Entities\PaymentInputDTO;
use PayPlugPluginMcp\Models\Entities\RefundInputDTO;

class Core
{
    /**
     * @description create a payment resource using the core of the plugin and return the response
     *
     * @return array
     */
    public function createCorePayment(PaymentInputDTO $payment_input)
    {
        try {
            $payment_object = $this
                ->getPaymentAction()
                ->createAction($payment_input);

            return [
                'result' => true,
                'code' => 200,
                'resource' => $payment_object->getResource(),
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @description create a refund resource using the core of the plugin and return the response
     *
     * @return array
     */
    public function createCoreRefund(RefundInputDTO $refund_input)
    {
        try {
            $payment_object = $this
                ->getPaymentAction()
                ->refundAction($refund_input);

            return [
                'result' => true,
                'code' => 200,
                'resource' => $payment_object->getResource(),
            ];
        } catch (\Exception $e) {
            return [
                'result' => false,
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
            ];
        }
    }

    private function getPaymentAction(): PaymentAction
    {
        return new PaymentAction();
    }
}
