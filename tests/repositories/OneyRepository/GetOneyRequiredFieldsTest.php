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

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\AddressMock;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class GetOneyRequiredFieldsTest extends BaseOneyRepository
{
    private $addressMock;

    public function setUp()
    {
        parent::setUp();

        $this->addressMock = AddressMock::get();
    }

    public function testWithValidPaymentData()
    {
        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturn([]);
        $this->assertSame(
            [],
            $this->repo->getOneyRequiredFields()
        );
    }

    public function testWithInvalidShippingEmail()
    {
        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('shipping-email', $arr)) {
                    return ['error message: shipping-email'];
                }
                return [];
            });

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'shipping' => [
                'email' => [
                    'text' => 'error message: shipping-email',
                    'input' => [
                        [
                            'name' => 'email',
                            'value' => 'customer@payplug.com',
                            'type' => 'text'
                        ]
                    ],
                ]
            ]
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidShippingMobilePhone()
    {
        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('shipping-mobile_phone_number', $arr)) {
                    return ['error message: shipping-mobile_phone_number'];
                }
                return [];
            });

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'shipping' => [
                'mobile_phone_number' => [
                    'text' => 'error message: shipping-mobile_phone_number',
                    'input' => [
                        [
                            'name' => 'mobile_phone_number',
                            'value' => $this->addressMock->phone_mobile,
                            'type' => 'text'
                        ]
                    ],
                ]
            ]
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidShippingCity()
    {
        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('shipping-city', $arr)) {
                    return ['error message: shipping-city'];
                }
                return [];
            });

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'shipping' => [
                'city' => [
                    'text' => 'error message: shipping-city',
                    'input' => [
                        [
                            'name' => 'first_name',
                            'value' => $this->addressMock->firstname,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'last_name',
                            'value' => $this->addressMock->lastname,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'address1',
                            'value' => $this->addressMock->address1,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'postcode',
                            'value' => $this->addressMock->postcode,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'city',
                            'value' => $this->addressMock->city,
                            'type' => 'text'
                        ]
                    ],
                ]
            ]
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidBillingMobilePhone()
    {
        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('billing-mobile_phone_number', $arr)) {
                    return ['error message: billing-mobile_phone_number'];
                }
                return [];
            });

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'billing' => [
                'mobile_phone_number' => [
                    'text' => 'error message: billing-mobile_phone_number',
                    'input' => [
                        [
                            'name' => 'mobile_phone_number',
                            'value' => $this->addressMock->phone_mobile,
                            'type' => 'text'
                        ]
                    ],
                ]
            ]
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidBillingCity()
    {
        $this->context
            ->shouldReceive('getContext')
            ->andReturn(ContextMock::get());
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('billing-city', $arr)) {
                    return ['error message: shipping-city'];
                }
                return [];
            });

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'billing' => [
                'city' => [
                    'text' => 'error message: shipping-city',
                    'input' => [
                        [
                            'name' => 'first_name',
                            'value' => $this->addressMock->firstname,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'last_name',
                            'value' => $this->addressMock->lastname,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'address1',
                            'value' => $this->addressMock->address1,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'postcode',
                            'value' => $this->addressMock->postcode,
                            'type' => 'text'
                        ],
                        [
                            'name' => 'city',
                            'value' => $this->addressMock->city,
                            'type' => 'text'
                        ]
                    ],
                ]
            ]
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithoutCustomer()
    {

//        $contextWithoutCustomer = MockHelper::createContextMock('Payplug\src\specific\ContextSpecific');
        $contextWithoutCustomer = ContextMock::get();
        $contextWithoutCustomer->customer = null;

        $this->context
            ->shouldReceive('getContext')
            ->andReturn($contextWithoutCustomer);

        $this->assertSame([], $this->repo->getOneyRequiredFields());
    }
}
