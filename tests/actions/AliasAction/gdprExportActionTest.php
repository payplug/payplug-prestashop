<?php

namespace PayPlug\tests\actions\AliasAction;

/**
 * @group unit
 * @group action
 * @group alias_action
 */
class gdprExportActionTest extends BaseAliasAction
{
    public function setUp(): void
    {
        parent::setUp();

        $translation = \Mockery::mock('TranslationClass');
        $translation->shouldReceive('l')->andReturnUsing(function ($key) {
            return $key;
        });
        $this->dependencies->getPlugin()->shouldReceive('getTranslationClass')->andReturn($translation);
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $id_customer
     */
    public function testReturnsAnEmptyArrayForAnInvalidCustomerId($id_customer)
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->never();

        $this->assertSame([], $this->action->gdprExportAction($id_customer));
    }

    public function invalidIntegerFormatDataProvider()
    {
        return [[0], [null], ['7'], [true]];
    }

    public function testReturnsAnEmptyArrayWhenTheCustomerHasNoAlias()
    {
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([]);

        $this->assertSame([], $this->action->gdprExportAction(7));
    }

    public function testExportsEveryAliasInTheCardExportFormat()
    {
        // Expired aliases are exported too: the export covers every stored card, like gdprCardExport().
        // No '#' column: HookClass numbers the card and alias rows together.
        $this->alias_repository->shouldReceive('getAllByCustomer')->once()->with(7)->andReturn([
            $this->aliasRow(),
            $this->aliasRow(['id_payplug_alias' => '2', 'brand' => 'cb', 'last4' => '4242', 'exp_month' => '01', 'exp_year' => '2020']),
        ]);

        $this->assertSame([
            [
                'payplug.gdprCardExport.brand' => 'visa',
                'payplug.gdprCardExport.card' => '**** **** **** 0001',
                'payplug.gdprCardExport.expiryDate' => '12 / 99',
            ],
            [
                'payplug.gdprCardExport.brand' => 'cb',
                'payplug.gdprCardExport.card' => '**** **** **** 4242',
                'payplug.gdprCardExport.expiryDate' => '01 / 20',
            ],
        ], $this->action->gdprExportAction(7));
    }
}
