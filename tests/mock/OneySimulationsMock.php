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

namespace PayPlug\tests\mock;

class OneySimulationsMock
{
    public static function getOneySimulations()
    {
        $simulations = [
            'x3_with_fees' => [
                'installments' => [
                    [
                        'date' => '2021-02-19T01:00:00.000Z',
                        'amount' => 8042,
                    ],
                    [
                        'date' => '2021-03-19T01:00:00.000Z',
                        'amount' => 8041,
                    ],
                ],
                'total_cost' => 350,
                'nominal_annual_percentage_rate' => 17.76,
                'effective_annual_percentage_rate' => 19.27,
                'down_payment_amount' => 8392,
            ],
            'x4_with_fees' => [
                'installments' => [
                    [
                        'date' => '2021-02-19T01:00:00.000Z',
                        'amount' => 6031,
                    ],
                    [
                        'date' => '2021-03-19T01:00:00.000Z',
                        'amount' => 6031,
                    ],
                    [
                        'date' => '2021-04-19T00:00:00.000Z',
                        'amount' => 6032,
                    ],
                ],
                'total_cost' => 531,
                'nominal_annual_percentage_rate' => 18.05,
                'effective_annual_percentage_rate' => 19.62,
                'down_payment_amount' => 6562,
            ],
        ];

        return $simulations;
    }

    public static function getOneySimulationsNotAvailable()
    {
        $simulations = [
            'details' => 'Access to this feature is not available.'
        ];

        return $simulations;
    }

    public static function getOneySimulationsIsError()
    {
        $simulations = [
            'object' => 'error.'
        ];

        return $simulations;
    }
}
