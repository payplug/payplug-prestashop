<?php

namespace PayPlug\src\utilities\services;

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
