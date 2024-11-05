<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group class
 * @group apirest_class
 *
 * @runTestsInSeparateProcesses
 */
class getPaymentMethodsSectionTest extends BaseApiRest
{
    private $payment_method_option;

    public function setUp()
    {
        parent::setUp();
        $context = \Mockery::mock('Context');
        $context->shouldReceive([
            'get' => ContextMock::get(),
        ]);
        $this->plugin->shouldReceive([
            'getContext' => $context,
        ]);
        $this->class->shouldReceive([
            'getDeferredState' => [],
        ]);

        $link = \Mockery::mock('Link');
        $link->shouldReceive([
            'getAdminLink' => 'AdminPayPlugInstallment',
        ]);
        $this->plugin->getContext()->get()->link = $link;

        $this->payment_method_option = [
            'available_test_mode' => false,
            'checked' => false,
            'descriptions' => [
                'live' => [
                    'description' => 'Lorem ipsum live description ',
                    'link_know_more' => [
                        'target' => '_blank',
                        'text' => 'Sit dolor emet.',
                        'url' => 'https://support.payplug.com/hc/fr/articles/123456789',
                    ],
                ],
                'sandbox' => [
                    'description' => 'Lorem ipsum sandbox description ',
                    'link_know_more' => [
                        'target' => '_blank',
                        'text' => 'Sit dolor emet.',
                        'url' => 'https://support.payplug.com/hc/fr/articles/123456789',
                    ],
                ],
            ],
            'image' => '/modules/payplug/views/img/svg/payment/apple_pay.svg',
            'name' => 'payment_method',
            'title' => 'Paiement Payplug',
            'type' => 'payment_method',
        ];
    }

    public function expectedPaymentMethods()
    {
        yield ['feature_standard', 'standard'];
        yield ['feature_amex', 'american_express'];
        yield ['feature_applepay', 'applepay'];
        yield ['feature_bancontact', 'bancontact'];
        yield ['feature_satispay', 'satispay'];
        yield ['feature_ideal', 'ideal'];
        yield ['feature_mybank', 'mybank'];
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $current_configuration
     */
    public function testWhenGivenConfigurationIsInvalidArrayFormat($current_configuration)
    {
        $this->assertSame(
            [],
            $this->class->getPaymentMethodsSection($current_configuration)
        );
    }

    public function testWhenNoMethodAreEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'isValidFeature' => false,
        ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->class->getPaymentMethodsSection($current_configuration));
    }

    /**
     * @dataProvider expectedPaymentMethods
     *
     * @param string $feature
     * @param string $expected
     */
    public function testWhenGivenMethodIsEnable($feature, $expected)
    {
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) use ($feature) {
                return $feature == $key;
            });

        if ('applepay' == $expected) {
            $this->carrier_adapter->shouldReceive([
                'getAllActiveCarriers' => [],
            ]);
        }

        $this->dependencies->configClass = $configClass;
        $response = $this->class->getPaymentMethodsSection([]);
        $payment_methods = [];
        if (isset($response['options'])) {
            foreach ($response['options'] as $payment_method) {
                $payment_methods[] = $payment_method['name'];
            }
        }
        $this->assertTrue(in_array($expected, $payment_methods));
    }
}
