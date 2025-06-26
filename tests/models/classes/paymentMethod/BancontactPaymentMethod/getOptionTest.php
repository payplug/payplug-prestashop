<?php

namespace PayPlug\tests\models\classes\paymentMethod\BancontactPaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group bancontact_payment_method_class
 */
class getOptionTest extends BaseBancontactPaymentMethod
{
    public $config;

    public function setUp()
    {
        parent::setUp();
        $this->config = [
            'bancontact_country' => true,
        ];
    }

    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->class->getOption($this->config)['available_test_mode']);
    }

    public function testWhenGivenOptionHasValidOption()
    {
        $expected = [
            [
                'type' => 'payment_option',
                'sub_type' => 'switch',
                'name' => 'bancontact_country',
                'title' => 'paymentmethods.bancontact.user.title',
                'descriptions' => [
                    'live' => [
                        'description' => 'paymentmethods.bancontact.user.description',
                        'link_know_more' => [
                            'text' => 'paymentmethods.bancontact.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/4408157435794',
                            'target' => '_blank',
                        ],
                    ],
                    'sandbox' => [
                        'description' => 'paymentmethods.bancontact.user.description',
                        'link_know_more' => [
                            'text' => 'paymentmethods.bancontact.link',
                            'url' => 'https://support.payplug.com/hc/fr/articles/4408157435794',
                            'target' => '_blank',
                        ],
                    ],
                ],
                'checked' => true,
            ],
        ];
        $this->assertSame($expected, $this->class->getOption($this->config)['options']);
    }
}
