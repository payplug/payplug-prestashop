<?php

namespace PayPlug\src\utilities\helpers;

class AmountHelper
{
    /**
     * @description Format the Oney thresholds amount
     *
     * @param $amount
     *
     * @return array
     */
    public function formatOneyAmount($amount)
    {
        if (!is_int($amount)) {
            return [
                'result' => false,
                'message' => '$amount must be a int type',
            ];
        }

        return [
            'result' => $amount / 100,
            'message' => '$amount is formatted',
        ];
    }
}
