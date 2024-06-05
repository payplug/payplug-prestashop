<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @dontrunTestsInSeparateProcesses
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
        $this->assertFalse($this->classe->resetPaymentMethodFromPermission($permissions));
    }

    public function testWhenConfigurationCantBeUpdated()
    {
        $permissions = [
            'standard' => true,
        ];
        $this->configuration
            ->shouldReceive([
                'getValue' => '{"standard":true}',
                'set' => false,
            ]);
        $this->assertFalse($this->classe->resetPaymentMethodFromPermission($permissions));
    }

    public function testWhenConfigurationIsUpdated()
    {
        $permissions = [
            'standard' => true,
        ];
        $this->configuration
            ->shouldReceive([
                'getValue' => '{"standard":true}',
                'set' => true,
            ]);
        $this->assertTrue($this->classe->resetPaymentMethodFromPermission($permissions));
    }
}
