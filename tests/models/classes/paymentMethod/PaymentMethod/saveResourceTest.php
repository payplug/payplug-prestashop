<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group parent_payment_method_classe
 */
class saveResourceTest extends BasePaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->class->saveResource()
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_tab
     */
    public function testWhenGivenPaymentTabIsntValidArray($payment_tab)
    {
        $this->class->set('name', 'standard');
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->class->saveResource($payment_tab)
        );
    }

    public function testWhenPermissionErrorIsReturned()
    {
        $this->class->set('name', 'standard');
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $resource = [
            'code' => (int) 403,
            'result' => false,
            'message' => 'Bad permission',
        ];
        $this->api_service->shouldReceive([
            'createPayment' => $resource,
        ]);

        $configClass = \Mockery::mock('configClass');
        $configClass->shouldReceive([
            'getAvailableOptions' => [
                'standard' => true,
            ],
        ]);

        $this->dependencies->configClass = $configClass;

        $this->class->shouldReceive([
            'resetPaymentMethodFromPermission' => true,
        ]);

        $this->assertSame(
            $resource,
            $this->class->saveResource($payment_tab)
        );
    }

    public function testWhenCredentialErrorIsReturned()
    {
        $this->class->set('name', 'standard');
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $resource = [
            'code' => (int) 401,
            'result' => false,
            'message' => 'Bad credential',
        ];
        $this->api_service->shouldReceive([
            'createPayment' => $resource,
        ]);

        $configurationAction = \Mockery::mock('ConfigurationAction');
        $configurationAction->shouldReceive([
            'logoutAction' => true,
        ]);
        $this->plugin->shouldReceive([
            'getConfigurationAction' => $configurationAction,
        ]);

        $this->assertSame(
            $resource,
            $this->class->saveResource($payment_tab)
        );
    }

    public function testWhenResourceIsCreatedWithNoError()
    {
        $this->class->set('name', 'standard');
        $payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $resource = [
            'result' => true,
            'code' => 200,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->api_service->shouldReceive([
            'createPayment' => $resource,
        ]);
        $this->assertSame(
            $resource,
            $this->class->saveResource($payment_tab)
        );
    }
}
