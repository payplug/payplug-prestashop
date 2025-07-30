<?php

namespace PayPlug\tests\models\classes\paymentMethod\ApplepayPaymentMethod;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group class
 * @group payment_method_class
 * @group applepay_payment_method_class
 */
class patchPaymentResourceTest extends BaseApplepayPaymentMethod
{
    private $cart_data;
    private $resource_id;
    private $token;
    private $workflow;
    private $payment_database_mock;

    public function setUp()
    {
        parent::setUp();

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
        ], $this->class->patchPaymentResource($resource_id));
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $token
     */
    public function testWhenGivenTokenIsInvalidArrayFormat($token)
    {
        $this->assertSame([
            'result' => false,
            'message' => 'Invalid argument, $token must be a non empty array.',
        ], $this->class->patchPaymentResource($this->resource_id, $token));
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
        ], $this->class->patchPaymentResource($this->resource_id, $this->token, $workflow));
    }

    public function testWhenPaymentCantBeRetrieved()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->assertSame([
            'result' => false,
            'message' => 'No payment id for given resource id',
        ], $this->class->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPaymentRetrievedIdIsDifferentFromGivenResourceID()
    {
        $resource_id = 'pay_azerty';
        $this->payment_repository->shouldReceive([
            'getBy' => $this->payment_database_mock,
        ]);
        $this->assertSame([
            'result' => false,
            'message' => 'No correspondance with given payment id',
        ], $this->class->patchPaymentResource($resource_id, $this->token, $this->workflow));
    }

    public function testWhenCartDataGetterReturnError()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->payment_database_mock,
        ]);
        $this->class->shouldReceive([
            'getCartData' => [
                'result' => false,
                'message' => 'cart data can not be getted',
            ],
        ]);
        $this->assertSame([
            'result' => false,
            'message' => 'cart data can not be getted',
        ], $this->class->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenResourceCantBePatch()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->payment_database_mock,
        ]);
        $this->class->shouldReceive([
            'getCartData' => $this->cart_data,
        ]);
        $this->api_service->shouldReceive([
            'patchPayment' => [
                'result' => false,
                'message' => 'Payment can not be patched',
            ],
        ]);

        $this->assertSame([
            'result' => false,
            'message' => 'An error occured during payment patch : Payment can not be patched',
        ], $this->class->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPatchedResourceIsFailed()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->payment_database_mock,
        ]);
        $this->class->shouldReceive([
            'getCartData' => $this->cart_data,
        ]);
        $this->api_service->shouldReceive([
            'patchPayment' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(),
            ],
        ]);
        $this->validators['payment']->shouldReceive([
            'isFailed' => [
                'result' => true,
                'message' => 'Resource has failure',
            ],
        ]);
        $this->assertSame([
            'result' => false,
            'message' => 'Resource has failure',
        ], $this->class->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPatchedResourceIsNotPaid()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->payment_database_mock,
        ]);
        $this->class->shouldReceive([
            'getCartData' => $this->cart_data,
        ]);
        $this->api_service->shouldReceive([
            'patchPayment' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => false]),
            ],
        ]);
        $this->validators['payment']->shouldReceive([
            'isFailed' => [
                'result' => false,
                'message' => '',
            ],
        ]);
        $this->assertSame([
            'result' => false,
            'message' => 'Payment is not paid',
        ], $this->class->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }

    public function testWhenPatchedResourceIsPatched()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->payment_database_mock,
        ]);
        $this->class->shouldReceive([
            'getCartData' => $this->cart_data,
        ]);
        $this->api_service->shouldReceive([
            'patchPayment' => [
                'result' => true,
                'resource' => PaymentMock::getStandard(['is_paid' => true]),
            ],
        ]);
        $this->validators['payment']->shouldReceive([
            'isFailed' => [
                'result' => false,
                'message' => '',
            ],
        ]);
        $this->assertSame([
            'result' => true,
            'return_url' => 'link',
        ], $this->class->patchPaymentResource($this->resource_id, $this->token, $this->workflow));
    }
}
