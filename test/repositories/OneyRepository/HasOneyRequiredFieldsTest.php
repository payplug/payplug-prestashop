<?php

namespace PayPlug\tests\repositories\OneyRepository;

/**
 * @group unit
 * @group old_repository
 * @group oney
 * @group oney_repository
 *
 * @dontrunTestsInSeparateProcesses
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
            })
        ;
    }

    public function invalidPaymentDataProvider()
    {
        yield [false];
        yield [[]];
        yield ['wrong_parameters'];
        yield [42];
    }

    /**
     * @dataProvider invalidPaymentDataProvider
     *
     * @param mixed $payment_data
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
                    'error' => '',
                ],
            ])
        ;

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
            ],
        ];

        $this->assertSame(
            true,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }

    public function testWithInvalidShippingMobilePhone()
    {
        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => true,
                ],
            ])
        ;

        $this->validators['payment']->shouldReceive([
                'isPhoneNumber' => [
                    'result' => false,
                    'message' => '',
                ],
            ]);

        $this->validators['payment']->shouldReceive([
                'isValidMobilePhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->tools->shouldReceive([
            'tool' => 15,
        ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => false,
                'country' => 'FR',
            ],
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
                    'result' => true,
                ],
            ])
        ;

        $this->validators['payment']->shouldReceive([
                'isPhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->validators['payment']->shouldReceive([
                'isValidMobilePhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->tools->shouldReceive([
            'tool' => 45,
        ]);

        $this->repo
            ->shouldReceive([
                'isValidOneyEmail' => [
                    'result' => true,
                ],
            ])
        ;
        $this->validators['payment']
            ->shouldReceive([
                'isValidMobilePhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'nom de ville de plus de 32 caracteres de long',
            ],
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
                    'result' => true,
                ],
            ])
        ;

        $this->validators['payment']->shouldReceive([
                'isPhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->validators['payment']->shouldReceive([
                'isValidMobilePhoneNumber' => [
                    'result' => false,
                    'message' => '',
                ],
            ]);

        $this->tools->shouldReceive([
            'tool' => 15,
        ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'paris',
            ],
            'billing' => [
                'mobile_phone_number' => false,
                'country' => 'FR',
                'city' => 'paris',
            ],
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
                    'result' => true,
                ],
            ])
        ;

        $this->validators['payment']->shouldReceive([
                'isPhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->validators['payment']->shouldReceive([
                'isValidMobilePhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->tools->shouldReceive([
            'tool' => 45,
        ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'paris',
            ],
            'billing' => [
                'mobile_phone_number' => true,
                'country' => 'FR',
                'city' => 'nom de ville de plus de 32 caracteres de long',
            ],
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
                    'result' => true,
                ],
            ])
        ;

        $this->validators['payment']->shouldReceive([
                'isPhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->validators['payment']->shouldReceive([
                'isValidMobilePhoneNumber' => [
                    'result' => true,
                    'message' => '',
                ],
            ]);

        $this->tools->shouldReceive([
            'tool' => 15,
        ]);

        $payment_data = [
            'shipping' => [
                'email' => 'customer@payplug.com',
                'mobile_phone_number' => '0123456789',
                'country' => 'FR',
                'city' => 'paris',
            ],
            'billing' => [
                'mobile_phone_number' => '0123456789',
                'country' => 'FR',
                'city' => 'paris',
            ],
        ];

        $this->assertSame(
            false,
            $this->repo->hasOneyRequiredFields($payment_data)
        );
    }
}
