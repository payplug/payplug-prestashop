<?php

namespace PayPlug\src\utilities\services;

use PayplugPluginCore\Models\Entities\PaymentInputDTO;

class Core
{
    /**
     * @desccription create a payment resource using the core of the plugin and return the response
     * @param PaymentInputDTO $payment_input
     * @return array
     */
    public function createCorePayment(PaymentInputDTO $payment_input)
    {
        try {
            $payment_action = new \PayplugPluginCore\Actions\PaymentAction();
            $payment_object = $payment_action->createAction($payment_input);
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
}
