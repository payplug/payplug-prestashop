<?php

namespace PayPlug\tests\repositories\OneyRepository;

use PayPlug\tests\mock\AddressMock;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group old_repository
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
            ->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturn([])
        ;
        $this->assertSame(
            [],
            $this->repo->getOneyRequiredFields()
        );
    }

    public function testWithInvalidShippingEmail()
    {
        $this->context
            ->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('shipping-email', $arr)) {
                    return ['error message: shipping-email'];
                }

                return [];
            })
        ;

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'shipping' => [
                'email' => [
                    'text' => 'error message: shipping-email',
                    'input' => [
                        [
                            'name' => 'email',
                            'value' => 'customer@payplug.com',
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidShippingMobilePhone()
    {
        $this->context
            ->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('shipping-mobile_phone_number', $arr)) {
                    return ['error message: shipping-mobile_phone_number'];
                }

                return [];
            })
        ;

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'shipping' => [
                'mobile_phone_number' => [
                    'text' => 'error message: shipping-mobile_phone_number',
                    'input' => [
                        [
                            'name' => 'mobile_phone_number',
                            'value' => $this->addressMock->phone_mobile,
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidShippingCity()
    {
        $this->context
            ->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('shipping-city', $arr)) {
                    return ['error message: shipping-city'];
                }

                return [];
            })
        ;

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'shipping' => [
                'city' => [
                    'text' => 'error message: shipping-city',
                    'input' => [
                        [
                            'name' => 'first_name',
                            'value' => $this->addressMock->firstname,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'last_name',
                            'value' => $this->addressMock->lastname,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'address1',
                            'value' => $this->addressMock->address1,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'postcode',
                            'value' => $this->addressMock->postcode,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'city',
                            'value' => $this->addressMock->city,
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidBillingMobilePhone()
    {
        $this->context
            ->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('billing-mobile_phone_number', $arr)) {
                    return ['error message: billing-mobile_phone_number'];
                }

                return [];
            })
        ;

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'billing' => [
                'mobile_phone_number' => [
                    'text' => 'error message: billing-mobile_phone_number',
                    'input' => [
                        [
                            'name' => 'mobile_phone_number',
                            'value' => $this->addressMock->phone_mobile,
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithInvalidBillingCity()
    {
        $this->context
            ->shouldReceive('get')
            ->andReturn(ContextMock::get())
        ;
        $this->repo->shouldReceive('checkOneyRequiredFields')
            ->andReturnUsing(function ($arr) {
                if (array_key_exists('billing-city', $arr)) {
                    return ['error message: shipping-city'];
                }

                return [];
            })
        ;

        $response = $this->repo->getOneyRequiredFields();
        $expected = [
            'billing' => [
                'city' => [
                    'text' => 'error message: shipping-city',
                    'input' => [
                        [
                            'name' => 'first_name',
                            'value' => $this->addressMock->firstname,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'last_name',
                            'value' => $this->addressMock->lastname,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'address1',
                            'value' => $this->addressMock->address1,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'postcode',
                            'value' => $this->addressMock->postcode,
                            'type' => 'text',
                        ],
                        [
                            'name' => 'city',
                            'value' => $this->addressMock->city,
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertSame($expected, $response);
    }

    public function testWithoutCustomer()
    {
        $contextWithoutCustomer = ContextMock::get();
        $contextWithoutCustomer->customer = null;

        $this->validators['payment']->shouldReceive([
                'isPhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->validators['payment']
            ->shouldReceive([
                'isValidMobilePhoneNumber' => true,
            ]);

        $this->context
            ->shouldReceive('get')
            ->andReturn($contextWithoutCustomer)
        ;

        $this->validators['payment']->shouldReceive([
                'isOneyEmail' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->assertSame([], $this->repo->getOneyRequiredFields());
    }
}
