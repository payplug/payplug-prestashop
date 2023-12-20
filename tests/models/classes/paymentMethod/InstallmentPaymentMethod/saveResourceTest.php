<?php

namespace PayPlug\tests\models\classes\paymentMethod\InstallmentPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class saveResourceTest extends BaseInstallmentPaymentMethod
{
    public function testWhenPaymentMethodHasNoNameDefined()
    {
        $this->classe->set('name', '');
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
        $this->classe->set('name', 'installment');
        $this->assertSame(
            [
                'result' => false,
            ],
            $this->classe->saveResource($payment_tab)
        );
    }

    public function testWhenPermissionErrorIsReturned()
    {
        $this->classe->set('name', 'installment');
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
                'createInstallment' => $resource,
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
        $this->classe->set('name', 'installment');
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
                'createInstallment' => $resource,
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
        $this->classe->set('name', 'installment');
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
            'resource' => PaymentMock::getInstallment(),
        ];
        $apiClass = \Mockery::mock('apiClass');
        $apiClass
            ->shouldReceive([
                'createInstallment' => $resource,
            ]);
        $this->dependencies->apiClass = $apiClass;
        $this->classe
            ->shouldReceive([
                'addInstallmentSchedules' => true,
            ]);
        $this->assertSame(
            $resource,
            $this->classe->saveResource($payment_tab)
        );
    }
}
