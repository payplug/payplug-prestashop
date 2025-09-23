<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_class
 */
class resetPaymentMethodFromPermissionTest extends BasePaymentMethod
{
    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $permissions
     */
    public function testWhenGivenPermissionIsntValidArray($permissions)
    {
        $this->assertFalse($this->class->resetPaymentMethodFromPermission($permissions));
    }

    public function testWhenConfigurationCantBeUpdated()
    {
        $permissions = [
            'standard' => true,
        ];
        $this->configuration->shouldReceive([
            'getValue' => '{"standard":true}',
            'set' => false,
        ]);
        $this->assertFalse($this->class->resetPaymentMethodFromPermission($permissions));
    }

    public function testWhenConfigurationIsUpdated()
    {
        $permissions = [
            'standard' => true,
        ];
        $this->configuration->shouldReceive([
            'getValue' => '{"standard":true}',
            'set' => true,
        ]);
        $this->assertTrue($this->class->resetPaymentMethodFromPermission($permissions));
    }
}
