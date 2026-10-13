<?php

namespace PayPlug\tests\models\classes\paymentMethod\SatispayPaymentMethod;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group satispay_payment_method_class
 */
class getDefaultPaymentTabTest extends BaseSatispayPaymentMethod
{
    use MockeryPHPUnitIntegration;

    protected $apm_eligibility;

    public function setUp(): void
    {
        parent::setUp();

        $this->validate_adapter->shouldReceive('validate')
            ->andReturnUsing(function ($method, $object) {
                return (bool) $object;
            });
        $this->configuration->shouldReceive('getValue')
            ->with('currencies')
            ->andReturn('EUR');
        $this->helpers['amount']->shouldReceive([
            'validateAmount' => [
                'result' => true,
            ],
        ]);
        $this->tools_adapter->shouldReceive('tool')
            ->andReturnUsing(function ($method, $arg) {
                return 'getShopDomainSsl' == $method;
            });
        $configClass = \Mockery::mock('Config');
        $configClass->shouldReceive([
            'getIsoCodeByCountryId' => 'FR',
        ]);
        $this->dependencies->configClass = $configClass;
        $this->language_helper->shouldReceive([
            'getIsoFromCodeLang' => 'fr',
        ]);

        $this->apm_eligibility = \Mockery::mock('ApmEligibility');
        $this->apm_eligibility->shouldReceive([
            'isPpro' => true,
        ]);
        $this->class->set('apm_eligibility', $this->apm_eligibility);
    }

    public function testNoPaymentIsCreatedWhenTheBillingCountryIsNoLongerEligible()
    {
        $this->apm_eligibility->shouldReceive('isEligible')
            ->once()
            ->with('satispay', 4242, 'EUR', 'FR')
            ->andReturn(['result' => false, 'reason' => 'country']);

        $this->assertSame([], $this->class->getDefaultPaymentTab());
    }

    public function testNoPaymentIsCreatedWhenTheAmountIsNoLongerEligible()
    {
        $this->apm_eligibility->shouldReceive('isEligible')
            ->once()
            ->andReturn(['result' => false, 'reason' => 'amount_high']);

        $this->assertSame([], $this->class->getDefaultPaymentTab());
    }

    public function testThePaymentTabIsBuiltWhenEligible()
    {
        $this->apm_eligibility->shouldReceive('isEligible')
            ->once()
            ->andReturn(['result' => true, 'reason' => 'ok']);

        $payment_tab = $this->class->getDefaultPaymentTab();

        $this->assertSame(4242, (int) $payment_tab['amount']);
        $this->assertSame('EUR', $payment_tab['currency']);
    }
}
