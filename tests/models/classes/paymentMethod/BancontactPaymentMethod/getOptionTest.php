<?php

namespace PayPlug\tests\models\classes\paymentMethod\BancontactPaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class getOptionTest extends BaseBancontactPaymentMethod
{
    private $config;

    protected function setUp()
    {
        parent::setUp();
        $this->config = [
            'bancontact_country' => true,
        ];
    }

    public function testWhenGivenOptionIsntAvailableWithSandboxMode()
    {
        $this->assertFalse($this->classe->getOption($this->config)['available_test_mode']);
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
        $this->assertSame($expected, $this->classe->getOption($this->config)['options']);
    }
}
