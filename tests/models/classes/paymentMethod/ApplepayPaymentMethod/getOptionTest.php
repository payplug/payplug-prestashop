<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 */
class getOptionTest extends BaseApplepayPaymentMethod
{
    public function testWhenNoCarriersReturned()
    {
        $this->class->shouldReceive([
            'getAvailableCarriers' => [],
        ]);
        $configuration = [
            'applepay_display' => '{"checkout":true,"cart":false,"product":false}',
        ];
        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'IOptions',
                'name' => 'applepay_display',
                'title' => 'paymentmethods.applepay.display.title',
                'multiple' => true,
                'options' => [
                    [
                        'name' => 'applepay_checkout',
                        'image_url' => 'modules/payplug/views/img/applepay/checkoutCta.jpg',
                        'title' => 'paymentmethods.applepay.display.checkout',
                        'switch' => true,
                        'checked' => true,
                    ],
                    [
                        'name' => 'applepay_cart',
                        'image_url' => 'modules/payplug/views/img/applepay/cartCta.jpg',
                        'title' => 'paymentmethods.applepay.display.cart',
                        'switch' => true,
                        'checked' => false,
                    ],
                    [
                        'name' => 'applepay_product',
                        'image_url' => 'modules/payplug/views/img/applepay/productCta.jpg',
                        'title' => 'paymentmethods.applepay.display.product',
                        'switch' => true,
                        'checked' => false,
                    ],
                ],
                'carriers' => [],
            ],
        ];
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame(
            $expected,
            $this->class->getOption($configuration)['options']
        );
    }

    public function testWhenCarrierIsReturned()
    {
        $carriers = [
            [
                'id_carrier' => 42,
                'name' => 'carrier',
                'checked' => true,
            ],
        ];
        $this->class->shouldReceive([
            'getAvailableCarriers' => $carriers,
        ]);
        $configuration = [
            'applepay_display' => '{"checkout":true,"cart":false,"product":false}',
        ];

        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'IOptions',
                'name' => 'applepay_display',
                'title' => 'paymentmethods.applepay.display.title',
                'multiple' => true,
                'options' => [
                    [
                        'name' => 'applepay_checkout',
                        'image_url' => 'modules/payplug/views/img/applepay/checkoutCta.jpg',
                        'title' => 'paymentmethods.applepay.display.checkout',
                        'switch' => true,
                        'checked' => true,
                    ],
                    [
                        'name' => 'applepay_cart',
                        'image_url' => 'modules/payplug/views/img/applepay/cartCta.jpg',
                        'title' => 'paymentmethods.applepay.display.cart',
                        'switch' => true,
                        'checked' => false,
                    ],
                    [
                        'name' => 'applepay_product',
                        'image_url' => 'modules/payplug/views/img/applepay/productCta.jpg',
                        'title' => 'paymentmethods.applepay.display.product',
                        'switch' => true,
                        'checked' => false,
                    ],
                ],
                'carriers' => [
                    'title' => 'paymentmethods.applepay.carrier.title',
                    'alert' => 'paymentmethods.applepay.carrier.alert',
                    'descriptions' => [
                        'live' => [
                            'description' => 'paymentmethods.applepay.carrier.description',
                            'description_bold' => 'paymentmethods.applepay.carrier.description_bold',
                            'description_warning' => 'paymentmethods.applepay.carrier.description_warning',
                        ],
                        'sandbox' => [],
                    ],
                    'instructions' => 'paymentmethods.applepay.carrier.instructions',
                    'carriers_list' => $carriers,
                ],
            ],
        ];

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => true,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame(
            $expected,
            $this->class->getOption($configuration)['options']
        );
    }

    public function testAddsTheDomainAssociationDiagnostics()
    {
        $this->givenAnOptionWithoutCarrier();
        $diagnostics = [
            'state' => 'not_synced',
            'last_attempt_at' => 30,
            'last_success_at' => 20,
            'last_error' => 'Unexpected HTTP status 500 from secure.payplug.com.',
            'root_file_present' => true,
        ];
        $this->domain_association_action->shouldReceive('getDiagnosticsAction')->once()->andReturn($diagnostics);

        $option = $this->class->getOption(['applepay_display' => '{"checkout":true,"cart":false,"product":false}']);

        $this->assertSame(
            array_merge($diagnostics, [
                'texts' => [
                    'title' => 'paymentmethods.applepay.domain_association.title',
                    'state_synced' => 'paymentmethods.applepay.domain_association.state_synced',
                    'state_not_synced' => 'paymentmethods.applepay.domain_association.state_not_synced',
                    'last_success' => 'paymentmethods.applepay.domain_association.last_success',
                    'last_error' => 'paymentmethods.applepay.domain_association.last_error',
                    'never' => 'paymentmethods.applepay.domain_association.never',
                    'resync' => 'paymentmethods.applepay.domain_association.resync',
                    'root_file' => 'paymentmethods.applepay.domain_association.root_file',
                ],
            ]),
            $option['domain_association']
        );
    }

    public function testOmitsTheDomainAssociationDiagnosticsAndLogsWhenTheyAreUnavailable()
    {
        $this->givenAnOptionWithoutCarrier();
        $this->domain_association_action->shouldReceive('getDiagnosticsAction')
            ->andThrow(new \RuntimeException('container failure'));

        $option = $this->class->getOption(['applepay_display' => '{"checkout":true,"cart":false,"product":false}']);

        $this->assertArrayNotHasKey('domain_association', $option);
        $this->assertSame('applepay_display', $option['options'][0]['name']);
        $this->logger->shouldHaveReceived('addLog')
            ->with(\Mockery::pattern('/^ApplepayPaymentMethod::getOption: .*container failure/'), 'error')
            ->once();
    }

    private function givenAnOptionWithoutCarrier()
    {
        $this->class->shouldReceive([
            'getAvailableCarriers' => [],
        ]);
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->dependencies->configClass = $configClass;
    }
}
