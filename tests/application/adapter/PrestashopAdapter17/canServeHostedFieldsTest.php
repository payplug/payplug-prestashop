<?php

namespace PayPlug\tests\application\adapter\PrestashopAdapter17;

/**
 * @group unit
 * @group adapter
 * @group prestashop_adapter17
 */
class canServeHostedFieldsTest extends BasePrestashopAdapter17
{
    public function setUp(): void
    {
        parent::setUp();

        $this->context->currency->iso_code = 'USD';
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(true)->byDefault();
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')->andReturn(true)->byDefault();
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')->andReturn('{"usd":"ident_42"}')->byDefault();
        $this->routes->shouldReceive('getSourceUrl')
            ->andReturn(['hosted_fields' => 'https://hosted-fields.test/sdk.js'])
            ->byDefault();
    }

    public function testReturnsTrueWhenEveryConditionIsMet()
    {
        $this->logger->shouldReceive('addLog')->never();

        $this->assertTrue($this->adapter->canServeHostedFields('USD'));
    }

    public function testReturnsFalseWhenTheStandardFeatureIsNotValid()
    {
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_standard')->andReturn(false);

        $this->assertFalse($this->adapter->canServeHostedFields('USD'));
    }

    public function testReturnsFalseWhenTheHostedFieldsFeatureIsNotValid()
    {
        $this->config_class->shouldReceive('isValidFeature')
            ->with('feature_hosted_fields')->andReturn(false);

        $this->assertFalse($this->adapter->canServeHostedFields('USD'));
    }

    public function testReturnsFalseForEur()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')->andReturn('{"eur":"ident_42"}');

        $this->assertFalse($this->adapter->canServeHostedFields('EUR'));
    }

    public function testReturnsFalseWhenNoIdentifierIsConfiguredForTheCurrency()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')->andReturn('{"gbp":"ident_42"}');

        $this->assertFalse($this->adapter->canServeHostedFields('USD'));
    }

    public function testChecksTheIdentifierOfTheGivenCurrencyRatherThanTheContextOne()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')->andReturn('{"gbp":"ident_42"}');

        $this->assertTrue($this->adapter->canServeHostedFields('GBP'));
    }

    public function testReturnsFalseAndLogsWhenTheSdkUrlIsNotConfigured()
    {
        $this->routes->shouldReceive('getSourceUrl')->andReturn(['hosted_fields' => '']);
        $this->logger->shouldReceive('addLog')
            ->once()
            ->with(\Mockery::pattern('/HOSTED_FIELDS_URL is not configured/'), 'error');

        $this->assertFalse($this->adapter->canServeHostedFields('USD'));
    }

    public function testDoesNotCheckTheSdkUrlWhenNoIdentifierIsConfigured()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')->andReturn('{}');
        $this->routes->shouldReceive('getSourceUrl')->never();
        $this->logger->shouldReceive('addLog')->never();

        $this->assertFalse($this->adapter->canServeHostedFields('USD'));
    }
}
