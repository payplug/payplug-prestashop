<?php

namespace PayPlug\tests\application\adapter\PrestashopAdapter17;

/**
 * @group unit
 * @group adapter
 * @group prestashop_adapter17
 */
class isHostedFieldsIdentifierConfiguredTest extends BasePrestashopAdapter17
{
    /**
     * @dataProvider notConfiguredDataProvider
     *
     * @param mixed $hosted_fields
     */
    public function testReturnsFalseWhenNoIdentifierIsConfiguredForTheCurrency($hosted_fields)
    {
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn($hosted_fields);

        $this->assertFalse($this->adapter->isHostedFieldsIdentifierConfigured('USD'));
    }

    public function notConfiguredDataProvider()
    {
        yield 'empty identifier' => ['{"usd":""}'];

        yield 'missing currency key' => ['{"gbp":"ident_42"}'];

        yield 'empty map' => ['{}'];

        yield 'empty configuration' => [''];

        yield 'missing configuration' => [false];

        yield 'invalid json' => ['{"usd":'];

        yield 'non string identifier' => ['{"usd":["ident_42"]}'];

        yield 'null identifier' => ['{"usd":null}'];
    }

    public function testReturnsTrueWhenAnIdentifierIsConfiguredForTheCurrency()
    {
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{"usd":"ident_42"}');

        $this->assertTrue($this->adapter->isHostedFieldsIdentifierConfigured('USD'));
    }

    public function testDefaultsToTheContextCurrency()
    {
        $this->context->currency->iso_code = 'GBP';
        $this->configuration->shouldReceive('getValue')
            ->with('hosted_fields')
            ->andReturn('{"gbp":"ident_42"}');

        $this->assertTrue($this->adapter->isHostedFieldsIdentifierConfigured());
    }
}
