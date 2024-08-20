<?php

namespace PayPlug\tests\models\entities\CardEntity;

/**
 * @group entity
 * @group card
 * @group card_entity
 */
final class GetDefinitionCardTest extends BaseCardEntity
{
    protected $brands;

    protected function setUp()
    {
        parent::setUp();
        $this->brands = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 3,
        ];
        $this->definition = [
            'table' => 'payplug_card',
            'primary' => 'id_payplug_card',
            'fields' => [
                'id_customer' => ['type' => 'integer', 'required' => true],
                'id_company' => ['type' => 'integer', 'required' => true],
                'is_sandbox' => ['type' => 'boolean', 'required' => true],
                'id_card' => ['type' => 'string', 'required' => true],
                'last4' => ['type' => 'string', 'required' => true],
                'exp_month' => ['type' => 'string', 'required' => true],
                'exp_year' => ['type' => 'string', 'required' => true],
                'brand' => ['type' => 'string', 'required' => false],
                'country' => ['type' => 'string', 'required' => true],
                'metadata' => ['type' => 'string', 'required' => false],
            ],
        ];
    }

    public function testReturnDefinition()
    {
        $this->assertSame(
            $this->definition,
            $this->entity->getDefinition()
        );
    }

    public function testDefinitionIsAnArray()
    {
        $this->assertTrue(
            is_array($this->entity->getDefinition())
        );
    }
}
