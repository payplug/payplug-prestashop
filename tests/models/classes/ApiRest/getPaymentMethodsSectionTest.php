<?php

namespace PayPlug\tests\models\classes\ApiRest;

use PayPlug\src\models\classes\ApiRest;
use PayPlug\tests\mock\ContextMock;

/**
 * @group unit
 * @group classes
 * @group apirest_classes
 * @group dev
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
        $this->classe->shouldReceive([
            'getDeferredState' => [],
        ]);

        $link = \Mockery::mock('Link');
        $link
            ->shouldReceive([
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

    public function invalidArrayFormatDataProvider()
    {
        yield [42];
        yield [null];
        yield [false];
        yield ['lorem ipsum'];
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
            $this->classe->getPaymentMethodsSection($current_configuration)
        );
    }

    public function testWhenNoMethodAreEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive([
                'isValidFeature' => false,
            ]);
        $this->dependencies->configClass = $configClass;
        $this->assertSame([], $this->classe->getPaymentMethodsSection($current_configuration));
    }

    public function atestWhenStandardIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_standard' == $key;
            });
        $this->payment_method_option['name'] = 'standard';
        $this->payment_methods['standard']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);

        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('standard', $payment_methods));
    }

    public function atestWhenAmexIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_amex' == $key;
            });
        $this->payment_method_option['name'] = 'amex';
        $this->payment_methods['amex']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);

        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('american_express', $payment_methods));
    }

    public function atestWhenApplePayIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_applepay' == $key;
            });
        $this->payment_method_option['name'] = 'applepay';
        $this->payment_methods['applepay']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('applepay', $payment_methods));
    }

    public function atestWhenBancontactIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_bancontact' == $key;
            });
        $this->payment_method_option['name'] = 'bancontact';
        $this->payment_methods['bancontact']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);
        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('bancontact', $payment_methods));
    }

    public function atestWhenSatispayIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_satispay' == $key;
            });
        $this->payment_method_option['name'] = 'satispay';
        $this->payment_methods['satispay']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);

        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('satispay', $payment_methods));
    }

    public function atestWhenSofortIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_sofort' == $key;
            });
        $this->payment_method_option['name'] = 'sofort';
        $this->payment_methods['sofort']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);

        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('sofort', $payment_methods));
    }

    public function atestWhenGiropayIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_giropay' == $key;
            });
        $this->payment_method_option['name'] = 'giropay';
        $this->payment_methods['giropay']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);

        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('giropay', $payment_methods));
    }

    public function atestWhenIdealIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_ideal' == $key;
            });
        $this->payment_method_option['name'] = 'ideal';
        $this->payment_methods['ideal']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);

        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('ideal', $payment_methods));
    }

    public function atestWhenMyBankIsEnable()
    {
        $current_configuration = [];

        $configClass = \Mockery::mock('Config');
        $configClass
            ->shouldReceive('isValidFeature')
            ->andReturnUsing(function ($key) {
                return 'feature_mybank' == $key;
            });
        $this->payment_method_option['name'] = 'mybank';
        $this->payment_methods['mybank']->shouldReceive([
            'getOption' => $this->payment_method_option,
        ]);

        $this->dependencies->configClass = $configClass;
        $response = $this->classe->getPaymentMethodsSection($current_configuration);

        $payment_methods = [];
        foreach ($response['options'] as $payment_method) {
            $payment_methods[] = $payment_method['name'];
        }
        $this->assertTrue(in_array('mybank', $payment_methods));
    }
}
