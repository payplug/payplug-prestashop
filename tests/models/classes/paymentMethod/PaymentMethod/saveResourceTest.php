<?php

namespace PayPlug\tests\models\classes\paymentMethod\PaymentMethod;

use PayPlug\tests\mock\PaymentMock;
use PayPlug\tests\models\classes\paymentMethod\BasePaymentMethod;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class saveResourceTest extends BasePaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->classe->saveResource()
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_tab
     */
    public function testWhenGivenPaymentTabIsntValidArray($payment_tab)
    {
        $this->classe->set('name', 'standard');
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->classe->saveResource($payment_tab)
        );
    }

    public function testWhenPermissionErrorIsReturned()
    {
        $this->classe->set('name', 'standard');
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
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'createPayment' => $resource,
            ]);

        $configClass = \Mockery::mock('configClass');
        $configClass
            ->shouldReceive([
                'getAvailableOptions' => [
                    'standard' => true,
                ],
            ]);

        $this->dependencies->apiClass = $apiClass;
        $this->dependencies->configClass = $configClass;

        $this->classe
            ->shouldReceive([
                'resetPaymentMethodFromPermission' => true,
            ]);

        $this->assertSame(
            $resource,
            $this->classe->saveResource($payment_tab)
        );
    }

    public function testWhenCredentialErrorIsReturned()
    {
        $this->classe->set('name', 'standard');
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
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'createPayment' => $resource,
            ]);
        $this->dependencies->apiClass = $apiClass;

        $configurationAction = \Mockery::mock('ConfigurationAction');
        $configurationAction
            ->shouldReceive([
                'logoutAction' => true,
            ]);
        $this->plugin
            ->shouldReceive([
                'getConfigurationAction' => $configurationAction,
            ]);

        $this->assertSame(
            $resource,
            $this->classe->saveResource($payment_tab)
        );
    }

    public function testWhenResourceIsCreatedWithNoError()
    {
        $this->classe->set('name', 'standard');
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
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'createPayment' => $resource,
            ]);
        $this->dependencies->apiClass = $apiClass;
        $this->assertSame(
            $resource,
            $this->classe->saveResource($payment_tab)
        );
    }
}
