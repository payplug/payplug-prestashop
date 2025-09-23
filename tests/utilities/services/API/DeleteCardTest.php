<?php

namespace PayPlug\tests\utilities\services\API;

use PayPlug\tests\mock\PaymentMock;

/**
 * @group unit
 * @group service
 * @group api_service
 */
class DeleteCardTest extends BaseApi
{
    public $card_id;

    public function setUp()
    {
        parent::setUp();
        $this->card_id = 'card_123456azerty';
    }

    /**
     * @dataProvider invalidStringFormatDataProvider
     *
     * @param mixed $card_id
     */
    public function testWhenGivenCartIdIsntValidString($card_id)
    {
        $this->assertSame(
            [
                'result' => false,
                'code' => null,
                'message' => 'Wrong $card_id given',
            ],
            $this->service->deleteCard($card_id)
        );
    }

    public function testWhenAPiCantBeInitialize()
    {
        $this->service->shouldReceive([
            'initialize' => false,
        ]);
        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'Cannot connect to the API',
            ],
            $this->service->deleteCard($this->card_id)
        );
    }

    public function testWhenConfigurationNotSetExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->card
            ->shouldReceive('delete')
            ->andThrow(new \Payplug\Exception\ConfigurationNotSetException('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->deleteCard($this->card_id)
        );
    }

    public function testWhenNotFoundExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->card
            ->shouldReceive('delete')
            ->andThrow(new \Payplug\Exception\NotFoundException('An error occured during the process', '', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->deleteCard($this->card_id)
        );
    }

    public function testWhenExceptionIsThrown()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $this->card
            ->shouldReceive('delete')
            ->andThrow(new \Exception('An error occured during the process', 500));

        $this->assertSame(
            [
                'result' => false,
                'code' => 500,
                'message' => 'An error occured during the process',
            ],
            $this->service->deleteCard($this->card_id)
        );
    }

    public function testWhenCardIsDeleted()
    {
        $this->service->shouldReceive([
            'initialize' => true,
        ]);

        $resource = PaymentMock::getStandard();
        $this->card->shouldReceive([
            'delete' => $resource,
        ]);

        $this->assertSame(
            [
                'result' => true,
                'code' => 200,
                'resource' => $resource,
            ],
            $this->service->deleteCard($this->card_id)
        );
    }
}
