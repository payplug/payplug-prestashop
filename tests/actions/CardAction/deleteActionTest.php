<?php

namespace PayPlug\tests\actions\CardAction;

/**
 * @group unit
 * @group action
 * @group card_action
 *
 * @runTestsInSeparateProcesses
 */
class deleteActionTest extends BaseCardAction
{
    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $customer_id
     */
    public function testWhenGivenCustomerIdIsInvalidIntegerFormat($customer_id)
    {
        $card_id = 4242;
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    /**
     * @dataProvider invalidIntegerFormatDataProvider
     *
     * @param mixed $card_id
     */
    public function testWhenGivenCardIdIsInvalidIntegerFormat($card_id)
    {
        $customer_id = 4242;
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    public function testWhenNoCardsFoundForAGivenId()
    {
        $card_id = 4242;
        $customer_id = 4242;
        $this->card_repository
            ->shouldReceive([
                'get' => [],
            ]);
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    public function testWhenCardFoundDoesNotCorrespondWithGivenCustomerId()
    {
        $card_id = 4242;
        $customer_id = 4242;
        $this->card_repository
            ->shouldReceive([
                'get' => [
                    'id_customer' => 42,
                ],
            ]);
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    public function testWhenCardFoundDoesIsntExpiredAndCartCantBeDeletedFromAPI()
    {
        $card_id = 4242;
        $customer_id = 4242;
        $this->card_repository
            ->shouldReceive([
                'get' => [
                    'id_customer' => $customer_id,
                    'id_card' => 'card_azerty',
                    'exp_month' => date('m'),
                    'exp_year' => date('Y'),
                ],
            ]);
        $this->card_validator
            ->shouldReceive([
                'isValidExpiration' => [
                    'result' => true,
                ],
            ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                'deleteCard' => [
                    'code' => 200,
                    'resource' => [
                        'httpResponse' => [
                            'object' => 'error',
                        ],
                    ],
                ],
            ]);

        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    public function testWhenCardFoundDoesIsntExpiredAndCartCantBeDeletedFromDatabase()
    {
        $card_id = 4242;
        $customer_id = 4242;
        $this->card_repository
            ->shouldReceive([
                'get' => [
                    'id_customer' => $customer_id,
                    'id_card' => 'card_azerty',
                    'exp_month' => date('m'),
                    'exp_year' => date('Y'),
                ],
                'remove' => false,
            ]);
        $this->card_validator
            ->shouldReceive([
                'isValidExpiration' => [
                    'result' => true,
                ],
            ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                'deleteCard' => [
                    'code' => 200,
                    'resource' => [
                        'httpResponse' => [
                            'object' => 'success',
                        ],
                    ],
                ],
            ]);
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    public function testWhenCardFoundDoesIsntExpiredAndCartIsDeletedFromDatabase()
    {
        $card_id = 4242;
        $customer_id = 4242;
        $this->card_repository
            ->shouldReceive([
                'get' => [
                    'id_customer' => $customer_id,
                    'id_card' => 'card_azerty',
                    'exp_month' => date('m'),
                    'exp_year' => date('Y'),
                ],
                'remove' => false,
            ]);
        $this->card_validator
            ->shouldReceive([
                'isValidExpiration' => [
                    'result' => true,
                ],
            ]);
        $this->dependencies->apiClass
            ->shouldReceive([
                'deleteCard' => [
                    'code' => 200,
                    'resource' => [
                        'httpResponse' => [
                            'object' => 'success',
                        ],
                    ],
                ],
            ]);
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    public function testWhenCardFoundDoesIsExpiredAndCartCantBeDeletedFromDatabase()
    {
        $card_id = 4242;
        $customer_id = 4242;
        $this->card_repository
            ->shouldReceive([
                'get' => [
                    'id_customer' => $customer_id,
                    'exp_month' => date('m'),
                    'exp_year' => date('Y'),
                ],
                'remove' => false,
            ]);
        $this->card_validator
            ->shouldReceive([
                'isValidExpiration' => [
                    'result' => false,
                ],
            ]);
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }

    public function testWhenCardFoundDoesIsExpiredAndCartIsDeletedFromDatabase()
    {
        $card_id = 4242;
        $customer_id = 4242;
        $this->card_repository
            ->shouldReceive([
                'get' => [
                    'id_customer' => $customer_id,
                    'exp_month' => date('m'),
                    'exp_year' => date('Y'),
                ],
                'remove' => false,
            ]);
        $this->card_validator
            ->shouldReceive([
                'isValidExpiration' => [
                    'result' => false,
                ],
            ]);
        $this->assertFalse($this->action->deleteAction($customer_id, $card_id));
    }
}
