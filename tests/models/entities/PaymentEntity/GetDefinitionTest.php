<?php

namespace PayPlug\tests\models\entities\PaymentEntity;

/**
 * @group entity
 * @group payment
 * @group payment_entity
 */
final class GetDefinitionTest extends BasePaymentEntity
{
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
