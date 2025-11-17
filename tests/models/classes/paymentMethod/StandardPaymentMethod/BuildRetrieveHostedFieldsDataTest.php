<?php

namespace PayPlug\tests\models\classes\paymentMethod\StandardPaymentMethod;

class BuildRetrieveHostedFieldsDataTest extends BaseStandardPaymentMethod
{
    public function SetUp()
    {
        parent::setUp();
    }

    /**
     * @group unit
     * @group class
     * @group payment_method_class
     * @group standard_payment_method_class
     */
    public function testWhenHostedFieldsRetrieveDataIsBuilt()
    {
        $resource_id = 'abc123';
        $multi_account = [
            'identifier_eur' => 'IDENTIFIER123',
        ];
        $this->configuration->shouldReceive('getValue')
            ->with('multi_account')
            ->andReturn(json_encode($multi_account));
        // buildHashContent is protected, partial mock allows mocking it
        $this->class->shouldReceive('buildHashContent')
            ->andReturn('mock-hash');

        $result = $this->class->BuildRetrieveHostedFieldsData($resource_id);

        $this->assertSame('getTransactions', $result['method']);
        $this->assertSame('IDENTIFIER123', $result['params']['IDENTIFIER']);
        $this->assertSame('getTransaction', $result['params']['OPERATIONTYPE']);
        $this->assertSame($resource_id, $result['params']['TRANSACTIONID']);
        $this->assertSame('3.0', $result['params']['VERSION']);
        $this->assertSame('mock-hash', $result['params']['HASH']);
    }

    /**
     * @group unit
     * @group class
     * @group payment_method_class
     * @group standard_payment_method_class
     */
    public function testWhenIdentifierIsMissingInMultiAccount()
    {
        $resource_id = 'A890789';
        $multi_account = [];
        $this->configuration->shouldReceive('getValue')
            ->with('multi_account')
            ->andReturn(json_encode($multi_account));
        $this->class->shouldReceive('buildHashContent')
            ->andReturn('mock-hash');

        $result = $this->class->BuildRetrieveHostedFieldsData($resource_id);

        $this->assertSame([], $result);
    }
}
