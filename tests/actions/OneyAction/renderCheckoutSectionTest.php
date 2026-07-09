<?php

namespace PayPlug\tests\actions\OneyAction;

use PayPlug\src\models\classes\Oney\OneyAccountData;

/**
 * @group unit
 * @group action
 * @group oney_action
 */
class renderCheckoutSectionTest extends BaseOneyAction
{
    /**
     * @description test invalid $type param in renderCheckoutSection
     *
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $type
     */
    public function testWhenGivenTypeIsInvalidStringFormat($type)
    {
        $controller = $this->instance->shouldReceive(['getController' => 'product']);
        $this->dispatcher->shouldReceive(['getInstance' => $controller]);

        $this->assertFalse(
            $this->action->renderCheckoutSection($type, 147.20, 'FR')
        );
    }

    /**
     * @description test invalid $amount param in renderCheckoutSection
     *
     * @dataProvider invalidNumericFormatDataProvider
     *
     * @param mixed $amount
     */
    public function testWhenGivenAmountIsInvalidNumericFormat($amount)
    {
        $controller = $this->instance->shouldReceive(['getController' => 'product']);
        $this->dispatcher->shouldReceive(['getInstance' => $controller]);

        $this->assertFalse(
            $this->action->renderCheckoutSection('x3_with_fees', $amount, 'FR')
        );
    }

    /**
     * @description test invalid $isoCode param in renderCheckoutSection
     *
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $isoCode
     */
    public function testWhenGivenIsoCodeIsInvalidStringFormat($isoCode)
    {
        $controller = $this->instance->shouldReceive(['getController' => 'product']);
        $this->dispatcher->shouldReceive(['getInstance' => $controller]);

        $this->assertFalse(
            $this->action->renderCheckoutSection('x3_with_fees', 147.20, $isoCode)
        );
    }

    /**
     * @description test renderCheckoutSection when no business transaction code
     * matches the given type for this country
     */
    public function testWhenNoBusinessTransactionCodeMatchesType()
    {
        $controller = $this->instance->shouldReceive(['getController' => 'product']);
        $this->dispatcher->shouldReceive(['getInstance' => $controller]);

        $this->payment_method->shouldReceive([
            'getOneyAccountData' => OneyAccountData::fromAccountResponse([]),
        ]);

        $this->assertFalse(
            $this->action->renderCheckoutSection('x3_with_fees', 147.20, 'FR')
        );
    }

    /**
     * @description test the success path: a matching business transaction code and
     * merchant_guid are found, the checkout placeholder is assigned to smarty and the
     * rendered template is returned
     */
    public function testWhenBusinessCodeAndMerchantGuidAreFoundRendersCheckoutSection()
    {
        $this->dependencies->name = 'Payplug';

        $controller = $this->instance->shouldReceive(['getController' => 'product']);
        $this->dispatcher->shouldReceive(['getInstance' => $controller]);

        $this->payment_method->shouldReceive([
            'getOneyAccountData' => OneyAccountData::fromAccountResponse([
                'countries_metadata' => [
                    'FR' => [
                        'merchant_guid' => 'd5104abda4e74c45a78c08901107bb08',
                        'oney_business_codes' => [
                            'x3_with_fees' => 'W3135',
                        ],
                    ],
                ],
            ]),
        ]);

        $smarty = \Mockery::mock('Smarty');
        $smarty->shouldReceive('assign')
            ->once()
            ->with([
                'oney_checkout_placeholder' => 'PayplugOneyCheckout_x3_with_fees',
                'oney_checkout_business_transaction_code' => 'W3135',
                'oney_checkout_amount' => 147.20,
                'oney_checkout_country' => 'FR',
                'oney_checkout_merchant_guid' => 'd5104abda4e74c45a78c08901107bb08',
            ]);

        $context_with_smarty = \Mockery::mock('ContextWithSmarty');
        $context_with_smarty->smarty = $smarty;
        $context_with_smarty->shouldReceive(['getContext' => $context_with_smarty]);
        $this->context_adapter->shouldReceive('get')->andReturn($context_with_smarty);

        $config_class = \Mockery::mock('ConfigClass');
        $config_class->shouldReceive('fetchTemplate')
            ->with('oney/checkout.tpl')
            ->andReturn('<div>rendered checkout section</div>');
        $this->dependencies->configClass = $config_class;

        $this->assertSame(
            '<div>rendered checkout section</div>',
            $this->action->renderCheckoutSection('x3_with_fees', 147.20, 'FR')
        );
    }
}
