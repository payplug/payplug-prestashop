<?php

namespace PayPlug\tests\repositories\CardRepository;

/**
 * @group unit
 * @group repository
 * @group card_repository
 *
 * @runTestsInSeparateProcesses
 */
final class DeleteCardFromAPITest extends BaseCardRepository
{
    private $id_card;
    private $card;

    public function setUp()
    {
        parent::setUp();
        $this->card = \Mockery::mock('alias:Payplug\Card');
        $this->id_card = 'card_4242LoremIpsumSit42442';
    }

    public function invalidDataProvider()
    {
        // invalid string $id_customer
        yield [null];
        yield [false];
        yield [42];
        yield [['key' => 'value']];
    }

    /**
     * @dataProvider invalidDataProvider
     *
     * @param $id_customer
     * @param $id_payplug_card
     * @param mixed $id_card
     */
    public function testWithInvalidParams($id_card)
    {
        $this->assertFalse($this->repo->deleteCardFromAPI($id_card));
    }

    public function testWhenAPIThrowingConfigurationNotSetException()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 500,
                'result' => false,
                'message' => 'An error occured',
            ],
        ]);

        $this->assertTrue($this->repo->deleteCardFromAPI($this->id_card));
    }

    public function testWhenAPIThrowingNotFoundException()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 404,
                'result' => false,
                'message' => 'Card not found',
            ],
        ]);

        $this->assertTrue($this->repo->deleteCardFromAPI($this->id_card));
    }

    public function testWhenAPIReturnError()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 200,
                'result' => true,
                'resource' => [
                    'httpResponse' => [
                        'object' => 'error',
                    ],
                ],
            ],
        ]);

        $this->assertFalse($this->repo->deleteCardFromAPI($this->id_card));
    }

    public function testWhenAPIReturnSuccess()
    {
        $this->dependencies->apiClass->shouldReceive([
            'deleteCard' => [
                'code' => 200,
                'result' => true,
                'resource' => [
                    'httpResponse' => [
                        'object' => 'success',
                    ],
                ],
            ],
        ]);

        $this->assertTrue($this->repo->deleteCardFromAPI($this->id_card));
    }
}
