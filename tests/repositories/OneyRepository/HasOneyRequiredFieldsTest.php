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

/**
 * @group unit
 * @group repository
 * @group oney
 * @group oney_repository
 *
 * @runTestsInSeparateProcesses
 */
final class HasOneyRequiredFieldsTest extends BaseOneyRepository
{
    public function setUp()
    {
        parent::setUp();

        $this->dependencies->configClass
            ->shouldReceive('isValidMobilePhoneNumber')
            ->andReturnUsing(function ($country, $phone) {
                if (!$phone) {
                    return false;
                }
                return true;
            });
    }

    public function invalidPaymentDataProvider()
    {
        yield[false];
        yield[[]];
        yield['wrong_parameters'];
        yield[42];
    }

    /**
     * @dataProvider invalidPaymentDataProvider
     */
    public function testWithInvalidPaymentData($payment_data)
    {
        $this->assertSame(
            false,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }

    public function testWithInvalidEmail()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => false,
                    'error' => ''
                ]
            ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com'
            ]
        ];

        $this->assertSame(
            true,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }

    public function testWithInvalidShippingMobilePhone()
    {
        $this->dependencies->configClass
            ->shouldReceive([
                'isValidMobilePhoneNumber' => 3500
            ]);

        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => true
                ]
            ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => false,
                'country' => 'FR'
            ]
        ];

        $this->assertSame(
            true,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }

    public function testWithInvalidShippingCity()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => true
                ]
            ]);
        $this->dependencies->configClass
            ->shouldReceive([
                'isValidMobilePhoneNumber' => true
            ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'nom de ville de plus de 32 caracteres de long'
            ]
        ];

        $this->assertSame(
            true,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }

    public function testWithInvalidBillingMobilePhone()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => true
                ]
            ]);
        $this->dependencies->configClass
            ->shouldReceive([
                'isValidMobilePhoneNumber' => true
            ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'paris'
            ],
            'billing' => [
                'mobile_phone_number' => false,
                'country' => 'FR',
                'city' => 'paris'
            ]
        ];

        $this->assertSame(
            true,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }

    public function testWithInvalidBillingCity()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => true
                ]
            ]);
        $this->dependencies->configClass
            ->shouldReceive([
                'isValidMobilePhoneNumber' => true
            ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'paris'
            ],
            'billing' => [
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'nom de ville de plus de 32 caracteres de long'
            ]
        ];

        $this->assertSame(
            true,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }

    public function testWithValidPaymentData()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => true
                ]
            ]);
        $this->dependencies->configClass
            ->shouldReceive([
                'isValidMobilePhoneNumber' => true
            ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'paris'
            ],
            'billing' => [
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'paris'
            ]
        ];

        $this->assertSame(
            false,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }
}
