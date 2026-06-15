<?php

namespace PayPlug\src\utilities\services;

use PayplugPluginCore\Actions\PaymentAction;
use PayplugPluginCore\Models\Entities\PaymentInputDTO;

class Core
{
    /** @var PaymentAction */
    private $payment_action;

    public function __construct(PaymentAction $payment_action)
    {
        $this->payment_action = $payment_action;
    }

    /**
     * @description create a payment resource using the core of the plugin and return the response
     *
     * @return array
     */
    public function createCorePayment(PaymentInputDTO $payment_input)
    {
        try {
            $payment_object = $this->payment_action->createAction($payment_input);

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
