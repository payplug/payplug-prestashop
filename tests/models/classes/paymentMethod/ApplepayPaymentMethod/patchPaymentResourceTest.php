<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group classes
 * @group payment_method_classes
 * @group applepay_payment_method_classes
 *
 * @runTestsInSeparateProcesses
 */
class patchPaymentResourceTest extends BaseApplepayPaymentMethod
{
    private $api_service;
    private $cart_data;
    private $resource_id;
    private $token;
    private $workflow;
    private $payment_database_mock;

    public function setUp()
    {
        parent::setUp();

        $this->api_service = \Mockery::mock('ApiService');
        $this->resource_id = 'pay_azerty1234';
        $this->token = [
            'token' => 'applepay_token',
        ];
        $this->workflow = 'checkout';
        $this->payment_database_mock = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];
        $this->cart_data = [
            'result' => true,
            'data' => [],
        ];

        $this->plugin
            ->shouldReceive([
                'getApiService' => $this->api_service,
            ]);
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $resource_id
     */
    public function testWhenGivenResourceIdIsInvalidStringFormat($resource_id)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $resource_id must be a non empty string.',
        ], $this->classe->patchPaymentResource($resource_id));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $token
     */
    public function testWhenGivenTokenIsInvalidStringFormat($token)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $token must be a non empty string.',
        ], $this->classe->patchPaymentResource($this->resource_id, $token));
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $workflow
     */
    public function testWhenGivenWorkflowIsInvalidStringFormat($workflow)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $workflow must be a non empty string. given: ' . json_encode($workflow),
        ], $this->classe->patchPaymentResource($this->resource_id, $this->token, $workflow));
    }

    public function testWhenPaymentCantBeRetrieved()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => [],
            ]);
        $this->assertSame([
            'result' => false,
            'message' => 'No payment id for given resource id',
        ], $this->classe->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPaymentRetrievedIdIsDifferentFromGivenResourceID()
    {
        $resource_id = 'pay_azerty';
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => $this->payment_database_mock,
            ]);
        $this->assertSame([
            'result' => false,
            'message' => 'No correspondance with given payment id',
        ], $this->classe->patchPaymentResource($resource_id, $this->token, $this->workflow));
    }

    public function testWhenCartDataGetterReturnError()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => $this->payment_database_mock,
            ]);
        $this->classe
            ->shouldReceive([
                'getCartData' => [
                    'result' => false,
                    'message' => 'cart data can not be getted',
                ],
            ]);
        $this->assertSame([
            'result' => false,
            'message' => 'cart data can not be getted',
        ], $this->classe->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenResourceCantBePatch()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => $this->payment_database_mock,
            ]);
        $this->classe
            ->shouldReceive([
                'getCartData' => $this->cart_data,
            ]);
        $this->api_service
            ->shouldReceive([
                'patchPayment' => [
                    'result' => false,
                    'message' => 'Payment can not be patched',
                ],
            ]);

        $this->assertSame([
            'result' => false,
            'message' => 'An error occured during payment patch : Payment can not be patched',
        ], $this->classe->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPatchedResourceIsFailed()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => $this->payment_database_mock,
            ]);
        $this->classe
            ->shouldReceive([
                'getCartData' => $this->cart_data,
            ]);
        $this->api_service
            ->shouldReceive([
                'patchPayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(),
                ],
            ]);
        $this->validators['payment']
            ->shouldReceive([
                'isFailed' => [
                    'result' => true,
                    'message' => 'Resource has failure',
                ],
            ]);
        $this->assertSame([
            'result' => false,
            'message' => 'Resource has failure',
        ], $this->classe->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPatchedResourceIsNotPaid()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => $this->payment_database_mock,
            ]);
        $this->classe
            ->shouldReceive([
                'getCartData' => $this->cart_data,
            ]);
        $this->api_service
            ->shouldReceive([
                'patchPayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(['is_paid' => false]),
                ],
            ]);
        $this->validators['payment']
            ->shouldReceive([
                'isFailed' => [
                    'result' => false,
                    'message' => '',
                ],
            ]);
        $this->assertSame([
            'result' => false,
            'message' => 'Payment is not paid',
        ], $this->classe->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPatchedResourceIsPatched()
    {
        $this->payment_repository
            ->shouldReceive([
                'getByResourceId' => $this->payment_database_mock,
            ]);
        $this->classe
            ->shouldReceive([
                'getCartData' => $this->cart_data,
            ]);
        $this->api_service
            ->shouldReceive([
                'patchPayment' => [
                    'result' => true,
                    'resource' => PaymentMock::getStandard(['is_paid' => true]),
                ],
            ]);
        $this->validators['payment']
            ->shouldReceive([
                'isFailed' => [
                    'result' => false,
                    'message' => '',
                ],
            ]);
        $this->assertSame([
            'result' => true,
            'return_url' => 'link',
        ], $this->classe->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }
}
