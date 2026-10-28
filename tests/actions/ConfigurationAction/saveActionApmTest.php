<?php

namespace PayPlug\tests\actions\ConfigurationAction;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PayPlug\src\models\classes\ApmEligibility;

/**
 * @group unit
 * @group action
 * @group configuration_action
 */
class saveActionApmTest extends BaseConfigurationAction
{
    use MockeryPHPUnitIntegration;

    protected $apm_eligibility;
    protected $payment_method_class;
    protected $stored;

    public function setUp(): void
    {
        parent::setUp();

        $this->apm_eligibility = \Mockery::mock('ApmEligibility');
        $this->apm_eligibility->shouldReceive('getBackOfficeCurrency')
            ->andReturn('EUR')
            ->byDefault();
        $this->apm_eligibility->shouldReceive([
            'getApiLimits' => ['configured' => true, 'currency_supported' => true, 'min' => 30, 'max' => 2000000, 'countries' => ['IT']],
        ]);
        $this->apm_eligibility->shouldReceive('getBrandName')
            ->andReturnUsing(function ($name) {
                return isset(ApmEligibility::BRAND_NAMES[$name]) ? ApmEligibility::BRAND_NAMES[$name] : '';
            })
            ->byDefault();

        $this->payment_method_class = \Mockery::mock('PaymentMethodClass');
        $this->payment_method_class->shouldReceive([
            'getApmEligibility' => $this->apm_eligibility,
        ]);

        $context = new \stdClass();
        $context->language = new \stdClass();
        $context->language->id = 1;
        $context_adapter = \Mockery::mock('ContextAdapter');
        $context_adapter->shouldReceive([
            'get' => $context,
        ]);

        $this->plugin->shouldReceive([
            'getPaymentMethodClass' => $this->payment_method_class,
            'getContext' => $context_adapter,
        ]);

        $amount_helper = \Mockery::mock('AmountHelper');
        $amount_helper->shouldReceive('convertAmount')
            ->andReturnUsing(function ($amount, $from_cents = false, $currency = '') {
                $minor_units = ['EUR' => 100, 'JPY' => 1];
                if (!isset($minor_units[$currency])) {
                    return 0;
                }

                return $from_cents ? $amount / $minor_units[$currency] : (int) round($amount * $minor_units[$currency]);
            });
        $this->module
            ->shouldReceive('getService')
            ->with('payplug.utilities.helper.amount')
            ->andReturn($amount_helper);

        $this->stored = [];
        $this->configuration_class->shouldReceive('set')
            ->andReturnUsing(function ($key, $value) {
                $this->stored[$key] = $value;

                return true;
            });

        $this->action->shouldReceive([
            'renderConfiguration' => [
                'success' => true,
                'data' => [],
            ],
        ]);
        $this->api_service->shouldReceive([
            'initialize' => true,
        ]);
    }

    public function testValidSettingsAreConvertedToCentsAndSavedInOneWrite()
    {
        $settings = ['min' => ['EUR' => 1000], 'max' => ['EUR' => 50000], 'countries' => ['IT']];
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->once()
            ->with('satispay', ['min' => 1000, 'max' => 50000, 'countries' => ['IT']], 1)
            ->andReturn(['result' => true, 'code' => 'ok', 'settings' => $settings]);
        $this->apm_eligibility->shouldReceive('saveMerchantSettings')
            ->once()
            ->with(['satispay' => $settings])
            ->andReturn(true);

        $datas = $this->getSaveData('satispay', ['satispay_min_amounts' => 10, 'satispay_max_amounts' => '500', 'satispay_countries' => ['IT']]);

        $this->assertTrue($this->action->saveAction($datas)['success']);
    }

    public function testEmptyCountrySelectionIsSaved()
    {
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->once()
            ->with('bancontact', ['countries' => []], 1)
            ->andReturn(['result' => true, 'code' => 'ok', 'settings' => ['countries' => []]]);
        $this->apm_eligibility->shouldReceive('saveMerchantSettings')
            ->once()
            ->with(['bancontact' => ['countries' => []]])
            ->andReturn(true);

        $datas = $this->getSaveData('bancontact', ['bancontact_countries' => []]);

        $this->assertTrue($this->action->saveAction($datas)['success']);
    }

    public function testAmountOutsideTheApiRangeIsRefusedWithTheAllowedRange()
    {
        $this->translation->shouldReceive('getModalTranslations')
            ->andReturn([
                'error' => ['text' => 'modal.error.text', 'submit' => 'modal.error.submit'],
                'apm' => ['amount_out_of_range' => '%1$s: %2$s - %3$s', 'submit' => 'modal.apm.submit'],
            ]);
        $this->apm_eligibility->shouldReceive([
            'validateMerchantSettings' => ['result' => false, 'code' => 'amount_out_of_range'],
        ]);
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $datas = $this->getSaveData('satispay', ['satispay_min_amounts' => 0.1]);

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'Satispay: 0.3 - 20000',
                    'close' => 'modal.apm.submit',
                    'class' => '-error',
                ],
            ],
            $this->action->saveAction($datas)
        );
        $this->assertArrayNotHasKey('apm_custom_settings', $this->stored);
    }

    public function testRefusalNamesThePaymentMethodByItsBrand()
    {
        $this->translation->shouldReceive('getModalTranslations')
            ->andReturn([
                'error' => ['text' => 'modal.error.text', 'submit' => 'modal.error.submit'],
                'apm' => ['country_not_allowed' => 'countries of %s', 'submit' => 'modal.apm.submit'],
            ]);
        $this->apm_eligibility->shouldReceive([
            'validateMerchantSettings' => ['result' => false, 'code' => 'country_not_allowed'],
        ]);
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $result = $this->action->saveAction($this->getSaveData('ideal', ['ideal_countries' => ['FR']]));

        $this->assertFalse($result['success']);
        $this->assertSame('countries of iDEAL', $result['data']['msg']);
    }

    public function testRefusalFallsBackToThePaymentMethodTitleWithoutBrandName()
    {
        $this->translation->shouldReceive('getModalTranslations')
            ->andReturn([
                'error' => ['text' => 'modal.error.text', 'submit' => 'modal.error.submit'],
                'apm' => ['max_not_above_min' => 'maximum of %s', 'submit' => 'modal.apm.submit'],
            ]);
        $this->apm_eligibility->shouldReceive('getBrandName')
            ->with('satispay')
            ->andReturn('');
        $this->apm_eligibility->shouldReceive([
            'validateMerchantSettings' => ['result' => false, 'code' => 'max_not_above_min'],
        ]);
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $result = $this->action->saveAction($this->getSaveData('satispay', ['satispay_max_amounts' => 10]));

        $this->assertFalse($result['success']);
        $this->assertSame('maximum of paymentmethods.satispay.title', $result['data']['msg']);
    }

    public function refusalProvider()
    {
        yield ['amount_invalid', 'modal.apm.amount_invalid'];

        yield ['max_not_above_min', 'modal.apm.max_not_above_min'];

        yield ['country_not_allowed', 'modal.apm.country_not_allowed'];

        yield ['unknown_method', 'modal.error.text'];
    }

    /**
     * @dataProvider refusalProvider
     *
     * @param string $code
     * @param string $message
     */
    public function testRefusalIsExplainedAndNothingIsSaved($code, $message)
    {
        $this->apm_eligibility->shouldReceive([
            'validateMerchantSettings' => ['result' => false, 'code' => $code],
        ]);
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $result = $this->action->saveAction($this->getSaveData('satispay', ['satispay_max_amounts' => 10]));

        $this->assertFalse($result['success']);
        $this->assertSame($message, $result['data']['msg']);
        $this->assertSame('-error', $result['data']['class']);
    }

    public function testNothingIsSavedWhenOneOfSeveralApmsIsInvalid()
    {
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->with('satispay', \Mockery::any(), 1)
            ->andReturn(['result' => true, 'code' => 'ok', 'settings' => []]);
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->with('ideal', \Mockery::any(), 1)
            ->andReturn(['result' => false, 'code' => 'max_not_above_min']);
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $datas = $this->getSaveData('satispay', ['satispay_min_amounts' => 10]);
        $datas->enable_ideal = true;
        $datas->ideal_max_amounts = 1;

        $this->assertFalse($this->action->saveAction($datas)['success']);
    }

    public function testFieldsOfADisabledApmAreIgnored()
    {
        $this->apm_eligibility->shouldNotReceive('validateMerchantSettings');
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $datas = $this->getSaveData('satispay', ['satispay_min_amounts' => 'abc', 'satispay_countries' => 'IT']);
        $datas->enable_satispay = false;

        $this->assertTrue($this->action->saveAction($datas)['success']);
    }

    public function testEmptyAmountResetsTheBoundAndInvalidValuesAreLeftForRefusal()
    {
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->once()
            ->with('satispay', ['min' => null, 'max' => 'abc', 'countries' => 'IT'], 1)
            ->andReturn(['result' => false, 'code' => 'amount_invalid']);

        $datas = $this->getSaveData('satispay', ['satispay_min_amounts' => false, 'satispay_max_amounts' => 'abc', 'satispay_countries' => 'IT']);

        $this->assertFalse($this->action->saveAction($datas)['success']);
    }

    public function testWhenNoLimitIsCachedTheyAreFetchedOnceBeforeRefusing()
    {
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->twice()
            ->andReturn(['result' => false, 'code' => 'no_config']);
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->withNoArgs()
            ->andReturn([]);

        $result = $this->action->saveAction($this->getSaveData('wero', ['wero_countries' => ['BE']]));

        $this->assertFalse($result['success']);
        $this->assertSame('modal.apm.no_config', $result['data']['msg']);
    }

    public function testWhenNoLimitIsCachedAndTheFetchSucceedsTheSettingsAreSaved()
    {
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->twice()
            ->andReturn(
                ['result' => false, 'code' => 'no_config'],
                ['result' => true, 'code' => 'ok', 'settings' => ['countries' => ['BE']]]
            );
        $this->api_service->shouldReceive('getAccount')
            ->once()
            ->withNoArgs()
            ->andReturn(['is_live' => false]);
        $this->apm_eligibility->shouldReceive('saveMerchantSettings')
            ->once()
            ->andReturn(true);

        $this->assertTrue($this->action->saveAction($this->getSaveData('wero', ['wero_countries' => ['BE']]))['success']);
    }

    public function testScalapayAmountsGoThroughTheGenericValidation()
    {
        $settings = ['min' => ['EUR' => 1000], 'max' => ['EUR' => 150000], 'countries' => ['IT']];
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->once()
            ->with('scalapay', ['min' => 1000, 'max' => 150000, 'countries' => ['IT']], 1)
            ->andReturn(['result' => true, 'code' => 'ok', 'settings' => $settings]);
        $this->apm_eligibility->shouldReceive('saveMerchantSettings')
            ->once()
            ->with(['scalapay' => $settings])
            ->andReturn(true);

        $datas = $this->getSaveData('scalapay', ['scalapay_min_amounts' => 10, 'scalapay_max_amounts' => 1500, 'scalapay_countries' => ['IT']]);

        $this->assertTrue($this->action->saveAction($datas)['success']);
        $this->assertArrayNotHasKey('scalapay_custom_min_amounts', $this->stored);
        $this->assertArrayNotHasKey('scalapay_custom_max_amounts', $this->stored);
    }

    public function testAmountsAreConvertedInTheBackOfficeCurrencyOfThePaymentMethod()
    {
        $this->apm_eligibility->shouldReceive('getBackOfficeCurrency')
            ->with('scalapay')
            ->andReturn('JPY');
        $settings = ['min' => ['JPY' => 1000], 'max' => ['JPY' => 200000]];
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->once()
            ->with('scalapay', ['min' => 1000, 'max' => 200000], 1)
            ->andReturn(['result' => true, 'code' => 'ok', 'settings' => $settings]);
        $this->apm_eligibility->shouldReceive('saveMerchantSettings')
            ->once()
            ->with(['scalapay' => $settings])
            ->andReturn(true);

        $datas = $this->getSaveData('scalapay', ['scalapay_min_amounts' => 1000, 'scalapay_max_amounts' => 200000]);

        $this->assertTrue($this->action->saveAction($datas)['success']);
    }

    public function testScalapayRefusalIsExplainedAndNothingIsSaved()
    {
        $this->apm_eligibility->shouldReceive('validateMerchantSettings')
            ->once()
            ->with('scalapay', ['min' => 1000, 'max' => 1000], 1)
            ->andReturn(['result' => false, 'code' => 'max_not_above_min']);
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $result = $this->action->saveAction($this->getSaveData('scalapay', ['scalapay_min_amounts' => 10, 'scalapay_max_amounts' => 10]));

        $this->assertFalse($result['success']);
        $this->assertSame('modal.apm.max_not_above_min', $result['data']['msg']);
        $this->assertSame([], $this->stored);
    }

    public function testWhenTheSettingsCannotBeSavedAnErrorIsReturned()
    {
        $this->apm_eligibility->shouldReceive([
            'validateMerchantSettings' => ['result' => true, 'code' => 'ok', 'settings' => []],
            'saveMerchantSettings' => false,
        ]);

        $result = $this->action->saveAction($this->getSaveData('satispay', ['satispay_min_amounts' => 10]));

        $this->assertSame(
            [
                'success' => false,
                'data' => [
                    'title' => null,
                    'msg' => 'modal.error.text',
                    'close' => 'modal.error.submit',
                    'class' => '-error',
                ],
            ],
            $result
        );
    }

    public function testPayloadWithoutApmFieldsDoesNotTouchTheApmSettings()
    {
        $this->apm_eligibility->shouldNotReceive('validateMerchantSettings');
        $this->apm_eligibility->shouldNotReceive('saveMerchantSettings');

        $this->assertTrue($this->action->saveAction($this->getSaveData('satispay', []))['success']);
    }

    /**
     * @param string $name
     * @param array $fields
     *
     * @return \stdClass
     */
    private function getSaveData($name, $fields)
    {
        $datas = new \stdClass();
        $datas->action = 'payplug_save_data';
        $datas->{'enable_' . $name} = true;
        foreach ($fields as $field => $value) {
            $datas->{$field} = $value;
        }

        return $datas;
    }
}
