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
    public static function get()
    {
        return [
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
                'nominal_annual_percentage_rate' => number_format(17.76, 2),
                'effective_annual_percentage_rate' => number_format(19.27, 2),
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
                'nominal_annual_percentage_rate' => number_format(18.05, 2),
                'effective_annual_percentage_rate' => number_format(19.62, 2),
                'down_payment_amount' => 6562,
            ],
        ];
    }

    public static function getFormated()
    {
        return [
            'x3_with_fees' => [
                'installments' => [
                    [
                        'date' => '2021-02-19T01:00:00.000Z',
                        'amount' => number_format(80.42, 2),
                        'value' => '80,42 €',
                    ],
                    [
                        'date' => '2021-03-19T01:00:00.000Z',
                        'amount' => number_format(80.41, 2),
                        'value' => '80,41 €',
                    ],
                ],
                'total_cost' => [
                    'amount' => number_format(3.5, 2),
                    'value' => '3,50 €',
                ],
                'nominal_annual_percentage_rate' => number_format(17.76, 2),
                'effective_annual_percentage_rate' => number_format(19.27, 2),
                'down_payment_amount' => [
                    'amount' => number_format(83.92, 2),
                    'value' => '83,92 €',
                ],
                'split' => 3,
                'title' => 'Payment in 3x',
                'total_amount' => [
                    'amount' => number_format(15003.5, 2),
                    'value' => '15,003,50 €',
                ]
            ],
            'x4_with_fees' => [
                'installments' => [
                    [
                        'date' => '2021-02-19T01:00:00.000Z',
                        'amount' => number_format(60.31, 2),
                        'value' => '60,31 €',
                    ],
                    [
                        'date' => '2021-03-19T01:00:00.000Z',
                        'amount' => number_format(60.31, 2),
                        'value' => '60,31 €',
                    ],
                    [
                        'date' => '2021-04-19T00:00:00.000Z',
                        'amount' => number_format(60.32, 2),
                        'value' => '60,32 €',
                    ],
                ],
                'total_cost' => [
                    'amount' => number_format(5.31, 2),
                    'value' => '5,31 €',
                ],
                'nominal_annual_percentage_rate' => number_format(18.05, 2),
                'effective_annual_percentage_rate' => number_format(19.62, 2),
                'down_payment_amount' => [
                    'amount' => number_format(65.62, 2),
                    'value' => '65,62 €',
                ],
                'split' => 4,
                'title' => 'Payment in 4x',
                'total_amount' => [
                    'amount' => number_format(15005.31, 2),
                    'value' => '15,005,31 €',
                ],
            ]
        ];
    }

    public static function getNotAvailable()
    {
        return [
            'details' => 'Access to this feature is not available.'
        ];
    }

    public static function getIsError()
    {
        return [
            'object' => 'error'
        ];
    }

    public static function getFromCache()
    {
        $cache_value = [
            'result' => true,
            'simulations' => self::get()
        ];
        return [
            'cache_key' => 'cache_key',
            'cache_value' => json_encode($cache_value),
            'date_add' => '2021-01-01 00:00:00',
            'date_upd' => '2021-01-01 00:00:00',
        ];
    }
}
