<?php

namespace PayPlug\tests\actions\PaymentAction;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group action
 * @group payment_action
 *
 * @runTestsInSeparateProcesses
 */
class createActionTest extends BasePaymentAction
{
    public $method;
    public $payment_tab;
    public $stored_resource;

    public function setUp()
    {
        parent::setUp();

        $this->method = 'standard';
        $this->payment_tab = [
            'amount' => 4242,
            'force_3ds' => false,
            'hosted_payment' => [],
            'metadata' => [],
            'allow_save_card' => false,
        ];
        $this->stored_resource = [
            'id_payplug_payment' => 42,
            'resource_id' => 'pay_azerty1234',
            'method' => 'standard',
            'id_cart' => 42,
            'cart_hash' => 'cart-hash-azerty1234567',
            'schedules' => 'NULL',
            'date_upd' => '1970-01-01 00:00:00',
        ];
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $method
     */
    public function testWhenGivenMethodIsntValidString($method)
    {
        $this->assertSame(
            [],
            $this->action->createAction($method, $this->payment_tab)
        );
    }

    /**
     * @dataProvider invalidArrayFormatDataProvider
     *
     * @param mixed $payment_tab
     */
    public function testWhenGivenPaymentTabIsntValidArray($payment_tab)
    {
        $this->assertSame(
            [],
            $this->action->createAction($this->method, $payment_tab)
        );
    }

    public function testWhenAResourceAlreadyExistsAndCantBeRemoved()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => $this->stored_resource,
        ]);
        $this->payment_method->cancellable = true;
        $this->action->shouldReceive([
            'removeAction' => false,
        ]);
        $this->assertSame(
            [],
            $this->action->createAction($this->method, $this->payment_tab)
        );
        $this->assertSame(
            [],
            $this->action->createAction($this->method, $this->payment_tab)
        );
    }

    public function testWhenResourceCantBeSaved()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
        ]);
        $this->payment_method->cancellable = true;
        $this->payment_method->shouldReceive([
            'saveResource' => [
                'result' => false,
            ],
        ]);
        $this->assertSame(
            [],
            $this->action->createAction($this->method, $this->payment_tab)
        );
    }

    public function testWhenResourceCantBeSavedInDataBase()
    {
        $this->payment_repository->shouldReceive([
            'getBy' => [],
            'createEntity' => false,
        ]);
        $this->payment_method->cancellable = true;
        $this->payment_method->shouldReceive([
            'saveResource' => [
                'result' => false,
            ],
            'getPaymentMethodHash' => 'cart-hash-azerty1234567',
        ]);
        $this->assertSame(
            [],
            $this->action->createAction($this->method, $this->payment_tab)
        );
    }

    public function testWhenSelectedMethodIsApplePay()
    {
        $method = 'applepay';
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_repository->shouldReceive([
            'getBy' => [],
            'createEntity' => $resource,
        ]);
        $this->payment_method->cancellable = true;
        $this->payment_method->shouldReceive([
            'saveResource' => $resource,
            'getPaymentMethodHash' => 'cart-hash-azerty1234567',
        ]);
        $this->assertSame(
            $resource,
            $this->action->createAction($method, $this->payment_tab)
        );
    }

    public function testWhenTheReturnUrlIsReturned()
    {
        $resource = [
            'result' => true,
            'resource' => PaymentMock::getStandard(),
        ];
        $this->payment_repository->shouldReceive([
            'getBy' => [],
            'createEntity' => $resource,
        ]);
        $this->payment_method->cancellable = true;
        $return_url = [
            'result' => 'new_card',
            'embedded' => false,
            'redirect' => true,
            'return_url' => 'return_url',
            'resource_stored' => [],
        ];
        $this->payment_method->shouldReceive([
            'saveResource' => $resource,
            'getPaymentMethodHash' => 'cart-hash-azerty1234567',
            'getReturnUrl' => $return_url,
        ]);
        $this->assertSame(
            $return_url,
            $this->action->createAction($this->method, $this->payment_tab)
        );
    }
}
